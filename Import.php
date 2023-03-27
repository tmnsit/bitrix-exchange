<?php

namespace Prioritet\Exchange;


use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Prioritet\Exchange\BitrixHelper\ElementHelp;
use Prioritet\Exchange\BitrixHelper\SectionHelp;

class Import
{

    public function execute($products)
    {
        $sectionHelper = new SectionHelp();
        $elementHelper = new ElementHelp();

        foreach ($products as $product) {
            $product['parent_sections'] = $sectionHelper->processImport($product['category_name'], $product['category_code']);
            $elementHelper->saveProduct($product);
        }
        // Установка количества мин и макс цены
        $sectionHelper->setPropertySections();
    }


    public function execute_cards($cards)
    {
        // Статический масив id правил карзины где ключ это размер скидки значение это id в битриксе
        $discounts_ids = [
            3 => 1,
            5 => 2,
            10 => 3
        ];


        if (Loader::includeModule('catalog')) {

            $db_coupons = DiscountCouponTable::getList();

            $arrCoupons = [];
            foreach ($db_coupons as $coupon) {
                $arrCoupons[$coupon['COUPON']] = $coupon;
            }
            foreach ($cards as $card)
            {
                $COUPON = $card['code'];
                $arCouponFields = array(
                    "DISCOUNT_ID" => $discounts_ids[$card['discount']],
                    "ACTIVE" => "Y",
                    "TYPE" => DiscountCouponTable::TYPE_MULTI_ORDER,
                    "COUPON" => (string)$COUPON,
                );


                if ($arrCoupons[$card['code']])
                {
//                    if ($discounts_ids[$card['discount']] != $arrCoupons[$card['code']]['DISCOUNT_ID']) {
//                      // Если купон найден и правило корзины отличается(нужно обновить) иначе просто пропускаем
                        $res = DiscountCouponTable::update($arrCoupons[$card['code']]['ID'], $arCouponFields);
//                    }
                } else {
                    // Если купон не найден нужно добавить
                    if ($discounts_ids[$card['discount']]) {
                        // Если такая скидка найдена в битриксе
                        $res = DiscountCouponTable::add($arCouponFields);

                    }
                }
            }


            // Снова получаем все купоны для проверки на удаление
            $db_coupons_del = DiscountCouponTable::getList();
            $arr_del = [];
            foreach ($db_coupons_del as $coupon_del) {
                $arr_del[$coupon_del['COUPON']] = $coupon_del;
            }


            foreach ($cards as $key => $card){
                // Удаляем из массива те которых нет в списке из 1с
                unset($arr_del[$key]);
            }


            // Пробегаемся по массиву и удаляем оставшиеся
            foreach($arr_del as $del_el){
                if($del_el['ID']){
                    $res = DiscountCouponTable::delete($del_el['ID']);
                }
            }


        }
    }

}
