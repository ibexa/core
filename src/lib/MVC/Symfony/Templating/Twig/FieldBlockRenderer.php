<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating\Twig;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\FieldTypeAliasResolverInterface;
use Ibexa\Core\MVC\Symfony\Templating\Exception\MissingFieldBlockException;
use Ibexa\Core\MVC\Symfony\Templating\FieldBlockRendererInterface;
use Twig\Environment;
use Twig\Template;

class FieldBlockRenderer implements FieldBlockRendererInterface
{
    public const VIEW = 1;
    public const EDIT = 2;

    public const FIELD_VIEW_SUFFIX = '_field';
    public const FIELD_EDIT_SUFFIX = '_field_edit';
    public const FIELD_DEFINITION_VIEW_SUFFIX = '_settings';
    public const FIELD_DEFINITION_EDIT_SUFFIX = '_field_definition_edit';

    public const FIELD_RESOURCES_MAP = [
        self::VIEW => 'fieldViewResources',
        self::EDIT => 'fieldEditResources',
    ];

    public const FIELD_DEFINITION_RESOURCES_MAP = [
        self::VIEW => 'fieldDefinitionViewResources',
        self::EDIT => 'fieldDefinitionEditResources',
    ];

    private Environment $twig;

    private ResourceProviderInterface $resourceProvider;

    private FieldTypeAliasResolverInterface $fieldTypeAliasResolver;

    /**
     * A \Twig\Template instance used to render template blocks, or path to the template to use.
     *
     * @var \Twig\Template|string
     */
    private $baseTemplate;

    /**
     * Template blocks.
     *
     * @var array
     */
    private $blocks;

    /**
     * @param string|\Twig\Template $baseTemplate
     * @param array $blocks
     */
    public function __construct(
        Environment $twig,
        ResourceProviderInterface $resourceProvider,
        FieldTypeAliasResolverInterface $fieldTypeAliasResolver,
        $baseTemplate,
        array $blocks = []
    ) {
        $this->twig = $twig;
        $this->resourceProvider = $resourceProvider;
        $this->fieldTypeAliasResolver = $fieldTypeAliasResolver;
        $this->baseTemplate = $baseTemplate;
        $this->blocks = $blocks;
    }

    /**
     * @param \Twig\Environment $twig
     */
    public function setTwig(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function renderContentFieldView(Field $field, $fieldTypeIdentifier, array $params = []): string
    {
        return $this->renderContentField($field, $fieldTypeIdentifier, $params, self::VIEW);
    }

    public function renderContentFieldEdit(Field $field, $fieldTypeIdentifier, array $params = []): string
    {
        return $this->renderContentField($field, $fieldTypeIdentifier, $params, self::EDIT);
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field $field
     * @param string $fieldTypeIdentifier
     * @param array $params
     * @param int $type Either self::VIEW or self::EDIT
     *
     * @throws \Ibexa\Core\MVC\Symfony\Templating\Exception\MissingFieldBlockException If no template block can be found for $field
     *
     * @return string
     */
    private function renderContentField(
        Field $field,
        string $fieldTypeIdentifier,
        array $params,
        int $type,
    ): string {
        $fieldTypeIdentifier = $this->fieldTypeAliasResolver->resolveIdentifier($fieldTypeIdentifier);

        $localTemplate = null;
        if (isset($params['template'])) {
            // local override of the template
            // this template is put on the top the templates stack
            $localTemplate = $params['template'];
            unset($params['template']);
        }

        $params += ['field' => $field];

        // Getting instance of \Twig\Template that will be used to render blocks
        if (is_string($this->baseTemplate)) {
            $this->baseTemplate = $this->twig->loadTemplate(
                $this->twig->getTemplateClass($this->baseTemplate),
                $this->baseTemplate
            );
        }
        $blockName = $this->getRenderFieldBlockName($fieldTypeIdentifier, $type);
        $context = $params + $this->twig->getGlobals();
        $blocks = $this->getBlocksByField($fieldTypeIdentifier, $type, $localTemplate);

        if (!$this->baseTemplate->hasBlock($blockName, $context, $blocks)) {
            throw new MissingFieldBlockException("Cannot find '$blockName' template block.");
        }

        return $this->baseTemplate->renderBlock($blockName, $context, $blocks);
    }

    public function renderFieldDefinitionView(FieldDefinition $fieldDefinition, array $params = []): string
    {
        return $this->renderFieldDefinition($fieldDefinition, $params, self::VIEW);
    }

    public function renderFieldDefinitionEdit(FieldDefinition $fieldDefinition, array $params = []): string
    {
        return $this->renderFieldDefinition($fieldDefinition, $params, self::EDIT);
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDefinition
     * @param array $params
     * @param int $type Either self::VIEW or self::EDIT
     *
     * @return string
     */
    private function renderFieldDefinition(FieldDefinition $fieldDefinition, array $params, $type): string
    {
        $fieldTypeIdentifier = $this->fieldTypeAliasResolver->resolveIdentifier(
            $fieldDefinition->getFieldTypeIdentifier(),
        );

        if (is_string($this->baseTemplate)) {
            $this->baseTemplate = $this->twig->loadTemplate(
                $this->twig->getTemplateClass($this->baseTemplate),
                $this->baseTemplate
            );
        }

        $params += [
            'fielddefinition' => $fieldDefinition,
            'settings' => $fieldDefinition->getFieldSettings(),
        ];
        $blockName = $this->getRenderFieldDefinitionBlockName($fieldDefinition->fieldTypeIdentifier, $type);
        $context = $params + $this->twig->getGlobals();
        $blocks = $this->getBlockByName(
            $this->getRenderFieldDefinitionBlockName($fieldTypeIdentifier, $type),
            self::FIELD_DEFINITION_RESOURCES_MAP[$type]
        );

        if (!$this->baseTemplate->hasBlock($blockName, $context, $blocks)) {
            return '';
        }

        return $this->baseTemplate->renderBlock($blockName, $context, $blocks);
    }

    /**
     * Returns the block named $blockName in the given template. If it's not
     * found, returns null.
     *
     * @param string $blockName
     * @param \Twig\Template $tpl
     *
     * @return array|null
     */
    private function searchBlock(string $blockName, Template $tpl): ?array
    {
        // Current template might have parents, so we need to loop against
        // them to find a matching block
        do {
            foreach ($tpl->getBlocks() as $name => $block) {
                if ($name === $blockName) {
                    return $block;
                }
            }
        } while (($tpl = $tpl->getParent([])) instanceof Template);

        return null;
    }

    /**
     * Returns template blocks for $fieldTypeIdentifier. First check in the $localTemplate if it's provided.
     * Template block convention name is <fieldTypeIdentifier>_field
     * Example: 'ezstring_field' will be relevant for a full view of ezstring field type.
     *
     * @param string $fieldTypeIdentifier
     * @param int $type Either self::VIEW or self::EDIT
     * @param string|\Twig\Template|null $localTemplate a file where to look for the block first
     *
     * @return array
     */
    private function getBlocksByField($fieldTypeIdentifier, $type, $localTemplate = null): array
    {
        $fieldBlockName = $this->getRenderFieldBlockName($fieldTypeIdentifier, $type);
        if ($localTemplate !== null) {
            // $localTemplate might be a \Twig\Template instance already (e.g. using _self Twig keyword)
            if (!$localTemplate instanceof Template) {
                $localTemplate = $this->twig->loadTemplate(
                    $this->twig->getTemplateClass($localTemplate),
                    $localTemplate
                );
            }

            $block = $this->searchBlock($fieldBlockName, $localTemplate);
            if ($block !== null) {
                return [$fieldBlockName => $block];
            }
        }

        return $this->getBlockByName($fieldBlockName, self::FIELD_RESOURCES_MAP[$type]);
    }

    /**
     * Returns the template block of the given $name available in the resources
     * which name is $resourcesName.
     *
     * @param string $name
     * @param string $resourcesName
     *
     * @return array
     */
    private function getBlockByName($name, $resourcesName): array
    {
        if (isset($this->blocks[$name])) {
            return [$name => $this->blocks[$name]];
        }

        foreach ($this->getResources($resourcesName) as &$template) {
            if (!$template instanceof Template) {
                $template = $this->twig->loadTemplate(
                    $this->twig->getTemplateClass($template['template']),
                    $template['template']
                );
            }

            $tpl = $template;

            $block = $this->searchBlock($name, $tpl);
            if ($block !== null) {
                $this->blocks[$name] = $block;

                return [$name => $block];
            }
        }

        return [];
    }

    /**
     * Returns expected block name for $fieldTypeIdentifier, attached in $content.
     *
     * @param string $fieldTypeIdentifier
     * @param int $type Either self::VIEW or self::EDIT
     *
     * @return string
     */
    private function getRenderFieldBlockName($fieldTypeIdentifier, $type): string
    {
        $suffix = $type === self::EDIT ? self::FIELD_EDIT_SUFFIX : self::FIELD_VIEW_SUFFIX;

        return $fieldTypeIdentifier . $suffix;
    }

    /**
     * Returns the name of the block to render the settings of the field
     * definition $definition.
     *
     * @param string $fieldTypeIdentifier
     * @param int $type Either self::VIEW or self::EDIT
     *
     * @return string
     */
    private function getRenderFieldDefinitionBlockName($fieldTypeIdentifier, $type): string
    {
        $suffix = $type === self::EDIT ? self::FIELD_DEFINITION_EDIT_SUFFIX : self::FIELD_DEFINITION_VIEW_SUFFIX;

        return $fieldTypeIdentifier . $suffix;
    }

    /**
     * @return array|\Twig\Template[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function getResources(string $resourceType): array
    {
        switch ($resourceType) {
            case 'fieldViewResources':
                return $this->sortResources($this->resourceProvider->getFieldViewResources());
            case 'fieldEditResources':
                return $this->sortResources($this->resourceProvider->getFieldEditResources());
            case 'fieldDefinitionViewResources':
                return $this->sortResources($this->resourceProvider->getFieldDefinitionViewResources());
            case 'fieldDefinitionEditResources':
                return $this->sortResources($this->resourceProvider->getFieldDefinitionEditResources());
            default:
                throw new InvalidArgumentException(
                    '$resourceType',
                    sprintf('Invalid resource type: %s', $resourceType)
                );
        }
    }

    private function sortResources(array $resources): array
    {
        usort($resources, static function (array $a, array $b): int {
            return $b['priority'] - $a['priority'];
        });

        return $resources;
    }
}
