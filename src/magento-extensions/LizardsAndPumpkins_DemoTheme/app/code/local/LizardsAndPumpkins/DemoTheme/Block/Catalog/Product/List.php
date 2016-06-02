<?php

class LizardsAndPumpkins_DemoTheme_Block_Catalog_Product_List extends Mage_Catalog_Block_Product_List
{
    public function isNew(Mage_Catalog_Model_Product $product) : bool 
    {
        return date('Y-m-d') <= substr($product->getData('news_to_date'), 0, 10);
    }

    public function isOnSale(Mage_Catalog_Model_Product $product) : bool 
    {
        return $product->getFinalPrice() < $product->getPrice();
    }

    public function getSavings(Mage_Catalog_Model_Product $product) : string 
    {
        return number_format(100 - $product->getFinalPrice() / $product->getPrice() * 100, 0);
    }
}
