<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

class MenuComponent extends CBitrixComponent
{
    private function resolveMenuType(): string
    {
        $menuType = trim((string)($this->arParams['ROOT_MENU_TYPE'] ?? ''));
        if ($menuType === '') {
            $menuType = trim((string)($this->arParams['MENU_TYPE'] ?? ''));
        }

        return $menuType !== '' ? $menuType : 'top';
    }

    private function getFallbackItems(): array
    {
        $siteDir = defined('SITE_DIR') ? SITE_DIR : '/';
        $menuType = $this->resolveMenuType();

        if ($menuType === 'footer_info') {
            return [
                ['О нас', '/about/', [], [], ''],
                ['Контакты / Схема проезда', '/contacts/', [], [], ''],
                ['Сотрудничество', '#', [], [], ''],
                ['Политика обработки персональных данных', '#', [], [], ''],
            ];
        }

        if ($menuType === 'footer_order') {
            return [
                ['Доставка и оплата', '#', [], [], ''],
                ['Гарантия', '#', [], [], ''],
                ['Оформление заказа', '/personal/order/', [], [], ''],
            ];
        }

        return [
            ['ПРОДУКЦИЯ', '/catalog/', [], ['PRIMARY' => 'Y'], ''],
            ['ДОСТАВКА И ОПЛАТА', '#', [], [], ''],
            ['О НАС', '/about/', [], [], ''],
            ['ГАРАНТИЯ', '#', [], [], ''],
            ['СОТРУДНИЧЕСТВО', '#', [], [], ''],
            ['КОНТАКТЫ', '/contacts/', [], [], ''],
        ];
    }

    private function loadMenuItemsFromTopMenu(): array
    {
        $menuType = $this->resolveMenuType();
        $menuFile = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/.' . $menuType . '.menu.php';
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
