<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\QueryType;

use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\MVC\Symfony\View\ContentView;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Maps a ContentView to a QueryType using the 'query' parameter from the view configuration.
 */
class QueryParameterContentViewQueryTypeMapper implements ContentViewQueryTypeMapper
{
    private QueryTypeRegistry $queryTypeRegistry;

    public function __construct(QueryTypeRegistry $queryTypeRegistry)
    {
        $this->queryTypeRegistry = $queryTypeRegistry;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function map(ContentView $contentView): Query
    {
        if (!$contentView->hasParameter('query')) {
            throw new InvalidArgumentException('query', "Required 'query' view parameter is missing");
        }

        $queryOptions = $contentView->getParameter('query');

        return $this->queryTypeRegistry
            ->getQueryType($queryOptions['query_type'])
            ->getQuery($this->extractParametersFromContentView($contentView));
    }

    /**
     * @param \Ibexa\Core\MVC\Symfony\View\ContentView $contentView
     *
     * @return array
     */
    private function extractParametersFromContentView(ContentView $contentView)
    {
        $queryParameters = [];

        $queryOptions = $contentView->getParameter('query');
        if (isset($queryOptions['parameters'])) {
            foreach ($queryOptions['parameters'] as $name => $value) {
                $queryParameters[$name] = $this->extractParameters($contentView, $value);
            }
        }

        return $queryParameters;
    }

    /**
     * @param \Ibexa\Core\MVC\Symfony\View\ContentView $contentView
     * @param array $queryParameterValue
     *
     * @return array|string
     */
    private function extractParameters(ContentView $contentView, $queryParameterValue)
    {
        if (is_array($queryParameterValue)) {
            $queryParameters = [];
            foreach ($queryParameterValue as $name => $value) {
                $queryParameters[$name] = $this->extractParameters($contentView, $value);
            }

            return $queryParameters;
        }

        return $this->evaluateExpression($contentView, $queryParameterValue);
    }

    /**
     * @param \Ibexa\Core\MVC\Symfony\View\ContentView $contentView
     * @param string $queryParameterValue
     *
     * @return mixed
     */
    private function evaluateExpression(ContentView $contentView, $queryParameterValue)
    {
        if (is_string($queryParameterValue) && substr($queryParameterValue, 0, 2) === '@=') {
            $language = new ExpressionLanguage();

            return $language->evaluate(
                substr($queryParameterValue, 2),
                [
                    'view' => $contentView,
                    'location' => $contentView->getLocation(),
                    'content' => $contentView->getContent(),
                ]
            );
        }

        return $queryParameterValue;
    }
}
