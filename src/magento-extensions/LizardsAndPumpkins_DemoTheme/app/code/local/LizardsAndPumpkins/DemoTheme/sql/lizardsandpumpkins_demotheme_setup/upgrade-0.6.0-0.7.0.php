<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */
?>
<?php

$this->startSetup();

/** @var Mage_Core_Model_Config $config */
$config = Mage::getModel('core/config');
$config->saveConfig('web/url/use_store', 1);

$this->endSetup();
