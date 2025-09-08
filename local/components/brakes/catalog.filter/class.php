<?php

use App\Brakes\Helper\Storage;
use Bitrix\Main\Application;
use Bitrix\Iblock\Elements\ElementCatalogTable;

class CatalogFilterComponent extends \CBitrixComponent
{
    public function executeComponent(): void
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $getData = $request->getQueryList()->toArray();

        $this->arResult['FILTER'] = [
            'MARK' => [],
            'MODEL' => [],
            'BODY' => [],
        ];

        $filter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
        ];

        $group = [
            'PROPERTY_MARK',
        ];

        $this->arResult['SELECTED'] = $getData['brakes_filter'];

        if (!empty($getData['brakes_filter']['mark'])) {
            $filter['PROPERTY_MARK'] = $getData['brakes_filter']['mark'];
            $group[] = 'PROPERTY_MODEL';
        }

        if (!empty($getData['brakes_filter']['model'])) {
            $filter['PROPERTY_MODEL'] = $getData['brakes_filter']['model'];
            $group[] = 'PROPERTY_BODY';
        }

        $rsData = CIBlockElement::GetList(
            arFilter: $filter,
            arGroupBy: $group,
        );

        while ($arData = $rsData->Fetch()) {
            if ($arData['PROPERTY_MARK_VALUE']
                && ! in_array(
                    $arData['PROPERTY_MARK_VALUE'],
                    $this->arResult['FILTER']['MARK']
                )
            ) {
                $this->arResult['FILTER']['MARK'][] = $arData['PROPERTY_MARK_VALUE'];
            }

            if ($arData['PROPERTY_MODEL_VALUE']
                && ! in_array(
                    $arData['PROPERTY_MODEL_VALUE'],
                    $this->arResult['FILTER']['MODEL']
                )
            ) {
                $this->arResult['FILTER']['MODEL'][] = $arData['PROPERTY_MODEL_VALUE'];
            }

            if ($arData['PROPERTY_BODY_VALUE']
                && ! in_array(
                    $arData['PROPERTY_BODY_VALUE'],
                    $this->arResult['FILTER']['BODY']
                )
            ) {
                $this->arResult['FILTER']['BODY'][] = $arData['PROPERTY_BODY_VALUE'];
            }
        }

        $this->includeComponentTemplate();
    }
}
