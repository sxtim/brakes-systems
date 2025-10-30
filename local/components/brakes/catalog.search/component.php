<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

require_once __DIR__ . '/class.php';

$component = new BrakesCatalogSearchComponent($this);
$component->executeComponent();
