<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Tax;

use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Import\Tax\DemoProjectTaxableCountries
 */
class DemoProjectTaxableCountriesTest extends TestCase
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
     */
    public function testItReturnsTheAvailableCountries(string $availableCountry)
    {
        $this->assertContains($availableCountry, $this->countries->getCountries());
    }

    /**
     * @return array[]
     */
    public function availableCountriesDataProvider() : array
    {
        return [
            ['DE'],
            ['DK'],
            ['EN'],
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
