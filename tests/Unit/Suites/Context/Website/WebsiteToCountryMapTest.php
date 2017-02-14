<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Context\Website;

use LizardsAndPumpkins\Context\Country\Country;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Context\Website\WebsiteToCountryMap
 * @uses   \LizardsAndPumpkins\Context\Website\Website
 * @uses   \LizardsAndPumpkins\Context\Country\Country
 */
class WebsiteToCountryMapTest extends TestCase
{
    /**
     * @var WebsiteToCountryMap
     */
    private $websiteToCountryMap;

    private function assertCountryEqual(Country $expected, Country $actual)
    {
        $message = sprintf('Expected country "%s", got "%s"', $expected, $actual);
        $this->assertTrue($actual->isEqualTo($expected), $message);
    }
    
    protected function setUp()
    {
        $this->websiteToCountryMap = new WebsiteToCountryMap();
    }

    public function testItReturnsTheDefaultCountry()
    {
        $defaultCountry = $this->websiteToCountryMap->getDefaultCountry();
        $this->assertCountryEqual(Country::from2CharIso3166('FR'), $defaultCountry);
    }

    public function testItReturnsGermanyAsTheDefault()
    {
        
        $this->assertCountryEqual(
            $this->websiteToCountryMap->getDefaultCountry(),
            $this->websiteToCountryMap->getCountry(Website::fromString('unknown website'))
        );
    }

    /**
     * @dataProvider websiteToCountryDataProvider
     */
    public function testItReturnsTheCountryForAGivenWebsite(Website $website, Country $expectedCountry)
    {
        $this->assertCountryEqual($expectedCountry, $this->websiteToCountryMap->getCountry($website));
    }

    /**
     * @return array[]
     */
    public function websiteToCountryDataProvider() : array
    {
        return [
            [Website::fromString('de'), Country::from2CharIso3166('DE')],
            [Website::fromString('en'), Country::from2CharIso3166('EN')],
            [Website::fromString('fr'), Country::from2CharIso3166('FR')],
        ];
    }
}
