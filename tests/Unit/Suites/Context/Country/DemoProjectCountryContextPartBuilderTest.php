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
     * @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubRequest;

    /**
     * @var RequestToWebsiteMap|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubRequestToWebsiteMap;

    /**
     * @var WebsiteToCountryMap|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubWebsiteToCountryMap;

    private function setCountryCookieOnRequest(string $countryCode)
    {
        $json = json_encode(['country' => $countryCode]);
        $this->stubRequest->method('getCookieValue')->with(DemoProjectCountryContextPartBuilder::COOKIE_NAME)
            ->willReturn($json);
        $this->stubRequest->method('hasCookie')->with(DemoProjectCountryContextPartBuilder::COOKIE_NAME)
            ->willReturn(true);
    }

    private function mapCountryWithGivenCodeToWebsiteAndWebsiteToRequest(string $testCountryCode)
    {
        $dummyCountry = $this->createMock(Country::class);
        $dummyCountry->method('__toString')->willReturn($testCountryCode);

        $dummyWebsite = $this->createMock(Website::class);

        $this->stubRequestToWebsiteMap->method('getWebsiteFromRequest')->with($this->stubRequest)
            ->willReturn($dummyWebsite);

        $this->stubWebsiteToCountryMap->method('getCountry')->with($dummyWebsite)->willReturn($dummyCountry);
    }

    protected function setUp()
    {
        $this->stubRequest = $this->createMock(HttpRequest::class);
        $this->stubRequestToWebsiteMap = $this->createMock(RequestToWebsiteMap::class);
        $this->stubWebsiteToCountryMap = $this->createMock(WebsiteToCountryMap::class);

        $this->contextPartBuilder = new DemoProjectCountryContextPartBuilder(
            $this->stubRequestToWebsiteMap,
            $this->stubWebsiteToCountryMap
        );
    }
    
    public function testContextPartBuilderInterfaceIsImplemented()
    {
        $this->assertInstanceOf(ContextPartBuilder::class, $this->contextPartBuilder);
    }

    public function testContextCountryCodeIsReturned()
    {
        $this->assertSame(Country::CONTEXT_CODE, $this->contextPartBuilder->getCode());
    }

    public function testNullIsReturnedIfNeitherCountryNorRequestIsPresentInInputDataSet()
    {
        $inputDataSet = [];
        $this->assertNull($this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @dataProvider countryCodeProvider
     */
    public function testCountryCodeProvidedInInputDataSetIsReturned(string $testCountryCode)
    {
        $inputDataSet = [Country::CONTEXT_CODE => $testCountryCode];
        $this->assertSame($testCountryCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @dataProvider countryCodeProvider
     */
    public function testCountryCodeFromCookieIsReturned(string $testCountryCode)
    {
        $this->setCountryCookieOnRequest($testCountryCode);
        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];

        $this->assertSame($testCountryCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @dataProvider countryCodeProvider
     */
    public function testDeterminationOfCountryIsDelegatedToWebsiteToCountryMap(string $testCountryCode)
    {
        $this->mapCountryWithGivenCodeToWebsiteAndWebsiteToRequest($testCountryCode);
        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];

        $this->assertSame($testCountryCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    public function testExplicitCountryValueIsPreferredOverCookieOrMappedOne()
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

    public function testCookieCountryValueIsPreferredOverMappedOne()
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
    public function countryCodeProvider() : array
    {
        return [['foo'], ['bar']];
    }
}
