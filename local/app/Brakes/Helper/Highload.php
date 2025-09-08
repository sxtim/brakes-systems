<?php

namespace App\Brakes\Helper;

use Bitrix\Highloadblock\HighloadBlockTable;

class Highload
{
    public static function getClassEntity(string $table): string
    {
        $hl = HighloadBlockTable::getRow([
            'filter' => [
                '=TABLE_NAME' => $table
            ],
        ]);

        return HighloadBlockTable::compileEntity($hl)->getDataClass();
    }
}
