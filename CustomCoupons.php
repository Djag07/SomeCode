<?php
/*
Сlass for working with custom one-time, limited and unlimited coupons
*/

namespace Local\Sale;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Sale\Result;
use Bsl\Mobile\Session\Service\CurrentUuidFieldSessionService;
use Local\Init\ServiceHandler;


class CustomCoupons
{
    const IBLOCK_STATISTIC_COUPON = 8;
    protected $arStatisticProps = array(
        'USER' => 'RELATION_USER',
        'COUPONS' => 'COUPONS_CODES'
    );
    protected $arCacheSettings = array(
        'GET_USER_ENTER_COUPON' => array(
            'PATH' => 'custom_discount/coupons/user_statistic/',
            'TIME' => 3600000,
            'ID_PREFIX' => 'USER_STATISTIC_DATA_'
        )
    );

    public function __construct()
    {
        $this->includeBitrixModules();
    }

    protected function includeBitrixModules()
    {
        Loader::includeModule("iblock");
        Loader::includeModule("catalog");
        Loader::includeModule('mobile');
    }

    /**
     * Method get type coupon CERTIFICATE or CUSTOM_LIMITED or STANDARD or LIMITLESS or LIMITED_UNLIM
     *
     * @param $coupon
     *
     * @return string
     */
    public function getTypeCoupon($coupon)
    {
        $checkSocialLimited = $this->checkCouponIsSocialLimited($coupon);
        $checkLimitlessLimited = $this->checkCouponIsLimitlessLimited($coupon);
        $checkMultiple = $this->checkCouponIsMultiple($coupon);
        $checkCertificate = $this->checkCouponIsCertificate($coupon);
        $checkLimitless = $this->checkCouponIsLimitless($coupon);
        $checkSocial = $this->checkCouponIsSocial($coupon);

        if ($checkSocialLimited) {
            return 'SOCIAL_LIMITED';
        } elseif ($checkLimitlessLimited) {
            return 'LIMITED_UNLIM';
        } elseif ($checkMultiple) {
            return 'CUSTOM_LIMITED';
        } elseif ($checkCertificate) {
            return 'CERTIFICATE';
        } elseif ($checkLimitless) {
            return 'LIMITLESS';
        } elseif ($checkSocial) {
            return 'SOCIAL';
        } else {
            return 'STANDARD';
        }
    }

    /**
     * Method check coupon entered more times and delete coupon
     *
     * @param $coupon
     * @param $userId
     * @param $customTypeCoupon
     *
     * @return bool
     */
    public function checkCouponEntered($coupon, $userId, $customTypeCoupon)
    {
        // Check mobile only coupon and request
        if ($this->isCouponForMobileAppOnly($coupon)) {
            ServiceHandler::writeToLog(array('customTypeCoupon' => $customTypeCoupon, 'coupon' => var_export($coupon, true)), '', '$customTypeCoupon');
            if (!$this->isRequestFromMobileApp()) {
                if (DiscountCouponsManager::delete($coupon)) {
                    return true;
                }
            }
        }

        // Сheck coupon entered more than onсe
        if ($customTypeCoupon === 'CERTIFICATE' || $customTypeCoupon === 'CUSTOM_LIMITED' || $customTypeCoupon === 'LIMITED_UNLIM' || $customTypeCoupon === 'SOCIAL_LIMITED') {
            $couponsOld = $this->getUserEnterCoupon($userId);
            if (!empty($couponsOld['COUPONS'])) {
                $arCoupons = html_entity_decode($couponsOld['COUPONS']);
                $arOldCoupons = json_decode($arCoupons);
                $resultCheck = in_array($coupon, $arOldCoupons);
                if ($resultCheck) {
                    $resultDelete = DiscountCouponsManager::delete($coupon);
                    if ($resultDelete) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Method check coupon from custom multiple
     * coupon can get all user, cannot get more two times
     *
     * @param $couponCode
     *
     * @return bool
     */
    protected function checkCouponIsMultiple($couponCode)
    {
        if (is_array($couponCode)) {
            $couponCode = array_keys($couponCode)[0];
        }
        return (stripos($couponCode, '_ABC') !== false) ? true : false;
    }

    /**
     * Method check coupon from certificate
     *
     * @param $couponCode
     * @return bool
     */
    protected function checkCouponIsCertificate($couponCode)
    {
        if (is_array($couponCode)) {
            $couponCode = array_keys($couponCode)[0];
        }
        return (stripos($couponCode, 'CERT_') !== false) ? true : false;
    }

    /**
     * Method check coupon
     * coupon can get all user, coupon is limitless
     *
     * @param $couponCode
     *
     * @return bool
     *
     * Спецкупон для игнорирования свойства На товар предоставляются доп. скидки
     */
    protected function checkCouponIsLimitless($couponCode)
    {
        if (is_array($couponCode)) {
            $couponCode = array_keys($couponCode)[0];
        }
        return (stripos($couponCode, '_UNLIM') !== false) ? true : false;
    }


    /**
     * Method checks mobile only coupon
     *
     * @param string|array $couponCode
     *
     * @return bool
     */
    private function isCouponForMobileAppOnly($couponCode): bool
    {
        if (is_array($couponCode)) {
            $couponCode = array_keys($couponCode)[0];
        }

        return false !== stripos($couponCode, '_MOB_');
    }

    /**
     * Method checks device id.
     * Device id sets on mobile application only
     *
     * @return bool
     */
    private function isRequestFromMobileApp(): bool
    {
        ServiceHandler::writeToLog(CurrentUuidFieldSessionService::get(), '', 'CurrentUuidFieldSessionService::get()');
        return (0 < mb_strlen(CurrentUuidFieldSessionService::get()));
    }

    /**
     * Method check coupon
     * coupon can get all user, coupon is limitless, cannot get more two times
     *
     * @param $couponCode
     *
     * @return bool
     *
     * Спецкупон для игнорирования свойства На товар предоставляются доп. скидки, который можно использовать один раз
     */
    protected function checkCouponIsLimitlessLimited($couponCode)
    {
        if (is_array($couponCode)) {
            $couponCode = array_keys($couponCode)[0];
        }
        return (stripos($couponCode, '_ABC_UNLIM') !== false) ? true : false;
    }

	/**
     * Method check coupon
     * coupon can get all user, coupon is limitless
     *
     * @param $couponCode
     *
     * @return bool
     *
     * Спецкупон для игнорирования свойства На товар предоставляются доп. скидки
     */
    protected function checkCouponIsSocial($couponCode)
    {
        if (is_array($couponCode)) {
            $couponCode = array_keys($couponCode)[0];
        }
        return ((stripos($couponCode, '_VSEM') !== false) || (stripos($couponCode, '_4U') !== false) || (stripos($couponCode, '_XL') !== false)) ? true : false;
    }

	/**
     * Method check coupon
     * coupon can get all user, coupon cannot get more two times
     *
     * @param $couponCode
     *
     * @return bool
     *
     * Спецкупон для игнорирования свойства На товар предоставляются доп. скидки, который можно использовать один раз
     */
    protected function checkCouponIsSocialLimited($couponCode)
    {
        if (is_array($couponCode)) {
            $couponCode = array_keys($couponCode)[0];
        }
        return (stripos($couponCode, '_ABC_VSEM') !== false) ? true : false;
    }

    /**
     * Method get statistic row user
     *
     * @param $userId
     *
     * @return array
     */
    protected function getUserEnterCoupon($userId)
    {
        $cache = Cache::createInstance();
        $cacheTime = $this->arCacheSettings['GET_USER_ENTER_COUPON']['TIME'];
        $cacheDir = $this->arCacheSettings['GET_USER_ENTER_COUPON']['PATH'];
        $cacheId = md5($this->arCacheSettings['GET_USER_ENTER_COUPON']['ID_PREFIX'] . $userId);

        if ($cache->initCache($cacheTime, $cacheId, $cacheDir)) {
            $arUserStatistic = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $arUserStatistic = array();

            $arSelect = array("ID", "PROPERTY_" . $this->arStatisticProps["COUPONS"]);
            $arFilter = array(
                "ACTIVE" => "Y",
                "IBLOCK_ID" => self::IBLOCK_STATISTIC_COUPON,
                "PROPERTY_" . $this->arStatisticProps["USER"] => $userId
            );

            $resultEnter = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
            while ($obEnter = $resultEnter->GetNext()) {
                $arUserStatistic['ID'] = $obEnter['ID'];
                $arUserStatistic['COUPONS'] = $obEnter["PROPERTY_" . $this->arStatisticProps["COUPONS"] . '_VALUE'];
            }

            if (empty($arUserStatistic)) {
                $cache->abortDataCache();
            }

            $cache->endDataCache($arUserStatistic);
        }

        return $arUserStatistic;
    }


    public function updateCouponsStatistic($userId, $arCoupons, $oldCoupons = "")
    {
        $arUserStatistic = $this->getUserEnterCoupon($userId);
        $couponsJson = $this->prepareCouponsJson($arCoupons, $arUserStatistic['COUPONS']);

        if ($arUserStatistic['ID'] !== false && $arUserStatistic['ID'] > 0) {
            $this->updateCouponsEnteredFromStatistic($couponsJson, $arUserStatistic['ID']);
        } else {
            $this->addNewCouponsEnteredFromStatistic($userId, $couponsJson);
        }
    }

    /**
     * This method prepare array coupons from Json format
     *
     * @param $arCoupons
     * @param string $oldCoupons
     *
     * @return string Json
     */
    protected function prepareCouponsJson($arCoupons, $oldCoupons = "")
    {
        $arNewCoupons = array();
        if (!empty($oldCoupons)) {
            $oldCoupons = html_entity_decode($oldCoupons);
            $oldCoupons = json_decode($oldCoupons);
            foreach ($oldCoupons as $keyOld => $valueOld) {
                $arNewCoupons[] = $valueOld;
            }
        }
        foreach ($arCoupons as $keyCoupon => $valueCoupon) {
            $arNewCoupons[] = $valueCoupon['COUPON'];
        }
        $arNewCouponsJson = json_encode($arNewCoupons);

        return $arNewCouponsJson;
    }

    /**
     * Method update row with data applies coupons limited or coupons certificate from iblock statistic
     *
     * @param $coupons
     * @param $idRows
     *
     * @return Result|bool|Bitrix\Sale\BasketItem
     */
    protected function updateCouponsEnteredFromStatistic($coupons, $idRows)
    {
        $result = new Result();
        if (!empty($coupons) && (int)$idRows) {

            \CIBlockElement::SetPropertyValuesEx($idRows, false,
                array($this->arStatisticProps["COUPONS"] => $coupons));

            $this->clearCacheCoupons();

            return true;
        } else {
            $result->addError(new Sale\ResultWarning('Incorrect method arguments', 'CUSTOM_UPDATE_COUPON_STATISTIC'));
            return $result;
        }
    }


    /**
     * Method add new row with data applies coupons limited or coupons certificate from iblock statistic
     *
     * @param $userId
     * @param $coupons
     *
     * @return bool|Bitrix\Sale\BasketItem
     */
    protected function addNewCouponsEnteredFromStatistic($userId, $coupons)
    {
        $result = new Result();

        $addElementStatistic = new \CIBlockElement;
        $arPropertiesAdd = array(
            $this->arStatisticProps["USER"] => $userId,
            $this->arStatisticProps["COUPONS"] => $coupons,
        );

        $arStatisticElement = array(
            'MODIFIED_BY' => $userId,
            'IBLOCK_SECTION_ID' => false,
            'IBLOCK_ID' => self::IBLOCK_STATISTIC_COUPON,
            'PROPERTY_VALUES' => $arPropertiesAdd,
            'NAME' => 'Покупатель' . $userId,
            'CODE' => 'USER_' . $userId,
            'ACTIVE' => 'Y',
        );

        if ($newStatisticId = $addElementStatistic->Add($arStatisticElement)):
            return $newStatisticId;
        else:
            $result->addError(new Sale\ResultWarning('Error add rows from statistic', 'CUSTOM_ADD_COUPON_STATISTIC'));
            return $result;
        endif;
    }

    /**
     * Method add new coupon from rule work with basket
     *
     * @param $dataCoupon
     * @param string $codePrefix
     *
     * @return Result|Bitrix\Sale\BasketItem
     */
    public function addCouponFromRulesWorkWithBasket($dataCoupon, $codePrefix = '')
    {
        $result = new Result();

        $newCouponCode = $this->generateCodeCoupon($codePrefix);
        $dataCoupon['COUPON'] = $newCouponCode;

        if ($dataCoupon['DISCOUNT_ID'] > 0 && $dataCoupon['TYPE'] > 0) {
            $addDb = DiscountCouponTable::add($dataCoupon);
            if (!$addDb->isSuccess()) {
                $result->addError(new Sale\ResultWarning('Error add coupon from rules work basket',
                    'CUSTOM_ADD_COUPON_STATISTIC'));
            }
        } else {
            $result->addError(new Sale\ResultWarning('Incorrect coupon params DISCOUNT_ID or  TYPE',
                'CUSTOM_ADD_COUPON_STATISTIC'));
        }
        return $result;
    }


    public function addCertificateUser($discountId)
    {
        $activeTo = new DateTime();
        $activeTo = $activeTo->add('183 days');
        $dataCoupon = array(
            'DISCOUNT_ID' => $discountId,
            'COUPON' => "",
            'TYPE' => 2,
            'ACTIVE_FROM' => new DateTime(),
            'ACTIVE_TO' => $activeTo,
            'MAX_USE' => 1,
            'USER_ID' => 0,
            'DESCRIPTION' => ''
        );
        $this->addCouponFromRulesWorkWithBasket($dataCoupon, 'CERT_');
    }

    /**
     * This method generate new coupon code
     *
     * @param $codePrefix
     *
     * @return string|Bitrix\Sale\BasketItem
     */
    public function generateCodeCoupon($codePrefix)
    {
        $allChars = 'ABCDEFGHIJKLNMOPQRSTUVWXYZ0123456789';

        do {
            $string1 = '';
            $string2 = '';
            for ($i = 0; $i < 5; $i++)
                $string1 .= substr($allChars, round((rand(0, 10) * 0.1) * (strlen($allChars) - 1)), 1);
            for ($i = 0; $i < 7; $i++)
                $string2 .= substr($allChars, round((rand(0, 10) * 0.1) * (strlen($allChars) - 1)), 1);

            $newCoupon = $string1 . "-" . $string2;
            if (!empty($codePrefix)) {
                $newCoupon = $codePrefix . $newCoupon;
            }

            $dbCouponCheck = \CCatalogDiscountCoupon::GetList(array(), array("COUPON" => $newCoupon), false, false, array('ID'));

        } while (intval($dbCouponCheck->SelectedRowsCount()) > 0);

        return $newCoupon;
    }

    protected function clearCacheCoupons()
    {
        $cache = Cache::createInstance();
        $cache->cleanDir($this->arCacheSettings['GET_USER_ENTER_COUPON']['PATH']);

        return true;
    }
}
