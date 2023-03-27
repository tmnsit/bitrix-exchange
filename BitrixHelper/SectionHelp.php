<?php

namespace Prioritet\Exchange\BitrixHelper;

use CIBlockElement;
use CIBlockSection;
use CUtil;

class SectionHelp
{

    private $isSubsection = false;
    private $iblockId = 26;

    public function processImport($stringName, $stringCode): array
    {
        $pathCategoryGroupsCode = explode('*', $stringCode);
        $pathCategoryGroupsName = explode('*', $stringName);

        $catIds = [];
        foreach ($pathCategoryGroupsCode as $key => $codePath) {
            $arrCategoryName = explode('/', $pathCategoryGroupsName[$key]);
            $arrCategoryCode = explode('/', $codePath);
            $categories = [];
            foreach ($arrCategoryCode as $keyName => $code) {
                $categories[] = [
                    'name' => $arrCategoryName[$keyName],
                    'code' => $code
                ];
            }

            $ids = $this->saveCategories($categories);
            $catIds[] = $ids;
        }

        return $catIds;
    }


    private function saveCategories($arrCats)
    {
        $ids = [];
        foreach ($arrCats as $key => $cat) {
            if (!$cat['code']) continue;
            $IBLOCK_ID = $this->iblockId;

            $arFilter = array('IBLOCK_ID' => $IBLOCK_ID, 'UF_EXT_CODE' => $cat['code'] ?: false);
            $db_list = CIBlockSection::GetList([], $arFilter, false, ['UF_*', 'NAME', 'ID']);
            if ($ar_result = $db_list->GetNext()) {
                $ids[] = $ar_result['ID'];
            } else {
                $ids[] = $this->createCategory($cat, $ids);
            }
        }
        return $ids;
    }


    private function updateCategory($ID, $cat, $parentIds)
    {
        if (count($parentIds) && $this->isSubsection) {
            $ids = $parentIds;
        } elseif (count($parentIds) && !$this->isSubsection) {
            $ids = $parentIds[count($parentIds) - 1];
        }
        $IBLOCK_ID = $this->iblockId;

        $arParams = array("replace_space" => "-", "replace_other" => "-");
        $trans = Cutil::translit($cat['name'], "ru", $arParams);
        $bs = new CIBlockSection;
        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_SECTION_ID" => $ids,
            "IBLOCK_ID" => $IBLOCK_ID,
            "NAME" => $cat['name'],
            "CODE" => $trans,
            "UF_EXT_CODE" => $cat['code']
        );

        $bs->Update($ID, $arFields);
        return $ID;
    }

    private function createCategory($cat, $parentIds)
    {
        if (count($parentIds) && $this->isSubsection) {
            $ids = $parentIds;
        } elseif (count($parentIds) && !$this->isSubsection) {
            $ids = $parentIds[count($parentIds) - 1];
        }
        $IBLOCK_ID = $this->iblockId;

        $arParams = array("replace_space" => "-", "replace_other" => "-");
        $trans = Cutil::translit($cat['name'], "ru", $arParams);
        $bs = new CIBlockSection;
        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_SECTION_ID" => $ids,
            "IBLOCK_ID" => $IBLOCK_ID,
            "NAME" => $cat['name'],
            "CODE" => $trans,
            "UF_EXT_CODE" => $cat['code']
        );


        $ID = $bs->Add($arFields);
        if ($ID > 0) {
            return $ID;
        }
    }


    public function setPropertySections()
    {
        $arFilter = array('IBLOCK_ID' => $this->iblockId, 'GLOBAL_ACTIVE' => 'Y');
        $db_list = CIBlockSection::GetList([], $arFilter, true);
        while ($ar_result = $db_list->GetNext()) {
            $max = 0;
            $min = 0;


            // MIN PRICE
            $dbElements = CIBlockElement::GetList(
                array('CATALOG_PRICE_1' => 'ASC'),
                array('IBLOCK_ID' => $this->iblockId, 'SECTION_ID' =>  $ar_result['ID'], 'INCLUDE_SUBSECTIONS' => 'Y'),
                false,
                array('nTopCount' => 1),
                array('ID', 'IBLOCK_ID', 'IBLOCK_TYPE', 'CATALOG_PRICE_1')
            );

            while ($arrElement = $dbElements->Fetch()) {
                $min = $arrElement['CATALOG_PRICE_1'];
            }
            unset($dbElements, $arrElement);
            // MAX PRICE
            $dbElements = CIBlockElement::GetList(array('CATALOG_PRICE_1' => 'DESC'),
                array('IBLOCK_ID' => $this->iblockId, 'SECTION_ID' =>  $ar_result['ID'], 'INCLUDE_SUBSECTIONS' => 'Y'),
                false,
                array('nTopCount' => 1),
                array('ID', 'IBLOCK_ID', 'IBLOCK_TYPE', 'CATALOG_PRICE_1')
            );
            while ($arrElement = $dbElements->Fetch()) {
                $max = $arrElement['CATALOG_PRICE_1'];
            }

            $count = CIBlockElement::GetList(
                array(),//arOrder
                array('IBLOCK_ID' => $this->iblockId, 'SECTION_ID' => $ar_result['ID'], 'INCLUDE_SUBSECTIONS' => 'Y'),//arFilter
                array(),//arGroupBy
                false,//arNavStartParams
                array('ID', 'NAME')//arSelectFields
            );


            $arUpdate = array ("UF_MINIMUM_PRICE" => intval($min), "UF_MAXIMUM_PRICE" => intval($max),"UF_COUNT" => $count);
            $obSection = new CIBlockSection;
            $obSection->Update($ar_result['ID'], $arUpdate);

        }
    }

}