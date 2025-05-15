<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Persistence\Legacy;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Tests\Integration\Core\FieldType\FieldConstraintsStorage\Stub\ExampleFieldType;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

//class FileHandlerTest extends BaseTest
class FileHandlerTest extends RepositoryTestCase
{
    /*
     export DATABASE_URL=mysql://test:test@db:3306/test ; export DATABASE=mysql://test:test@db:3306/test
     time vendor/bin/phpunit -c phpunit-integration-legacy.xml --filter FileHandlerTest

    Ibexa\Core\Event\ContentService
     */

    public function testUpdateFields(): void
    {
        $contentService =  self::getContentService();
        $contentTypeService = self::getContentTypeService();
        $permissionResolver = self::getPermissionResolver();
//        $repository = $this->getRepository();
//        $contentTypeService = $repository->getContentTypeService();
//        $contentService = $repository->getContentService();
//        $permissionResolver = $repository->getPermissionResolver();



        // Create new ContentType
        var_dump("SSSSSSSSSSSSSSSSTTTTTTTTTAAAAAAAAAARRRRRRRRRRRTTTTTTTTTTTTTTT");
        $fieldDefCreateStruct = $this->createFieldDefinitionStruct('name', 'Name', true);

        $contentTypeCreateStruct = $this->createTypeCreateStruct();
        $contentTypeCreateStruct->addFieldDefinition($fieldDefCreateStruct);

        $contentType = $contentTypeService->createContentType($contentTypeCreateStruct, [
            $contentTypeService->loadContentTypeGroupByIdentifier('Content'),
        ]);

        $contentTypeService->publishContentTypeDraft($contentType);


        // Create content, with two translations
        $content = $this->createNewContent('Some Content', ['eng-GB', 'ger-DE']);

        // Create draft in each translation
        $content = $contentService->loadContent($content->getId(), ['eng-GB']);
        $engUpdateStruct = $this->createUpdateStruct($content, '', ['eng-GB']);
        $engDraft = $this->createContentDraft($content, 'eng-GB');
        $engDraft = $this->updateContent($engDraft, $engUpdateStruct);
//        $content = $contentService->loadContent($content->getId(), ['ger-DE']);
//        $gerUpdateStruct = $this->createUpdateStruct($content, '', ['ger-DE']);
//        $gerDraft = $this->createContentDraft($content, 'ger-DE');;
//        $gerDraft = $this->updateContent($gerDraft, $gerUpdateStruct);

//        $gerContent = $this->createDraft($content->getId(), '', ['ger-DE']);
//        $gerContent = $this->createDraft($content->getId(), '', ['ger-DE']);


        // Create new non-translatable field
        $contentType = $contentTypeService->loadContentTypeByIdentifier('multi_lang_drafts');
        $contentTypeDraft = $contentTypeService->createContentTypeDraft($contentType);
        $fieldDefCreateStruct = $this->createFieldDefinitionStruct('non_trans_field', 'Non translatable field', false);
        $contentTypeService->addFieldDefinition($contentTypeDraft, $fieldDefCreateStruct);

        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        // Update eng-GB draft
        var_dump("PPPPPUUUBBBBBLISH", get_class($engUpdateStruct));
        // Ibexa\Core\Event\ContentService vs Ibexa\Core\Repository\SiteAccessAware\ContentService
        $engUpdateStruct->setField('non_trans_field', '', 'eng-GB');
        $this->updateContent($engDraft, $engUpdateStruct);
        //$contentService->updateContent($engContent->getVersionInfo(), $contentService->newContentUpdateStruct());
        //$contentService->publishVersion($engContent->versionInfo);


        //$creatorId = $this->generateId('user', $permissionResolver->getCurrentUserReference()->getUserId());



        var_dump("kake");
    }

    private function createFieldDefinitionStruct(string $identifier, string $name, bool $isTranslatable): FieldDefinitionCreateStruct
    {
        $contentTypeService = self::getContentTypeService();

//        $repository = $this->getRepository();
//        $contentTypeService = $repository->getContentTypeService();

        $fieldDefCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
            $identifier,
            'ezstring'
        );

        $fieldDefCreateStruct->names = ['eng-GB' => $name];
        $fieldDefCreateStruct->descriptions = [
            'eng-GB' => '',
        ];
        $fieldDefCreateStruct->isTranslatable = $isTranslatable;

        return $fieldDefCreateStruct;
    }

    private function createTypeCreateStruct(): ContentTypeCreateStruct {
        $contentTypeService = self::getContentTypeService();
        $permissionResolver = self::getPermissionResolver();

//        $repository = $this->getRepository();
//        $contentTypeService = $repository->getContentTypeService();
//        $permissionResolver = $repository->getPermissionResolver();

        //$creatorId = $this->generateId('user', $permissionResolver->getCurrentUserReference()->getUserId());

        $typeCreateStruct = $contentTypeService->newContentTypeCreateStruct('multi_lang_drafts');
        $typeCreateStruct->mainLanguageCode = 'eng-GB';
        $typeCreateStruct->names = ['eng-GB' => 'Multi lang drafts'];
        //$typeCreateStruct->creatorId = $creatorId;
        //$typeCreateStruct->creationDate = $this->createDateTime();

        return $typeCreateStruct;
    }

    protected function createNewContent(string $name, array $languages = ['eng-GB'], int $parentLocationId = 2, ): Content
    {
        $contentTypeService = self::getContentTypeService();
        $contentService = self::getContentService();
        $locationService = self::getLocationService();
//        $repository = $this->getRepository();
//        $contentTypeService = $repository->getContentTypeService();
//        $contentService = $repository->getContentService();
//        $locationService = $repository->getLocationService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('multi_lang_drafts');
        $createStruct = $contentService->newContentCreateStruct($contentType, $languages[0]);

        foreach ($languages as $language) {
            $createStruct->setField('name', "[$language]" . $name, $language);
        }
        $locationCreateStruct = $locationService->newLocationCreateStruct($parentLocationId);

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);

        return $contentService->publishVersion($draft->versionInfo);
    }

    protected function createUpdateStruct(Content $content, string $translatedName, array $languages )
    {
        $contentService = self::getContentService();
//        $repository = $this->getRepository();
//        $contentService = $repository->getContentService();

        //$content = $contentService->loadContent($contentId);

        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->initialLanguageCode = $languages[0];

        if ($translatedName === '') {
            $translatedNameOrg = $content->getName();
        } else {
            $translatedNameOrg = $translatedName;
        }

        /** Need to use $languiage param? */
        //$draft = $contentService->createContentDraft($content->contentInfo);
        foreach ($languages as $language) {
            $translatedName = "[$language]" . $translatedNameOrg;

            //$updateStruct->initialLanguageCode = $newLanguage;
            $updateStruct->setField('name', $translatedName, $language);
        }
        return $updateStruct;
//        $updatedContent = $contentService->updateContent($draft->versionInfo,$updateStruct);
//
//        return $updatedContent;
        //return $contentService->publishVersion($updatedContent->versionInfo);
//        $this->output->writeln("Translated content: '$translatedName' with contentId=" . $content->getId() . ' and locationId=' . $content->contentInfo->mainLocationId . ' to language(s) : ' . implode(', ', $languages));

    }

    protected function createContentDraft(Content $content, string $languageCode): Content
    {
        $contentService = self::getContentService();
        $contentLanguageService = self::getLanguageService();
//        $repository = $this->getRepository();
//        $contentService = $repository->getContentService();
//        $contentLanguageService = $repository->getContentLanguageService();

        $language = $contentLanguageService->loadLanguage($languageCode);
        $draft = $contentService->createContentDraft($content->contentInfo, null, null, $language);
        return $draft;
    }

    protected function updateContent(Content $draft, ContentUpdateStruct $updateStruct/*, array $languages*/): Content
    {
        $contentService = self::getContentService();
//        $repository = $this->getRepository();
//        $contentService = $repository->getContentService();
        
        //$draft = $contentService->loadContent($draft->id, $languages, $draft->versionInfo->getVersionNo());
        var_dump("my content service", get_class($contentService));
        var_dump("vupdateStruct1", $updateStruct);

        // At this point, $updateStruct is correct. However, when it reaches Ibexa\Core\Persistence\Legacy\Content::updateFields(),
        // it will have an additional field (in ger-DE). This does not happen when running the same code in
        // a controller inside the application (you then need to replace 'self::get*Service()' with DI).
        // It seems like for instance decoration Ibexa\Core\Event\ContentService is not used inside tests, maybe other decorations to?
        $updatedDraft = $contentService->updateContent($draft->versionInfo,$updateStruct);
        return $updatedDraft;
    }


}