<?php
/**
 * @var Mage_Core_Model_Resource_Setup $this
 */
?>
<?php

$this->startSetup();

/** @var Mage_Core_Model_Config $config */
$config = Mage::getModel('core/config');
$config->saveConfig('design/package/name', 'lizardsandpumpkins');

$this->endSetup();
