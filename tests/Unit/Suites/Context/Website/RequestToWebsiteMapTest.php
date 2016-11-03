<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Context\Website;

use LizardsAndPumpkins\Http\HttpRequest;
use LizardsAndPumpkins\Http\HttpUrl;

/**
 * @covers \LizardsAndPumpkins\Context\Website\RequestToWebsiteMap
 */
class RequestToWebsiteMapTest extends \PHPUnit_Framework_TestCase
{
    public function testWebsiteIsReturned()
    {
        $testUrlString = 'http://example.com/';
        $dummyWebsite = $this->createMock(Website::class);
        
        $stubHttpUrl = $this->createMock(HttpUrl::class);
        $stubHttpUrl->method('__toString')->willReturn($testUrlString);
        
        /** @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject $stubHttpRequest */
        $stubHttpRequest = $this->createMock(HttpRequest::class);
        $stubHttpRequest->method('getUrl')->willReturn($stubHttpUrl);

        /** @var UrlToWebsiteMap|\PHPUnit_Framework_MockObject_MockObject $stubUrlToWebsiteMap */
        $stubUrlToWebsiteMap = $this->createMock(UrlToWebsiteMap::class);
        $stubUrlToWebsiteMap->method('getWebsiteCodeByUrl')->with($testUrlString)->willReturn($dummyWebsite);

        $requestToWebsiteMap = new RequestToWebsiteMap($stubUrlToWebsiteMap);

        $result = $requestToWebsiteMap->getWebsiteFromRequest($stubHttpRequest);
        
        $this->assertSame($dummyWebsite, $result);
    }
}
