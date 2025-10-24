<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Command;

use Exception;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Console Command which deletes a given Translation from all the Versions of a given Content Item.
 */
#[AsCommand(
    name: 'ibexa:delete-content-translation',
    description: 'Deletes a translation from all versions of a Content item'
)]
class DeleteContentTranslationCommand extends Command
{
    /** @var Repository */
    private $repository;

    /** @var ContentService */
    private $contentService;

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /** @var QuestionHelper */
    private $questionHelper;

    public function __construct(Repository $repository)
    {
        parent::__construct(null);
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content Object Id')
            ->addArgument(
                'language-code',
                InputArgument::REQUIRED,
                'Language code of the translation to be deleted'
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Ibexa username (with Role containing at least content Policies: read, versionread, edit, remove, versionremove)',
                'admin'
            );
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ): void {
        parent::initialize($input, $output);
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $this->getHelper('question');
        $this->contentService = $this->repository->getContentService();

        $this->repository->getPermissionResolver()->setCurrentUserReference(
            $this->repository->getUserService()->loadUserByLogin($input->getOption('user'))
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $contentId = (int) ($input->getArgument('content-id'));
        $languageCode = $input->getArgument('language-code');

        if ($contentId === 0) {
            throw new InvalidArgumentException(
                'content-id',
                'Content ID must be an integer'
            );
        }

        $this->output->writeln(
            '<comment>**NOTE**: Make sure to run this command using the same SYMFONY_ENV setting as your Ibexa installation</comment>'
        );

        $versionInfo = $this->contentService->loadVersionInfoById($contentId);
        $contentInfo = $versionInfo->contentInfo;

        $this->repository->beginTransaction();
        try {
            if ($contentInfo->mainLanguageCode === $languageCode) {
                $contentInfo = $this->promptUserForMainLanguageChange(
                    $contentInfo,
                    $languageCode,
                    // allow to change Main Translation to only those existing in the last Version
                    $versionInfo->languageCodes
                );
            }

            // Confirm operation
            $contentName = "#{$contentInfo->id} ($contentInfo->name)";
            $question = new ConfirmationQuestion(
                "Are you sure you want to delete the {$languageCode} translation from Content item {$contentName}? This operation is permanent. [y/N] ",
                false
            );
            if (!$this->questionHelper->ask($this->input, $this->output, $question)) {
                // Rollback any cleanup change (see above)
                $this->repository->rollback();
                $this->output->writeln('Reverting and aborting.');

                return self::SUCCESS;
            }

            // Delete Translation
            $output->writeln(
                "<info>Deleting the {$languageCode} translation of Content item {$contentName}</info>"
            );
            $this->contentService->deleteTranslation($contentInfo, $languageCode);

            $output->writeln('<info>Translation deleted</info>');

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return self::SUCCESS;
    }

    /**
     * Interact with user to update main Language of a Content Object.
     *
     * @param ContentInfo $contentInfo
     * @param string $languageCode language code of the Translation to be deleted
     * @param string[] $lastVersionLanguageCodes all Translations last Version has.
     *
     * @return ContentInfo
     */
    private function promptUserForMainLanguageChange(
        ContentInfo $contentInfo,
        $languageCode,
        array $lastVersionLanguageCodes
    ) {
        $contentName = "#{$contentInfo->id} ($contentInfo->name)";
        $this->output->writeln(
            "<comment>The specified translation '{$languageCode}' is the main translation of Content item {$contentName}. It needs to be changed before removal.</comment>"
        );

        // get main Translation candidates w/o Translation being removed
        $mainTranslationCandidates = array_filter(
            $lastVersionLanguageCodes,
            static function ($versionLanguageCode) use ($languageCode): bool {
                return $versionLanguageCode !== $languageCode;
            }
        );
        if (empty($mainTranslationCandidates)) {
            throw new InvalidArgumentException(
                'language-code',
                "The last version of Content item {$contentName} has no other translations beside the main one"
            );
        }
        $question = new ChoiceQuestion(
            "Set the Main Translation of the Content {$contentName} to:",
            array_values($mainTranslationCandidates)
        );

        $newMainLanguageCode = $this->questionHelper->ask($this->input, $this->output, $question);
        $this->output->writeln(
            "<info>Updating main translation of Content item {$contentName} to {$newMainLanguageCode}</info>"
        );

        $contentMetadataUpdateStruct = $this->contentService->newContentMetadataUpdateStruct();
        $contentMetadataUpdateStruct->mainLanguageCode = $newMainLanguageCode;

        return $this->contentService->updateContentMetadata(
            $contentInfo,
            $contentMetadataUpdateStruct
        )->contentInfo;
    }
}
