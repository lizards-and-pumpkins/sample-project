<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Context\Website;

use LizardsAndPumpkins\Context\ContextBuilder;
use LizardsAndPumpkins\Context\ContextPartBuilder;
use LizardsAndPumpkins\Context\Website\Exception\UnableToDetermineContextWebsiteException;
use LizardsAndPumpkins\Http\HttpRequest;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Context\Website\DemoProjectWebsiteContextPartBuilder
 */
class DemoProjectWebsiteContextPartBuilderTest extends TestCase
{
    /**
     * @var DemoProjectWebsiteContextPartBuilder
     */
    private $contextPartBuilder;

    /**
     * @var RequestToWebsiteMap
     */
    private $stubRequestToWebsiteMap;

    /**
     * @param string $testWebsiteCode
     * @return HttpRequest
     */
    private function createStubRequestAndMapItToWebsiteWithGivenCode(string $testWebsiteCode): HttpRequest
    {
        $dummyRequest = $this->createMock(HttpRequest::class);

        $dummyWebsite = $this->createMock(Website::class);
        $dummyWebsite->method('__toString')->willReturn($testWebsiteCode);

        $this->stubRequestToWebsiteMap->method('getWebsiteFromRequest')->with($dummyRequest)->willReturn($dummyWebsite);

        return $dummyRequest;
    }

    final protected function setUp(): void
    {
        $this->stubRequestToWebsiteMap = $this->createMock(RequestToWebsiteMap::class);
        $this->contextPartBuilder = new DemoProjectWebsiteContextPartBuilder($this->stubRequestToWebsiteMap);
    }

    public function testContextPartBuilderInterfaceIsImplemented(): void
    {
        $this->assertInstanceOf(ContextPartBuilder::class, $this->contextPartBuilder);
    }

    public function testContextWebsiteCodeIsReturned(): void
    {
        $this->assertSame(Website::CONTEXT_CODE, $this->contextPartBuilder->getCode());
    }

    public function testExceptionIsThrownIfNeitherWebsiteNorRequestIsPresentInInputDataSet(): void
    {
        $this->expectException(UnableToDetermineContextWebsiteException::class);
        $inputDataSet = [];
        $this->contextPartBuilder->getValue($inputDataSet);
    }

    /**
     * @dataProvider websiteCodeProvider
     * @param string $testWebsiteCode
     */
    public function testWebsiteCodeProvidedInInputDataSetIsReturned(string $testWebsiteCode): void
    {
        $inputDataSet = [Website::CONTEXT_CODE => $testWebsiteCode];
        $this->assertSame($testWebsiteCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @dataProvider websiteCodeProvider
     * @param string $testWebsiteCode
     */
    public function testDeterminationOfWebsiteIsDelegatedToRequestToWebsiteMap(string $testWebsiteCode): void
    {
        $dummyRequest = $this->createStubRequestAndMapItToWebsiteWithGivenCode($testWebsiteCode);
        $inputDataSet = [ContextBuilder::REQUEST => $dummyRequest];

        $this->assertSame($testWebsiteCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    public function testExplicitWebsiteValueIsPreferredOverMappedOne(): void
    {
        $testExplicitWebsiteCode = 'foo';
        $testWebsiteCodeFromRequest = 'bar';

        $dummyRequest = $this->createStubRequestAndMapItToWebsiteWithGivenCode($testWebsiteCodeFromRequest);
        $inputDataSet = [Website::CONTEXT_CODE => $testExplicitWebsiteCode, ContextBuilder::REQUEST => $dummyRequest];

        $this->assertSame($testExplicitWebsiteCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @return array[]
     */
    public function websiteCodeProvider(): array
    {
        return [['foo'], ['bar']];
    }
}
