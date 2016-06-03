<?php

namespace LizardsAndPumpkins\Context\Locale;

use LizardsAndPumpkins\Context\ContextBuilder;
use LizardsAndPumpkins\Context\ContextPartBuilder;
use LizardsAndPumpkins\Context\Locale\Exception\UnableToDetermineContextLocaleException;
use LizardsAndPumpkins\Context\Website\RequestToWebsiteMap;
use LizardsAndPumpkins\Context\Website\Website;
use LizardsAndPumpkins\Http\HttpRequest;

/**
 * @covers \LizardsAndPumpkins\Context\Locale\DemoProjectLocaleContextPartBuilder
 */
class DemoProjectLocaleContextPartBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DemoProjectLocaleContextPartBuilder
     */
    private $contextLocale;

    /**
     * @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubRequest;

    /**
     * @var RequestToWebsiteMap|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubRequestToWebsiteMap;

    /**
     * @param string $websiteCode
     */
    private function setWebsiteWithGivenCodeOnRequest($websiteCode)
    {
        $dummyWebsite = $this->getMock(Website::class, [], [], '', false);;
        $dummyWebsite->method('__toString')->willReturn($websiteCode);
        $this->stubRequestToWebsiteMap->method('getWebsiteFromRequest')->with($this->stubRequest)
            ->willReturn($dummyWebsite);
    }

    protected function setUp()
    {
        $this->stubRequest = $this->getMock(HttpRequest::class, [], [], '', false);
        $this->stubRequestToWebsiteMap = $this->getMock(RequestToWebsiteMap::class, [], [], '', false);

        $this->contextLocale = new DemoProjectLocaleContextPartBuilder($this->stubRequestToWebsiteMap);
    }

    public function testItIsAContextPartBuilder()
    {
        $this->assertInstanceOf(ContextPartBuilder::class, $this->contextLocale);
    }

    public function testItReturnsTheCode()
    {
        $this->assertSame(Locale::CONTEXT_CODE, $this->contextLocale->getCode());
    }

    public function testExceptionIsThrownIfNeitherLocaleNorRequestIsPresentInInputDataSet()
    {
        $this->expectException(UnableToDetermineContextLocaleException::class);
        $this->expectExceptionMessage(
            'Unable to determine context locale as neither the locale nor the request are set in the input array.'
        );
        
        $inputDataSet = [];
        $this->contextLocale->getValue($inputDataSet);
    }

    public function testExceptionIsThrownIfLocaleCanNotBeDeterminedFromRequest()
    {
        $this->expectException(UnableToDetermineContextLocaleException::class);
        $this->expectExceptionMessage('Unable to determine locale from request.');

        $unmappedWebsiteCode = 'foo';
        $this->setWebsiteWithGivenCodeOnRequest($unmappedWebsiteCode);

        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];
        $this->contextLocale->getValue($inputDataSet);
    }

    public function testItReturnsTheLocaleFromTheInputArrayIfItIsPresent()
    {
        $inputDataSet = [Locale::CONTEXT_CODE => 'xx_XX'];
        $this->assertSame('xx_XX', $this->contextLocale->getValue($inputDataSet));
    }

    public function testItReturnsTheLocaleFromTheRequestIfNotExplicitlySpecifiedInInputArray()
    {
        $this->setWebsiteWithGivenCodeOnRequest('en');

        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];

        $this->assertSame('en_US', $this->contextLocale->getValue($inputDataSet));
    }
}
