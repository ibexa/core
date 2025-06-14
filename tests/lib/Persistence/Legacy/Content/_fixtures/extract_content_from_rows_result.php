<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

$content = new Content();

$content->fields = [];

$versionInfo = new VersionInfo();
$versionInfo->id = 676;
$versionInfo->names = ['eng-US' => 'Something', 'eng-GB' => 'Something'];
$versionInfo->versionNo = 2;
$versionInfo->modificationDate = 1313061404;
$versionInfo->creatorId = 14;
$versionInfo->creationDate = 1313061317;
$versionInfo->status = 1;
$versionInfo->initialLanguageCode = 'eng-US';
$versionInfo->languageCodes = ['eng-US'];

$versionInfo->contentInfo = new ContentInfo();
$versionInfo->contentInfo->id = 226;
$versionInfo->contentInfo->contentTypeId = 16;
$versionInfo->contentInfo->sectionId = 1;
$versionInfo->contentInfo->ownerId = 14;
$versionInfo->contentInfo->remoteId = '95a226fb62c1533f60c16c3769bc7c6c';
$versionInfo->contentInfo->alwaysAvailable = false;
$versionInfo->contentInfo->modificationDate = 1313061404;
$versionInfo->contentInfo->publicationDate = 1313047907;
$versionInfo->contentInfo->currentVersionNo = 2;
$versionInfo->contentInfo->mainLanguageCode = 'eng-US';
$versionInfo->contentInfo->name = 'Something';
$versionInfo->contentInfo->mainLocationId = 228;
$versionInfo->contentInfo->status = 1;

$content->versionInfo = $versionInfo;

$field = new Field();
$field->id = 1332;
$field->fieldDefinitionId = 183;
$field->type = 'ibexa_string';
$field->value = new FieldValue();
$field->languageCode = 'eng-US';
$field->versionNo = 2;

$content->fields[] = $field;

$field = new Field();
$field->id = 1333;
$field->fieldDefinitionId = 184;
$field->type = 'ibexa_string';
$field->value = new FieldValue();
$field->languageCode = 'eng-US';
$field->versionNo = 2;

$content->fields[] = $field;

$field = new Field();
$field->id = 1334;
$field->fieldDefinitionId = 185;
$field->type = 'ibexa_author';
$field->value = new FieldValue();
$field->languageCode = 'eng-US';
$field->versionNo = 2;

$content->fields[] = $field;

$field = new Field();
$field->id = 1337;
$field->fieldDefinitionId = 188;
$field->type = 'ibexa_boolean';
$field->value = new FieldValue();
$field->languageCode = 'eng-US';
$field->versionNo = 2;

$content->fields[] = $field;

$field = new Field();
$field->id = 1338;
$field->fieldDefinitionId = 189;
$field->type = 'ibexa_image';
$field->value = new FieldValue();
$field->languageCode = 'eng-US';
$field->versionNo = 2;

$content->fields[] = $field;

$field = new Field();
$field->id = 1340;
$field->fieldDefinitionId = 191;
$field->type = 'ibexa_datetime';
$field->value = new FieldValue();
$field->languageCode = 'eng-US';
$field->versionNo = 2;

$content->fields[] = $field;

$field = new Field();
$field->id = 1341;
$field->fieldDefinitionId = 192;
$field->type = 'ibexa_datetime';
$field->value = new FieldValue();
$field->languageCode = 'eng-US';
$field->versionNo = 2;

$content->fields[] = $field;

$field = new Field();
$field->id = 1342;
$field->fieldDefinitionId = 193;
$field->type = 'ibexa_keyword';
$field->value = new FieldValue();
$field->languageCode = 'eng-US';
$field->versionNo = 2;

$content->fields[] = $field;

return $content;
