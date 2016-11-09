<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */
?>
<?php

$this->startSetup();

/** @var Mage_Core_Model_Store $store */
$store = Mage::getModel('core/store');

$store->load('default', 'code');
$store->setData('code', 'en');
$store->save();

$store->load('german', 'code');
$store->setData('code', 'de');
$store->save();

$store->load('french', 'code');
$store->setData('code', 'fr');
$store->save();

$this->endSetup();
