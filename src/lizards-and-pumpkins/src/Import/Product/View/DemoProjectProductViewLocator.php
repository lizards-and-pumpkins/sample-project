<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\View;

use LizardsAndPumpkins\Import\Product\Composite\ConfigurableProduct;
use LizardsAndPumpkins\Import\Product\Product;
use LizardsAndPumpkins\ProductDetail\Import\View\DemoProjectProductPageTitle;

class DemoProjectProductViewLocator implements ProductViewLocator
{
    /**
     * @var ProductImageFileLocator
     */
    private $imageFileLocator;

    /**
     * @var DemoProjectProductPageTitle
     */
    private $pageTitle;

    public function __construct(ProductImageFileLocator $imageFileLocator, DemoProjectProductPageTitle $pageTitle)
    {
        $this->imageFileLocator = $imageFileLocator;
        $this->pageTitle = $pageTitle;
    }

    public function createForProduct(Product $product) : ProductView
    {
        if ($product instanceof ConfigurableProduct) {
            return new DemoProjectConfigurableProductView($this, $product, $this->pageTitle, $this->imageFileLocator);
        }

        return new DemoProjectSimpleProductView($product, $this->pageTitle, $this->imageFileLocator);
    }
}
