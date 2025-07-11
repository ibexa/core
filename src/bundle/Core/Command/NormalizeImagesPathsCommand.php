<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Doctrine\DBAL\Connection;
use Ibexa\Core\FieldType\Image\ImageStorage\Gateway as ImageStorageGateway;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\FilePathNormalizerInterface;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use Ibexa\Core\IO\Values\MissingBinaryFile;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand(
    name: 'ibexa:images:normalize-paths',
    description: 'Normalizes stored paths for images.'
)]
final class NormalizeImagesPathsCommand extends Command
{
    private const IMAGE_LIMIT = 100;
    private const BEFORE_RUNNING_HINTS = <<<EOT
<error>Before you continue:</error>
- Make sure to back up your database.
- Run this command in production environment using <info>--env=prod</info>
- Manually clear SPI/HTTP cache after running this command.
EOT;

    private const SKIP_HASHING_COMMAND_PARAMETER = 'no-hash';

    /** @var \Ibexa\Core\FieldType\Image\ImageStorage\Gateway */
    private $imageGateway;

    /** @var \Ibexa\Core\IO\FilePathNormalizerInterface */
    private $filePathNormalizer;

    private Connection $connection;

    /** @var \Ibexa\Core\IO\IOServiceInterface */
    private $ioService;

    /** @var bool */
    private $skipHashing;

    public function __construct(
        ImageStorageGateway $imageGateway,
        FilePathNormalizerInterface $filePathNormalizer,
        Connection $connection,
        IOServiceInterface $ioService
    ) {
        parent::__construct();

        $this->imageGateway = $imageGateway;
        $this->filePathNormalizer = $filePathNormalizer;
        $this->connection = $connection;
        $this->ioService = $ioService;
    }

    protected function configure(): void
    {
        $beforeRunningHints = self::BEFORE_RUNNING_HINTS;

        $this
            ->addOption(
                self::SKIP_HASHING_COMMAND_PARAMETER,
                null,
                InputOption::VALUE_NONE,
                'Skip filename hashing'
            )
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> normalizes paths for images.

{$beforeRunningHints}
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Normalize image paths');

        $io->writeln([
            'Determining the number of images that require path normalization.',
            'It may take some time.',
        ]);

        $this->skipHashing = $input->getOption(self::SKIP_HASHING_COMMAND_PARAMETER);
        $this->skipHashing
            ? $io->caution('Image filenames will not be hashed.')
            : $io->caution('Image filenames will be hashed with format {hash}-{sanitized name}.');

        $imagePathsToNormalize = $this->getImagePathsToNormalize($io);

        $imagePathsToNormalizeCount = \count($imagePathsToNormalize);
        $io->note(sprintf('Found: %d', $imagePathsToNormalizeCount));
        if ($imagePathsToNormalizeCount === 0) {
            $io->success('No paths to normalize.');

            return self::SUCCESS;
        }

        if (!$io->confirm('Do you want to continue?')) {
            return self::SUCCESS;
        }

        $io->writeln('Normalizing image paths. Please wait...');
        $io->progressStart($imagePathsToNormalizeCount);

        $oldBinaryFilesToDelete = $this->normalizeImagePaths($imagePathsToNormalize, $io);

        foreach ($oldBinaryFilesToDelete as $binaryFile) {
            try {
                $this->ioService->deleteBinaryFile($binaryFile);
            } catch (\Exception $e) {
                // Continue with deletion
            }
        }

        $io->progressFinish();
        $io->success('Done!');

        return self::SUCCESS;
    }

    /**
     * @param resource $inputStream
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function updateImagePath(
        int $fieldId,
        string $oldPath,
        string $newPath,
        BinaryFile $oldBinaryFile,
        $inputStream
    ): void {
        $oldPathInfo = pathinfo($oldPath);
        $newPathInfo = pathinfo($newPath);
        // In Image's XML, basename does not contain a file extension, and the filename does - pathinfo results are exactly the opposite.
        $oldFileName = $oldPathInfo['basename'];
        $newFilename = $newPathInfo['basename'];
        $newBaseName = $newPathInfo['filename'];

        $allVersionsXMLData = $this->imageGateway->getAllVersionsImageXmlForFieldId($fieldId);
        foreach ($allVersionsXMLData as $xmlData) {
            if (empty($xmlData['data_text'])) {
                continue;
            }

            $dom = new \DOMDocument();
            $dom->loadXml($xmlData['data_text']);

            /** @var \DOMElement $imageTag */
            $imageTag = $dom->getElementsByTagName('ezimage')->item(0);
            if ($imageTag && $imageTag->getAttribute('filename') === $oldFileName) {
                $imageTag->setAttribute('filename', $newFilename);
                $imageTag->setAttribute('basename', $newBaseName);
                $imageTag->setAttribute('dirpath', $newPath);
                $imageTag->setAttribute('url', $newPath);

                $this->imageGateway->updateImageData(
                    $fieldId,
                    (int)$xmlData['version'],
                    $dom->saveXML()
                );
            }
        }

        $this->imageGateway->updateImagePath($fieldId, $oldPath, $newPath);

        $newId = str_replace($oldFileName, $newFilename, $oldBinaryFile->id);
        $binaryCreateStruct = new BinaryFileCreateStruct(
            [
                'id' => $newId,
                'size' => $oldBinaryFile->size,
                'inputStream' => $inputStream,
                'mimeType' => $this->ioService->getMimeType($oldBinaryFile->id),
            ]
        );

        // Before creating a new file validate if the same file doesn't exist already in order to not duplicate files
        $newBinaryFile = $this->ioService->loadBinaryFileByUri(\DIRECTORY_SEPARATOR . $newPath);
        if ($newBinaryFile instanceof MissingBinaryFile) {
            $this->ioService->createBinaryFile($binaryCreateStruct);
        }
    }

    protected function updateImagePathsToNormalize(
        $imageData,
        array $imagePathsToNormalize
    ): array {
        $filePath = $imageData['filepath'];
        $fieldId = (int)$imageData['contentobject_attribute_id'];

        $finalNormalizedPath = $this->getFinalNormalizedPath(
            $filePath,
            $imagePathsToNormalize
        );

        if ($finalNormalizedPath !== $filePath) {
            $imagePathsToNormalize[] = [
                'fieldId' => $fieldId,
                'oldPath' => $filePath,
                'newPath' => $finalNormalizedPath,
            ];
        }

        return $imagePathsToNormalize;
    }

    private function getFinalNormalizedPath(
        string $filePath,
        array $imagePathsToNormalize
    ): string {
        $processedPaths = array_values(
            array_filter(
                $imagePathsToNormalize,
                static function (array $data) use ($filePath): bool {
                    return $data['oldPath'] === $filePath;
                }
            )
        );

        return !empty($processedPaths)
            ? $processedPaths[0]['newPath']
            : $this->filePathNormalizer->normalizePath($filePath, !$this->skipHashing);
    }

    private function getImagePathsToNormalize(SymfonyStyle $io): array
    {
        $imagesDataCount = $this->imageGateway->countDistinctImagesData();
        $imagePathsToNormalize = [];
        $iterations = ceil($imagesDataCount / self::IMAGE_LIMIT);
        $io->progressStart($imagesDataCount);
        for ($i = 0; $i < $iterations; ++$i) {
            $imagesData = $this->imageGateway->getImagesData(
                $i * self::IMAGE_LIMIT,
                self::IMAGE_LIMIT
            );

            foreach ($imagesData as $imageData) {
                $imagePathsToNormalize = $this->updateImagePathsToNormalize(
                    $imageData,
                    $imagePathsToNormalize
                );

                $io->progressAdvance();
            }
        }
        $io->progressFinish();

        return $imagePathsToNormalize;
    }

    private function normalizeImagePaths(array $imagePathsToNormalize, SymfonyStyle $io): array
    {
        $oldBinaryFilesToDelete = [];
        foreach ($imagePathsToNormalize as $imagePathToNormalize) {
            $this->connection->beginTransaction();
            try {
                $oldPath = $imagePathToNormalize['oldPath'];

                $oldBinaryFile = $this->ioService->loadBinaryFileByUri(
                    \DIRECTORY_SEPARATOR . $oldPath
                );
                $inputStream = $this->ioService->getFileInputStream($oldBinaryFile);

                $this->updateImagePath(
                    $imagePathToNormalize['fieldId'],
                    $oldPath,
                    $imagePathToNormalize['newPath'],
                    $oldBinaryFile,
                    $inputStream
                );

                $io->progressAdvance();

                $oldBinaryFilesToDelete[$oldBinaryFile->id] = $oldBinaryFile;

                $this->connection->commit();
            } catch (BinaryFileNotFoundException $e) {
                $io->warning(
                    sprintf('File %s does not exist. Skipping.', $oldPath)
                );

                $this->connection->rollBack();
            } catch (\Exception $e) {
                $this->connection->rollBack();
            }
        }

        return $oldBinaryFilesToDelete;
    }
}
