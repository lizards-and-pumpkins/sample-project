<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */
?>
<?php

$this->startSetup();

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$blockContents = <<<EOH
<img src="{{skin url="images/fashion-special.jpg"}}" alt="Menu Special" />
<p><span>At vero eos et accusam et justo duo dolores et ea rebum</span>Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>
EOH;

/** @var Mage_Cms_Model_Block $cmsBlock */
$cmsBlock = Mage::getModel('cms/block');

for ($i = 4; $i <= 9; $i ++) {
    $cmsBlock->setData([
        'title'      => 'Menu Special Block',
        'identifier' => 'menu_special_' . $i,
        'content'    => $blockContents,
        'is_active'  => 1,
        'stores'     => [Mage_Core_Model_App::ADMIN_STORE_ID]
    ]);
    $cmsBlock->save();
}

$this->endSetup();
