<?php

namespace LizardsAndPumpkins\Import\Tax;

use LizardsAndPumpkins\Context\Country\Country;
use LizardsAndPumpkins\Context\Website\Website;

class DemoProjectTaxServiceLocator implements TaxServiceLocator
{
    private static $rateTable = [
        // [websites],       [tax rates],                country, rate
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'DE', 19],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'DK', 25],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'AT', 20],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'FR', 20],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'ES', 21],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'FI', 24],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'NL', 21],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'SE', 25],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'LU', 17],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'IT', 21],
        [['de', 'en', 'fr'], ['Taxable Goods', 'Shipping'], 'BE', 21],
    ];
    
    private static $websiteIdx = 0;
    private static $taxClassIdx = 1;
    private static $countryIdx = 2;
    private static $rateIdx = 3;

    /**
     * @var Website
     */
    private $website;

    /**
     * @var Country
     */
    private $country;

    /**
     * @var ProductTaxClass
     */
    private $taxClass;

    /**
     * @param mixed[] $options
     * @return TaxService
     */
    public function get(array $options)
    {
        $this->website = $this->getWebsiteFromOptions($options);
        $this->taxClass = $this->getProductTaxClassFromOptions($options);
        $this->country = $this->getCountryFromOptions($options);
        
        return $this->findRule();
    }

    /**
     * @return DemoProjectTaxRate
     */
    private function findRule()
    {
        foreach (self::$rateTable as $rule) {
            if ($this->isMatchingRule($rule)) {
                return DemoProjectTaxRate::fromInt($rule[self::$rateIdx]);
            }
        }
        throw $this->createUnableToLocateServiceException();
    }

    /**
     * @param mixed[] $rule
     * @return bool
     */
    private function isMatchingRule(array $rule)
    {
        return
            in_array((string) $this->website, $rule[self::$websiteIdx]) &&
            in_array((string) $this->taxClass, $rule[self::$taxClassIdx]) &&
            (string) $this->country === $rule[self::$countryIdx];
    }

    /**
     * @param mixed[] $options
     * @return Country
     */
    private function getCountryFromOptions(array $options)
    {
        return Country::from2CharIso3166($options[self::OPTION_COUNTRY]);
    }

    /**
     * @param mixed[] $options
     * @return ProductTaxClass
     */
    private function getProductTaxClassFromOptions(array $options)
    {
        return ProductTaxClass::fromString($options[self::OPTION_PRODUCT_TAX_CLASS]);
    }

    /**
     * @param mixed[] $options
     * @return Website
     */
    private function getWebsiteFromOptions(array $options)
    {
        return Website::fromString($options[self::OPTION_WEBSITE]);
    }

    /**
     * @return UnableToLocateTaxServiceException
     */
    private function createUnableToLocateServiceException()
    {
        $message = sprintf(
            'Unable to locate a tax service for website "%s", product tax class "%s" and country "%s"',
            $this->website,
            $this->taxClass,
            $this->country
        );
        return new UnableToLocateTaxServiceException($message);
    }
}
