<?php

namespace Local\API\Methods\Products;

use Bitrix\Main\HttpRequest;
use CIBlockElement;
use Local\API\Methods\BaseMethod;
use Local\API\Methods\IAPIMethodImplementation;

/**
 * Class handles 'products/deactivateoffers/' POST request
 * It returns by given XML_ID status of deactivation
 *
 * Income: json array with 'XML_ID' key
 *
 * Outcome: assoc. array of XML_ID and status of deactivation
 */
class DeactivateOffers extends BaseMethod implements IAPIMethodImplementation
{
    /**
     * RemoveDeliveryScheme constructor
     *
     * @param HttpRequest $request
     * @param string $requestBody
     */
    public function __construct(HttpRequest $request, string $requestBody)
    {
        parent::__construct($request, $requestBody);
    }

    /**
     * Handle request
     */
    public function execute(): void
    {
        if (!$this->loadModules(array('iblock')))
            return;

        $arIds = $this->getIdsFromRequestBody();
        if (!count($arIds))
            return;

        $this->httpStatus = '200';
        $this->data = array(
            'status' => 'success',
            'description' => '',
            'data' => json_encode($this->deactivateOffers($arIds))
        );
    }

    /**
     * Method extracts XML_ID array from request body
     *
     * @return array
     */
    private function getIdsFromRequestBody(): array
    {
        $arRequestBody = json_decode($this->requestBody, true);
        if (!array_key_exists('xml-id', $arRequestBody) || !count($arRequestBody['xml-id'])) {
            $this->httpStatus = '400';
            $this->data = array(
                'status' => 'error',
                'description' => 'xml-id array missing or empty or failed to decode',
                'data' => ''
            );
            $this->stopProcessing = true;
            return array();
        } elseif (is_string($arRequestBody['xml-id'])) {
            return array($arRequestBody['xml-id']);
        } elseif (is_array($arRequestBody['xml-id'])) {
            return $arRequestBody['xml-id'];
        } else {
            return array();
        }
    }


    /**
     * Method deactivate product offers by given XML_ID
     *
     * @param array $arIds
     *
     * @return array
     */
    private function deactivateOffers(array $arIds): array
    {
        $arrInactive = $alreadyDeactivated = array();
        $arSelect = array('ID', 'ACTIVE', 'XML_ID');
        $arFilter = array('IBLOCK_ID' => IBLOCK_TOVAR_OFFERS, 'XML_ID' => $arIds);

        $dbIdsResult = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
        if ($dbIdsResult->AffectedRowsCount() == '0') {
            $arrInactive['error'] = 'all elements xml_ids are incorrect';
        }

        while ($arElement = $dbIdsResult->GetNext()) {
            if ($arElement['ACTIVE'] !== 'N') {
                $needToBeDeactivated[$arElement['XML_ID']] = $arElement ['ID'];
            } else {
                $alreadyDeactivated[$arElement['XML_ID']] = 'this item is already deactivated';
            }
            $processedIds[] = $arElement['XML_ID'];
        }

        $notProcessed = array_diff($arIds, $processedIds);
        foreach ($notProcessed as $xmlID) {
            $arrInactive[$xmlID] = 'this item isn\'t deactivate because XML_ID is incorrect';
        }

        if (isset($needToBeDeactivated)) {
            $el = new CIBlockElement;

            $arLoadProductArray = Array("ACTIVE" => "N");

            foreach ($needToBeDeactivated as $xml=>$id) {
                if ($res = $el->Update($id, $arLoadProductArray)) {
                    $arrInactive[$xml] = 'success';
                } else {
                    $arrInactive[$xml] = 'error';
                }
            }
        }

        if (isset($alreadyDeactivated)) {
            $arrInactive = $arrInactive + $alreadyDeactivated;
        }

        return $arrInactive;
    }
}
