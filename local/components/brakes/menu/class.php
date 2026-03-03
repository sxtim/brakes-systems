<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

class MenuComponent extends CBitrixComponent
{
    private function getFallbackItems(): array
    {
        $siteDir = defined('SITE_DIR') ? SITE_DIR : '/';

        return [
            ['ПРОДУКЦИЯ', '/catalog/', [], [], ''],
            ['ДОСТАВКА И ОПЛАТА', '#', [], [], ''],
            ['О НАС', '/about/', [], [], ''],
            ['ГАРАНТИЯ', '#', [], [], ''],
            ['СОТРУДНИЧЕСТВО', '#', [], [], ''],
            ['КОНТАКТЫ', '/contacts/', [], [], ''],
            ['ГЛАВНАЯ', $siteDir, [], [], ''],
        ];
    }

    private function loadMenuItemsFromTopMenu(): array
    {
        $menuFile = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/.top.menu.php';
        if ($menuFile === '' || !is_file($menuFile)) {
            return [];
        }

        $aMenuLinks = [];
        require $menuFile;

        return is_array($aMenuLinks) ? $aMenuLinks : [];
    }

    public function executeComponent()
    {
        $items = $this->loadMenuItemsFromTopMenu();
        if (empty($items)) {
            $items = $this->getFallbackItems();
        }

        $this->arResult['ITEMS'] = $items;
        $this->includeComponentTemplate();
    }
}
