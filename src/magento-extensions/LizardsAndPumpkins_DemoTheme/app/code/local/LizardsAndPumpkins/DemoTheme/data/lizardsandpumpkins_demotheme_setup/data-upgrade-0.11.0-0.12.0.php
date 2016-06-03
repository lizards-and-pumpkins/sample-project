<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */

$this->startSetup();

/** @var Mage_Core_Model_Config $config */
$config = Mage::getModel('core/config');

$config->saveConfig(
    LizardsAndPumpkins_MagentoConnector_Model_Export_Content::XML_SPECIAL_BLOCKS,
    'main.menu,mobile.menu'
);

$this->endSetup();
