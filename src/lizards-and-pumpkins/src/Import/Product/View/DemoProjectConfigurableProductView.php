<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\View;

use LizardsAndPumpkins\Import\Product\Composite\ConfigurableProduct;
use LizardsAndPumpkins\Import\Product\Product;
use LizardsAndPumpkins\Import\Product\ProductAttribute;
use LizardsAndPumpkins\ProductDetail\Import\View\DemoProjectProductPageTitle;

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
     * @var DemoProjectProductPageTitle
     */
    private $pageTitle;

    /**
     * @var ProductImageFileLocator
     */
    private $productImageFileLocator;

    public function __construct(
        ProductViewLocator $productViewLocator,
        ConfigurableProduct $product,
        DemoProjectProductPageTitle $pageTitle,
        ProductImageFileLocator $productImageFileLocator
    ) {
        $this->productViewLocator = $productViewLocator;
        $this->product = $product;
        $this->pageTitle = $pageTitle;
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

    final public function getProductPageTitle() : string
    {
        return $this->pageTitle->forProductView($this);
    }
}
