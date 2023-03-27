<?php

namespace Prioritet\Exchange\BitrixHelper;

use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Loader;
use CPHPCache;

class StoreHelp{

    public const amount = 1;
    public const gerz = 2;
    public const mosk = 3;
    public const privoz = 4;
    public const tyumen = 5;
    public const holod = 6;
    public const yamsk = 7;
    public const lenina = 8;
    public const ostrov = 9;
    public const kaskara = 10;
    public const zelbereg = 11;


    public function getAllStore(): array
    {
        $arStores = [];
        $arStoreBd = \Bitrix\Catalog\StoreTable::getList(array(
            'filter'=>array(),
            'select'=>array('*','UF_*'),
        ));
        while($arStore = $arStoreBd->fetch()){
            $arStores[] = $arStore;
        }

        return $arStores;
    }
}