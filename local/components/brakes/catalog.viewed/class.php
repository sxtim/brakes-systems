<?php

use App\Brakes\Helper\Storage;
use Bitrix\Main\Application;
use Bitrix\Iblock\Elements\ElementCatalogTable;

class CatalogViewedComponent extends \CBitrixComponent
{
    public function executeComponent(): void
    {
        $session = Application::getInstance()->getSession();
        $ids = $session->get('CATALOG_ITEM_VIEWED');

        if ( ! $ids) {
            return;
        }

        $rsData = ElementCatalogTable::getList([
            'filter' => [
                '=ID' => $ids,
                '!ID' => Storage::get('ITEM_ID'),
            ],
            'select' => [
                'ID',
                'IBLOCK_ID',
                'CODE',
                'NAME',
                'LINK_PHOTO_VAL'  => 'LINK_PHOTO.VALUE',
                'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL',
                'IBLOCK_SECTION_ID',
            ],
        ]);

        $this->arResult['ITEMS'] = [];

        while ($data = $rsData->fetch()) {
            $data['DETAIL_PAGE_URL'] = CIBlock::ReplaceDetailUrl(
                $data['DETAIL_PAGE_URL'],
                $data,
                false,
                'E'
            );

            $data['IMG'] = getPreviewImgCatalog($data['LINK_PHOTO_VAL']);
            $this->arResult['ITEMS'][] = $data;
        }

        $this->includeComponentTemplate();
    }
}
