<?php

namespace Local\Iblock;

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use CCatalogSKU;
use CIBlockElement;
use Exception;

class actionAddOrUpdate
{
    /**
     * Array of iblock properties ids
     */
    public const PROPERTIES_INFO = array(
        'ACT_PRODUCTS' => array('ID' => 149, 'CODE' => 'PRODUCT_ID'),
        'BADGE_ACTION' => array('ID' => 769, 'CODE' => 'ATTACH_BADGE'),
        'BADGE_PRODUCT' => array('ID' => 130, 'CODE' => 'PRODUCT_BADGE')
    );

    /**
     * Method checks prerequisites to add element props
     *
     * @param array $item Item info
     */
    public static function OnAfterIBlockElementAdd(array $item): void
    {
        // Actions only
        if (IBLOCK_ACTIONS !== intval($item['IBLOCK_ID'])) {
            return;
        }

        $arProducts = $item['PROPERTY_VALUES'][self::PROPERTIES_INFO['ACT_PRODUCTS']['ID']];
        $arBadge = current($item['PROPERTY_VALUES'][self::PROPERTIES_INFO['BADGE_ACTION']['ID']])['VALUE'];

        if (count($arProducts) && $arBadge) {
            self::updateBadgeProperty($arBadge, $item, $arProducts);
        }
    }

    /**
     * Method checks prerequisites to update element props
     *
     * @param array $item Item info
     */
    public static function OnAfterIBlockElementUpdate(array $item): void
    {
        // Actions only
        if (IBLOCK_ACTIONS !== intval($item['IBLOCK_ID'])) {
            return;
        }

        $arProducts = $item['PROPERTY_VALUES'][self::PROPERTIES_INFO['ACT_PRODUCTS']['ID']];
        $arBadge = current($item['PROPERTY_VALUES'][self::PROPERTIES_INFO['BADGE_ACTION']['ID']]);
        // Check product activity
        if ($item['ACTIVE'] == 'N') {
            self::updateBadgeProperty('', $item, $arProducts);
        } else {
            if (count($arProducts) && $arBadge) {
                self::updateBadgeProperty($arBadge['VALUE'], $item, $arProducts);
            }
        }
    }

    /**
     * Method update badge property
     *
     * @param string $arBadge Badge value
     * @param array $item Action info
     * @param array $arProducts Action Items info
     */
    private static function updateBadgeProperty(string $arBadge, array $item, array $arProducts): void
    {
        try {
            Loader::includeModule('iblock');
        } catch (Exception $e) {
            return;
        }
        foreach ($arProducts as $arItem) {
            self::updateBitrixProperty(intval($arItem['VALUE']), array(self::PROPERTIES_INFO['BADGE_PRODUCT']['CODE'] => $arBadge));
        }
    }

    /**
     * Method executes bitrix function to update product/offer props
     *
     * @param int $productId Product or offer id
     * @param array $arrayToUpdate Array with props values to update
     */
    private static function updateBitrixProperty(int $productId, array $arrayToUpdate)
    {
        CIBlockElement::SetPropertyValuesEx(
            $productId,
            false,
            $arrayToUpdate
        );
    }
}
