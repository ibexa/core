<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Translation;

use Doctrine\Common\Annotations\DocParser;
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
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Twig\Node\Node as TwigNode;

/**
 * Visits calls to some known translatable exceptions, into the repository_exceptions domain.
 */
class TranslatableExceptionsFileVisitor implements LoggerAwareInterface, FileVisitorInterface, NodeVisitor
{
    private FileSourceFactory $fileSourceFactory;

    private NodeTraverser $traverser;

    private MessageCatalogue $catalogue;

    private SplFileInfo $file;

    private DocParser $docParser;

    private ?LoggerInterface $logger = null;

    private Node $previousNode;

    protected string $defaultDomain = 'ibexa_repository_exceptions';

    /** @var string[] exception class names, lowercase */
    protected array $exceptionsToExtractFrom = [
        'contentvalidationexception',
        'forbiddenexception',
    ];

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
        if (!$node instanceof Node\Expr\Throw_
            || !$node->expr instanceof Node\Expr\New_
            || !$node->expr->class instanceof Node\Name
        ) {
            $this->previousNode = $node;

            return null;
        }

        $exceptionClass = $node->expr->class->getParts()[0];
        if (!in_array(strtolower($exceptionClass), $this->exceptionsToExtractFrom, true)) {
            $this->previousNode = $node;

            return null;
        }

        $node = $node->expr;
        $ignore = false;
        $desc = $meaning = null;
        if (null !== $docComment = $this->getDocCommentForNode($node)) {
            foreach ($this->docParser->parse($docComment, 'file ' . $this->file . ' near line ' . $node->getLine()) as $annot) {
                if ($annot instanceof Ignore) {
                    $ignore = true;
                } elseif ($annot instanceof Desc) {
                    $desc = $annot->text;
                } elseif ($annot instanceof Meaning) {
                    $meaning = $annot->text;
                }
            }
        }

        if (!$node->args[0]->value instanceof String_) {
            if ($ignore) {
                return null;
            }

            $message = sprintf('Can only extract the translation ID from a scalar string, but got "%s". Refactor your code to make it extractable, or add the doc comment /** @Ignore */ to this code element (in %s on line %d).', get_class($node->args[0]->value), $this->file, $node->args[0]->value->getLine());

            if (null !== $this->logger) {
                $this->logger->error($message);

                return null;
            }

            throw new RuntimeException($message);
        }

        $id = $node->args[0]->value->value;

        $message = new Message($id, $this->defaultDomain);
        $message->setDesc($desc);
        $message->setMeaning($meaning);
        $message->addSource($this->fileSourceFactory->create($this->file, $node->getLine()));
        $this->catalogue->add($message);

        return null;
    }

    /**
     * @param \SplFileInfo $file
     * @param \JMS\TranslationBundle\Model\MessageCatalogue $catalogue
     * @param array<\PhpParser\Node> $ast
     */
    public function visitPhpFile(SplFileInfo $file, MessageCatalogue $catalogue, array $ast): void
    {
        $this->file = $file;
        $this->catalogue = $catalogue;
        $this->traverser->traverse($ast);
    }

    public function beforeTraverse(array $nodes): null
    {
        return null;
    }

    public function leaveNode(Node $node): null
    {
        return null;
    }

    public function afterTraverse(array $nodes): null
    {
        return null;
    }

    /**
     * @param \SplFileInfo $file
     * @param \JMS\TranslationBundle\Model\MessageCatalogue $catalogue
     */
    public function visitFile(SplFileInfo $file, MessageCatalogue $catalogue): void
    {
    }

    /**
     * @param \SplFileInfo $file
     * @param \JMS\TranslationBundle\Model\MessageCatalogue $catalogue
     * @param \Twig\Node\Node $ast
     */
    public function visitTwigFile(SplFileInfo $file, MessageCatalogue $catalogue, TwigNode $ast): void
    {
    }

    /**
     * @param \PhpParser\Node\Expr\New_ $node
     */
    private function getDocCommentForNode(Node $node): ?string
    {
        // check if there is a doc comment for the ID argument
        // ->trans(/** @Desc("FOO") */ 'my.id')
        if (null !== $comment = $node->args[0]->getDocComment()) {
            return $comment->getText();
        }

        // this may be placed somewhere up in the hierarchy,
        // -> /** @Desc("FOO") */ trans('my.id')
        // /** @Desc("FOO") */ ->trans('my.id')
        // /** @Desc("FOO") */ $translator->trans('my.id')
        if (null !== $comment = $node->getDocComment()) {
            return $comment->getText();
        }

        return isset($this->previousNode) && ($comment = $this->previousNode->getDocComment()) !== null
            ? $comment->getText()
            : null;
    }
}
