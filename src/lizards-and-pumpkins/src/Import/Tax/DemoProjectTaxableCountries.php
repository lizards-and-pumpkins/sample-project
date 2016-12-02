<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Tax;

class DemoProjectTaxableCountries implements TaxableCountries
{
    private static $countries = [
        'DE',
        'AT',
        'EN',
        'DK',
        'FR',
        'ES',
        'FI',
        'NL',
        'SE',
        'LU',
        'IT',
        'BE',
    ];

    /**
     * @return string[]
     */
    public function getCountries() : array
    {
        return self::$countries;
    }

    public function getIterator() : \Iterator
    {
        return new \ArrayIterator(self::$countries);
    }
}
