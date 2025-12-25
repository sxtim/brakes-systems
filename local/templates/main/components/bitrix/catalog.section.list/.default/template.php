<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$buildTree = static function (array $flatSections): array {
    $tree = [];
    $stack = [];

    foreach ($flatSections as $section) {
        $depth = (int)($section['DEPTH_LEVEL'] ?? 0);
        if ($depth < 1) {
            continue;
        }

        $node = [
            'DEPTH_LEVEL' => $depth,
            'NAME' => (string)($section['NAME'] ?? ''),
            'CODE' => (string)($section['CODE'] ?? ''),
            'SECTION_PAGE_URL' => (string)($section['SECTION_PAGE_URL'] ?? ''),
            'SVG' => (string)($section['SVG'] ?? ''),
            'CHILDREN' => [],
        ];

        while (count($stack) >= $depth) {
            array_pop($stack);
        }

        if (empty($stack)) {
            $tree[] = $node;
            $stack = [];
            $stack[] = &$tree[count($tree) - 1];
            continue;
        }

        $parentIndex = count($stack) - 1;
        $parent = &$stack[$parentIndex];
        $parent['CHILDREN'][] = $node;
        $stack[] = &$parent['CHILDREN'][count($parent['CHILDREN']) - 1];
    }

    return $tree;
};

$sectionsTree = $buildTree(is_array($arResult['SECTIONS'] ?? null) ? $arResult['SECTIONS'] : []);
?>
<nav class="menu__body">
    <div class="menu__container">
        <ul class="menu__list">
            <?php foreach ($sectionsTree as $category): ?>
                <li class="menu__item">
                    <div data-fls-spollers="99999,max" class="spollers">
                        <details class="menu-spollers__item spollers__item">
                            <summary class="menu-spollers__title spollers__title">
                                <div class="spollers__icon-box">
                                    <img class="spollers__title-icon"
                                         src="<?= htmlspecialcharsbx($category['SVG']) ?>"
                                         alt="Image">
                                </div>
                                <p class="spollers__title-text">
                                    <?= htmlspecialcharsbx($category['NAME']) ?>
                                </p>
                            </summary>
                            <div class="menu-spollers__body spollers__body">
                                <?php if (!empty($category['CHILDREN'])): ?>
                                    <div data-fls-spollers="99999,max" class="spollers">
                                        <?php foreach ($category['CHILDREN'] as $brand): ?>
                                            <details class="submenu-spollers__item spollers__item">
                                                <summary class="submenu-spollers__title spollers__title">
                                                    <?= htmlspecialcharsbx($brand['NAME']) ?>
                                                </summary>
                                                <div class="submenu-spollers__body spollers__body">
                                                    <?php if (!empty($brand['CHILDREN'])): ?>
                                                        <ul class="submenu-spollers__list">
                                                            <?php foreach ($brand['CHILDREN'] as $model): ?>
                                                                <?php if (!empty($model['CHILDREN'])): ?>
                                                                    <li class="submenu-spollers__li">
                                                                        <details class="submenu-spollers__item spollers__item">
                                                                            <summary class="submenu-spollers__title spollers__title">
                                                                                <?= htmlspecialcharsbx($model['NAME']) ?>
                                                                            </summary>
                                                                            <div class="submenu-spollers__body spollers__body">
                                                                                <ul class="submenu-spollers__list">
                                                                                    <?php foreach ($model['CHILDREN'] as $body): ?>
                                                                                        <li class="submenu-spollers__li">
                                                                                            <a href="<?= htmlspecialcharsbx($body['SECTION_PAGE_URL']) ?>">
                                                                                                <?= htmlspecialcharsbx($body['NAME']) ?>
                                                                                            </a>
                                                                                        </li>
                                                                                    <?php endforeach; ?>
                                                                                </ul>
                                                                            </div>
                                                                        </details>
                                                                    </li>
                                                                <?php else: ?>
                                                                    <li class="submenu-spollers__li">
                                                                        <a href="<?= htmlspecialcharsbx($model['SECTION_PAGE_URL']) ?>">
                                                                            <?= htmlspecialcharsbx($model['NAME']) ?>
                                                                        </a>
                                                                    </li>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
                                            </details>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </details>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
