<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO;

use DateTime;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\RangeAwareIOServiceInterface;
use Ibexa\Core\IO\Stream\StreamSeeker;
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
    protected static $trustXSendfileTypeHeader = false;

    /** @var BinaryFile */
    protected $file;

    /** @var IOServiceInterface */
    protected $ioService;

    protected $offset;

    protected $maxlen;

    /**
     * Opened in prepare(), so storage errors are raised before any header is sent.
     *
     * @var resource|null
     */
    private $inputStream;

    /**
     * Constructor.
     *
     * @param BinaryFile         $binaryFile         The file to stream
     * @param IOServiceInterface $ioService          The IO service used to read the file
     * @param int                $status             The response status code
     * @param array<string, string|string[]> $headers An array of response headers
     * @param bool               $public             Files are public by default
     * @param string|null        $contentDisposition The type of Content-Disposition to set automatically with the filename
     * @param bool               $autoLastModified   Whether the Last-Modified header should be automatically set
     */
    public function __construct(
        BinaryFile $binaryFile,
        IOServiceInterface $ioService,
        $status = 200,
        $headers = [],
        $public = true,
        $contentDisposition = null,
        $autoLastModified = true
    ) {
        $this->ioService = $ioService;

        parent::__construct(null, $status, $headers);

        $this->setFile($binaryFile, $contentDisposition, $autoLastModified);

        if ($public) {
            $this->setPublic();
        }
    }

    /**
     * Sets the file to stream.
     *
     * @param BinaryFile $file The file to stream
     * @param string|null $contentDisposition The type of Content-Disposition to set automatically with the filename
     * @param bool $autoLastModified Whether the Last-Modified header should be automatically set
     *
     * @return $this
     */
    public function setFile(
        $file,
        $contentDisposition = null,
        $autoLastModified = true
    ) {
        $this->file = $file;

        if ($autoLastModified) {
            $this->setAutoLastModified();
        }

        if ($contentDisposition) {
            $this->setContentDisposition($contentDisposition);
        }

        return $this;
    }

    /**
     * Gets the file.
     *
     * @return BinaryFile The file to stream
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Automatically sets the Last-Modified header according the file modification date.
     *
     * @return $this
     */
    public function setAutoLastModified()
    {
        $this->setLastModified(DateTime::createFromFormat('U', $this->file->mtime->getTimestamp()));

        return $this;
    }

    /**
     * Sets the Content-Disposition header with the given filename.
     *
     * @param string $disposition ResponseHeaderBag::DISPOSITION_INLINE or ResponseHeaderBag::DISPOSITION_ATTACHMENT
     * @param string $filename Optionally use this filename instead of the real name of the file
     * @param string $filenameFallback A fallback filename, containing only ASCII characters. Defaults to an automatically encoded filename
     *
     * @return $this
     */
    public function setContentDisposition(
        $disposition,
        $filename = '',
        $filenameFallback = ''
    ) {
        if ($filename === '') {
            $filename = $this->file->id;
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
     * {@inheritdoc}
     */
    public function prepare(Request $request)
    {
        $this->headers->set('Content-Length', (string) $this->file->size);
        $this->headers->set('Accept-Ranges', 'bytes');
        $this->headers->set('Content-Transfer-Encoding', 'binary');

        if (!$this->headers->has('Content-Type')) {
            $this->headers->set(
                'Content-Type',
                $this->ioService->getMimeType($this->file->id) ?: 'application/octet-stream'
            );
        }

        if ('HTTP/1.0' != $request->server->get('SERVER_PROTOCOL')) {
            $this->setProtocolVersion('1.1');
        }

        $this->ensureIEOverSSLCompatibility($request);

        $this->offset = 0;
        $this->maxlen = -1;

        if ($this->isRangeRequested($request) && !$this->applyRange((string)$request->headers->get('Range'))) {
            return $this;
        }

        $this->closeInputStream();
        if ($this->isSuccessful() && 0 !== $this->maxlen) {
            $this->inputStream = $this->openInputStream();
        }

        return $this;
    }

    /**
     * Sends the file.
     */
    public function sendContent()
    {
        if (!$this->isSuccessful()) {
            parent::sendContent();

            return;
        }

        if (0 === $this->maxlen) {
            return;
        }

        $in = $this->inputStream ?? $this->openInputStream();
        $this->inputStream = null;
        $out = fopen('php://output', 'wb');

        stream_copy_to_stream($in, $out, $this->maxlen ?? -1);

        fclose($in);
        fclose($out);
    }

    private function isRangeRequested(Request $request): bool
    {
        return $request->headers->has('Range')
            && (!$request->headers->has('If-Range') || $this->getEtag() == $request->headers->get('If-Range'));
    }

    /**
     * @return bool false when the range is not satisfiable
     */
    private function applyRange(string $range): bool
    {
        $fileSize = $this->file->size;

        list($start, $end) = explode('-', substr($range, 6), 2) + [0];

        $end = ('' === $end) ? $fileSize - 1 : (int)$end;

        if ('' === $start) {
            $start = $fileSize - $end;
            $end = $fileSize - 1;
        } else {
            $start = (int)$start;
        }

        if ($start > $end) {
            return true;
        }

        if ($start < 0 || $end > $fileSize - 1) {
            $this->setStatusCode(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);

            return false;
        }

        $this->maxlen = $end - $start + 1;
        $this->offset = $start;

        $this->setStatusCode(Response::HTTP_PARTIAL_CONTENT);
        $this->headers->set('Content-Range', sprintf('bytes %s-%s/%s', $start, $end, $fileSize));
        $this->headers->set('Content-Length', $this->maxlen);

        return true;
    }

    private function closeInputStream(): void
    {
        if ($this->inputStream !== null) {
            fclose($this->inputStream);
            $this->inputStream = null;
        }
    }

    /**
     * @return resource stream positioned at the requested offset
     */
    private function openInputStream()
    {
        $offset = (int)$this->offset;
        $length = $this->maxlen > 0 ? (int)$this->maxlen : null;

        if ($this->ioService instanceof RangeAwareIOServiceInterface) {
            return $this->ioService->getFileInputStreamRange($this->file, $offset, $length);
        }

        $in = $this->ioService->getFileInputStream($this->file);
        StreamSeeker::seek($in, $offset);

        return $in;
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException when the content is not null
     */
    public function setContent($content)
    {
        if (null !== $content) {
            throw new LogicException('The content cannot be set on a BinaryStreamResponse instance.');
        }
    }

    public function getContent()
    {
        return null;
    }
}

class_alias(BinaryStreamResponse::class, 'eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse');
