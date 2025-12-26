<?php

use Bitrix\Iblock\Model\Section;
use Bitrix\Main\Loader;

class CatalogFilterComponent extends \CBitrixComponent
{
    public function executeComponent(): void
    {
        $iblockId = (int)($this->arParams['IBLOCK_ID'] ?? 0);
        $currentSectionCodeOrPath = trim((string)($this->arParams['SECTION'] ?? ''), " \t\n\r\0\x0B/");
        $currentSectionId = (int)($this->arParams['SECTION_ID'] ?? 0);

        $iblockModuleLoaded = $iblockId > 0 && Loader::includeModule('iblock');

        $this->arResult['CATEGORIES'] = [];
        $this->arResult['MARKS'] = [];
        $this->arResult['MODELS'] = [];
        $this->arResult['BODIES'] = [];

        $this->arResult['CATEGORY_SELECT_ID'] = 0;
        $this->arResult['MARK_SELECT_ID'] = 0;
        $this->arResult['MODEL_SELECT_ID'] = 0;
        $this->arResult['BODY_SELECT_ID'] = 0;

        if ($iblockId <= 0) {
            $this->includeComponentTemplate();
            return;
        }

        $select = [
            'IBLOCK_ID',
            'ID',
            'NAME',
            'CODE',
            'DEPTH_LEVEL',
            'IBLOCK_SECTION_ID',
            'SECTION_PAGE_URL' => 'IBLOCK.SECTION_PAGE_URL',
            'UF_SVG',
        ];

        $entitySections = Section::compileEntityByIblock($iblockId);

        $mapRow = static function (array $row): array {
            $row['SECTION_PAGE_URL'] = \CIBlock::ReplaceDetailUrl(
                $row['SECTION_PAGE_URL'] ?? '',
                $row,
                false,
                'S'
            );

            $ufSvg = $row['UF_SVG'] ?? null;
            if (is_string($ufSvg) && $ufSvg !== '' && $ufSvg[0] === '/') {
                $row['UF_SVG'] = $ufSvg;
            } else {
                $row['UF_SVG'] = (string)\CFile::GetPath((int)$ufSvg);
            }

            return $row;
        };

        $fetchSections = static function (
            $entitySections,
            array $filter,
            array $select,
            callable $mapRow,
            array $order = []
        ): array {
            $items = [];
            $rsData = $entitySections::getList([
                'order' => $order,
                'filter' => $filter,
                'select' => $select,
            ]);
            while ($row = $rsData->fetch()) {
                $items[] = $mapRow($row);
            }
            return $items;
        };

	        $this->arResult['CATEGORIES'] = $fetchSections(
	            $entitySections,
	            [
	                'IBLOCK_ID' => $iblockId,
	                'ACTIVE' => 'Y',
	                'DEPTH_LEVEL' => 1,
	                '!=CODE' => 'other',
	            ],
	            $select,
	            $mapRow
	        );
	        // Safety: hide technical "other" category in UI even if filter is ignored by ORM.
	        $this->arResult['CATEGORIES'] = array_values(array_filter(
	            $this->arResult['CATEGORIES'],
	            static fn(array $section): bool => ((string)($section['CODE'] ?? '')) !== 'other'
	        ));

        $currentSection = null;
        if ($iblockModuleLoaded && $currentSectionId <= 0 && $currentSectionCodeOrPath !== '') {
            $resolvedSectionId = (int)\CIBlockFindTools::GetSectionIDByCodePath($iblockId, $currentSectionCodeOrPath);
            if ($resolvedSectionId > 0) {
                $currentSectionId = $resolvedSectionId;
            }
        }

        if ($currentSectionId > 0) {
            $currentSection = $entitySections::getRow([
                'filter' => [
                    'ID' => $currentSectionId,
                ],
                'select' => [
                    'ID',
                    'CODE',
                    'DEPTH_LEVEL',
                    'IBLOCK_SECTION_ID',
                ],
            ]);
        }

        $categoryId = 0;
        $markId = 0;
        $modelId = 0;
        $bodyId = 0;

        if (is_array($currentSection) && !empty($currentSection['ID'])) {
            $depth = (int)($currentSection['DEPTH_LEVEL'] ?? 0);
            $id = (int)$currentSection['ID'];
            $parentId = (int)($currentSection['IBLOCK_SECTION_ID'] ?? 0);

            if ($depth === 1) {
                $categoryId = $id;
            } elseif ($depth === 2) {
                $categoryId = $parentId;
                $markId = $id;
            } elseif ($depth === 3) {
                $markId = $parentId;
                $modelId = $id;

                $markRow = $entitySections::getRow([
                    'filter' => ['ID' => $markId],
                    'select' => ['IBLOCK_SECTION_ID'],
                ]);
                $categoryId = (int)($markRow['IBLOCK_SECTION_ID'] ?? 0);
            } elseif ($depth >= 4) {
                $modelId = $parentId;
                $bodyId = $id;

                $modelRow = $entitySections::getRow([
                    'filter' => ['ID' => $modelId],
                    'select' => ['IBLOCK_SECTION_ID'],
                ]);
                $markId = (int)($modelRow['IBLOCK_SECTION_ID'] ?? 0);

                $markRow = $markId > 0 ? $entitySections::getRow([
                    'filter' => ['ID' => $markId],
                    'select' => ['IBLOCK_SECTION_ID'],
                ]) : null;
                $categoryId = (int)(is_array($markRow) ? ($markRow['IBLOCK_SECTION_ID'] ?? 0) : 0);
            }
        }

        $this->arResult['CATEGORY_SELECT_ID'] = $categoryId;
        $this->arResult['MARK_SELECT_ID'] = $markId;
        $this->arResult['MODEL_SELECT_ID'] = $modelId;
        $this->arResult['BODY_SELECT_ID'] = $bodyId;

        if ($categoryId > 0) {
            $this->arResult['MARKS'] = $fetchSections(
                $entitySections,
                [
                    'IBLOCK_ID' => $iblockId,
                    'ACTIVE' => 'Y',
                    'DEPTH_LEVEL' => 2,
                    'IBLOCK_SECTION_ID' => $categoryId,
                ],
                $select,
                $mapRow,
                ['NAME' => 'ASC']
            );
        }

        if ($markId > 0) {
            $this->arResult['MODELS'] = $fetchSections(
                $entitySections,
                [
                    'IBLOCK_ID' => $iblockId,
                    'ACTIVE' => 'Y',
                    'DEPTH_LEVEL' => 3,
                    'IBLOCK_SECTION_ID' => $markId,
                ],
                $select,
                $mapRow
            );
        }

        if ($modelId > 0) {
            $this->arResult['BODIES'] = $fetchSections(
                $entitySections,
                [
                    'IBLOCK_ID' => $iblockId,
                    'ACTIVE' => 'Y',
                    'DEPTH_LEVEL' => 4,
                    'IBLOCK_SECTION_ID' => $modelId,
                ],
                $select,
                $mapRow
            );
        }

        $this->includeComponentTemplate();
    }
}
