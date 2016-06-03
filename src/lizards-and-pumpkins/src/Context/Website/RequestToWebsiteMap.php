<?php

namespace LizardsAndPumpkins\Context\Website;

use LizardsAndPumpkins\Http\HttpRequest;

class RequestToWebsiteMap
{
    /**
     * @var UrlToWebsiteMap
     */
    private $urlToWebsiteMap;

    public function __construct(UrlToWebsiteMap $urlToWebsiteMap)
    {
        $this->urlToWebsiteMap = $urlToWebsiteMap;
    }

    /**
     * @param HttpRequest $httpRequest
     * @return Website
     */
    public function getWebsiteFromRequest(HttpRequest $httpRequest)
    {
        return $this->urlToWebsiteMap->getWebsiteCodeByUrl($httpRequest->getUrl());
    }
}
