<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */

$this->startSetup();

/** @var Mage_Core_Model_Config $config */
$config = Mage::getModel('core/config');

$config->saveConfig('web/unsecure/base_js_url', '{{unsecure_base_url}}mage-js/');
$config->saveConfig('web/secure/base_js_url', '{{secure_base_url}}mage-js/');

$this->endSetup();
