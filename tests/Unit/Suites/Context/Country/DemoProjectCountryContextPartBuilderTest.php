<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Context\Country;

use LizardsAndPumpkins\Context\ContextBuilder;
use LizardsAndPumpkins\Context\ContextPartBuilder;
use LizardsAndPumpkins\Context\Website\RequestToWebsiteMap;
use LizardsAndPumpkins\Context\Website\Website;
use LizardsAndPumpkins\Context\Website\WebsiteToCountryMap;
use LizardsAndPumpkins\Http\HttpRequest;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Context\Country\DemoProjectCountryContextPartBuilder
 */
class DemoProjectCountryContextPartBuilderTest extends TestCase
{
    /**
     * @var DemoProjectCountryContextPartBuilder
     */
    private $contextPartBuilder;

    /**
     * @var HttpRequest
     */
    private $stubRequest;

    /**
     * @var RequestToWebsiteMap
     */
    private $stubRequestToWebsiteMap;

    /**
     * @var WebsiteToCountryMap
     */
    private $stubWebsiteToCountryMap;

    private function setCountryCookieOnRequest(string $countryCode): void
    {
        $json = json_encode(['country' => $countryCode]);
        $this->stubRequest->method('getCookieValue')->with(DemoProjectCountryContextPartBuilder::COOKIE_NAME)
            ->willReturn($json);
        $this->stubRequest->method('hasCookie')->with(DemoProjectCountryContextPartBuilder::COOKIE_NAME)
            ->willReturn(true);
    }

    private function mapCountryWithGivenCodeToWebsiteAndWebsiteToRequest(string $testCountryCode): void
    {
        $dummyCountry = $this->createMock(Country::class);
        $dummyCountry->method('__toString')->willReturn($testCountryCode);

        $dummyWebsite = $this->createMock(Website::class);

        $this->stubRequestToWebsiteMap->method('getWebsiteFromRequest')->with($this->stubRequest)
            ->willReturn($dummyWebsite);

        $this->stubWebsiteToCountryMap->method('getCountry')->with($dummyWebsite)->willReturn($dummyCountry);
    }

    final protected function setUp(): void
    {
        $this->stubRequest = $this->createMock(HttpRequest::class);
        $this->stubRequestToWebsiteMap = $this->createMock(RequestToWebsiteMap::class);
        $this->stubWebsiteToCountryMap = $this->createMock(WebsiteToCountryMap::class);

        $this->contextPartBuilder = new DemoProjectCountryContextPartBuilder(
            $this->stubRequestToWebsiteMap,
            $this->stubWebsiteToCountryMap
        );
    }

    public function testContextPartBuilderInterfaceIsImplemented(): void
    {
        $this->assertInstanceOf(ContextPartBuilder::class, $this->contextPartBuilder);
    }

    public function testContextCountryCodeIsReturned(): void
    {
        $this->assertSame(Country::CONTEXT_CODE, $this->contextPartBuilder->getCode());
    }

    public function testNullIsReturnedIfNeitherCountryNorRequestIsPresentInInputDataSet(): void
    {
        $inputDataSet = [];
        $this->assertNull($this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @dataProvider countryCodeProvider
     * @param string $testCountryCode
     */
    public function testCountryCodeProvidedInInputDataSetIsReturned(string $testCountryCode): void
    {
        $inputDataSet = [Country::CONTEXT_CODE => $testCountryCode];
        $this->assertSame($testCountryCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @dataProvider countryCodeProvider
     * @param string $testCountryCode
     */
    public function testCountryCodeFromCookieIsReturned(string $testCountryCode): void
    {
        $this->setCountryCookieOnRequest($testCountryCode);
        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];

        $this->assertSame($testCountryCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @dataProvider countryCodeProvider
     * @param string $testCountryCode
     */
    public function testDeterminationOfCountryIsDelegatedToWebsiteToCountryMap(string $testCountryCode): void
    {
        $this->mapCountryWithGivenCodeToWebsiteAndWebsiteToRequest($testCountryCode);
        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];

        $this->assertSame($testCountryCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    public function testExplicitCountryValueIsPreferredOverCookieOrMappedOne(): void
    {
        $testExplicitCountryCode = 'foo';
        $testCountryCodeFromCookie = 'bar';
        $testCountryCodeFromWebsite = 'baz';

        $this->setCountryCookieOnRequest($testCountryCodeFromCookie);
        $this->mapCountryWithGivenCodeToWebsiteAndWebsiteToRequest($testCountryCodeFromWebsite);

        $inputDataSet = [
            Country::CONTEXT_CODE => $testExplicitCountryCode,
            ContextBuilder::REQUEST => $this->stubRequest
        ];

        $this->assertSame($testExplicitCountryCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    public function testCookieCountryValueIsPreferredOverMappedOne(): void
    {
        $testCountryCodeFromCookie = 'foo';
        $testCountryCodeFromWebsite = 'bar';

        $this->setCountryCookieOnRequest($testCountryCodeFromCookie);
        $this->mapCountryWithGivenCodeToWebsiteAndWebsiteToRequest($testCountryCodeFromWebsite);

        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];

        $this->assertSame($testCountryCodeFromCookie, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @return array[]
     */
    public function countryCodeProvider(): array
    {
        return [['foo'], ['bar']];
    }
}
