<?php

declare(strict_types = 1);

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
    private function createMockPageBuilderDelegate(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createMock(PageBuilder::class);
    }

    public function testImplementsPageBuilder()
    {
        $delegate = $this->createMockPageBuilderDelegate();
        
        $this->assertInstanceOf(PageBuilder::class, new DemoSitePageBuilderDecorator($delegate));
    }

    public function testDelegatesAddingASnippetToThePage()
    {
        $dummyCode = 'foo snippet code';
        $dummyContent = 'bar sippet content';
        
        $delegate = $this->createMockPageBuilderDelegate();
        $delegate->expects($this->once())->method('addSnippetToPage')
            ->with($dummyCode, $dummyContent);
        
        (new DemoSitePageBuilderDecorator($delegate))->addSnippetToPage($dummyCode, $dummyContent);
    }

    public function testDelegatesAddingSnippetsToThePage()
    {
        $dummyCodeToKeyMap = ['foo' => 'bar'];
        $dummyKeyToContentMap = ['bar' => 'baz'];

        $delegate = $this->createMockPageBuilderDelegate();
        $delegate->expects($this->once())->method('addSnippetsToPage')
            ->with($dummyCodeToKeyMap, $dummyKeyToContentMap);

        (new DemoSitePageBuilderDecorator($delegate))->addSnippetsToPage($dummyCodeToKeyMap, $dummyKeyToContentMap);
    }

    public function testDelegatesAddingASnippetToAContainer()
    {
        $dummyContainerCode = 'foo';
        $dummySnippetCode = 'bar';

        $delegate = $this->createMockPageBuilderDelegate();
        $delegate->expects($this->once())->method('addSnippetToContainer')
            ->with($dummyContainerCode, $dummySnippetCode);

        (new DemoSitePageBuilderDecorator($delegate))->addSnippetToContainer($dummyContainerCode, $dummySnippetCode);
    }

    public function testDelegatesSnippetTransformationRegistration()
    {
        $dummyCode = 'foo';
        $dummyTransformation = function () {};

        $delegate = $this->createMockPageBuilderDelegate();
        $delegate->expects($this->once())->method('registerSnippetTransformation')
            ->with($dummyCode, $dummyTransformation);

        (new DemoSitePageBuilderDecorator($delegate))->registerSnippetTransformation($dummyCode, $dummyTransformation);
    }

    public function testDecoratesBuildPageResultInHttpResponseDecorator()
    {
        $dummyMetaInfo = $this->createMock(PageMetaInfoSnippetContent::class);
        $dummyContext = $this->createMock(Context::class);
        $testKeyGeneratorParams = [];
        
        $delegate = $this->createMockPageBuilderDelegate();
        $delegate->expects($this->once())->method('buildPage')
            ->with($dummyMetaInfo, $dummyContext, $testKeyGeneratorParams);

        $decorator = new DemoSitePageBuilderDecorator($delegate);
        $result = $decorator->buildPage($dummyMetaInfo, $dummyContext, $testKeyGeneratorParams);
        
        $this->assertInstanceOf(PoweredByLizardsAndPumpkinsHttpResponseDecorator::class, $result);
    }
}
