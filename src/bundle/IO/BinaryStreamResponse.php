<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO;

use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A modified version of HttpFoundation's BinaryFileResponse that accepts a stream as the input.
 */
class BinaryStreamResponse extends Response
{
    protected BinaryFile $file;

    protected IOServiceInterface $ioService;

    protected int $offset;

    protected int $maxlen;

    /**
     * @param array<string, array<string>> $headers An array of response headers
     * @param bool $public Files are public by default
     *
     * @phpstan-param \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_*|null $contentDisposition The type of Content-Disposition to set automatically with the filename
     *
     * @param bool $autoLastModified Whether the Last-Modified header should be automatically set
     */
    public function __construct(
        BinaryFile $binaryFile,
        IOServiceInterface $ioService,
        int $status = 200,
        array $headers = [],
        bool $public = true,
        ?string $contentDisposition = null,
        bool $autoLastModified = true
    ) {
        $this->ioService = $ioService;

        parent::__construct(null, $status, $headers);

        $this->setFile($binaryFile, $contentDisposition, $autoLastModified);

        if ($public) {
            $this->setPublic();
        }
    }

    /**
     * @phpstan-param \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_*|null $contentDisposition
     *
     * @return $this
     */
    public function setFile(BinaryFile $file, ?string $contentDisposition = null, bool $autoLastModified = true): static
    {
        $this->file = $file;

        if ($autoLastModified) {
            $this->setAutoLastModified();
        }

        if (!empty($contentDisposition)) {
            $this->setContentDisposition($contentDisposition);
        }

        return $this;
    }

    public function getFile(): BinaryFile
    {
        return $this->file;
    }

    /**
     * Automatically sets the Last-Modified header according the file modification date.
     *
     * @return $this
     */
    public function setAutoLastModified(): static
    {
        $this->setLastModified($this->file->getMtime());

        return $this;
    }

    /**
     * Sets the Content-Disposition header with the given filename.
     *
     * @phpstan-param \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_* $disposition
     *
     * @param string $filename Optionally use this filename instead of the real name of the file
     * @param string $filenameFallback A fallback filename, containing only ASCII characters. Defaults to an automatically encoded filename
     *
     * @return $this
     */
    public function setContentDisposition(string $disposition, string $filename = '', string $filenameFallback = ''): BinaryStreamResponse
    {
        if ($filename === '') {
            $filename = $this->file->getId();
        }

        if (empty($filenameFallback)) {
            $filenameFallback = mb_convert_encoding($filename, 'ASCII');

            if ($filenameFallback === false) {
                throw new InvalidArgumentException(sprintf(
                    'Could not convert filename "%s" to ASCII. It contains invalid or non-ASCII byte sequences.',
                    $filename
                ));
            }
        }

        $dispositionHeader = $this->headers->makeDisposition($disposition, $filename, $filenameFallback);
        $this->headers->set('Content-Disposition', $dispositionHeader);

        return $this;
    }

    /**
     * @return $this
     */
    public function prepare(Request $request): static
    {
        $this->headers->set('Content-Length', (string)$this->file->getSize());
        $this->headers->set('Accept-Ranges', 'bytes');
        $this->headers->set('Content-Transfer-Encoding', 'binary');

        if (!$this->headers->has('Content-Type')) {
            $this->headers->set(
                'Content-Type',
                $this->ioService->getMimeType($this->file->getId()) ?: 'application/octet-stream'
            );
        }

        if ('HTTP/1.0' !== $request->server->get('SERVER_PROTOCOL')) {
            $this->setProtocolVersion('1.1');
        }

        $this->ensureIEOverSSLCompatibility($request);

        $this->offset = 0;
        $this->maxlen = -1;

        if ($this->isRangeRequest($request)) {
            $this->processRangeRequest($request);
        }

        return $this;
    }

    /**
     * Sends the file.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @return $this
     */
    public function sendContent(): static
    {
        if (!$this->isSuccessful()) {
            parent::sendContent();

            return $this;
        }

        if ($this->maxlen !== 0) {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                throw new LogicException('Failed to create binary output stream');
            }

            $in = $this->ioService->getFileInputStream($this->file);
            stream_copy_to_stream($in, $out, $this->maxlen, $this->offset);

            fclose($out);
        }

        return $this;
    }

    /**
     * @throws \LogicException when the content is not null
     *
     * @return $this
     */
    public function setContent(?string $content): static
    {
        if (null !== $content) {
            throw new LogicException('The content cannot be set on a BinaryStreamResponse instance.');
        }

        return $this;
    }

    public function getContent(): false
    {
        return false;
    }

    /**
     * @phpstan-assert-if-true !null $request->headers->get('Range')
     */
    private function isRangeRequest(Request $request): bool
    {
        return $request->headers->has('Range')
            && (
                !$request->headers->has('If-Range') || $this->getEtag() === $request->headers->get('If-Range')
            );
    }

    private function processRangeRequest(Request $request): void
    {
        $range = $request->headers->get('Range');
        $fileSize = $this->file->getSize();

        [$start, $end] = explode('-', substr($range, 6), 2) + [0];

        $end = ('' === $end) ? $fileSize - 1 : (int)$end;

        if ('' === $start) {
            $start = $fileSize - $end;
            $end = $fileSize - 1;
        } else {
            $start = (int)$start;
        }

        if ($start <= $end) {
            if ($start < 0 || $end > $fileSize - 1) {
                $this->setStatusCode(
                    Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE
                );
            } elseif ($start !== 0 || $end !== $fileSize - 1) {
                $this->maxlen = $end < $fileSize ? $end - $start + 1 : -1;
                $this->offset = $start;

                $this->setStatusCode(Response::HTTP_PARTIAL_CONTENT);
                $this->headers->set('Content-Range', sprintf('bytes %s-%s/%s', $start, $end, $fileSize));
                $this->headers->set('Content-Length', (string) ($end - $start + 1));
            }
        }
    }
}
