<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Debug\Collector;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class IbexaCoreCollector extends DataCollector
{
    public function __construct()
    {
        $this->reset();
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        /** @var \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface $innerCollector */
        foreach ($this->data['collectors'] as $innerCollector) {
            $innerCollector->collect($request, $response, $exception);
        }
    }

    public function getName(): string
    {
        return 'ezpublish.debug.toolbar';
    }

    /**
     * @param \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface $collector
     */
    public function addCollector(DataCollectorInterface $collector, $panelTemplate = null, $toolbarTemplate = null)
    {
        $name = $collector->getName();
        $this->data['collectors'][$name] = $collector;
        $this->data['panelTemplates'][$name] = $panelTemplate;
        $this->data['toolbarTemplates'][$name] = $toolbarTemplate;
    }

    /**
     * @param string $name Name of the collector
     *
     * @return \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getCollector($name)
    {
        if (!isset($this->data['collectors'][$name])) {
            throw new InvalidArgumentException("Invalid debug collector '$name'");
        }

        return $this->data['collectors'][$name];
    }

    /**
     * @return \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface[]
     */
    public function getAllCollectors()
    {
        return $this->data['collectors'];
    }

    /**
     * Returns toolbar template for given collector name.
     *
     * @param string $collectorName Name of corresponding collector.
     *
     * @return string
     */
    public function getToolbarTemplate($collectorName)
    {
        if (!isset($this->data['toolbarTemplates'][$collectorName])) {
            return null;
        }

        return $this->data['toolbarTemplates'][$collectorName];
    }

    /**
     * Returns panel template to use for given collector name.
     *
     * @param string $collectorName Name of corresponding collector.
     *
     * @return string
     */
    public function getPanelTemplate($collectorName)
    {
        if (!isset($this->data['panelTemplates'][$collectorName])) {
            return null;
        }

        return $this->data['panelTemplates'][$collectorName];
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [
            'collectors' => [],
            'panelTemplates' => [],
            'toolbarTemplates' => [],
        ];
    }
}
