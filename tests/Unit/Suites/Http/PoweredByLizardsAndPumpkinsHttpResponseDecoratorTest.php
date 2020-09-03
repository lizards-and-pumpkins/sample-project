<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Http;

use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Http\PoweredByLizardsAndPumpkinsHttpResponseDecorator
 */
class PoweredByLizardsAndPumpkinsHttpResponseDecoratorTest extends TestCase
{
    public function testImplementsHttpResponse(): void
    {
        $delegate = $this->createMock(HttpResponse::class);
        $decorator = PoweredByLizardsAndPumpkinsHttpResponseDecorator::decorateHttpResponse($delegate);
        $this->assertInstanceOf(HttpResponse::class, $decorator);
        $this->assertInstanceOf(PoweredByLizardsAndPumpkinsHttpResponseDecorator::class, $decorator);
    }

    public function testDelegatesRetrievingTheResponseBody(): void
    {
        $delegate = $this->createMock(HttpResponse::class);
        $delegate->expects($this->once())->method('getBody')->willReturn('foo');
        $decorator = PoweredByLizardsAndPumpkinsHttpResponseDecorator::decorateHttpResponse($delegate);
        $this->assertSame('foo', $decorator->getBody());
    }

    public function testDelegatesRetrievingTheResponseStatusCode(): void
    {
        $delegate = $this->createMock(HttpResponse::class);
        $delegate->expects($this->once())->method('getStatusCode')->willReturn(123);
        $decorator = PoweredByLizardsAndPumpkinsHttpResponseDecorator::decorateHttpResponse($delegate);
        $this->assertSame(123, $decorator->getStatusCode());
    }

    public function testPowerdByLizardsAndPumpkinsHeaderIsMergedIntoDelegateHeaders(): void
    {
        $stubHttpHeaders = $this->createMock(HttpHeaders::class);
        $stubHttpHeaders->method('getAll')->willReturn(['Foo' => 'Bar']);
        $delegate = $this->createMock(HttpResponse::class);
        $delegate->expects($this->once())->method('getHeaders')->willReturn($stubHttpHeaders);
        $decorator = PoweredByLizardsAndPumpkinsHttpResponseDecorator::decorateHttpResponse($delegate);

        $resultHeaders = $decorator->getHeaders();

        $this->assertSame('Bar', $resultHeaders->get('Foo'));
        $this->assertSame('Lizards & Pumpkins', $resultHeaders->get('X-Powered-By'));
    }

    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testDelegatesSend(): void
    {
        $dummyStatusCode = 200;

        $stubHttpHeaders = $this->createMock(HttpHeaders::class);
        $stubHttpHeaders->method('getAll')->willReturn([]);
        $delegate = $this->createMock(HttpResponse::class);
        $delegate->method('getHeaders')->willReturn($stubHttpHeaders);
        $delegate->method('getBody')->willReturn('bar');
        $delegate->method('getStatusCode')->willReturn($dummyStatusCode);

        $decorator = PoweredByLizardsAndPumpkinsHttpResponseDecorator::decorateHttpResponse($delegate);
        $decorator->send();

        $this->assertEquals($dummyStatusCode, http_response_code());
        $expectedHeader = 'X-Powered-By: Lizards & Pumpkins';
        $this->assertContains($expectedHeader, xdebug_get_headers());
        $this->expectOutputString('bar');
    }
}
