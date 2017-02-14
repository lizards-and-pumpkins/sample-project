<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Tax;

use LizardsAndPumpkins\Import\Tax\Exception\UnableToLocateTaxServiceException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Import\Tax\DemoProjectTaxServiceLocator
 * @uses   \LizardsAndPumpkins\Import\Tax\DemoProjectTaxRate
 */
class DemoProjectTaxServiceLocatorTest extends TestCase
{
    /**
     * @var DemoProjectTaxServiceLocator
     */
    private $taxServiceLocator;

    /**
     * @param string $website
     * @param string $taxClass
     * @param string $country
     * @return string[]
     */
    private function createTaxServiceLocatorOptions(string $website, string $taxClass, string $country) : array
    {
        return [
            TaxServiceLocator::OPTION_PRODUCT_TAX_CLASS => $taxClass,
            TaxServiceLocator::OPTION_COUNTRY => $country,
            TaxServiceLocator::OPTION_WEBSITE => $website
        ];
    }

    private function assertTaxServiceLocatorReturns(
        string $website,
        string $productTaxClass,
        string $country,
        int $expectedRate
    ) {
        $taxService = $this->getTaxServiceFor($website, $productTaxClass, $country);
        $this->assertInstanceOf(DemoProjectTaxRate::class, $taxService);
        $message = sprintf(
            'Expected the tax rate for website "%s", tax class "%s" and country "%s" to be "%s", got "%s"',
            $website,
            $productTaxClass,
            $country,
            $expectedRate,
            $taxService->getRate()
        );
        $this->assertSame($expectedRate, $taxService->getRate(), $message);
    }

    private function getTaxServiceFor(string $website, string $productTaxClass, string $country) : DemoProjectTaxRate
    {
        $options = $this->createTaxServiceLocatorOptions($website, $productTaxClass, $country);
        return $this->taxServiceLocator->get($options);
    }

    protected function setUp()
    {
        $this->taxServiceLocator = new DemoProjectTaxServiceLocator();
    }

    public function testItImplementsTheTaxServiceLocatorInterface()
    {
        $this->assertInstanceOf(TaxServiceLocator::class, $this->taxServiceLocator);
    }

    public function testItThrowsAnExceptionIfTheTaxServiceCanNotBeDetermined()
    {
        $this->expectException(UnableToLocateTaxServiceException::class);
        $this->expectExceptionMessage(
            'Unable to locate a tax service for website "test", product tax class "tax class" and country "GG"'
        );

        $website = 'test';
        $taxClass = 'tax class';
        $country = 'GG';
        $this->taxServiceLocator->get($this->createTaxServiceLocatorOptions($website, $taxClass, $country));
    }

    /**
     * @dataProvider taxServiceLocatorOptionsProvider
     */
    public function testTaxServiceLocatorReturnsTheCorrectInstances(
        string $website,
        string $productTaxClass,
        string $country,
        int $rate
    ) {
        $this->assertTaxServiceLocatorReturns($website, $productTaxClass, $country, $rate);
    }

    /**
     * @return array[]
     */
    public function taxServiceLocatorOptionsProvider() : array
    {
        return [

            // ------ "Taxable Goods" tax class -------
            
            ['de', 'Taxable Goods', 'DE', 19],
            ['en', 'Taxable Goods', 'DE', 19],
            ['fr', 'Taxable Goods', 'DE', 19],
            
            ['de', 'Taxable Goods', 'DK', 25],
            ['en', 'Taxable Goods', 'DK', 25],
            ['fr', 'Taxable Goods', 'DK', 25],
            
            ['de', 'Taxable Goods', 'EN', 20],
            ['en', 'Taxable Goods', 'EN', 20],
            ['fr', 'Taxable Goods', 'EN', 20],
            
            ['de', 'Taxable Goods', 'AT', 20],
            ['en', 'Taxable Goods', 'AT', 20],
            ['fr', 'Taxable Goods', 'AT', 20],
            
            ['de', 'Taxable Goods', 'FR', 20],
            ['en', 'Taxable Goods', 'FR', 20],
            ['fr', 'Taxable Goods', 'FR', 20],
            
            ['de', 'Taxable Goods', 'ES', 21],
            ['en', 'Taxable Goods', 'ES', 21],
            ['fr', 'Taxable Goods', 'ES', 21],
            
            ['de', 'Taxable Goods', 'FI', 24],
            ['en', 'Taxable Goods', 'FI', 24],
            ['fr', 'Taxable Goods', 'FI', 24],
            
            ['de', 'Taxable Goods', 'NL', 21],
            ['en', 'Taxable Goods', 'NL', 21],
            ['fr', 'Taxable Goods', 'NL', 21],
            
            ['de', 'Taxable Goods', 'SE', 25],
            ['en', 'Taxable Goods', 'SE', 25],
            ['fr', 'Taxable Goods', 'SE', 25],
            
            ['de', 'Taxable Goods', 'LU', 17],
            ['en', 'Taxable Goods', 'LU', 17],
            ['fr', 'Taxable Goods', 'LU', 17],
            
            ['de', 'Taxable Goods', 'IT', 21],
            ['en', 'Taxable Goods', 'IT', 21],
            ['fr', 'Taxable Goods', 'IT', 21],
            
            ['de', 'Taxable Goods', 'BE', 21],
            ['en', 'Taxable Goods', 'BE', 21],
            ['fr', 'Taxable Goods', 'BE', 21],

            // ------ "Shipping" tax class -------

            ['de', 'Shipping', 'DE', 19],
            ['en', 'Shipping', 'DE', 19],
            ['fr', 'Shipping', 'DE', 19],

            ['de', 'Shipping', 'DK', 25],
            ['en', 'Shipping', 'DK', 25],
            ['fr', 'Shipping', 'DK', 25],

            ['de', 'Shipping', 'AT', 20],
            ['en', 'Shipping', 'AT', 20],
            ['fr', 'Shipping', 'AT', 20],

            ['de', 'Shipping', 'FR', 20],
            ['en', 'Shipping', 'FR', 20],
            ['fr', 'Shipping', 'FR', 20],

            ['de', 'Shipping', 'ES', 21],
            ['en', 'Shipping', 'ES', 21],
            ['fr', 'Shipping', 'ES', 21],

            ['de', 'Shipping', 'FI', 24],
            ['en', 'Shipping', 'FI', 24],
            ['fr', 'Shipping', 'FI', 24],

            ['de', 'Shipping', 'NL', 21],
            ['en', 'Shipping', 'NL', 21],
            ['fr', 'Shipping', 'NL', 21],

            ['de', 'Shipping', 'SE', 25],
            ['en', 'Shipping', 'SE', 25],
            ['fr', 'Shipping', 'SE', 25],

            ['de', 'Shipping', 'LU', 17],
            ['en', 'Shipping', 'LU', 17],
            ['fr', 'Shipping', 'LU', 17],

            ['de', 'Shipping', 'IT', 21],
            ['en', 'Shipping', 'IT', 21],
            ['fr', 'Shipping', 'IT', 21],

            ['de', 'Shipping', 'BE', 21],
            ['en', 'Shipping', 'BE', 21],
            ['fr', 'Shipping', 'BE', 21],
        ];
    }
}
