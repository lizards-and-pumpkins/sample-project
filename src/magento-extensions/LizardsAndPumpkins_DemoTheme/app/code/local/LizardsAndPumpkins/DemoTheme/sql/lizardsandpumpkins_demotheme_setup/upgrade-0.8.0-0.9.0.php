<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */

$this->startSetup();

$pathToShare = Mage::getRoot() . '/../../../share';
$pathToImagesDir = $pathToShare . '/product-images';

mkdir($pathToImagesDir);

/** @var Mage_Core_Model_Config $config */
$config = Mage::getModel('core/config');

$config->saveConfig('lizardsAndPumpkins/magentoconnector/local_path_for_product_export', 'file://' . $pathToShare);
$config->saveConfig('lizardsAndPumpkins/magentoconnector/api_url', 'http://demo.lizardsandpumpkins.com.loc/fr/api/');
$config->saveConfig('lizardsAndPumpkins/magentoconnector/image_target', $pathToImagesDir);

$this->endSetup();
