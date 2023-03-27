<?php
// необходимые классы
namespace Prioritet\Exchange\BitrixHelper;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

class HighLoadHelp{

    public const COLOR_IBLOCK = 3;

    public function __construct()
    {
        Loader::includeModule('highloadblock');
    }

    private function getEntityDataClass($HlBlockId)
    {
        if (empty($HlBlockId) || $HlBlockId < 1)
        {
            return false;
        }
        $hlblock = HLBT::getById($HlBlockId)->fetch();
        $entity = HLBT::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        return $entity_data_class;
    }


    public function getAll($id_table)
    {
        $elements = [];
        $entity_data_class = $this->getEntityDataClass($id_table);
        $rsData = $entity_data_class::getList(array(
            'order' => array('UF_NAME'=>'ASC'),
            'select' => array('*'),
            'filter' => array('!UF_NAME' => false)
        ));
        while($el = $rsData->fetch()){
            $elements[] = $el;
        }

        return $elements;
    }

    public function getByName($id_table, $name)
    {
        $entity_data_class = $this->getEntityDataClass($id_table);
        $rsData = $entity_data_class::getList(array(
            'order' => array('UF_NAME'=>'ASC'),
            'select' => array('*'),
            'filter' => array('!UF_NAME' => false, 'UF_NAME' => $name ?: false)
        ));
        if($el = $rsData->fetch()){
            return $el;
        }

        return false;
    }

}











