<?php

namespace LizardsAndPumpkins\Context\Country;

use LizardsAndPumpkins\Context\ContextBuilder;
use LizardsAndPumpkins\Context\ContextPartBuilder;
use LizardsAndPumpkins\Context\Website\RequestToWebsiteMap;
use LizardsAndPumpkins\Context\Website\Website;
use LizardsAndPumpkins\Context\Website\WebsiteToCountryMap;
use LizardsAndPumpkins\Http\HttpRequest;

/**
 * @covers \LizardsAndPumpkins\Context\Country\DemoProjectCountryContextPartBuilder
 */
class DemoProjectCountryContextPartBuilderTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @param string $countryCode
     */
    private function setCountryCookieOnRequest($countryCode)
    {
        $json = json_encode(['country' => $countryCode]);
        $this->stubRequest->method('getCookieValue')->with(DemoProjectCountryContextPartBuilder::COOKIE_NAME)
            ->willReturn($json);
        $this->stubRequest->method('hasCookie')->with(DemoProjectCountryContextPartBuilder::COOKIE_NAME)
            ->willReturn(true);
    }

    /**
     * @param string $testCountryCode
     */
    private function mapCountryWithGivenCodeToWebsiteAndWebsiteToRequest($testCountryCode)
    {
        $dummyCountry = $this->getMock(Country::class, [], [], '', false);
        $dummyCountry->method('__toString')->willReturn($testCountryCode);

        $dummyWebsite = $this->getMock(Website::class, [], [], '', false);

        $this->stubRequestToWebsiteMap->method('getWebsiteFromRequest')->with($this->stubRequest)
            ->willReturn($dummyWebsite);

        $this->stubWebsiteToCountryMap->method('getCountry')->with($dummyWebsite)->willReturn($dummyCountry);
    }

    protected function setUp()
    {
        $this->stubRequest = $this->getMock(HttpRequest::class, [], [], '', false);
        $this->stubRequestToWebsiteMap = $this->getMock(RequestToWebsiteMap::class, [], [], '', false);
        $this->stubWebsiteToCountryMap = $this->getMock(WebsiteToCountryMap::class);

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
     * @param string $testCountryCode
     * @dataProvider countryCodeProvider
     */
    public function testCountryCodeProvidedInInputDataSetIsReturned($testCountryCode)
    {
        $inputDataSet = [Country::CONTEXT_CODE => $testCountryCode];
        $this->assertSame($testCountryCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @param string $testCountryCode
     * @dataProvider countryCodeProvider
     */
    public function testCountryCodeFromCookieIsReturned($testCountryCode)
    {
        $this->setCountryCookieOnRequest($testCountryCode);
        $inputDataSet = [ContextBuilder::REQUEST => $this->stubRequest];

        $this->assertSame($testCountryCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @param string $testCountryCode
     * @dataProvider countryCodeProvider
     */
    public function testDeterminationOfCountryIsDelegatedToWebsiteToCountryMap($testCountryCode)
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
    public function countryCodeProvider()
    {
        return [['foo'], ['bar']];
    }
}
