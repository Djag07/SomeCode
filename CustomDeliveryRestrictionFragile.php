<?php

/*
A simple restriction for delivery services to check whether the product has the fragility property obtained from 1C
*/

use Bitrix\Main\Localization\Loc;

/**
 * Class CustomDeliveryRestrictionFragile
 */
class CustomDeliveryRestrictionFragile extends Bitrix\Sale\Delivery\Restrictions\Base
{
	const FRAGILE_PROPERTY_NAME = 'RESTRICTION_REGIONS';
    /**
     * @return string
     */
    public static function getClassTitle()
    {
        return Loc::getMessage('CDRF_RESTRICTION_NAME');
    }

    /**
     * @return string
     */
    public static function getClassDescription()
    {
        return Loc::getMessage('CDRF_RESTRICTION_DESCRIPTION');
    }

    /**
     * @param \Bitrix\Sale\Shipment $shipment
     *
     * @return bool
     */
    protected static function extractParams(Bitrix\Sale\Shipment $shipment)
    {
        $collection = $shipment->getCollection();
        $order = $collection->getOrder();
        $basket = $order->getBasket();

        $is_fragile = false;

        $arElementIds = array();

        foreach ($basket->getBasketItems() as $item) {
            $arElementIds[] = $item->getProductId();
        }

        $dbElementResult = CIBlockElement::GetList(
            array(),
            array('IBLOCK_ID' => array(IBLOCK_TOVAR, IBLOCK_OFFERS), 'ID' => $arElementIds),
            false,
            false,
            array('ID', 'IBLOCK_ID', 'PROPERTY_' . self::FRAGILE_PROPERTY_NAME)
        );
        while ($arElement = $dbElementResult->GetNext()) {

            if (isset($arElement['PROPERTY_' . self::FRAGILE_PROPERTY_NAME . '_VALUE']) &&
                ($arElement['PROPERTY_' . self::FRAGILE_PROPERTY_NAME . '_VALUE'] == 'Y')
            ) {
                $is_fragile_product = true;
                break;
            }
        }

        return $is_fragile_product;
    }

    /**
     * @param int $deliveryId
     *
     * @return array
     */
    public static function getParamsStructure($deliveryId = 0)
    {
        return array(
            "CHECK_FRAGILE" => array(
                'TYPE' => 'Y/N',
                'LABEL' => Loc::getMessage('CDRF_RESTRICTION_CHECK'),
            ),
        );
    }

    /**
     * Disable/enable delivery
     *
     * @param mixed $isFragile
     * @param array $restrictionParams
     * @param int $deliveryId
     *
     * @return bool true|false
     */
    public static function check($isFragile, array $restrictionParams, $deliveryId = 0)
    {
        if ($isFragile && ('Y' == $restrictionParams['CHECK_FRAGILE'])) {
            return false;
        }

        return true;
    }
}
