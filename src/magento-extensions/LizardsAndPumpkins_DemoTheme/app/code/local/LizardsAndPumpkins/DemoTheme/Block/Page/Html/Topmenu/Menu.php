<?php

use Mage_Catalog_Model_Resource_Category_Collection as Mage_Category_Collection;

class LizardsAndPumpkins_DemoTheme_Block_Page_Html_Topmenu_Menu extends Mage_Page_Block_Html_Topmenu
{
    private function renderHtml(Mage_Category_Collection $menuItems, int $parentId, int $level) : string
    {
        $items = iterator_to_array($menuItems);

        if (count($items) === 0) {
            return '';
        }

        return array_reduce($items, function ($carry, Mage_Catalog_Model_Category $item) use ($menuItems, $parentId) {
            if ($item->getParentId() !== $parentId) {
                return $carry;
            }

            $subMenu = $this->renderHtml($menuItems, (int) $item->getEntityId(), $item->getLevel());

            return $carry . '
                <li class="nav-level-' . ($item->getLevel() - 1) . '">
                    <a href="' . Mage::getBaseUrl() . $item->getUrlPath() . '">' . $item->getName() . '</a>
                    ' . $this->decorateSubMenu($item, $subMenu) . '
                </li>';
        }, '<ul class="nav-' . $level . '">') . '</ul>';
    }

    private function renderMobileHtml(Mage_Category_Collection $menuItems, int $parentId, int $level) : string
    {
        $items = iterator_to_array($menuItems);

        if (count($items) === 0) {
            return '';
        }

        return array_reduce($items, function ($carry, Mage_Catalog_Model_Category $item) use ($menuItems, $parentId) {
            if ($item->getParentId() !== $parentId) {
                return $carry;
            }

            $result = '<li class="nav-level-' . ($item->getLevel() - 1) . '">';

            $result .= $this->getMobileMenuItemHtml($item);
            $result .= $this->renderMobileHtml($menuItems, (int) $item->getEntityId(), $item->getLevel());

            $result .= '</li>';

            return $carry . $result;
        }, '<ul class="nav-' . $level . '">') . '</ul>';
    }

    private function getCategoryCollection(int $rootCategoryId) : Mage_Category_Collection
    {
        return Mage::getModel('catalog/category')
            ->getCollection()
            ->setStoreId(Mage::app()->getStore()->getId())
            ->addFieldToFilter('path', ['like' => "1/$rootCategoryId/%"])
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('include_in_menu', 1)
            ->addFieldToFilter('level', [2, 3])
            ->addAttributeToSelect(['name', 'url_path', 'entity_id', 'parent_id', 'level']);
    }

    public function mainMenuToHtml() : string
    {
        $rootCategoryId = (int) Mage::app()->getStore()->getRootCategoryId();
        $categories = $this->getCategoryCollection($rootCategoryId);
        $level = 1;

        return $this->renderHtml($categories, $rootCategoryId, $level);
    }

    public function mobileMenuToHtml() : string
    {
        $rootCategoryId = (int) Mage::app()->getStore()->getRootCategoryId();
        $categories = $this->getCategoryCollection($rootCategoryId);
        $level = 1;

        return $this->renderMobileHtml($categories, $rootCategoryId, $level);
    }

    private function decorateSubMenu(Mage_Catalog_Model_Category $category, string $subMenu) : string
    {
        if ($category->getLevel() !== '2') {
            return $subMenu;
        }

        $decoratedHtml = <<<EOH
<div class="sub-level">
    <p class="title"><a href="%s">%s</a></p>
    <div class="content-wrapper">
        <div class="left">%s</div>
        <div class="right">
            <div class="special">%s</div>
        </div>
    </div>
</div>
EOH;
        $categoryUrl = Mage::getBaseUrl() . $category->getUrlPath();
        $categoryTitle = Mage::helper('lizardsandpumpkins_demotheme')->__('Show all of %s', $category->getName());

        $cmsBlock = $this->getLayout()->createBlock('cms/block');
        $cmsBlock->setData('block_id', 'menu_special_' . $category->getEntityId());
        $cmsBlockHtml = $cmsBlock->toHtml();

        return sprintf($decoratedHtml, $categoryUrl, $categoryTitle, $subMenu, $cmsBlockHtml);
    }

    private function getMobileMenuItemHtml(Mage_Catalog_Model_Category $item) : string
    {
        $categoryUrl = Mage::getBaseUrl() . $item->getUrlPath();
        $categoryTitle = $item->getName();

        if ($item->getLevel() === '2') {
            $menuItemHtml = '<a class="show-all" href="%s">%s</a><p class="nav-toggle">%s</p>';
            $showAllTitle = Mage::helper('lizardsandpumpkins_demotheme')->__('Show all');

            return sprintf($menuItemHtml, $categoryUrl, $showAllTitle, $categoryTitle);
        }

        return sprintf('<a href="%s">%s</a>', $categoryUrl, $categoryTitle);
    }
}
