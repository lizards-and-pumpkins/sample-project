<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\View;

use LizardsAndPumpkins\Import\Product\Product;
use LizardsAndPumpkins\Import\Product\ProductAttribute;
use LizardsAndPumpkins\ProductDetail\Import\View\DemoProjectProductPageTitle;

class DemoProjectSimpleProductView extends AbstractProductView
{
    const MAX_PURCHASABLE_QTY = 5;

    /**
     * @var Product
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
        Product $product,
        DemoProjectProductPageTitle $pageTitle,
        ProductImageFileLocator $productImageFileLocator
    ) {
        $this->product = $product;
        $this->pageTitle = $pageTitle;
        $this->productImageFileLocator = $productImageFileLocator;
    }

    final public function getOriginalProduct() : Product
    {
        return $this->product;
    }

    final protected function getProductImageFileLocator() : ProductImageFileLocator
    {
        return $this->productImageFileLocator;
    }

    final protected function isAttributePublic(ProductAttribute $attribute) : bool
    {
        return (in_array($attribute->getCode(), ['backorders'])) ?
            false :
            parent::isAttributePublic($attribute);
    }

    final protected function getProcessedAttribute(ProductAttribute $attribute) : ProductAttribute
    {
        if ($attribute->getCode() == 'stock_qty') {
            return $this->getBoundedStockQtyAttribute($attribute);
        }
        return parent::getProcessedAttribute($attribute);
    }

    final public function getProductPageTitle() : string
    {
        return $this->pageTitle->forProductView($this);
    }

    private function getBoundedStockQtyAttribute(ProductAttribute $stockQty) : ProductAttribute
    {
        if ($this->isOverMaxQtyToShow($stockQty) || $this->hasBackorders()) {
            return $this->createStockQtyAttributeAtMaximumPurchasableLevel($stockQty);
        }

        return $stockQty;
    }

    private function isOverMaxQtyToShow(ProductAttribute $stockQty) : bool
    {
        return $stockQty->getValue() > self::MAX_PURCHASABLE_QTY;
    }

    private function hasBackorders() : bool
    {
        return $this->product->getFirstValueOfAttribute('backorders') === 'true';
    }

    private function createStockQtyAttributeAtMaximumPurchasableLevel(ProductAttribute $attribute) : ProductAttribute
    {
        return new ProductAttribute('stock_qty', self::MAX_PURCHASABLE_QTY, $attribute->getContextDataSet());
    }
}
