<?php

namespace LizardsAndPumpkins\Context\Website;

use LizardsAndPumpkins\Context\Country\Country;

class WebsiteToCountryMap
{
    private $defaultCountry = 'FR';

    private $map = [
        'de' => 'DE',
        'en' => 'DE',
        'fr' => 'FR',
    ];

    /**
     * @param Website $website
     * @return Country
     */
    public function getCountry(Website $website)
    {
        $countryCode = $this->getCountryFromMap((string) $website);
        return Country::from2CharIso3166($countryCode);
    }
    
    /**
     * @return Country
     */
    public function getDefaultCountry()
    {
        return Country::from2CharIso3166($this->defaultCountry);
    }

    /**
     * @param string $mapKey
     * @return string
     */
    private function getCountryFromMap($mapKey)
    {
        return isset($this->map[$mapKey]) ?
            $this->map[$mapKey] :
            $this->getDefaultCountry();
    }
}
