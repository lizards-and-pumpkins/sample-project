<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Context\Website;

use LizardsAndPumpkins\Context\Country\Country;

class WebsiteToCountryMap
{
    private $defaultCountry = 'FR';

    private $map = [
        'de' => 'DE',
        'en' => 'EN',
        'fr' => 'FR',
    ];

    public function getCountry(Website $website) : Country
    {
        $country = $this->getCountryFromMap((string) $website);
        return Country::from2CharIso3166($country);
    }
    
    public function getDefaultCountry() : Country
    {
        return Country::from2CharIso3166($this->defaultCountry);
    }

    private function getCountryFromMap(string $mapKey) : string
    {
        return $this->map[$mapKey] ?? (string) $this->getDefaultCountry();
    }
}
