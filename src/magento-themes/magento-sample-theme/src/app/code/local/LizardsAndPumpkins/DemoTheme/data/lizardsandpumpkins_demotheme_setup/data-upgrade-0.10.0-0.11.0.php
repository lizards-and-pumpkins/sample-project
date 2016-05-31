<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */

$this->startSetup();

/** @var Mage_Core_Model_Config $config */
$config = Mage::getModel('core/config');

$storeIds = array_map(function (Mage_Core_Model_Store $store) {
    return $store->getId();
}, Mage::app()->getStores());

$config->saveConfig('lizardsAndPumpkins/magentoconnector/stores_to_export', implode(',', $storeIds));

$this->endSetup();
