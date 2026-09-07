<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Translation;

use Doctrine\Common\Annotations\DocParser;
use Ibexa\Core\MVC\Symfony\Translation\Annotation\Domain;
use JMS\TranslationBundle\Annotation\Desc;
use JMS\TranslationBundle\Annotation\Ignore;
use JMS\TranslationBundle\Annotation\Meaning;
use JMS\TranslationBundle\Exception\RuntimeException;
use JMS\TranslationBundle\Logger\LoggerAwareInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use JMS\TranslationBundle\Translation\FileSourceFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Twig\Node\Node as TwigNode;

/**
 * Visits calls to some known translatable exceptions, into the repository_exceptions domain.
 */
class ValidationErrorFileVisitor implements LoggerAwareInterface, FileVisitorInterface, NodeVisitor
{
    private FileSourceFactory $fileSourceFactory;

    private NodeTraverser $traverser;

    private MessageCatalogue $catalogue;

    private SplFileInfo $file;

    private DocParser $docParser;

    private ?LoggerInterface $logger = null;

    private ?Node $previousNode = null;

    /** @var list<array<string, string>> */
    private array $classStringConstantStack = [];

    /** @var list<?string> */
    private array $classNameStack = [];

    /** @var list<?string> */
    private array $namespaceStack = [];

    protected string $defaultDomain = 'ibexa_repository_exceptions';

    public function __construct(DocParser $docParser, FileSourceFactory $fileSourceFactory)
    {
        $this->docParser = $docParser;
        $this->fileSourceFactory = $fileSourceFactory;
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($this);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function enterNode(Node $node): null
    {
        if ($node instanceof Namespace_) {
            $this->namespaceStack[] = $node->name?->toString();
            $this->previousNode = $node;

            return null;
        }

        if ($node instanceof ClassLike) {
            $this->classStringConstantStack[] = $this->extractStringConstants($node);
            $this->classNameStack[] = $node->name?->toString();
            $this->previousNode = $node;

            return null;
        }

        if (!$node instanceof New_
            || !$node->class instanceof Name
            || strtolower((string) $node->class) !== 'validationerror'
        ) {
            $this->previousNode = $node;

            return null;
        }

        $idArg = $this->getArgument($node, 0);
        if (null === $idArg) {
            $this->previousNode = $node;

            return null;
        }

        $ignore = false;
        $desc = $meaning = null;
        $domain = $this->defaultDomain;
        if (null !== $docComment = $this->getDocCommentForNode($node)) {
            foreach ($this->docParser->parse($docComment, 'file ' . $this->file . ' near line ' . $node->getLine()) as $annot) {
                if ($annot instanceof Ignore) {
                    $ignore = true;
                } elseif ($annot instanceof Desc) {
                    $desc = $annot->text;
                } elseif ($annot instanceof Meaning) {
                    $meaning = $annot->text;
                } elseif ($annot instanceof Domain) {
                    $domain = $annot->value;
                }
            }
        }

        $id = $this->resolveTranslationId($idArg->value);
        if (null === $id) {
            if ($ignore) {
                return null;
            }

            $message = sprintf(
                'Can only extract the translation ID from a scalar string, but got "%s". Refactor your code to make it extractable, or add the doc comment /** @Ignore */ to this code element (in %s on line %d).',
                get_class($idArg->value),
                $this->file,
                $idArg->value->getLine()
            );

            if (null !== $this->logger) {
                $this->logger->error($message);

                return null;
            }

            throw new RuntimeException($message);
        }

        $message = new Message($id, $domain);
        $message->setDesc($desc);
        $message->setMeaning($meaning);
        $message->addSource($this->fileSourceFactory->create($this->file, $node->getLine()));
        $this->catalogue->add($message);

        $pluralArg = $this->getArgument($node, 1);
        if (null !== $pluralArg && null !== ($pluralId = $this->resolveTranslationId($pluralArg->value))) {
            $message = new Message($pluralId, $domain);
            $message->setDesc($desc);
            $message->setMeaning($meaning);
            $message->addSource($this->fileSourceFactory->create($this->file, $node->getLine()));
            $this->catalogue->add($message);
        }

        return null;
    }

    /**
     * @param array<\PhpParser\Node> $nodes
     */
    public function beforeTraverse(array $nodes): null
    {
        $this->previousNode = null;
        $this->classStringConstantStack = [];
        $this->classNameStack = [];
        $this->namespaceStack = [];

        return null;
    }

    public function leaveNode(Node $node): null
    {
        if ($node instanceof ClassLike) {
            array_pop($this->classStringConstantStack);
            array_pop($this->classNameStack);
        }

        if ($node instanceof Namespace_) {
            array_pop($this->namespaceStack);
        }

        return null;
    }

    public function afterTraverse(array $nodes): null
    {
        return null;
    }

    /**
     * @param array<\PhpParser\Node> $ast
     */
    public function visitPhpFile(SplFileInfo $file, MessageCatalogue $catalogue, array $ast): void
    {
        $this->file = $file;
        $this->catalogue = $catalogue;
        $this->traverser->traverse($ast);
    }

    public function visitFile(SplFileInfo $file, MessageCatalogue $catalogue): void
    {
    }

    public function visitTwigFile(SplFileInfo $file, MessageCatalogue $catalogue, TwigNode $ast): void
    {
    }

    private function getDocCommentForNode(New_ $node): ?string
    {
        $idArg = $this->getArgument($node, 0);
        if (null !== $idArg && null !== ($comment = $idArg->getDocComment())) {
            return $comment->getText();
        }

        if (null !== $comment = $node->getDocComment()) {
            return $comment->getText();
        }

        return null !== $this->previousNode && null !== ($comment = $this->previousNode->getDocComment())
            ? $comment->getText()
            : null;
    }

    private function getArgument(New_ $node, int $index): ?Arg
    {
        return isset($node->args[$index]) && $node->args[$index] instanceof Arg
            ? $node->args[$index]
            : null;
    }

    private function resolveTranslationId(Node $node): ?string
    {
        if ($node instanceof String_) {
            return $node->value;
        }

        if (!$node instanceof ClassConstFetch || !$node->name instanceof Identifier) {
            return null;
        }

        if (!$this->isCurrentClassConstantFetch($node)) {
            return null;
        }

        $constants = end($this->classStringConstantStack);
        if (false === $constants) {
            return null;
        }

        return $constants[$node->name->toString()] ?? null;
    }

    private function isCurrentClassConstantFetch(ClassConstFetch $node): bool
    {
        if (!$node->class instanceof Name || [] === $this->classStringConstantStack) {
            return false;
        }

        $className = strtolower($node->class->toString());
        if (in_array($className, ['self', 'static'], true)) {
            return true;
        }

        $currentClass = $this->getCurrentClassName();
        if (null === $currentClass) {
            return false;
        }

        if ($className === strtolower($currentClass)) {
            return true;
        }

        $namespace = end($this->namespaceStack);
        if (false === $namespace || null === $namespace) {
            return false;
        }

        return ltrim($className, '\\') === strtolower($namespace . '\\' . $currentClass);
    }

    private function getCurrentClassName(): ?string
    {
        $className = end($this->classNameStack);

        return false === $className ? null : $className;
    }

    /**
     * @return array<string, string>
     */
    private function extractStringConstants(ClassLike $class): array
    {
        $constants = [];

        foreach ($class->stmts ?? [] as $statement) {
            if (!$statement instanceof ClassConst) {
                continue;
            }

            foreach ($statement->consts as $const) {
                if ($const->value instanceof String_) {
                    $constants[$const->name->toString()] = $const->value->value;
                }
            }
        }

        return $constants;
    }
}
