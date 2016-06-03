<?php

namespace LizardsAndPumpkins\Import\Tax;

/**
 * @covers \LizardsAndPumpkins\Import\Tax\DemoProjectTaxableCountries
 */
class DemoProjectTaxableCountriesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DemoProjectTaxableCountries
     */
    private $countries;

    protected function setUp()
    {
        $this->countries = new DemoProjectTaxableCountries();
    }

    /**
     * @dataProvider availableCountriesDataProvider
     * @param string $availableCountry
     */
    public function testItReturnsTheAvailableCountries($availableCountry)
    {
        $this->assertContains($availableCountry, $this->countries->getCountries());
    }

    /**
     * @return array[]
     */
    public function availableCountriesDataProvider()
    {
        return [
            ['DE'],
            ['DK'],
            ['AT'],
            ['FR'],
            ['ES'],
            ['FI'],
            ['NL'],
            ['SE'],
            ['LU'],
            ['IT'],
            ['BE'],
        ];
    }

    public function testItCanBeIteratedOver()
    {
        $this->assertInstanceOf(\IteratorAggregate::class, $this->countries);
        $this->assertInstanceOf(\ArrayIterator::class, $this->countries->getIterator());
    }

    public function testItImplementsTheTaxableCountriesInterface()
    {
        $this->assertInstanceOf(TaxableCountries::class, $this->countries);
    }
}
