<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Http\ContentDelivery\PageBuilder;

use LizardsAndPumpkins\Context\Context;
use LizardsAndPumpkins\Http\PoweredByLizardsAndPumpkinsHttpResponseDecorator;
use LizardsAndPumpkins\Import\PageMetaInfoSnippetContent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Http\ContentDelivery\PageBuilder\DemoSitePageBuilderDecorator
 * @uses   \LizardsAndPumpkins\Http\PoweredByLizardsAndPumpkinsHttpResponseDecorator
 */
class DemoSitePageBuilderDecoratorTest extends TestCase
{
    public function testImplementsPageBuilder(): void
    {
        $delegate = $this->createMock(PageBuilder::class);

        $this->assertInstanceOf(PageBuilder::class, new DemoSitePageBuilderDecorator($delegate));
    }

    public function testDelegatesAddingASnippetToThePage(): void
    {
        $dummyCode = 'foo snippet code';
        $dummyContent = 'bar sippet content';

        $delegate = $this->createMock(PageBuilder::class);
        $delegate->expects($this->once())->method('addSnippetToPage')
            ->with($dummyCode, $dummyContent);

        (new DemoSitePageBuilderDecorator($delegate))->addSnippetToPage($dummyCode, $dummyContent);
    }

    public function testDelegatesAddingSnippetsToThePage(): void
    {
        $dummyCodeToKeyMap = ['foo' => 'bar'];
        $dummyKeyToContentMap = ['bar' => 'baz'];

        $delegate = $this->createMock(PageBuilder::class);
        $delegate->expects($this->once())->method('addSnippetsToPage')
            ->with($dummyCodeToKeyMap, $dummyKeyToContentMap);

        (new DemoSitePageBuilderDecorator($delegate))->addSnippetsToPage($dummyCodeToKeyMap, $dummyKeyToContentMap);
    }

    public function testDelegatesAddingASnippetToAContainer(): void
    {
        $dummyContainerCode = 'foo';
        $dummySnippetCode = 'bar';

        $delegate = $this->createMock(PageBuilder::class);
        $delegate->expects($this->once())->method('addSnippetToContainer')
            ->with($dummyContainerCode, $dummySnippetCode);

        (new DemoSitePageBuilderDecorator($delegate))->addSnippetToContainer($dummyContainerCode, $dummySnippetCode);
    }

    public function testDelegatesSnippetTransformationRegistration(): void
    {
        $dummyCode = 'foo';
        $dummyTransformation = function () {
        };

        $delegate = $this->createMock(PageBuilder::class);
        $delegate->expects($this->once())->method('registerSnippetTransformation')
            ->with($dummyCode, $dummyTransformation);

        (new DemoSitePageBuilderDecorator($delegate))->registerSnippetTransformation($dummyCode, $dummyTransformation);
    }

    public function testDecoratesBuildPageResultInHttpResponseDecorator(): void
    {
        $dummyMetaInfo = $this->createMock(PageMetaInfoSnippetContent::class);
        $dummyContext = $this->createMock(Context::class);
        $testKeyGeneratorParams = [];

        $delegate = $this->createMock(PageBuilder::class);
        $delegate->expects($this->once())->method('buildPage')
            ->with($dummyMetaInfo, $dummyContext, $testKeyGeneratorParams);

        $decorator = new DemoSitePageBuilderDecorator($delegate);
        $result = $decorator->buildPage($dummyMetaInfo, $dummyContext, $testKeyGeneratorParams);

        $this->assertInstanceOf(PoweredByLizardsAndPumpkinsHttpResponseDecorator::class, $result);
    }
}
