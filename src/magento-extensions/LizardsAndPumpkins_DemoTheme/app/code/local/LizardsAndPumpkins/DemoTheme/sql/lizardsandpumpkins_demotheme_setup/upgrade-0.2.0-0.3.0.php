<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */
?>
<?php

$this->startSetup();

$this->run(
    'INSERT INTO ' . $this->getTable('permission_block') . ' (`block_name`, `is_allowed`) VALUES ("cms/block", 1)'
);

$this->endSetup();
