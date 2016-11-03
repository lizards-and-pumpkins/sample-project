<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Context\Country;

use LizardsAndPumpkins\Context\ContextBuilder;
use LizardsAndPumpkins\Context\ContextPartBuilder;
use LizardsAndPumpkins\Context\Website\RequestToWebsiteMap;
use LizardsAndPumpkins\Context\Website\WebsiteToCountryMap;
use LizardsAndPumpkins\Http\HttpRequest;

class DemoProjectCountryContextPartBuilder implements ContextPartBuilder
{
    const COOKIE_NAME = 'lizardsAndPumpkinsTransport';

    private $cookieDataKey = 'country';

    /**
     * @var RequestToWebsiteMap
     */
    private $requestToWebsiteMap;

    /**
     * @var WebsiteToCountryMap
     */
    private $websiteToCountryMap;

    public function __construct(RequestToWebsiteMap $requestToWebsiteMap, WebsiteToCountryMap $websiteToCountryMap)
    {
        $this->requestToWebsiteMap = $requestToWebsiteMap;
        $this->websiteToCountryMap = $websiteToCountryMap;
    }

    /**
     * @param mixed[] $inputDataSet
     * @return string|null
     */
    public function getValue(array $inputDataSet)
    {
        if (isset($inputDataSet[Country::CONTEXT_CODE])) {
            return (string) $inputDataSet[Country::CONTEXT_CODE];
        }

        if (isset($inputDataSet[ContextBuilder::REQUEST])) {
            return $this->getCountryCodeFromRequest($inputDataSet[ContextBuilder::REQUEST]);
        }

        return null;
    }

    public function getCode() : string
    {
        return Country::CONTEXT_CODE;
    }

    private function getCountryCodeFromRequest(HttpRequest $httpRequest) : string
    {
        if ($httpRequest->hasCookie(self::COOKIE_NAME)) {
            $cookieData = json_decode($httpRequest->getCookieValue(self::COOKIE_NAME), true);

            if (isset($cookieData[$this->cookieDataKey])) {
                return $cookieData[$this->cookieDataKey];
            }
        }

        $website =  $this->requestToWebsiteMap->getWebsiteFromRequest($httpRequest);
        
        return (string) $this->websiteToCountryMap->getCountry($website);
    }
}
