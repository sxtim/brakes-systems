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

const CATALOG_IBLOCK_ID = 1;
const CATALOG_SECTION_ENTITY_ID = 'IBLOCK_1_SECTION';

function ensureCatalogSectionUserField(array $field): array
{
    $fieldName = trim((string)($field['FIELD_NAME'] ?? ''));
    if ($fieldName === '') {
        throw new RuntimeException('FIELD_NAME is required');
    }

    $entityId = (string)($field['ENTITY_ID'] ?? CATALOG_SECTION_ENTITY_ID);
    $existing = CUserTypeEntity::GetList(
        [],
        [
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName,
        ]
    )->Fetch();

    $settings = $field['SETTINGS'] ?? [];
    $labels = [
        'EDIT_FORM_LABEL' => $field['EDIT_FORM_LABEL'] ?? ['ru' => $field['EDIT_FORM_LABEL_RU'] ?? $fieldName],
        'LIST_COLUMN_LABEL' => $field['LIST_COLUMN_LABEL'] ?? ['ru' => $field['LIST_COLUMN_LABEL_RU'] ?? $fieldName],
        'LIST_FILTER_LABEL' => $field['LIST_FILTER_LABEL'] ?? ['ru' => $field['LIST_FILTER_LABEL_RU'] ?? $fieldName],
        'ERROR_MESSAGE' => $field['ERROR_MESSAGE'] ?? ['ru' => ''],
        'HELP_MESSAGE' => $field['HELP_MESSAGE'] ?? ['ru' => ''],
    ];

    $commonFields = [
        'ENTITY_ID' => $entityId,
        'FIELD_NAME' => $fieldName,
        'USER_TYPE_ID' => 'string',
        'XML_ID' => $field['XML_ID'] ?? '',
        'SORT' => (int)($field['SORT'] ?? 100),
        'MULTIPLE' => 'N',
        'MANDATORY' => 'N',
        'SHOW_FILTER' => 'N',
        'SHOW_IN_LIST' => 'N',
        'EDIT_IN_LIST' => 'Y',
        'IS_SEARCHABLE' => 'N',
        'SETTINGS' => $settings,
    ] + $labels;

    $userType = new CUserTypeEntity();

    if ($existing) {
        $updateFields = $commonFields;
        unset($updateFields['ENTITY_ID'], $updateFields['FIELD_NAME']);

        $result = $userType->Update((int)$existing['ID'], $updateFields);
        if (!$result) {
            throw new RuntimeException('Failed to update user field ' . $fieldName . ': ' . (string)$userType->LAST_ERROR);
        }

        return [
            'created' => false,
            'updated' => true,
            'id' => (int)$existing['ID'],
        ];
    }

    $fieldId = (int)$userType->Add($commonFields);
    if ($fieldId <= 0) {
        throw new RuntimeException('Failed to create user field ' . $fieldName . ': ' . (string)$userType->LAST_ERROR);
    }

    return [
        'created' => true,
        'updated' => false,
        'id' => $fieldId,
    ];
}

$messages = [];
$created = 0;
$updated = 0;

try {
    $fields = [
        [
            'FIELD_NAME' => 'UF_BROWSER_TITLE',
            'XML_ID' => 'UF_BROWSER_TITLE',
            'SORT' => 500,
            'EDIT_FORM_LABEL_RU' => 'Meta Title',
            'LIST_COLUMN_LABEL_RU' => 'Meta Title',
            'LIST_FILTER_LABEL_RU' => 'Meta Title',
            'SETTINGS' => [
                'SIZE' => 60,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
        ],
        [
            'FIELD_NAME' => 'UF_META_DESCRIPTION',
            'XML_ID' => 'UF_META_DESCRIPTION',
            'SORT' => 510,
            'EDIT_FORM_LABEL_RU' => 'Meta Description',
            'LIST_COLUMN_LABEL_RU' => 'Meta Description',
            'LIST_FILTER_LABEL_RU' => 'Meta Description',
            'SETTINGS' => [
                'SIZE' => 80,
                'ROWS' => 4,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
        ],
        [
            'FIELD_NAME' => 'UF_KEYWORDS',
            'XML_ID' => 'UF_KEYWORDS',
            'SORT' => 520,
            'EDIT_FORM_LABEL_RU' => 'Meta Keywords',
            'LIST_COLUMN_LABEL_RU' => 'Meta Keywords',
            'LIST_FILTER_LABEL_RU' => 'Meta Keywords',
            'SETTINGS' => [
                'SIZE' => 80,
                'ROWS' => 3,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
        ],
    ];

    foreach ($fields as $field) {
        $result = ensureCatalogSectionUserField($field + ['ENTITY_ID' => CATALOG_SECTION_ENTITY_ID]);
        if ($result['created']) {
            $created++;
        }
        if ($result['updated']) {
            $updated++;
        }
    }

    $messages[] = 'Catalog section SEO fields: OK';
    $messages[] = 'IBlock ID: ' . CATALOG_IBLOCK_ID;
    $messages[] = 'Entity: ' . CATALOG_SECTION_ENTITY_ID;
    $messages[] = 'Fields total: ' . count($fields);
    $messages[] = 'Created: ' . $created;
    $messages[] = 'Updated: ' . $updated;

    echo implode('<br>', array_map('htmlspecialcharsbx', $messages));
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . htmlspecialcharsbx($e->getMessage());
}
