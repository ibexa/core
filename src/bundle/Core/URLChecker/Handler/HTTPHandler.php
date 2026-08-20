<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\URLChecker\Handler;

use Ibexa\Contracts\Core\Repository\Values\URL\URL;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HTTPHandler extends AbstractConfigResolverBasedURLHandler
{
    private const METHOD_HEAD = 'HEAD';
    private const METHOD_GET = 'GET';

    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0';

    private const DEFAULT_HEADERS = [
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
    ];

    /**
     * {@inheritdoc}
     */
    public function validate(array $urls): void
    {
        $options = $this->getOptions();

        if (!$options['enabled']) {
            return;
        }

        $master = curl_multi_init();
        $requests = [];

        $batchSize = min(count($urls), $options['batch_size']);
        for ($i = 0; $i < $batchSize; ++$i) {
            curl_multi_add_handle(
                $master,
                $this->createCurlHandlerForUrl($urls[$i], $options['method'], $options, $requests)
            );
        }

        do {
            $status = curl_multi_exec($master, $running);

            while ($done = curl_multi_info_read($master)) {
                $handler = $done['handle'];
                $request = $requests[(int)$handler];
                unset($requests[(int)$handler]);

                $statusCode = (int)curl_getinfo($handler, CURLINFO_HTTP_CODE);

                if ($this->shouldRetryWithGet($statusCode, $request['method'], $options)) {
                    // Some servers and WAFs reject HEAD - recheck with GET before marking the URL as invalid
                    curl_multi_add_handle(
                        $master,
                        $this->createCurlHandlerForUrl($request['url'], self::METHOD_GET, $options, $requests)
                    );
                    $running = 1; // handles added mid-loop are not reflected in $running yet
                } else {
                    $this->setUrlStatus($request['url'], $this->isSuccessful($statusCode));

                    if ($i < count($urls)) {
                        curl_multi_add_handle(
                            $master,
                            $this->createCurlHandlerForUrl($urls[$i], $options['method'], $options, $requests)
                        );
                        ++$i;
                        $running = 1; // as above
                    }
                }

                curl_multi_remove_handle($master, $handler);
                curl_close($handler);
            }

            if ($running && curl_multi_select($master, 1.0) === -1) {
                // select failure - back off briefly to avoid busy-looping
                usleep(250);
            }
        } while ($running && $status === CURLM_OK);

        curl_multi_close($master);
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'enabled' => true,
            'timeout' => 10,
            'connection_timeout' => 5,
            'batch_size' => 10,
            'ignore_certificate' => false,
            'method' => self::METHOD_HEAD,
            'fallback_to_get' => true,
            'user_agent' => self::DEFAULT_USER_AGENT,
            'headers' => self::DEFAULT_HEADERS,
        ]);

        $resolver->setAllowedTypes('enabled', 'bool');
        $resolver->setAllowedTypes('timeout', 'int');
        $resolver->setAllowedTypes('connection_timeout', 'int');
        $resolver->setAllowedTypes('batch_size', 'int');
        $resolver->setAllowedTypes('ignore_certificate', 'bool');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', [self::METHOD_HEAD, self::METHOD_GET]);
        $resolver->setAllowedTypes('fallback_to_get', 'bool');
        $resolver->setAllowedTypes('user_agent', 'string');
        $resolver->setAllowedTypes('headers', 'array');

        return $resolver;
    }

    /**
     * Initialize and return a cURL session for given URL.
     *
     * @param array<string, mixed> $options
     * @param array<int, array{url: \Ibexa\Contracts\Core\Repository\Values\URL\URL, method: string}> $requests
     *
     * @return resource
     */
    private function createCurlHandlerForUrl(URL $url, string $method, array $options, array &$requests)
    {
        $handler = curl_init();
        if ($handler === false) {
            throw new RuntimeException('Unable to initialize cURL handler.');
        }

        $urlString = $url->url;
        if ($urlString === '') {
            throw new InvalidArgumentException('URL must be a non-empty string.');
        }

        /** @var non-empty-string $urlString */
        curl_setopt_array($handler, [
            CURLOPT_URL => $urlString,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => $options['connection_timeout'],
            CURLOPT_TIMEOUT => $options['timeout'],
            CURLOPT_FAILONERROR => true,
            CURLOPT_USERAGENT => $options['user_agent'],
            CURLOPT_HTTPHEADER => $this->buildRequestHeaders($options['headers']),
            CURLOPT_ENCODING => '',
        ]);

        if ($method === self::METHOD_HEAD) {
            curl_setopt($handler, CURLOPT_NOBODY, true);
        } else {
            // Abort on the first body chunk - the final (post-redirect) status code is already known
            // and the body must not be streamed to the output (CURLOPT_RETURNTRANSFER is disabled).
            curl_setopt($handler, CURLOPT_WRITEFUNCTION, static function ($handler, string $data): int {
                return 0;
            });
        }

        if ($options['ignore_certificate']) {
            curl_setopt_array($handler, [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
        }

        $requests[(int) $handler] = [
            'url' => $url,
            'method' => $method,
        ];

        return $handler;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function shouldRetryWithGet(int $statusCode, string $requestMethod, array $options): bool
    {
        return $requestMethod === self::METHOD_HEAD
            && $options['fallback_to_get']
            && !$this->isSuccessful($statusCode);
    }

    /**
     * @param array<string|int, string> $headers
     *
     * @return string[]
     */
    private function buildRequestHeaders(array $headers): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = is_int($name) ? $value : sprintf('%s: %s', $name, $value);
        }

        return $lines;
    }

    private function isSuccessful(int $statusCode): bool
    {
        return $statusCode >= 200 && $statusCode < 300;
    }
}

class_alias(HTTPHandler::class, 'eZ\Bundle\EzPublishCoreBundle\URLChecker\Handler\HTTPHandler');
