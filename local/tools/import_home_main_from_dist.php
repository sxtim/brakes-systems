<?php
@set_time_limit(0);
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

global $USER;

if (!$USER || !$USER->IsAdmin()) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

if (!Loader::includeModule('iblock')) {
    http_response_code(500);
    echo 'Iblock module is not available';
    exit;
}

const SETTINGS_IBLOCK_CODE = 'home_main_settings';
const PRODUCTS_IBLOCK_CODE = 'home_main_products';
const SETTINGS_ELEMENT_CODE = 'main';
const PRODUCT_1_CODE = 'product-1';
const PRODUCT_2_CODE = 'product-2';
const DEFAULT_DIST_INDEX_CANDIDATES = [
    '/dist/brakes_systems_main/index.html',
    '/local/dist/brakes_systems_main/index.html',
    '/local/templates/main/dist/brakes_systems_main/index.html',
    '/upload/dist/brakes_systems_main/index.html',
];

function getIblockIdByCode(string $code): int
{
    $res = CIBlock::GetList([], ['CODE' => $code, 'ACTIVE' => 'Y']);
    $row = $res ? $res->Fetch() : false;
    return (int)($row['ID'] ?? 0);
}

function getElementIdByCode(int $iblockId, string $code): int
{
    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'CODE' => $code],
        false,
        ['nTopCount' => 1],
        ['ID']
    );
    $row = $res ? $res->Fetch() : false;
    return (int)($row['ID'] ?? 0);
}

function ensureProperty(int $iblockId, array $property): array
{
    $code = trim((string)($property['CODE'] ?? ''));
    if ($code === '') {
        return ['created' => false, 'updated' => false];
    }

    $res = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code]);
    $existing = $res ? $res->Fetch() : false;
    if ($existing) {
        $updateFields = [];
        $expectedType = (string)($property['PROPERTY_TYPE'] ?? '');
        $expectedUserType = (string)($property['USER_TYPE'] ?? '');
        $expectedMultiple = (string)($property['MULTIPLE'] ?? '');

        if (($existing['ACTIVE'] ?? 'N') !== 'Y') {
            $updateFields['ACTIVE'] = 'Y';
        }

        if ((string)($existing['CODE'] ?? '') !== $code) {
            $updateFields['CODE'] = $code;
        }

        if ((string)($existing['NAME'] ?? '') !== (string)($property['NAME'] ?? '')) {
            $updateFields['NAME'] = (string)($property['NAME'] ?? '');
        }

        if ((string)($existing['SORT'] ?? '') !== (string)($property['SORT'] ?? '')) {
            $updateFields['SORT'] = (string)($property['SORT'] ?? '');
        }

        if ($expectedType !== '' && (string)($existing['PROPERTY_TYPE'] ?? '') !== $expectedType) {
            $updateFields['PROPERTY_TYPE'] = $expectedType;
        }

        if ((string)($existing['USER_TYPE'] ?? '') !== $expectedUserType) {
            $updateFields['USER_TYPE'] = $expectedUserType;
        }

        if ($expectedMultiple !== '' && (string)($existing['MULTIPLE'] ?? '') !== $expectedMultiple) {
            $updateFields['MULTIPLE'] = $expectedMultiple;
        }

        if (!empty($updateFields)) {
            $prop = new CIBlockProperty();
            $result = $prop->Update((int)$existing['ID'], $updateFields);
            if (!$result) {
                throw new RuntimeException('Failed to update property ' . $code . ': ' . (string)$prop->LAST_ERROR);
            }

            return ['created' => false, 'updated' => true];
        }

        return ['created' => false, 'updated' => false];
    }

    $fields = $property;
    $fields['IBLOCK_ID'] = $iblockId;
    $fields['ACTIVE'] = 'Y';
    $fields['FILTRABLE'] = 'N';
    $fields['SEARCHABLE'] = 'N';

    $prop = new CIBlockProperty();
    $result = $prop->Add($fields);
    if (!$result) {
        throw new RuntimeException('Failed to create property ' . $code . ': ' . (string)$prop->LAST_ERROR);
    }

    return ['created' => true, 'updated' => false];
}

function ensureHomeMainProperties(int $settingsIblockId, int $productsIblockId): array
{
    $settingsCreated = 0;
    $settingsUpdated = 0;
    $productsCreated = 0;
    $productsUpdated = 0;

    $settingsProperties = [
        ['NAME' => 'Видео файл', 'CODE' => 'VIDEO_FILE', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'N', 'SORT' => 100],
        ['NAME' => 'Ссылка на видео', 'CODE' => 'VIDEO_URL', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 110],
        ['NAME' => 'Заголовок 1', 'CODE' => 'TITLE_1', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 120],
        ['NAME' => 'Заголовок 2', 'CODE' => 'TITLE_2', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 130],
        ['NAME' => 'Логотипы в слайде', 'CODE' => 'LOGO_SLIDER', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'Y', 'SORT' => 140],
        ['NAME' => 'Наши проекты', 'CODE' => 'PROJECTS', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'Y', 'SORT' => 150],
        ['NAME' => 'О нас: заголовок', 'CODE' => 'ABOUT_TITLE', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 160],
        ['NAME' => 'О нас: текст', 'CODE' => 'ABOUT_TEXT', 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'HTML', 'MULTIPLE' => 'N', 'SORT' => 170],
        ['NAME' => 'О нас: фото', 'CODE' => 'ABOUT_PHOTO', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'N', 'SORT' => 180],
        ['NAME' => 'Сертификаты', 'CODE' => 'CERTIFICATES', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'Y', 'SORT' => 190],
    ];

    foreach ($settingsProperties as $property) {
        $result = ensureProperty($settingsIblockId, $property);
        if ($result['created']) {
            $settingsCreated++;
        }
        if ($result['updated']) {
            $settingsUpdated++;
        }
    }

    $productProperties = [
        ['NAME' => 'Подзаголовок', 'CODE' => 'SUBTITLE', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 100],
        ['NAME' => 'Текст (HTML)', 'CODE' => 'TEXT_HTML', 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'HTML', 'MULTIPLE' => 'N', 'SORT' => 110],
        ['NAME' => 'Характеристики (HTML)', 'CODE' => 'FEATURES_HTML', 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'HTML', 'MULTIPLE' => 'N', 'SORT' => 120],
        ['NAME' => 'Фото', 'CODE' => 'PHOTO', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'N', 'SORT' => 130],
    ];

    foreach ($productProperties as $property) {
        $result = ensureProperty($productsIblockId, $property);
        if ($result['created']) {
            $productsCreated++;
        }
        if ($result['updated']) {
            $productsUpdated++;
        }
    }

    return [
        'SETTINGS_TOTAL' => count($settingsProperties),
        'SETTINGS_CREATED' => $settingsCreated,
        'SETTINGS_UPDATED' => $settingsUpdated,
        'PRODUCTS_TOTAL' => count($productProperties),
        'PRODUCTS_CREATED' => $productsCreated,
        'PRODUCTS_UPDATED' => $productsUpdated,
    ];
}

function getIblockPropertyMap(int $iblockId): array
{
    $result = [];
    $res = CIBlockProperty::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => $iblockId]);
    while ($row = $res->Fetch()) {
        $code = (string)($row['CODE'] ?? '');
        if ($code === '') {
            continue;
        }

        $result[$code] = [
            'ID' => (int)($row['ID'] ?? 0),
            'TYPE' => (string)($row['PROPERTY_TYPE'] ?? ''),
            'USER_TYPE' => (string)($row['USER_TYPE'] ?? ''),
            'MULTIPLE' => (string)($row['MULTIPLE'] ?? ''),
            'ACTIVE' => (string)($row['ACTIVE'] ?? ''),
        ];
    }

    return $result;
}

function resolvePropertyKeyByCode(array $propertyMap, string $expectedCode)
{
    $expectedCode = mb_strtoupper(trim($expectedCode));
    foreach ($propertyMap as $actualCode => $meta) {
        if (mb_strtoupper((string)$actualCode) === $expectedCode) {
            return (string)$actualCode;
        }
    }

    return $expectedCode;
}

function getPropertyByCodeInsensitive(array $properties, string $expectedCode): array
{
    $expectedCode = mb_strtoupper(trim($expectedCode));
    foreach ($properties as $code => $property) {
        if (mb_strtoupper((string)$code) === $expectedCode) {
            return is_array($property) ? $property : [];
        }
    }

    return [];
}

function getPropertyMetaByCodeInsensitive(array $propertyMap, string $expectedCode): ?array
{
    $expectedCode = mb_strtoupper(trim($expectedCode));
    foreach ($propertyMap as $actualCode => $meta) {
        if (mb_strtoupper((string)$actualCode) === $expectedCode) {
            $meta['CODE'] = (string)$actualCode;
            return is_array($meta) ? $meta : null;
        }
    }

    return null;
}

function getElementPropertiesById(int $iblockId, int $elementId): array
{
    $res = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ID' => $elementId],
        false,
        ['nTopCount' => 1],
        ['ID', 'IBLOCK_ID']
    );

    $element = $res ? $res->GetNextElement() : false;
    if (!$element) {
        return [];
    }

    return (array)$element->GetProperties();
}

function getPropertyTextValue(array $property): string
{
    $value = $property['VALUE'] ?? '';
    if (is_array($value)) {
        return trim((string)($value['TEXT'] ?? ''));
    }

    return trim((string)$value);
}

function getPropertyFileCount(array $property): int
{
    $value = $property['VALUE'] ?? [];
    $items = is_array($value) ? $value : [$value];

    $count = 0;
    foreach ($items as $item) {
        if ((int)$item > 0) {
            $count++;
        }
    }

    return $count;
}

function createElement(int $iblockId, string $code, string $name, int $sort): int
{
    $el = new CIBlockElement();
    $id = (int)$el->Add([
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'CODE' => $code,
        'NAME' => $name,
        'SORT' => $sort,
    ]);

    if ($id <= 0) {
        throw new RuntimeException((string)$el->LAST_ERROR);
    }

    return $id;
}

function ensureElement(int $iblockId, string $code, string $name, int $sort): int
{
    $id = getElementIdByCode($iblockId, $code);
    if ($id > 0) {
        return $id;
    }

    return createElement($iblockId, $code, $name, $sort);
}

function queryOne(DOMXPath $xpath, string $query, ?DOMNode $contextNode = null): ?DOMNode
{
    $nodes = $contextNode ? $xpath->query($query, $contextNode) : $xpath->query($query);
    if (!$nodes || $nodes->length === 0) {
        return null;
    }

    return $nodes->item(0);
}

function queryAll(DOMXPath $xpath, string $query, ?DOMNode $contextNode = null): array
{
    $nodes = $contextNode ? $xpath->query($query, $contextNode) : $xpath->query($query);
    if (!$nodes) {
        return [];
    }

    $result = [];
    foreach ($nodes as $node) {
        $result[] = $node;
    }

    return $result;
}

function queryOneByVariants(DOMXPath $xpath, array $queries, ?DOMNode $contextNode = null): ?DOMNode
{
    foreach ($queries as $query) {
        $node = queryOne($xpath, (string)$query, $contextNode);
        if ($node !== null) {
            return $node;
        }
    }

    return null;
}

function queryAllByVariants(DOMXPath $xpath, array $queries, ?DOMNode $contextNode = null): array
{
    foreach ($queries as $query) {
        $nodes = queryAll($xpath, (string)$query, $contextNode);
        if (!empty($nodes)) {
            return $nodes;
        }
    }

    return [];
}

function innerHtml(DOMNode $node): string
{
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }

    return trim($html);
}

function resolveDistFilePath(string $rawPath, string $distRootAbsolute): string
{
    $rawPath = trim($rawPath);
    if ($rawPath === '') {
        return '';
    }

    if (preg_match('~^https?://~i', $rawPath)) {
        return '';
    }

    $path = (string)(parse_url($rawPath, PHP_URL_PATH) ?? $rawPath);
    $distRoot = rtrim($distRootAbsolute, '/');

    if (strpos($path, '/') === 0) {
        $absolute = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $path;
    } else {
        $absolute = $distRoot . '/' . ltrim($path, '/');
    }

    if (!is_file($absolute)) {
        return '';
    }

    return $absolute;
}

function resolveFirstExistingDistPath(array $rawPaths, string $distRootAbsolute): string
{
    foreach ($rawPaths as $rawPath) {
        $resolved = resolveDistFilePath((string)$rawPath, $distRootAbsolute);
        if ($resolved !== '') {
            return $resolved;
        }
    }

    return '';
}

function makeFileValue(string $absolutePath): ?array
{
    if ($absolutePath === '' || !is_file($absolutePath)) {
        return null;
    }

    $file = CFile::MakeFileArray($absolutePath);
    if (!is_array($file) || empty($file['tmp_name'])) {
        return null;
    }

    $file['MODULE_ID'] = 'iblock';
    return $file;
}

function setSingleFileProperty(int $elementId, int $iblockId, $propertyKey, string $absolutePath): void
{
    $file = makeFileValue($absolutePath);
    if ($file === null) {
        return;
    }

    // For single file properties SetPropertyValuesEx expects plain file array.
    CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
        $propertyKey => $file,
    ]);
}

function setMultipleFileProperty(int $elementId, int $iblockId, $propertyKey, array $absolutePaths): void
{
    $values = [];
    $index = 0;
    foreach ($absolutePaths as $absolutePath) {
        $file = makeFileValue((string)$absolutePath);
        if ($file === null) {
            continue;
        }
        $values['n' . $index] = ['VALUE' => $file, 'DESCRIPTION' => ''];
        $index++;
    }

    if (empty($values)) {
        return;
    }

    CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [$propertyKey => false]);
    CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [$propertyKey => $values]);
}

function extractImagesFromNodes(array $nodes, string $attribute = 'src'): array
{
    $result = [];
    foreach ($nodes as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }
        $value = trim((string)$node->getAttribute($attribute));
        if ($value === '') {
            continue;
        }
        $result[] = $value;
    }

    return $result;
}

function splitHeroTitles(string $html): array
{
    $parts = preg_split('/<br\\s*\\/?\\s*>/iu', $html);
    $parts = is_array($parts) ? $parts : [];
    $parts = array_map(static fn($item) => trim(strip_tags((string)$item)), $parts);
    $parts = array_values(array_filter($parts, static fn($item) => $item !== ''));

    return [
        'TITLE_1' => (string)($parts[0] ?? ''),
        'TITLE_2' => (string)($parts[1] ?? ''),
    ];
}

function updateElementName(int $elementId, string $name): void
{
    if ($elementId <= 0 || trim($name) === '') {
        return;
    }

    $el = new CIBlockElement();
    $el->Update($elementId, ['NAME' => trim($name)]);
}

function htmlPropertyValue(string $html): array
{
    return [
        'VALUE' => [
            'TEXT' => $html,
            'TYPE' => 'HTML',
        ],
    ];
}

function resolveIndexSourcePath(): array
{
    $docRoot = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');
    $checked = [];

    $source = trim((string)($_GET['source'] ?? ''));
    if ($source !== '') {
        // Allow explicit index source path relative to DOCUMENT_ROOT.
        $source = '/' . ltrim($source, '/');
        if (strpos($source, '..') !== false) {
            throw new RuntimeException('Invalid source path.');
        }

        $candidate = $docRoot . $source;
        $checked[] = $source;
        if (is_file($candidate)) {
            return [$candidate, dirname($candidate), $checked];
        }
    }

    foreach (DEFAULT_DIST_INDEX_CANDIDATES as $relativePath) {
        $candidate = $docRoot . $relativePath;
        $checked[] = $relativePath;
        if (is_file($candidate)) {
            return [$candidate, dirname($candidate), $checked];
        }
    }

    $distRoot = $docRoot . '/dist';
    if (is_dir($distRoot)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($distRoot, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue;
            }

            if (mb_strtolower($fileInfo->getFilename()) !== 'index.html') {
                continue;
            }

            $path = (string)$fileInfo->getPathname();
            $relative = str_replace($docRoot, '', $path);
            $checked[] = $relative;

            $sample = (string)file_get_contents($path, false, null, 0, 250000);
            if (
                strpos($sample, 'home_block') !== false
                || strpos($sample, 'home-main__hero') !== false
            ) {
                return [$path, dirname($path), $checked];
            }
        }
    }

    return ['', '', $checked];
}

try {
    $settingsIblockId = getIblockIdByCode(SETTINGS_IBLOCK_CODE);
    $productsIblockId = getIblockIdByCode(PRODUCTS_IBLOCK_CODE);

    if ($settingsIblockId <= 0 || $productsIblockId <= 0) {
        throw new RuntimeException(
            'Iblocks not found. Run /local/tools/setup_home_main_iblocks.php first.'
        );
    }

    $propertyEnsureStats = ensureHomeMainProperties($settingsIblockId, $productsIblockId);
    $settingsPropertyMap = getIblockPropertyMap($settingsIblockId);
    $productsPropertyMap = getIblockPropertyMap($productsIblockId);

    $settingsKeys = [
        'VIDEO_FILE' => resolvePropertyKeyByCode($settingsPropertyMap, 'VIDEO_FILE'),
        'VIDEO_URL' => resolvePropertyKeyByCode($settingsPropertyMap, 'VIDEO_URL'),
        'TITLE_1' => resolvePropertyKeyByCode($settingsPropertyMap, 'TITLE_1'),
        'TITLE_2' => resolvePropertyKeyByCode($settingsPropertyMap, 'TITLE_2'),
        'LOGO_SLIDER' => resolvePropertyKeyByCode($settingsPropertyMap, 'LOGO_SLIDER'),
        'PROJECTS' => resolvePropertyKeyByCode($settingsPropertyMap, 'PROJECTS'),
        'ABOUT_TITLE' => resolvePropertyKeyByCode($settingsPropertyMap, 'ABOUT_TITLE'),
        'ABOUT_TEXT' => resolvePropertyKeyByCode($settingsPropertyMap, 'ABOUT_TEXT'),
        'ABOUT_PHOTO' => resolvePropertyKeyByCode($settingsPropertyMap, 'ABOUT_PHOTO'),
        'CERTIFICATES' => resolvePropertyKeyByCode($settingsPropertyMap, 'CERTIFICATES'),
    ];

    $productsKeys = [
        'SUBTITLE' => resolvePropertyKeyByCode($productsPropertyMap, 'SUBTITLE'),
        'TEXT_HTML' => resolvePropertyKeyByCode($productsPropertyMap, 'TEXT_HTML'),
        'FEATURES_HTML' => resolvePropertyKeyByCode($productsPropertyMap, 'FEATURES_HTML'),
        'PHOTO' => resolvePropertyKeyByCode($productsPropertyMap, 'PHOTO'),
    ];

    $settingsElementId = ensureElement($settingsIblockId, SETTINGS_ELEMENT_CODE, 'Главная', 100);
    $product1ElementId = ensureElement($productsIblockId, PRODUCT_1_CODE, 'Товар 1', 100);
    $product2ElementId = ensureElement($productsIblockId, PRODUCT_2_CODE, 'Товар 2', 200);

    [$indexPath, $distRootAbsolute, $checkedCandidates] = resolveIndexSourcePath();
    if ($indexPath === '') {
        throw new RuntimeException(
            'Dist index not found. Checked: ' . implode(', ', $checkedCandidates)
        );
    }

    $html = (string)file_get_contents($indexPath);
    if ($html === '') {
        throw new RuntimeException('Dist index is empty.');
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $heroTitles = ['TITLE_1' => '', 'TITLE_2' => ''];

    $heroH1Node = queryOneByVariants($xpath, [
        "//section[contains(@class,'home_block')]//h1",
        "//section[contains(@class,'home-main__hero')]//h1",
    ]);
    if ($heroH1Node !== null) {
        $heroTitles = splitHeroTitles(innerHtml($heroH1Node));
    }

    if ($heroTitles['TITLE_1'] === '' && $heroTitles['TITLE_2'] === '') {
        $heroTitleNode = queryOneByVariants($xpath, [
            "//section[contains(@class,'home-main__hero')]//*[contains(@class,'home-main__hero-title')]",
        ]);
        if ($heroTitleNode !== null) {
            $heroSpanNodes = queryAll($xpath, ".//span", $heroTitleNode);
            if (!empty($heroSpanNodes)) {
                $heroTitles['TITLE_1'] = trim((string)($heroSpanNodes[0]->textContent ?? ''));
                $heroTitles['TITLE_2'] = trim((string)($heroSpanNodes[1]->textContent ?? ''));
            } else {
                $heroTitles = splitHeroTitles(innerHtml($heroTitleNode));
            }
        }
    }

    $videoSourceNode = queryOneByVariants($xpath, [
        "//section[contains(@class,'home_block')]//video/source",
        "//section[contains(@class,'home-main__hero')]//video/source",
    ]);
    $videoSource = $videoSourceNode instanceof DOMElement ? (string)$videoSourceNode->getAttribute('src') : '';
    $videoFilePath = resolveDistFilePath($videoSource, $distRootAbsolute);

    $logoImageNodes = queryAllByVariants($xpath, [
        "//section[contains(@class,'partners_block') and not(contains(@class,'second_partners_group'))]//div[contains(@class,'partners_group')]//img",
        "//section[contains(@class,'home-main__logos')]//img",
    ]);
    $logoSources = extractImagesFromNodes($logoImageNodes, 'src');
    $logoFilePaths = array_values(array_filter(array_map(
        static fn(string $src): string => resolveDistFilePath($src, $distRootAbsolute),
        $logoSources
    )));

    $projectNodes = queryAllByVariants($xpath, [
        "//section[contains(@class,'about_projects')]//div[contains(@class,'swiper-slide')]//a[contains(@class,'img_project')]",
        "//section[contains(@class,'home-main__projects')]//*[contains(@class,'home-main__gallery-item')]",
        "//section[contains(@class,'home-main__projects')]//img",
    ]);
    $projectSources = [];
    foreach ($projectNodes as $projectNode) {
        if (!$projectNode instanceof DOMElement) {
            continue;
        }

        if (mb_strtolower($projectNode->tagName) === 'img') {
            $src = trim((string)$projectNode->getAttribute('src'));
            if ($src !== '') {
                $projectSources[] = $src;
            }
            continue;
        }

        $href = trim((string)$projectNode->getAttribute('href'));
        if ($href !== '') {
            $projectSources[] = $href;
            continue;
        }

        $imgNode = queryOne($xpath, ".//img", $projectNode);
        if ($imgNode instanceof DOMElement) {
            $src = trim((string)$imgNode->getAttribute('src'));
            if ($src !== '') {
                $projectSources[] = $src;
            }
        }
    }
    $projectFilePaths = array_values(array_filter(array_map(
        static fn(string $src): string => resolveDistFilePath($src, $distRootAbsolute),
        $projectSources
    )));

    $aboutTitleNode = queryOneByVariants($xpath, [
        "//section[contains(@class,'about_us')]//h2",
        "//section[contains(@class,'home-main__about')]//*[contains(@class,'home-main__section-title')]",
    ]);
    $aboutTitle = $aboutTitleNode ? trim($aboutTitleNode->textContent) : '';

    $aboutTextNode = queryOneByVariants($xpath, [
        "//section[contains(@class,'about_us')]//div[contains(@class,'left_text')]",
        "//section[contains(@class,'home-main__about')]//*[contains(@class,'home-main__about-html')]",
    ]);
    $aboutTextHtml = $aboutTextNode ? innerHtml($aboutTextNode) : '';

    $aboutPhotoNode = queryOneByVariants($xpath, [
        "//section[contains(@class,'about_us')]//img[contains(@class,'img_cars')]",
        "//section[contains(@class,'home-main__about')]//*[contains(@class,'home-main__about-media')]//img",
    ]);
    $aboutPhotoSource = $aboutPhotoNode instanceof DOMElement ? trim((string)$aboutPhotoNode->getAttribute('src')) : '';
    $aboutPhotoPath = resolveDistFilePath($aboutPhotoSource, $distRootAbsolute);

    $certificateNodes = queryAllByVariants($xpath, [
        "//section[contains(@class,'sertificates_block')]//div[contains(@class,'sertificates_group')]//a[contains(@class,'sertificate')]",
        "//section[contains(@class,'home-main__certificates')]//*[contains(@class,'home-main__gallery-item')]",
        "//section[contains(@class,'home-main__certificates')]//img",
    ]);
    $certificateSources = [];
    foreach ($certificateNodes as $certificateNode) {
        if (!$certificateNode instanceof DOMElement) {
            continue;
        }

        if (mb_strtolower($certificateNode->tagName) === 'img') {
            $src = trim((string)$certificateNode->getAttribute('src'));
            if ($src !== '') {
                $certificateSources[] = $src;
            }
            continue;
        }

        $href = trim((string)$certificateNode->getAttribute('href'));
        if ($href !== '') {
            $certificateSources[] = $href;
            continue;
        }

        $imgNode = queryOne($xpath, ".//img", $certificateNode);
        if ($imgNode instanceof DOMElement) {
            $src = trim((string)$imgNode->getAttribute('src'));
            if ($src !== '') {
                $certificateSources[] = $src;
            }
        }
    }
    $certificateFilePaths = array_values(array_filter(array_map(
        static fn(string $src): string => resolveDistFilePath($src, $distRootAbsolute),
        $certificateSources
    )));

    $productNodes = queryAllByVariants($xpath, [
        "//section[contains(@class,'about_products')]//div[contains(@class,'about_block')]/div[contains(@class,'product')]",
        "//section[contains(@class,'home-main__products')]//article[contains(@class,'home-main-product')]",
    ]);

    $productData = [];
    foreach ($productNodes as $productNode) {
        if (!$productNode instanceof DOMElement) {
            continue;
        }

        $isNewLayoutProduct = queryOne($xpath, ".//*[contains(@class,'home-main-product__title')]", $productNode) !== null;

        if ($isNewLayoutProduct) {
            $titleNode = queryOne($xpath, ".//*[contains(@class,'home-main-product__title')][1]", $productNode);
            $subtitleNode = queryOne($xpath, ".//*[contains(@class,'home-main-product__subtitle')][1]", $productNode);
            $textNode = queryOne($xpath, ".//*[contains(@class,'home-main-product__text')][1]", $productNode);
            $featuresNode = queryOne($xpath, ".//*[contains(@class,'home-main-product__features')][1]", $productNode);
            $photoNode = queryOneByVariants($xpath, [
                ".//*[contains(@class,'home-main-product__media')]//img[1]",
                ".//*[contains(@class,'img_group')]//img[contains(@class,'img_product')][1]",
                ".//img[1]",
            ], $productNode);
        } else {
            $titleNode = queryOne($xpath, ".//div[contains(@class,'text_product')]//h3[1]", $productNode);
            $subtitleNode = queryOne($xpath, ".//div[contains(@class,'text_product')]//h4[1]", $productNode);
            $textNode = queryOne($xpath, ".//div[contains(@class,'text_product')]//p[1]", $productNode);
            $featuresNode = queryOne($xpath, ".//div[contains(@class,'text_product')]//div[contains(@class,'descriptions_group')][1]", $productNode);
            $photoNode = queryOneByVariants($xpath, [
                ".//img[contains(@class,'product_img')][1]",
                ".//div[contains(@class,'img_group')]//img[contains(@class,'img_product')][1]",
                ".//img[1]",
            ], $productNode);
        }

        $photoSource = $photoNode instanceof DOMElement ? trim((string)$photoNode->getAttribute('src')) : '';

        $productData[] = [
            'TITLE' => $titleNode ? trim($titleNode->textContent) : '',
            'SUBTITLE' => $subtitleNode ? trim($subtitleNode->textContent) : '',
            'TEXT_HTML' => $textNode ? innerHtml($textNode) : '',
            'FEATURES_HTML' => $featuresNode ? innerHtml($featuresNode) : '',
            'PHOTO_SOURCE' => $photoSource,
            'PHOTO_PATH' => resolveDistFilePath($photoSource, $distRootAbsolute),
        ];

        if (count($productData) >= 2) {
            break;
        }
    }

    // If product image was not parsed from markup, use deterministic fallback files from dist.
    if (!empty($productData[0]) && (string)($productData[0]['PHOTO_PATH'] ?? '') === '') {
        $productData[0]['PHOTO_PATH'] = resolveFirstExistingDistPath([
            'img/product.png',
            '/local/templates/main/assets/home-main-dist/img/product.png',
            'img/product2.png',
            '/local/templates/main/assets/home-main-dist/img/product2.png',
        ], $distRootAbsolute);
    }

    if (!empty($productData[1]) && (string)($productData[1]['PHOTO_PATH'] ?? '') === '') {
        $productData[1]['PHOTO_PATH'] = resolveFirstExistingDistPath([
            'img/product1.png',
            '/local/templates/main/assets/home-main-dist/img/product1.png',
            'img/product3.png',
            '/local/templates/main/assets/home-main-dist/img/product3.png',
        ], $distRootAbsolute);
    }

    $settingsValues = [];
    if ($heroTitles['TITLE_1'] !== '') {
        $settingsValues[$settingsKeys['TITLE_1']] = $heroTitles['TITLE_1'];
    }
    if ($heroTitles['TITLE_2'] !== '') {
        $settingsValues[$settingsKeys['TITLE_2']] = $heroTitles['TITLE_2'];
    }
    if ($aboutTitle !== '') {
        $settingsValues[$settingsKeys['ABOUT_TITLE']] = $aboutTitle;
    }
    if ($aboutTextHtml !== '') {
        $settingsValues[$settingsKeys['ABOUT_TEXT']] = htmlPropertyValue($aboutTextHtml);
    }

    if (!empty($settingsValues)) {
        CIBlockElement::SetPropertyValuesEx($settingsElementId, $settingsIblockId, $settingsValues);
    }

    setSingleFileProperty($settingsElementId, $settingsIblockId, $settingsKeys['VIDEO_FILE'], $videoFilePath);
    setMultipleFileProperty($settingsElementId, $settingsIblockId, $settingsKeys['LOGO_SLIDER'], $logoFilePaths);
    setMultipleFileProperty($settingsElementId, $settingsIblockId, $settingsKeys['PROJECTS'], $projectFilePaths);
    setSingleFileProperty($settingsElementId, $settingsIblockId, $settingsKeys['ABOUT_PHOTO'], $aboutPhotoPath);
    setMultipleFileProperty($settingsElementId, $settingsIblockId, $settingsKeys['CERTIFICATES'], $certificateFilePaths);

    if (!empty($productData[0])) {
        updateElementName($product1ElementId, (string)$productData[0]['TITLE']);
        $product1Values = [];
        if ((string)$productData[0]['SUBTITLE'] !== '') {
            $product1Values[$productsKeys['SUBTITLE']] = (string)$productData[0]['SUBTITLE'];
        }
        if ((string)$productData[0]['TEXT_HTML'] !== '') {
            $product1Values[$productsKeys['TEXT_HTML']] = htmlPropertyValue((string)$productData[0]['TEXT_HTML']);
        }
        if ((string)$productData[0]['FEATURES_HTML'] !== '') {
            $product1Values[$productsKeys['FEATURES_HTML']] = htmlPropertyValue((string)$productData[0]['FEATURES_HTML']);
        }

        if (!empty($product1Values)) {
            CIBlockElement::SetPropertyValuesEx($product1ElementId, $productsIblockId, $product1Values);
        }

        setSingleFileProperty($product1ElementId, $productsIblockId, $productsKeys['PHOTO'], (string)$productData[0]['PHOTO_PATH']);
    }

    if (!empty($productData[1])) {
        updateElementName($product2ElementId, (string)$productData[1]['TITLE']);
        $product2Values = [];
        if ((string)$productData[1]['SUBTITLE'] !== '') {
            $product2Values[$productsKeys['SUBTITLE']] = (string)$productData[1]['SUBTITLE'];
        }
        if ((string)$productData[1]['TEXT_HTML'] !== '') {
            $product2Values[$productsKeys['TEXT_HTML']] = htmlPropertyValue((string)$productData[1]['TEXT_HTML']);
        }
        if ((string)$productData[1]['FEATURES_HTML'] !== '') {
            $product2Values[$productsKeys['FEATURES_HTML']] = htmlPropertyValue((string)$productData[1]['FEATURES_HTML']);
        }

        if (!empty($product2Values)) {
            CIBlockElement::SetPropertyValuesEx($product2ElementId, $productsIblockId, $product2Values);
        }

        setSingleFileProperty($product2ElementId, $productsIblockId, $productsKeys['PHOTO'], (string)$productData[1]['PHOTO_PATH']);
    }

    $settingsProps = getElementPropertiesById($settingsIblockId, $settingsElementId);
    $product1Props = getElementPropertiesById($productsIblockId, $product1ElementId);
    $product2Props = getElementPropertiesById($productsIblockId, $product2ElementId);

    $settingsSchema = [];
    foreach (['VIDEO_FILE', 'VIDEO_URL', 'TITLE_1', 'TITLE_2', 'LOGO_SLIDER', 'PROJECTS', 'ABOUT_TITLE', 'ABOUT_TEXT', 'ABOUT_PHOTO', 'CERTIFICATES'] as $code) {
        $meta = getPropertyMetaByCodeInsensitive($settingsPropertyMap, $code);
        if ($meta !== null) {
            $settingsSchema[] = $code
                . '(id=' . (int)$meta['ID']
                . ',actual=' . $meta['CODE']
                . ',type=' . $meta['TYPE']
                . ',user=' . ($meta['USER_TYPE'] !== '' ? $meta['USER_TYPE'] : '-')
                . ',m=' . $meta['MULTIPLE']
                . ',a=' . $meta['ACTIVE'] . ')';
        } else {
            $settingsSchema[] = $code . '(missing)';
        }
    }

    $productsSchema = [];
    foreach (['SUBTITLE', 'TEXT_HTML', 'FEATURES_HTML', 'PHOTO'] as $code) {
        $meta = getPropertyMetaByCodeInsensitive($productsPropertyMap, $code);
        if ($meta !== null) {
            $productsSchema[] = $code
                . '(id=' . (int)$meta['ID']
                . ',actual=' . $meta['CODE']
                . ',type=' . $meta['TYPE']
                . ',user=' . ($meta['USER_TYPE'] !== '' ? $meta['USER_TYPE'] : '-')
                . ',m=' . $meta['MULTIPLE']
                . ',a=' . $meta['ACTIVE'] . ')';
        } else {
            $productsSchema[] = $code . '(missing)';
        }
    }

    echo 'Import complete.<br>';
    echo 'Source index: ' . htmlspecialcharsbx(str_replace((string)$_SERVER['DOCUMENT_ROOT'], '', $indexPath)) . '.<br>';
    echo 'Properties ensured: settings created=' . (int)$propertyEnsureStats['SETTINGS_CREATED']
        . ', updated=' . (int)$propertyEnsureStats['SETTINGS_UPDATED']
        . ', total=' . (int)$propertyEnsureStats['SETTINGS_TOTAL']
        . '; products created=' . (int)$propertyEnsureStats['PRODUCTS_CREATED']
        . ', updated=' . (int)$propertyEnsureStats['PRODUCTS_UPDATED']
        . ', total=' . (int)$propertyEnsureStats['PRODUCTS_TOTAL'] . '.<br>';
    echo 'Settings: updated.<br>';
    echo 'Products: updated (' . count($productData) . ' parsed).<br>';
    echo 'Logos: ' . count($logoFilePaths) . '.<br>';
    echo 'Projects: ' . count($projectFilePaths) . '.<br>';
    echo 'Certificates: ' . count($certificateFilePaths) . '.<br>';
    echo 'Video file: ' . ($videoFilePath !== '' ? 'set' : 'not found') . '.<br>';
    echo 'Readback settings: '
        . 'TITLE_1=' . (getPropertyTextValue(getPropertyByCodeInsensitive($settingsProps, 'TITLE_1')) !== '' ? 'set' : 'empty')
        . ', TITLE_2=' . (getPropertyTextValue(getPropertyByCodeInsensitive($settingsProps, 'TITLE_2')) !== '' ? 'set' : 'empty')
        . ', LOGO_SLIDER=' . getPropertyFileCount(getPropertyByCodeInsensitive($settingsProps, 'LOGO_SLIDER')) . ''
        . ', PROJECTS=' . getPropertyFileCount(getPropertyByCodeInsensitive($settingsProps, 'PROJECTS')) . ''
        . ', ABOUT_TITLE=' . (getPropertyTextValue(getPropertyByCodeInsensitive($settingsProps, 'ABOUT_TITLE')) !== '' ? 'set' : 'empty')
        . ', ABOUT_TEXT=' . (getPropertyTextValue(getPropertyByCodeInsensitive($settingsProps, 'ABOUT_TEXT')) !== '' ? 'set' : 'empty')
        . ', ABOUT_PHOTO=' . (getPropertyFileCount(getPropertyByCodeInsensitive($settingsProps, 'ABOUT_PHOTO')) > 0 ? 'set' : 'empty')
        . ', CERTIFICATES=' . getPropertyFileCount(getPropertyByCodeInsensitive($settingsProps, 'CERTIFICATES')) . '.<br>';
    echo 'Readback product-1: '
        . 'TEXT=' . (getPropertyTextValue(getPropertyByCodeInsensitive($product1Props, 'TEXT_HTML')) !== '' ? 'set' : 'empty')
        . ', FEATURES=' . (getPropertyTextValue(getPropertyByCodeInsensitive($product1Props, 'FEATURES_HTML')) !== '' ? 'set' : 'empty')
        . ', PHOTO=' . (getPropertyFileCount(getPropertyByCodeInsensitive($product1Props, 'PHOTO')) > 0 ? 'set' : 'empty') . '.<br>';
    echo 'Readback product-2: '
        . 'TEXT=' . (getPropertyTextValue(getPropertyByCodeInsensitive($product2Props, 'TEXT_HTML')) !== '' ? 'set' : 'empty')
        . ', FEATURES=' . (getPropertyTextValue(getPropertyByCodeInsensitive($product2Props, 'FEATURES_HTML')) !== '' ? 'set' : 'empty')
        . ', PHOTO=' . (getPropertyFileCount(getPropertyByCodeInsensitive($product2Props, 'PHOTO')) > 0 ? 'set' : 'empty') . '.<br>';
    echo 'Product photo sources: '
        . 'p1=' . htmlspecialcharsbx((string)($productData[0]['PHOTO_SOURCE'] ?? ''))
        . ' -> ' . htmlspecialcharsbx(str_replace((string)$_SERVER['DOCUMENT_ROOT'], '', (string)($productData[0]['PHOTO_PATH'] ?? '')))
        . '; p2=' . htmlspecialcharsbx((string)($productData[1]['PHOTO_SOURCE'] ?? ''))
        . ' -> ' . htmlspecialcharsbx(str_replace((string)$_SERVER['DOCUMENT_ROOT'], '', (string)($productData[1]['PHOTO_PATH'] ?? '')))
        . '.<br>';
    echo 'Settings schema: ' . htmlspecialcharsbx(implode('; ', $settingsSchema)) . '.<br>';
    echo 'Products schema: ' . htmlspecialcharsbx(implode('; ', $productsSchema)) . '.';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . htmlspecialcharsbx($e->getMessage());
}
