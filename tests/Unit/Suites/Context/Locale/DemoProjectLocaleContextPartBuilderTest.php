<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Context\Locale;

use LizardsAndPumpkins\Context\ContextBuilder;
use LizardsAndPumpkins\Context\ContextPartBuilder;
use LizardsAndPumpkins\Context\Locale\Exception\UnableToDetermineContextLocaleException;
use LizardsAndPumpkins\Context\Website\RequestToWebsiteMap;
use LizardsAndPumpkins\Context\Website\Website;
use LizardsAndPumpkins\Http\HttpRequest;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Context\Locale\DemoProjectLocaleContextPartBuilder
 */
class DemoProjectLocaleContextPartBuilderTest extends TestCase
{
    /**
     * @var DemoProjectLocaleContextPartBuilder
     */
    private $contextLocale;

    /**
     * @var HttpRequest
     */
    private $stubRequest;

    /**
     * @var RequestToWebsiteMap
     */
    private $stubRequestToWebsiteMap;

    private function setWebsiteWithGivenCodeOnRequest(string $websiteCode): void
    {
        $dummyWebsite = $this->createMock(Website::class);
        $dummyWebsite->method('__toString')->willReturn($websiteCode);
        $this->stubRequestToWebsiteMap->method('getWebsiteFromRequest')->with($this->stubRequest)
            ->willReturn($dummyWebsite);
    }

    final protected function setUp(): void
    {
        $this->stubRequest = $this->createMock(HttpRequest::class);
        $this->stubRequestToWebsiteMap = $this->createMock(RequestToWebsiteMap::class);

        $this->contextLocale = new DemoProjectLocaleContextPartBuilder($this->stubRequestToWebsiteMap);
    }

    public function testItIsAContextPartBuilder(): void
    {
        $this->assertInstanceOf(ContextPartBuilder::class, $this->contextLocale);
    }

    public function testItReturnsTheCode(): void
    {
        $this->assertSame(Locale::CONTEXT_CODE, $this->contextLocale->getCode());
    }

    public function testExceptionIsThrownIfNeitherLocaleNorRequestIsPresentInInputDataSet(): void
    {
        $this->expectException(UnableToDetermineContextLocaleException::class);
        $this->expectExceptionMessage(
            'Unable to determine context locale as neither the locale nor the request are set in the input array.'
        );

        $inputDataSet = [];
        $this->contextLocale->getValue($inputDataSet);
    }

    public function testExceptionIsThrownIfLocaleCanNotBeDeterminedFromRequest(): void
    {
        $this->expectException(UnableToDetermineContextLocaleException::class);
        $this->expectExceptionMessage('Unable to determine locale from request.');

        $unmappedWebsiteCode = 'foo';
        $this->setWebsiteWithGivenCodeOnRequest($unmappedWebsiteCode);

        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];
        $this->contextLocale->getValue($inputDataSet);
    }

    public function testItReturnsTheLocaleFromTheInputArrayIfItIsPresent(): void
    {
        $inputDataSet = [Locale::CONTEXT_CODE => 'xx_XX'];
        $this->assertSame('xx_XX', $this->contextLocale->getValue($inputDataSet));
    }

    public function testItReturnsTheLocaleFromTheRequestIfNotExplicitlySpecifiedInInputArray(): void
    {
        $this->setWebsiteWithGivenCodeOnRequest('en');

        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];

        $this->assertSame('en_US', $this->contextLocale->getValue($inputDataSet));
    }
}
