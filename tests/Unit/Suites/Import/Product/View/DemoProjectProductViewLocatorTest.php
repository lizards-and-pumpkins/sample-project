<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\View;

use LizardsAndPumpkins\Import\Product\Composite\ConfigurableProduct;
use LizardsAndPumpkins\Import\Product\Product;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Import\Product\View\DemoProjectProductViewLocator
 * @uses   \LizardsAndPumpkins\Import\Product\View\DemoProjectSimpleProductView
 * @uses   \LizardsAndPumpkins\Import\Product\View\DemoProjectConfigurableProductView
 */
class DemoProjectProductViewLocatorTest extends TestCase
{
    /**
     * @var DemoProjectProductViewLocator
     */
    private $locator;

    /**
     * @var ProductImageFileLocator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubProductImageLocator;

    protected function setUp()
    {
        $this->stubProductImageLocator = $this->createMock(ProductImageFileLocator::class);

        $this->locator = new DemoProjectProductViewLocator($this->stubProductImageLocator);
    }

    public function testProductViewInterfaceIsImplemented()
    {
        $this->assertInstanceOf(ProductViewLocator::class, $this->locator);
    }

    public function testSimpleProductViewIsReturned()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $stubProduct */
        $stubProduct = $this->createMock(Product::class);

        $result = $this->locator->createForProduct($stubProduct);

        $this->assertInstanceOf(DemoProjectSimpleProductView::class, $result);
    }

    public function testConfigurableProductViewIsReturned()
    {
        /** @var ConfigurableProduct|\PHPUnit_Framework_MockObject_MockObject $stubConfigurableProduct */
        $stubConfigurableProduct = $this->createMock(ConfigurableProduct::class);

        $result = $this->locator->createForProduct($stubConfigurableProduct);

        $this->assertInstanceOf(DemoProjectConfigurableProductView::class, $result);
    }
}
