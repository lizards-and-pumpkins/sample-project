<?php

declare(strict_types = 1);

namespace LizardsAndPumpkins\Http\ContentDelivery\PageBuilder;

use LizardsAndPumpkins\Context\Context;
use LizardsAndPumpkins\Http\PoweredByLizardsAndPumpkinsHttpResponseDecorator;
use LizardsAndPumpkins\Http\HttpResponse;
use LizardsAndPumpkins\Import\PageMetaInfoSnippetContent;

class DemoSitePageBuilderDecorator implements PageBuilder
{
    /**
     * @var PageBuilder
     */
    private $delegate;

    public function __construct(PageBuilder $delegate)
    {
        $this->delegate = $delegate;
    }
    
    /**
     * @param PageMetaInfoSnippetContent $metaInfo
     * @param Context $context
     * @param mixed[] $keyGeneratorParams
     * @return HttpResponse
     */
    public function buildPage(
        PageMetaInfoSnippetContent $metaInfo,
        Context $context,
        array $keyGeneratorParams
    ): HttpResponse {
        $originalResponse = $this->delegate->buildPage($metaInfo, $context, $keyGeneratorParams);
        return PoweredByLizardsAndPumpkinsHttpResponseDecorator::decorateHttpResponse($originalResponse);
    }

    /**
     * @param string[] $snippetCodeToKeyMap
     * @param string[] $snippetKeyToContentMap
     */
    public function addSnippetsToPage(array $snippetCodeToKeyMap, array $snippetKeyToContentMap)
    {
        $this->delegate->addSnippetsToPage($snippetCodeToKeyMap, $snippetKeyToContentMap);
    }

    public function registerSnippetTransformation(string $snippetCode, callable $transformation)
    {
        $this->delegate->registerSnippetTransformation($snippetCode, $transformation);
    }

    public function addSnippetToContainer(string $containerCode, string $snippetCode)
    {
        $this->delegate->addSnippetToContainer($containerCode, $snippetCode);
    }

    public function addSnippetToPage(string $snippetCode, string $snippetContent)
    {
        $this->delegate->addSnippetToPage($snippetCode, $snippetContent);
    }
}
