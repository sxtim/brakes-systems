<?php

use Bitrix\Main\Context;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

class BrakesCatalogSearchComponent extends CBitrixComponent
{
    protected array $searchContext = [
        'query' => '',
        'ids' => [],
        'sample' => [],
    ];

    protected string $filterName = 'arCatalogSearchFilter';

    public function onPrepareComponentParams($params)
    {
        $params['QUERY_VARIABLE'] = (string)($params['QUERY_VARIABLE'] ?? 'q');
        $params['FILTER_NAME'] = (string)($params['FILTER_NAME'] ?? 'arCatalogSearchFilter');
        $params['SEARCH_LIMIT'] = (int)($params['SEARCH_LIMIT'] ?? 500);
        if ($params['SEARCH_LIMIT'] <= 0) {
            $params['SEARCH_LIMIT'] = 500;
        }

        $params['CHECK_DATES'] = ($params['CHECK_DATES'] === 'N') ? 'N' : 'Y';
        $params['ERROR_ON_EMPTY_STEM'] = ($params['ERROR_ON_EMPTY_STEM'] === 'Y') ? 'Y' : 'N';
        $params['NO_WORD_LOGIC'] = ($params['NO_WORD_LOGIC'] === 'N') ? 'N' : 'Y';
        $params['CATALOG_TEMPLATE'] = (string)($params['CATALOG_TEMPLATE'] ?? 'search');

        if (!isset($params['SHOW_ALL_WO_SECTION'])) {
            $params['SHOW_ALL_WO_SECTION'] = 'Y';
        }

        if (!isset($params['INCLUDE_SUBSECTIONS'])) {
            $params['INCLUDE_SUBSECTIONS'] = 'Y';
        }

        if (!isset($params['CACHE_FILTER'])) {
            $params['CACHE_FILTER'] = 'Y';
        }

        $this->filterName = $params['FILTER_NAME'];

        return parent::onPrepareComponentParams($params);
    }

    protected function getQuery(): string
    {
        $request = Context::getCurrent()->getRequest();
        return trim((string)$request->get($this->arParams['QUERY_VARIABLE']));
    }

    protected function performSearch(string $query): array
    {
        $context = [
            'query' => $query,
            'ids' => [],
            'sample' => [],
        ];

        if ($query === '' || !Loader::includeModule('search')) {
            return [$context, [-1]];
        }

        $search = new CSearch();
        $search->SetOptions([
            'ERROR_ON_EMPTY_STEM' => $this->arParams['ERROR_ON_EMPTY_STEM'] === 'Y',
            'NO_WORD_LOGIC' => $this->arParams['NO_WORD_LOGIC'] === 'Y',
        ]);

        $filter = [
            'QUERY' => $query,
            'SITE_ID' => $this->arParams['SITE_ID'] ?? SITE_ID,
            'MODULE_ID' => 'iblock',
            'PARAM1' => $this->arParams['IBLOCK_TYPE'],
            'PARAM2' => $this->arParams['IBLOCK_ID'],
            'CHECK_DATES' => $this->arParams['CHECK_DATES'],
        ];

        $limit = $this->arParams['SEARCH_LIMIT'];

        $search->Search($filter);

        while ($item = $search->Fetch()) {
            $itemId = (int)($item['ITEM_ID'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            if (!in_array($itemId, $context['ids'], true)) {
                $context['ids'][] = $itemId;
            }

            if (count($context['sample']) < 10) {
                $context['sample'][] = [
                    'ITEM_ID' => $itemId,
                    'TITLE' => $item['TITLE'] ?? '',
                    'URL' => $item['URL'] ?? '',
                ];
            }

            if (count($context['ids']) >= $limit) {
                break;
            }
        }

        $ids = $context['ids'];
        if ($ids === []) {
            $ids = [-1];
        }

        return [$context, $ids];
    }

    protected function prepareCatalogParams(): array
    {
        $skipKeys = [
            'QUERY_VARIABLE',
            'FILTER_NAME',
            'SEARCH_LIMIT',
            'CATALOG_TEMPLATE',
        ];

        $catalogParams = array_diff_key($this->arParams, array_flip($skipKeys));
        $catalogParams['FILTER_NAME'] = $this->filterName;

        return $catalogParams;
    }

    protected function logSearch(array $context, array $ids): void
    {
        $logFilePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';

        $entry = json_encode(
            [
                'timestamp' => date('c'),
                'query' => $context['query'],
                'ids' => $ids,
                'sample' => $context['sample'],
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        file_put_contents($logFilePath, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function executeComponent()
    {
        $query = $this->getQuery();

        if ($this->startResultCache(false, [$query])) {
            [$context, $ids] = $this->performSearch($query);

            global ${$this->filterName}, $catalogSearchContext;

            ${$this->filterName} = [
                'ID' => $ids,
            ];

            $catalogSearchContext = $context;
            $this->searchContext = $context;

            $this->arResult = [
                'QUERY' => $query,
                'FILTER_NAME' => $this->filterName,
                'SEARCH_CONTEXT' => $context,
                'CATALOG_PARAMS' => $this->prepareCatalogParams(),
            ];

            $this->includeComponentTemplate();

            $this->logSearch($context, $ids);
        }
    }
}
