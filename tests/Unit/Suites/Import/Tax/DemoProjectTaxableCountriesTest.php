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

    final protected function setUp(): void
    {
        $this->countries = new DemoProjectTaxableCountries();
    }

    /**
     * @dataProvider availableCountriesDataProvider
     * @param string $availableCountry
     */
    public function testItReturnsTheAvailableCountries(string $availableCountry): void
    {
        $this->assertContains($availableCountry, $this->countries->getCountries());
    }

    /**
     * @return array[]
     */
    public function availableCountriesDataProvider(): array
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

    public function testItCanBeIteratedOver(): void
    {
        $this->assertInstanceOf(\IteratorAggregate::class, $this->countries);
        $this->assertInstanceOf(\ArrayIterator::class, $this->countries->getIterator());
    }

    public function testItImplementsTheTaxableCountriesInterface(): void
    {
        $this->assertInstanceOf(TaxableCountries::class, $this->countries);
    }
}
