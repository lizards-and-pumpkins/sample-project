<?php

namespace LizardsAndPumpkins\Context\Website;

use LizardsAndPumpkins\Context\ContextBuilder;
use LizardsAndPumpkins\Context\ContextPartBuilder;
use LizardsAndPumpkins\Context\Website\Exception\UnableToDetermineContextWebsiteException;
use LizardsAndPumpkins\Http\HttpRequest;

/**
 * @covers \LizardsAndPumpkins\Context\Website\DemoProjectWebsiteContextPartBuilder
 */
class DemoProjectWebsiteContextPartBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DemoProjectWebsiteContextPartBuilder
     */
    private $contextPartBuilder;

    /**
     * @var RequestToWebsiteMap|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubRequestToWebsiteMap;

    /**
     * @param string $testWebsiteCode
     * @return HttpRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createStubRequestAndMapItToWebsiteWithGivenCode($testWebsiteCode)
    {
        $dummyRequest = $this->createMock(HttpRequest::class);

        $dummyWebsite = $this->createMock(Website::class);
        $dummyWebsite->method('__toString')->willReturn($testWebsiteCode);

        $this->stubRequestToWebsiteMap->method('getWebsiteFromRequest')->with($dummyRequest)->willReturn($dummyWebsite);

        return $dummyRequest;
    }

    protected function setUp()
    {
        $this->stubRequestToWebsiteMap = $this->createMock(RequestToWebsiteMap::class);
        $this->contextPartBuilder = new DemoProjectWebsiteContextPartBuilder($this->stubRequestToWebsiteMap);
    }

    public function testContextPartBuilderInterfaceIsImplemented()
    {
        $this->assertInstanceOf(ContextPartBuilder::class, $this->contextPartBuilder);
    }

    public function testContextWebsiteCodeIsReturned()
    {
        $this->assertSame(Website::CONTEXT_CODE, $this->contextPartBuilder->getCode());
    }

    public function testExceptionIsThrownIfNeitherWebsiteNorRequestIsPresentInInputDataSet()
    {
        $this->expectException(UnableToDetermineContextWebsiteException::class);
        $inputDataSet = [];
        $this->contextPartBuilder->getValue($inputDataSet);
    }

    /**
     * @param string $testWebsiteCode
     * @dataProvider websiteCodeProvider
     */
    public function testWebsiteCodeProvidedInInputDataSetIsReturned($testWebsiteCode)
    {
        $inputDataSet = [Website::CONTEXT_CODE => $testWebsiteCode];
        $this->assertSame($testWebsiteCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    /**
     * @param string $testWebsiteCode
     * @dataProvider websiteCodeProvider
     */
    public function testDeterminationOfWebsiteIsDelegatedToRequestToWebsiteMap($testWebsiteCode)
    {
        $dummyRequest = $this->createStubRequestAndMapItToWebsiteWithGivenCode($testWebsiteCode);
        $inputDataSet = [ContextBuilder::REQUEST => $dummyRequest];

        $this->assertSame($testWebsiteCode, $this->contextPartBuilder->getValue($inputDataSet));
    }

    public function testExplicitWebsiteValueIsPreferredOverMappedOne()
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
    public function websiteCodeProvider()
    {
        return [['foo'], ['bar']];
    }
}
