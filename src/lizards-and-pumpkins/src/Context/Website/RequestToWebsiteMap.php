<?php

declare(strict_types=1);

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

    public function getWebsiteFromRequest(HttpRequest $httpRequest) : Website
    {
        return $this->urlToWebsiteMap->getWebsiteCodeByUrl((string) $httpRequest->getUrl());
    }
}
