<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */

$this->startSetup();

/** @var Mage_Core_Model_Config $config */
$config = Mage::getModel('core/config');

$config->saveConfig(Mage_Catalog_Helper_Product::XML_PATH_PRODUCT_URL_SUFFIX, 'html');
$config->saveConfig(Mage_Catalog_Helper_Category::XML_PATH_CATEGORY_URL_SUFFIX, 'html');

$this->endSetup();
