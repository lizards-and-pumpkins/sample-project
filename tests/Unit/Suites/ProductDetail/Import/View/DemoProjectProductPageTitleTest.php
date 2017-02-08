<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\ProductDetail\Import\View;

use LizardsAndPumpkins\Import\Product\View\ProductView;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\ProductDetail\Import\View\DemoProjectProductPageTitle
 */
class DemoProjectProductPageTitleTest extends TestCase
{
    /**
     * @var ProductView|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubProductView;

    /**
     * @var DemoProjectProductPageTitle
     */
    private $productPageTitle;

    protected function setUp()
    {
        $this->stubProductView = $this->createMock(ProductView::class);
        $this->productPageTitle = new DemoProjectProductPageTitle();
    }

    /**
     * @dataProvider requiredAttributeCodeProvider
     */
    public function testProductTitleContainsRequiredProductAttributes(string $requiredAttributeCode)
    {
        $testAttributeValue = 'foo';

        $this->stubProductView->method('getFirstValueOfAttribute')->willReturnCallback(
            function ($attributeCode) use ($requiredAttributeCode, $testAttributeValue) {
                if ($attributeCode === $requiredAttributeCode) {
                    return $testAttributeValue;
                }
                return '';
            }
        );

        $this->assertContains($testAttributeValue, $this->productPageTitle->forProductView($this->stubProductView));
    }

    /**
     * @return array[]
     */
    public function requiredAttributeCodeProvider() : array
    {
        return [
            ['name'],
            ['product_group'],
            ['brand'],
            ['style'],
        ];
    }

    public function testProductTitleContainsProductTitleSuffix()
    {
        $result = $this->productPageTitle->forProductView($this->stubProductView);
        $this->assertContains(DemoProjectProductPageTitle::PRODUCT_TITLE_SUFFIX, $result);
    }

    public function testProductMetaTitleIsNotExceedingDefinedLimit()
    {
        $maxTitleLength = DemoProjectProductPageTitle::MAX_PRODUCT_TITLE_LENGTH;
        $attributeLength = (int) round(
            ($maxTitleLength - strlen(DemoProjectProductPageTitle::PRODUCT_TITLE_SUFFIX)) / 2 - 1
        );

        $this->stubProductView->method('getFirstValueOfAttribute')->willReturn(str_repeat('-', $attributeLength));

        $result = $this->productPageTitle->forProductView($this->stubProductView);

        $this->assertLessThanOrEqual($maxTitleLength, strlen($result));
    }

}
