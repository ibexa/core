<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Translation;

use Doctrine\Common\Annotations\DocParser;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\File\DefaultPhpFileExtractor;
use JMS\TranslationBundle\Translation\FileSourceFactory;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use Psr\Log\LoggerAwareTrait;
use SplFileInfo;

class ExceptionMessageTemplateFileVisitor extends DefaultPhpFileExtractor
{
    use LoggerAwareTrait;

    /** @var array<string, int> */
    protected $methodsToExtractFrom = ['setMessageTemplate' => -1];

    protected string $defaultDomain = 'repository_exceptions';

    private FileSourceFactory $fileSourceFactory;

    private NodeTraverser $traverser;

    private SplFileInfo $file;

    private MessageCatalogue $catalogue;

    public function __construct(DocParser $docParser, FileSourceFactory $fileSourceFactory)
    {
        parent::__construct($docParser, $fileSourceFactory);
        $this->fileSourceFactory = $fileSourceFactory;
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($this);
    }

    public function enterNode(Node $node): void
    {
        $methodCallNodeName = null;
        if ($node instanceof Node\Expr\MethodCall) {
            $methodCallNodeName = $node->name instanceof Node\Identifier ? $node->name->name : $node->name;
        }
        if (
            !is_string($methodCallNodeName)
            || !array_key_exists($methodCallNodeName, $this->methodsToExtractFrom)
        ) {
            return;
        }

        if (!$node->args[0]->value instanceof String_) {
            $message = sprintf('Can only extract the translation id from a scalar string, but got "%s". Please refactor your code to make it extractable, or add the doc comment /** @Ignore */ to this code element (in %s on line %d).', get_class($node->args[0]->value), $this->file, $node->args[0]->value->getLine());

            $this->logger->error($message);

            return;
        }

        $id = $node->args[0]->value->value;

        $message = new Message($id, $this->defaultDomain);
        $message->addSource($this->fileSourceFactory->create($this->file, $node->getLine()));
        $this->catalogue->add($message);
    }

    public function visitPhpFile(SplFileInfo $file, MessageCatalogue $catalogue, array $ast): void
    {
        $this->file = $file;
        $this->catalogue = $catalogue;
        $this->traverser->traverse($ast);
    }
}

class_alias(ExceptionMessageTemplateFileVisitor::class, 'eZ\Publish\Core\MVC\Symfony\Translation\ExceptionMessageTemplateFileVisitor');
