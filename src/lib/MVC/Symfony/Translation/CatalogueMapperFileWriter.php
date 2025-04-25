<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Translation;

use JMS\TranslationBundle\Exception\InvalidArgumentException;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\Message\XliffMessage;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\FileWriter;
use JMS\TranslationBundle\Translation\LoaderManager;

/**
 * Before writing a catalogue to a file, maps the given catalogue with the english sources:
 * if a message doesn't have a human readable source (e.g. an id), sets the source to the
 * matching english string.
 */
class CatalogueMapperFileWriter extends FileWriter
{
    private const string XLF_FILE_NAME_REGEX_PATTERN = '/\.[-_a-z]+\.xlf$/i';

    private LoaderManager $loaderManager;

    private FileWriter $innerFileWriter;

    public function __construct(FileWriter $innerFileWriter, LoaderManager $loaderManager)
    {
        $this->loaderManager = $loaderManager;
        $this->innerFileWriter = $innerFileWriter;
    }

    public function write(MessageCatalogue $catalogue, $domain, $filePath, $format): void
    {
        $newCatalogue = new MessageCatalogue();
        $newCatalogue->setLocale($catalogue->getLocale());

        foreach (array_keys($catalogue->getDomains()) as $catalogueDomainString) {
            if ($catalogue->getLocale() !== 'en' && $this->hasEnglishCatalogue($filePath)) {
                $englishCatalogue = $this->loadEnglishCatalogue($filePath, $domain, $format);
            }

            $domainMessageCollection = $catalogue->getDomain($catalogueDomainString);
            /** @var \JMS\TranslationBundle\Model\Message $message */
            foreach ($domainMessageCollection->all() as $message) {
                if ($message->getDomain() !== $domain) {
                    continue;
                }

                $newMessage = $this->makeXliffMessage($message);

                if ($message->getId() === $message->getSourceString()) {
                    if (isset($englishCatalogue)) {
                        try {
                            $newMessage->setDesc(
                                $englishCatalogue
                                    ->get($message->getId(), $message->getDomain())
                                    ->getLocaleString()
                            );
                        } catch (InvalidArgumentException $e) {
                            continue;
                        }
                    } else {
                        $newMessage->setDesc($message->getLocaleString());
                    }
                }

                $newCatalogue->add($newMessage);
            }
        }

        $this->innerFileWriter->write($newCatalogue, $domain, $filePath, $format);
    }

    private function getEnglishFilePath(string $filePath): string
    {
        $enFilePath = preg_replace(self::XLF_FILE_NAME_REGEX_PATTERN, '.en.xlf', $filePath);
        if (null === $enFilePath) {
            throw new InvalidArgumentException("failed to get English XLF file path for '$filePath'");
        }

        return $enFilePath;
    }

    /**
     * @param $foreignFilePath
     * @param $domain
     * @param $format
     *
     * @return \JMS\TranslationBundle\Model\MessageCatalogue
     */
    private function loadEnglishCatalogue(string $foreignFilePath, $domain, $format)
    {
        return $this->loaderManager->loadFile(
            $this->getEnglishFilePath($foreignFilePath),
            $format,
            'en',
            $domain
        );
    }

    private function hasEnglishCatalogue(string $foreignFilePath): bool
    {
        return file_exists($this->getEnglishFilePath($foreignFilePath));
    }

    /**
     * @param $message
     *
     * @return \JMS\TranslationBundle\Model\Message\XliffMessage
     */
    private function makeXliffMessage(Message $message): XliffMessage
    {
        $newMessage = new XliffMessage($message->getId(), $message->getDomain());
        $newMessage->setNew($message->isNew());
        $newMessage->setLocaleString($message->getLocaleString());
        $newMessage->setSources($message->getSources());
        $newMessage->addNote('key: ' . $message->getId());

        if ($desc = $message->getDesc()) {
            $newMessage->setDesc($desc);
        }

        return $newMessage;
    }
}
