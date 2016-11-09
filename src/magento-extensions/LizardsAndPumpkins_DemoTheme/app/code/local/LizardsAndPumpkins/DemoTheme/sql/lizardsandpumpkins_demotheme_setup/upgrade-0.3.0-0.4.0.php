<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */
?>
<?php

$this->startSetup();

$this->run(
    'DELETE FROM ' . $this->getTable('catalog_category_entity_varchar') . ' WHERE `attribute_id` = 61'
);

$this->endSetup();
