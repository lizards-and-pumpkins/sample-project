<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\View;

use LizardsAndPumpkins\Import\Product\Composite\ConfigurableProduct;
use LizardsAndPumpkins\Import\Product\Product;
use LizardsAndPumpkins\Import\Product\ProductAttribute;

class DemoProjectConfigurableProductView extends AbstractConfigurableProductView implements CompositeProductView
{
    const MAX_PURCHASABLE_QTY = 5;

    /**
     * @var ProductViewLocator
     */
    private $productViewLocator;

    /**
     * @var ConfigurableProduct
     */
    private $product;

    /**
     * @var ProductImageFileLocator
     */
    private $productImageFileLocator;

    public function __construct(
        ProductViewLocator $productViewLocator,
        ConfigurableProduct $product,
        ProductImageFileLocator $productImageFileLocator
    ) {
        $this->productViewLocator = $productViewLocator;
        $this->product = $product;
        $this->productImageFileLocator = $productImageFileLocator;
    }

    public function getOriginalProduct() : Product
    {
        return $this->product;
    }

    final protected function isAttributePublic(ProductAttribute $attribute) : bool
    {
        return in_array($attribute->getCode(), ['backorders']) ?
            false :
            parent::isAttributePublic($attribute);
    }

    final protected function getProductImageFileLocator() : ProductImageFileLocator
    {
        return $this->productImageFileLocator;
    }

    final protected function getProductViewLocator() : ProductViewLocator
    {
        return $this->productViewLocator;
    }
}
