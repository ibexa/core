<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\Imagine\PlaceholderProvider;

use Ibexa\Bundle\Core\Imagine\PlaceholderProvider;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use RuntimeException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Remote placeholder provider e.g. http://placekitten.com.
 */
class RemoteProvider implements PlaceholderProvider
{
    /**
     * {@inheritdoc}
     */
    public function getPlaceholder(ImageValue $value, array $options = []): string
    {
        $options = $this->resolveOptions($options);

        $path = $this->getTemporaryPath();
        if ($path === '') {
            throw new RuntimeException('Temporary file path is empty.');
        }

        $placeholderUrl = $this->getPlaceholderUrl($options['url_pattern'], $value);
        if ($placeholderUrl === '') {
            throw new RuntimeException('Placeholder URL must be a non-empty string.');
        }

        $fp = fopen($path, 'wb');
        if ($fp === false) {
            throw new RuntimeException("Unable to open temp file for writing: {$path}");
        }

        $handler = \curl_init();
        if ($handler === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        $timeout = $options['timeout'];

        curl_setopt_array($handler, [
            CURLOPT_URL         => $placeholderUrl, // non-empty-string
            CURLOPT_FILE        => $fp,             // resource
            CURLOPT_TIMEOUT     => $timeout,        // int
            CURLOPT_FAILONERROR => true,            // bool
        ]);

        try {
            if (curl_exec($handler) === false) {
                throw new RuntimeException(
                    "Unable to download placeholder for {$value->id} ({$placeholderUrl}): " . curl_error($handler)
                );
            }
        } finally {
            curl_close($handler);
            fclose($fp);
        }

        return $path;
    }

    private function getPlaceholderUrl(string $urlPattern, ImageValue $value): string
    {
        return strtr($urlPattern, [
            '%id%' => $value->id,
            '%width%' => $value->width,
            '%height%' => $value->height,
        ]);
    }

    private function getTemporaryPath(): string
    {
        return stream_get_meta_data(tmpfile())['uri'];
    }

    private function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'url_pattern' => '',
            'timeout' => 5,
        ]);
        $resolver->setAllowedTypes('url_pattern', 'string');
        $resolver->setAllowedTypes('timeout', 'int');

        return $resolver->resolve($options);
    }
}

class_alias(RemoteProvider::class, 'eZ\Bundle\EzPublishCoreBundle\Imagine\PlaceholderProvider\RemoteProvider');
