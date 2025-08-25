<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

class MenuComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/.top.menu.php');

        $this->arResult['ITEMS'] = $aMenuLinks;
        $this->includeComponentTemplate();
    }
}
