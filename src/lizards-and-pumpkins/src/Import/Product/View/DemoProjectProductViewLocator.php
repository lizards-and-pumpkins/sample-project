<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\View;

use LizardsAndPumpkins\Import\Product\Composite\ConfigurableProduct;
use LizardsAndPumpkins\Import\Product\Product;

class DemoProjectProductViewLocator implements ProductViewLocator
{
    /**
     * @var ProductImageFileLocator
     */
    private $imageFileLocator;

    public function __construct(ProductImageFileLocator $imageFileLocator)
    {
        $this->imageFileLocator = $imageFileLocator;
    }

    public function createForProduct(Product $product) : ProductView
    {
        if ($product instanceof ConfigurableProduct) {
            return new DemoProjectConfigurableProductView($this, $product, $this->imageFileLocator);
        }

        return new DemoProjectSimpleProductView($product, $this->imageFileLocator);
    }
}
