<?php

use App\Brakes\Helper\Storage;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Application;
use Bitrix\Iblock\Elements\ElementCatalogTable;
use Bitrix\Iblock\Model\Section;

class CatalogFilterComponent extends \CBitrixComponent
{
    public function executeComponent(): void
    {
        $this->arResult['FILTER'] = [
            'MARK'  => [],
            'MODEL' => [],
            'BODY'  => [],
        ];

        $filter = [
            'ACTIVE'      => 'Y',
            'DEPTH_LEVEL' => 1,
        ];

        $select = [
            'IBLOCK_ID',
            'ID',
            'NAME',
            'CODE',
            'SECTION_PAGE_URL' => 'IBLOCK.SECTION_PAGE_URL',
            'UF_SVG',
        ];

        $entitySections = Section::compileEntityByIblock($this->arParams['IBLOCK_ID']);

        $rsData = $entitySections::getList([
            'filter'  => $filter,
            'select'  => $select,
        ]);

        while ($arData = $rsData->fetch()) {
            $arData['SECTION_PAGE_URL'] = CIBlock::ReplaceDetailUrl(
                $arData['SECTION_PAGE_URL'],
                $arData,
                false,
                'S'
            );

            $arData['UF_SVG'] = CFile::GetPath($arData['UF_SVG']);

            $this->arResult['FIRST'][] = $arData;
        }

        if ($this->arParams['SECTION']) {
            $section = $entitySections::getRow([
                'filter' => [
                    '=CODE' => $this->arParams['SECTION'],
                ],
                'select' => [
                    'ID',
                    'DEPTH_LEVEL',
                    'IBLOCK_SECTION_ID',
                ],
            ]);

            if ($section['DEPTH_LEVEL'] == 1) {
                $rsData = $entitySections::getList([
                    'filter'  => [
                        'IBLOCK_ID'   => $this->arParams['IBLOCK_ID'],
                        'ACTIVE'      => 'Y',
                        'IBLOCK_SECTION_ID' => $section['ID'],
                    ],
                    'select'  => $select,
                ]);

                while ($arData = $rsData->fetch()) {
                    $arData['SECTION_PAGE_URL'] = CIBlock::ReplaceDetailUrl(
                        $arData['SECTION_PAGE_URL'],
                        $arData,
                        false,
                        'S'
                    );

                    $arData['UF_SVG'] = CFile::GetPath($arData['UF_SVG']);

                    $this->arResult['SECOND'][] = $arData;
                }
            }

            if ($section['DEPTH_LEVEL'] == 2) {
                $this->arResult['FIRST_SELECT_ID'] = $section['IBLOCK_SECTION_ID'];
                $this->arResult['SECOND_SELECT_ID'] = $section['ID'];

                $rsData = $entitySections::getList([
                    'filter'  => [
                        'IBLOCK_ID'   => $this->arParams['IBLOCK_ID'],
                        'ACTIVE'      => 'Y',
                        'IBLOCK_SECTION_ID' => $section['IBLOCK_SECTION_ID'],
                    ],
                    'select'  => $select,
                ]);

                while ($arData = $rsData->fetch()) {
                    $arData['SECTION_PAGE_URL'] = CIBlock::ReplaceDetailUrl(
                        $arData['SECTION_PAGE_URL'],
                        $arData,
                        false,
                        'S'
                    );

                    $arData['UF_SVG'] = CFile::GetPath($arData['UF_SVG']);

                    $this->arResult['SECOND'][] = $arData;
                }

                $rsData = $entitySections::getList([
                    'filter'  => [
                        'IBLOCK_ID'   => $this->arParams['IBLOCK_ID'],
                        'ACTIVE'      => 'Y',
                        'IBLOCK_SECTION_ID' => $section['ID'],
                    ],
                    'select'  => $select,
                ]);

                while ($arData = $rsData->fetch()) {
                    $arData['SECTION_PAGE_URL'] = CIBlock::ReplaceDetailUrl(
                        $arData['SECTION_PAGE_URL'],
                        $arData,
                        false,
                        'S'
                    );

                    $arData['UF_SVG'] = CFile::GetPath($arData['UF_SVG']);

                    $this->arResult['THIRD'][] = $arData;
                }
            }

            if ($section['DEPTH_LEVEL'] == 3) {
                $lvl1Id = $entitySections::getRow([
                    'filter'  => [
                        'IBLOCK_ID'   => $this->arParams['IBLOCK_ID'],
                        'ACTIVE'      => 'Y',
                        'ID' => $section['IBLOCK_SECTION_ID'],
                    ],
                    'select' => [
                        'IBLOCK_SECTION_ID',
                    ],
                ])['IBLOCK_SECTION_ID'];

                $this->arResult['FIRST_SELECT_ID'] = $lvl1Id;
                $this->arResult['SECOND_SELECT_ID'] = $section['IBLOCK_SECTION_ID'];
                $this->arResult['THIRD_SELECT_ID'] = $section['ID'];

                $rsData = $entitySections::getList([
                    'filter'  => [
                        'IBLOCK_ID'   => $this->arParams['IBLOCK_ID'],
                        'ACTIVE'      => 'Y',
                        'IBLOCK_SECTION_ID' => $lvl1Id,
                    ],
                    'select'  => $select,
                ]);

                while ($arData = $rsData->fetch()) {
                    $arData['SECTION_PAGE_URL'] = CIBlock::ReplaceDetailUrl(
                        $arData['SECTION_PAGE_URL'],
                        $arData,
                        false,
                        'S'
                    );

                    $arData['UF_SVG'] = CFile::GetPath($arData['UF_SVG']);

                    $this->arResult['SECOND'][] = $arData;
                }

                $rsData = $entitySections::getList([
                    'filter'  => [
                        'IBLOCK_ID'   => $this->arParams['IBLOCK_ID'],
                        'ACTIVE'      => 'Y',
                        'IBLOCK_SECTION_ID' => $section['IBLOCK_SECTION_ID'],
                    ],
                    'select'  => $select,
                ]);

                while ($arData = $rsData->fetch()) {
                    $arData['SECTION_PAGE_URL'] = CIBlock::ReplaceDetailUrl(
                        $arData['SECTION_PAGE_URL'],
                        $arData,
                        false,
                        'S'
                    );

                    $arData['UF_SVG'] = CFile::GetPath($arData['UF_SVG']);

                    $this->arResult['THIRD'][] = $arData;
                }
            }
        }

        $this->includeComponentTemplate();
    }
}
