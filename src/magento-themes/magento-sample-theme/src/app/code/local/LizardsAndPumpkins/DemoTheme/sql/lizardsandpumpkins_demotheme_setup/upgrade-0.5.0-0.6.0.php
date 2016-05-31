<?php
/**
 * @var Mage_Eav_Model_Entity_Setup $this
 */
?>
<?php

$this->startSetup();

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$pageContents = <<<EOH
<div class="cms-home">
  <div class="row clearfix">
    <div class="col span_6"><img src="{{skin url="images/fashion-1.jpg"}}" alt="Fashion 1" /></div>
    <div class="col span_6">
      <img src="{{skin url="images/fashion-2.jpg"}}" alt="Fashion 2" />
      <div class="hero-text">
        <p><span>Lorem ipsum</span>dolor sit amet consetetur sadipscing elitr</p>
      </div>
    </div>
    <div class="col span_6">
      <img src="{{skin url="images/fashion-3.jpg"}}" alt="Fashion 3" />
      <div class="hero-text">
        <p><span>Lorem ipsum</span>dolor sit amet consetetur sadipscing elitr</p>
      </div>
    </div>
    <div class="col span_6"><img src="{{skin url="images/fashion-4.jpg"}}" alt="Fashion 4" /></div>
  </div>
</div>

{{block type="catalog/product_new" name="home.catalog.product.new" alias="product_homepage" template="catalog/product/new.phtml"}}
EOH;

/** @var Mage_Cms_Model_Page $cmsPage */
$cmsPage = Mage::getModel('cms/page');
$cmsPage->load('home', 'identifier');
$cmsPage->setContent($pageContents);
$cmsPage->save();

$this->endSetup();
