<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Features\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\Assert as Assertion;

class ContentPreviewContext extends RawMinkContext
{
    /** @var \Ibexa\Bundle\Core\Features\Context\ContentContext */
    private $contentContext;

    /** @BeforeScenario */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->contentContext = $environment->getContext(ContentContext::class);
    }

    /**
     * @Given /^I create a draft for a content type that uses a custom location controller$/
     */
    public function iCreateDraftOfContentTypeWithCustomLocationController()
    {
        $this->contentContext->createDraft(
            'blog_post',
            [
                'title' => 'Preview draft ' . date('c'),
                'body' => '<?xml version="1.0" encoding="UTF-8"?><section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0"><para>This is a paragraph.</para></section>',
            ]
        );
    }

    /**
     * @When /^I preview this draft$/
     */
    public function iPreviewThisDraft()
    {
        $this->getSession()->getDriver()->visit($this->mapToVersionViewUri($this->contentContext->getCurrentDraft()->versionInfo));
    }

    /**
     * @return string
     */
    private function mapToVersionViewUri(VersionInfo $version): string
    {
        return sprintf(
            '/content/versionview/%s/%s/%s',
            $version->contentInfo->id,
            $version->versionNo,
            $version->initialLanguageCode
        );
    }

    /**
     * @Then /^the output is valid$/
     */
    public function theOutputIsValid()
    {
        $this->checkForExceptions();
    }

    protected function checkForExceptions()
    {
        $exceptionElements = $this->getSession()->getPage()->findAll('xpath', "//div[@class='text-exception']/h1");
        $exceptionStackTraceItems = $this->getSession()->getPage()->findAll('xpath', "//ol[@id='traces-0']/li");
        if (count($exceptionElements) > 0) {
            $exceptionElement = $exceptionElements[0];
            $exceptionLines = [$exceptionElement->getText(), ''];

            foreach ($exceptionStackTraceItems as $stackTraceItem) {
                $html = $stackTraceItem->getHtml();
                $html = substr($html, 0, strpos($html, '<a href', 1));
                $html = htmlspecialchars_decode(strip_tags($html));
                $html = preg_replace('/\s+/', ' ', $html);
                $html = str_replace('  (', '(', $html);
                $html = str_replace(' ->', '->', $html);
                $exceptionLines[] = trim($html);
            }
            $message = 'An exception occured during rendering:' . implode("\n", $exceptionLines);
            Assertion::assertTrue(false, $message);
        }
    }

    /**
     * @Then /^I see a preview of this draft$/
     */
    public function iSeeAPreviewOfTheCurrentDraft()
    {
        $this->assertSession()->elementContains(
            'xpath',
            "//span[@class='ibexa_string-field']",
            $this->contentContext->getCurrentDraft()->getFieldValue('name')->text
        );
    }

    /**
     * This could belong in the content context.
     *
     * @Given /^I modify a field from the draft$/
     */
    public function iModifyAFieldFromTheDraft()
    {
        $this->contentContext->updateDraft(
            ['name' => 'MODIFIED - ' . $this->contentContext->getCurrentDraft()->getFieldValue('name')->text]
        );
    }
}
