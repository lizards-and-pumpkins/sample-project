<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\View;

use LizardsAndPumpkins\Import\ImageStorage\Image;
use LizardsAndPumpkins\Import\Product\Composite\ConfigurableProduct;
use LizardsAndPumpkins\Import\Product\ProductAttribute;
use LizardsAndPumpkins\Import\Product\ProductAttributeList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Import\Product\View\DemoProjectConfigurableProductView
 */
class DemoProjectConfigurableProductViewTest extends TestCase
{
    /**
     * @var ConfigurableProduct
     */
    private $mockProduct;

    /**
     * @var DemoProjectConfigurableProductView
     */
    private $productView;

    final protected function setUp(): void
    {
        $stubProductViewLocator = $this->createMock(ProductViewLocator::class);
        $this->mockProduct = $this->createMock(ConfigurableProduct::class);
        $stubProductImageFileLocator = $this->createMock(ProductImageFileLocator::class);
        $stubProductImageFileLocator->method('getPlaceholder')->willReturn($this->createMock(Image::class));
        $stubProductImageFileLocator->method('getVariantCodes')->willReturn(['large']);

        $this->productView = new DemoProjectConfigurableProductView(
            $stubProductViewLocator,
            $this->mockProduct,
            $stubProductImageFileLocator
        );
    }

    public function testProductViewInterfaceIsImplemented(): void
    {
        $this->assertInstanceOf(ProductView::class, $this->productView);
    }

    public function testItExtendsTheAbstractConfigurableProductView(): void
    {
        $this->assertInstanceOf(AbstractConfigurableProductView::class, $this->productView);
    }

    public function testOriginalProductIsReturned(): void
    {
        $this->assertSame($this->mockProduct, $this->productView->getOriginalProduct());
    }

    public function testGettingFirstValueOfBackordersAttributeReturnsEmptyString(): void
    {
        $testAttributeCode = 'backorders';
        $testAttributeValue = 'true';

        $attribute = new ProductAttribute($testAttributeCode, $testAttributeValue, []);
        $attributeList = new ProductAttributeList($attribute);
        $this->mockProduct->method('getAttributes')->willReturn($attributeList);

        $this->assertSame('', $this->productView->getFirstValueOfAttribute($testAttributeCode));
    }

    public function testGettingAllValuesOfBackordersAttributeReturnsEmptyArray(): void
    {
        $testAttributeCode = 'backorders';
        $testAttributeValue = true;

        $attribute = new ProductAttribute($testAttributeCode, $testAttributeValue, []);
        $attributeList = new ProductAttributeList($attribute);
        $this->mockProduct->method('getAttributes')->willReturn($attributeList);

        $this->assertSame([], $this->productView->getAllValuesOfAttribute($testAttributeCode));
    }

    public function testProductViewAttributeListDoesNotHaveBackorders(): void
    {
        $testAttributeCode = 'backorders';
        $testAttributeValue = true;

        $attribute = new ProductAttribute($testAttributeCode, $testAttributeValue, []);
        $attributeList = new ProductAttributeList($attribute);
        $this->mockProduct->method('getAttributes')->willReturn($attributeList);

        $this->assertFalse($this->productView->hasAttribute($testAttributeCode));
    }

    public function testFilteredProductAttributeListIsReturned(): void
    {
        $nonPriceAttribute = new ProductAttribute('foo', 'bar', []);
        $priceAttribute = new ProductAttribute('price', 1000, []);
        $specialPriceAttribute = new ProductAttribute('special_price', 900, []);
        $backordersAttribute = new ProductAttribute('backorders', true, []);

        $attributeList = new ProductAttributeList(
            $nonPriceAttribute,
            $priceAttribute,
            $specialPriceAttribute,
            $backordersAttribute
        );

        $this->mockProduct->method('getAttributes')->willReturn($attributeList);

        $result = $this->productView->getAttributes();

        $this->assertCount(1, $result);
        $this->assertContains($nonPriceAttribute, $result->getAllAttributes());
    }
}
