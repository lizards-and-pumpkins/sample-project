<?php

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
        $dummyWebsite = $this->getMock(Website::class, [], [], '', false);
        
        $stubHttpUrl = $this->getMock(HttpUrl::class, [], [], '', false);
        $stubHttpUrl->method('__toString')->willReturn($testUrlString);
        
        /** @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject $stubHttpRequest */
        $stubHttpRequest = $this->getMock(HttpRequest::class, [], [], '', false);
        $stubHttpRequest->method('getUrl')->willReturn($stubHttpUrl);

        /** @var UrlToWebsiteMap|\PHPUnit_Framework_MockObject_MockObject $stubUrlToWebsiteMap */
        $stubUrlToWebsiteMap = $this->getMock(UrlToWebsiteMap::class);
        $stubUrlToWebsiteMap->method('getWebsiteCodeByUrl')->with($testUrlString)->willReturn($dummyWebsite);

        $requestToWebsiteMap = new RequestToWebsiteMap($stubUrlToWebsiteMap);

        $result = $requestToWebsiteMap->getWebsiteFromRequest($stubHttpRequest);
        
        $this->assertSame($dummyWebsite, $result);
    }
}
