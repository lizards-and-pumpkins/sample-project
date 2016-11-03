<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\View;

use LizardsAndPumpkins\Context\Context;
use LizardsAndPumpkins\Import\ImageStorage\Image;
use LizardsAndPumpkins\Import\Product\Image\ProductImageList;
use LizardsAndPumpkins\Import\Product\Product;
use LizardsAndPumpkins\Import\Product\ProductAttribute;
use LizardsAndPumpkins\Import\Product\ProductAttributeList;
use LizardsAndPumpkins\ProductDetail\Import\View\DemoProjectProductPageTitle;

/**
 * @covers \LizardsAndPumpkins\Import\Product\View\DemoProjectSimpleProductView
 */
class DemoProjectSimpleProductViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockProduct;

    /**
     * @var DemoProjectSimpleProductView
     */
    private $productView;

    /**
     * @var DemoProjectProductPageTitle|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubPageTitle;

    /**
     * @var ProductImageFileLocator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockProductImageFileLocator;

    protected function setUp()
    {
        $this->mockProduct = $this->createMock(Product::class);
        $this->stubPageTitle = $this->createMock(DemoProjectProductPageTitle::class);
        $this->mockProductImageFileLocator = $this->createMock(ProductImageFileLocator::class);
        $this->mockProductImageFileLocator->method('getPlaceholder')->willReturn($this->createMock(Image::class));
        $this->mockProductImageFileLocator->method('getVariantCodes')->willReturn(['large']);

        $this->productView = new DemoProjectSimpleProductView(
            $this->mockProduct,
            $this->stubPageTitle,
            $this->mockProductImageFileLocator
        );
    }

    public function testOriginalProductIsReturned()
    {
        $this->assertSame($this->mockProduct, $this->productView->getOriginalProduct());
    }

    public function testProductViewInterfaceIsImplemented()
    {
        $this->assertInstanceOf(ProductView::class, $this->productView);
    }

    public function testGettingFirstValueOfBackordersAttributeReturnsEmptyString()
    {
        $testAttributeCode = 'backorders';
        $testAttributeValue = true;

        $attribute = new ProductAttribute($testAttributeCode, $testAttributeValue, []);
        $attributeList = new ProductAttributeList($attribute);
        $this->mockProduct->method('getAttributes')->willReturn($attributeList);

        $this->assertSame('', $this->productView->getFirstValueOfAttribute($testAttributeCode));
    }

    public function testGettingAllValuesOfBackordersAttributeReturnsEmptyArray()
    {
        $testAttributeCode = 'backorders';
        $testAttributeValue = true;

        $attribute = new ProductAttribute($testAttributeCode, $testAttributeValue, []);
        $attributeList = new ProductAttributeList($attribute);
        $this->mockProduct->method('getAttributes')->willReturn($attributeList);

        $this->assertSame([], $this->productView->getAllValuesOfAttribute($testAttributeCode));
    }

    public function testProductViewAttributeListDoesNotHaveBackorders()
    {
        $testAttributeCode = 'backorders';
        $testAttributeValue = true;

        $attribute = new ProductAttribute($testAttributeCode, $testAttributeValue, []);
        $attributeList = new ProductAttributeList($attribute);
        $this->mockProduct->method('getAttributes')->willReturn($attributeList);

        $this->assertFalse($this->productView->hasAttribute($testAttributeCode));
    }

    public function testProductJsonDoesNotHaveBackorders()
    {

        $testAttributeCode = 'backorders';
        $testAttributeValue = true;

        $attribute = new ProductAttribute($testAttributeCode, $testAttributeValue, []);
        $attributeList = new ProductAttributeList($attribute);
        $this->mockProduct->method('getAttributes')->willReturn($attributeList);
        $this->mockProduct->method('jsonSerialize')->willReturn(['attributes' => $attributeList]);

        $productData = json_decode(json_encode($this->productView), true);

        $this->assertArrayNotHasKey('backorders', $productData['attributes']);
    }

    public function testFilteredProductAttributeListIsReturned()
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

    public function testMaximumPurchasableQuantityIsReturnedIfProductIsAvailableForBackorders()
    {
        $stockAttributeCode = 'stock_qty';
        $testAttributeValue = 1;

        $stockQtyAttribute = new ProductAttribute($stockAttributeCode, $testAttributeValue, []);
        $backordersAttribute = new ProductAttribute('backorders', 'true', []);
        $attributeList = new ProductAttributeList($stockQtyAttribute, $backordersAttribute);

        $this->mockProduct->method('getAttributes')->willReturn($attributeList);
        $this->mockProduct->method('getFirstValueOfAttribute')->with('backorders')->willReturn('true');
        $result = $this->productView->getFirstValueOfAttribute($stockAttributeCode);

        $this->assertSame((string) DemoProjectSimpleProductView::MAX_PURCHASABLE_QTY, $result);
    }

    public function testMaximumPurchasableQuantityIsReturnedIfProductQuantityIsGreaterThanMaximumPurchasableQuantity()
    {
        $stockAttributeCode = 'stock_qty';
        $testAttributeValue = 6;

        $attribute = new ProductAttribute($stockAttributeCode, $testAttributeValue, []);
        $attributeList = new ProductAttributeList($attribute);

        $this->mockProduct->method('getAttributes')->willReturn($attributeList);
        $result = $this->productView->getFirstValueOfAttribute($stockAttributeCode);

        $this->assertSame((string) DemoProjectSimpleProductView::MAX_PURCHASABLE_QTY, $result);
    }

    public function testItReturnsTheOriginalStockQtyIfBackordersIsFalseAndQtyIsSmallerThanMaximumPurchasableQuantity()
    {
        $stockAttributeCode = 'stock_qty';
        $testAttributeValue = 4;

        $attribute = new ProductAttribute($stockAttributeCode, $testAttributeValue, []);
        $attributeList = new ProductAttributeList($attribute);

        $this->mockProduct->method('getAttributes')->willReturn($attributeList);
        $this->mockProduct->method('getFirstValueOfAttribute')->with('backorders')->willReturn('false');
        $result = $this->productView->getFirstValueOfAttribute($stockAttributeCode);

        $this->assertSame((string) $testAttributeValue, $result);
    }

    public function testItUsesTheInjectedProductImageFileLocatorToGetPlaceholderImages()
    {
        $stubAttributeList = $this->createMock(ProductAttributeList::class);
        $stubAttributeList->method('getAllAttributes')->willReturn([]);
        $this->mockProduct->method('getAttributes')->willReturn($stubAttributeList);
        $this->mockProduct->method('jsonSerialize')->willReturn(['images' => []]);
        $this->mockProduct->method('getImages')->willReturn(new ProductImageList());
        $this->mockProduct->method('getContext')->willReturn($this->createMock(Context::class));

        $this->mockProductImageFileLocator->expects($this->once())->method('getPlaceholder');
        json_encode($this->productView);
    }

    public function testProductPageTitleCreationIsDelegatedToPageTitle()
    {
        $testTitle = 'foo';
        $this->stubPageTitle->method('forProductView')->willReturn($testTitle);

        $this->assertSame($testTitle, $this->productView->getProductPageTitle());
    }
}
