<?php

namespace Prioritet\Exchange\BitrixHelper;

use CCatalogProduct;
use CCatalogStoreProduct;
use CFile;
use CIBlockElement;
use CPrice;

class ElementHelp
{

    private $iblockId = 26;
    public $errorMessage = null;

    public function saveProduct($product)
    {

       
        $parentSections = [];
        if (count($product['parent_sections'])) {
            foreach ($product['parent_sections'] as $arrIds) {
                $parentSections[] = $arrIds[count($arrIds) - 1];
            }
        }

        $highLoadBlockHelper = new HighLoadHelp();
        $hb = $highLoadBlockHelper->getByName($highLoadBlockHelper::COLOR_IBLOCK, $product['color']);

        $arHits = [];

        if ($product['hit']) {
            $arHits[] = 'HIT';
        }
        if ($product['new']) {
            $arHits[] = 'NEW';
        }


        $props = [
            "BRAND_NAME" => $product['group_name'],
            "EXT_CODE" => $product['1c_id'],
            "EXT_1C_CODE" => $product['code'],
            "CML2_ARTICLE" => $product['arta'],
            "COLOR_REF2" => $hb['UF_XML_ID'] ?: null,
            "PROP_2083" => [$product['material']],
            "PROP_2084" => $product['country'],
            "HIT" => $arHits,
            "PROP_2065" => $product['size'],
            "PROP_2091" => $product['length'],
            "PROP_307" => $product['width'],
            "PROP_159" => $product['weight'],
            "PROP_2026" => $product['volume'],
            "PROP_2300" => $product['waterproof'], // водонепроницаемость
            "PROP_2301" => $product['battery'], // тип источника питания
            "ARTA" =>  $product['article'], // внутренний артикул товара внутри организации. Если возникает вопрос – то пользователи называют его.
        ];

        $arFieldsElement = array(
            "MODIFIED_BY" => 1,
            "IBLOCK_SECTION" => count($parentSections) ? $parentSections : false,
            "IBLOCK_ID" => $this->iblockId,
            "NAME" => $product['name'],
            "ACTIVE" => "Y",
            "PROPERTY_VALUES" => $props,
            "DETAIL_TEXT" => $product['title'],
            "PREVIEW_TEXT" => $product['title'],
        );

        $stores = (new StoreHelp())->getAllStore();

        $countProduct = 0;
        foreach ($stores as $store) {
            if ($product[$store['CODE']]) {
                $countProduct += (int)$product[$store['CODE']];
            }
        }

        $el_ob = new CIBlockElement;
        $old_element = $this->checkElement($product['1c_id']);

        if ($old_element) {
            $PRODUCT_ID = $old_element['ID'];
            if($product['image']){
                if ($old_element['ORIGINAL_IMG'] && $old_element['DETAIL_PICTURE'] && $old_element['ORIGINAL_IMG'] == $product['image']) {
                    $arFieldsElement['DETAIL_PICTURE'] = $old_element['DETAIL_PICTURE'];
                    $arFieldsElement['PROPERTY_VALUES']['ORIGINAL_IMG_URL'] = $product['image'];
                } else {
                    if ($old_element['DETAIL_PICTURE']) {
                        CFile::Delete($old_element['DETAIL_PICTURE']);
                    }

                    $arFieldsElement["DETAIL_PICTURE"] = CFile::MakeFileArray($product['image']);
                    $arFieldsElement['PROPERTY_VALUES']['ORIGINAL_IMG_URL'] = $product['image'];
                }
            }

            $el_ob->Update($PRODUCT_ID, $arFieldsElement);
            $isUpdateProduct = CCatalogProduct::Update($PRODUCT_ID, ["VAT_INCLUDED" => "Y", "QUANTITY" => $countProduct, "QUANTITY_TRACE" => "Y", "QUANTITY_RESERVED" => 0]);

            if ($isUpdateProduct) {

                // Добавление остатков
                foreach ($stores as $store) {
                    if ($product[$store['CODE']]) {
                        $arFieldsAmount = array(
                            "PRODUCT_ID" => $PRODUCT_ID,
                            "STORE_ID" => $store['ID'],
                            "AMOUNT" => (int)$product[$store['CODE']],
                        );
                        $ID = CCatalogStoreProduct::Add($arFieldsAmount);
                    }
                }

                $arFieldsPrice = [
                    "PRODUCT_ID" => $PRODUCT_ID,
                    "CATALOG_GROUP_ID" => 1,
                    "PRICE" => $product['price'],
                    "CURRENCY" => "RUB",
                ];
                $res = CPrice::GetList([], ["PRODUCT_ID" => $PRODUCT_ID, "CATALOG_GROUP_ID" => 1]);
                if ($arr = $res->Fetch()) {
                    $isUpdatePrice = CPrice::Update($arr["ID"], $arFieldsPrice);
                } else {

                    $isUpdatePrice = CPrice::Add($arFieldsPrice);
                }
                if (!$isUpdatePrice) {
                    $this->errorMessage = 'Ошибка добавления цены для товара. ' . $PRODUCT_ID;
                    return false;
                }
            } else {
                $this->errorMessage = 'Ошибка добавления продукта для товара. ' . $PRODUCT_ID;
            }


        } else {
            if($product['image']) {

                $arFieldsElement["DETAIL_PICTURE"] = CFile::MakeFileArray($product['image']);
                $arFieldsElement['PROPERTY_VALUES']['ORIGINAL_IMG_URL'] = $product['image'];
            }

            $last_el_id = $el_ob->Add($arFieldsElement);
            $isAddProduct = CCatalogProduct::Add(["ID" => $last_el_id, "VAT_INCLUDED" => "Y", "QUANTITY" => $countProduct, "QUANTITY_TRACE" => "Y", "QUANTITY_RESERVED" => 0]);


            if ($isAddProduct) {
                // Добавление остатков
                foreach ($stores as $store) {
                    if ($product[$store['CODE']]) {
                        $arFieldsAmount = array(
                            "PRODUCT_ID" => $isAddProduct,
                            "STORE_ID" => $store['ID'],
                            "AMOUNT" => $product[$store['CODE']],
                        );

                        $ID = CCatalogStoreProduct::Add($arFieldsAmount);
                    }
                }

                $arFieldsPrice = [
                    "PRODUCT_ID" => $last_el_id,
                    "CATALOG_GROUP_ID" => 1,
                    "PRICE" => $product['price'],
                    "CURRENCY" => "RUB",

                ];
                $isAddPrice = CPrice::Add($arFieldsPrice);
                if (!$isAddPrice) {
                    $this->errorMessage = 'Ошибка добавления цены для товара. ' . $isAddProduct;
                    return false;
                }
            } else {
                $this->errorMessage = 'Ошибка добавления продукта для товара. ' . $isAddProduct;
            }

        }

        if (!$last_el_id) {
            $this->errorMessage = $el_ob->LAST_ERROR;
            return false;
        }

    }


    private function checkElement($extCode)
    {
        $arSelect = array("ID", "NAME", "DETAIL_PICTURE", "PROPERTY_ORIGINAL_IMG_URL");
        $arFilter = array("IBLOCK_ID" => IntVal($this->iblockId), "PROPERTY_EXT_CODE" => $extCode ?: false);
        $res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
        if ($ob = $res->GetNext()) {
            if ($ob['DETAIL_PICTURE']) {
                $ob['DETAIL_PICTURE'] = CFile::GetFileArray($ob['DETAIL_PICTURE']);
                $ob['ORIGINAL_IMG'] = $ob['PROPERTY_ORIGINAL_IMG_URL_VALUE'];
            }

            return $ob;
        }
        return false;
    }

}