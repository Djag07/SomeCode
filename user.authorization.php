<?php
/*
Class of user autorization via sms
*/

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true): die(); endif;

use Bitrix\Main\Localization\Loc;
use Custom\Service\Loggers\FileLogger;
use Custom\Service\Result;
use Custom\User\Entities\User;
use Bitrix\Main\Application;
use Local\Captcha\GoogleReCaptcha;

Loc::loadMessages(__FILE__);

class UserAuthorizationComponent extends \CBitrixComponent
{
    protected $obCodeGenerate;
    protected $obUserData;

    protected $obResult;
    protected $obRequest;
    protected $arRequestPost = array();

    function __construct($component)
    {
        parent::__construct($component);

        $this->includeLibClasses();
        $this->createObjectClasses();

        $this->arRequestPost = $this->getArrayPost();
    }

    /**
     * Method include classes lib
     *
     * @return bool
     * @throws Exception
     */
    protected function includeLibClasses()
    {
        if ((include 'lib/confirm_code.php') && (include 'lib/request_handler.php')) {
            return true;
        }
        throw new \Exception('Can\'t include lib classes! ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
    }

    /**
     * Method create object classes libs
     *
     * @return bool
     * @throws Exception
     */
    protected function createObjectClasses()
    {
        if (($this->obCodeGenerate = new ConfirmCode()) &&
            ($this->obUserData = new \Custom\User\Entities\UserSearch()) &&
            ($this->obResult = new Result\ResultJson()) &&
            ($this->obRequest = new UserAuthRequestHandler())
        ) {
            return true;
        }
        throw new \Exception('Can\'t include lib classes! ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
    }

    /**
     * Method get array POST
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function getArrayPost()
    {
        return $this->obRequest->getArrayPost();
    }

    /**
     * Parent bitrix CBitrixComponent class method prepare settings component
     *
     * @param $params
     *
     * @return array
     */
    public function onPrepareComponentParams($params)
    {
        return $this->arParams;
    }

    /**
     * Parent Bitrix CBitrixComponent class method component controller
     */
    public function executeComponent()
    {
        if ($this->obRequest->checkRequestParamsComponent()) {
            $this->callActionMethod($this->arRequestPost['action']);

            $this->showAjaxResult();
        } else {
            $this->includeComponentTemplate();
        }
    }

    /**
     * Method dynamic call methods
     *
     * @param string $method
     *
     * @return mixed
     * @throws Exception
     */
    protected function callActionMethod(string $method)
    {
        if (!empty($method)) {
            if (method_exists(__CLASS__, $method)) {
                return $this->{$method}();
            }
            throw new \Exception('Not found method ' . $method . ' ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
        }
        throw new \Exception('Argument method can not by empty ' . $method . ' ' . __CLASS__ . ' ' . __METHOD__ . ' '
            . __LINE__);
    }

    /**
     * Action method enter user handler
     *
     * @return bool
     */
    private function authUserGetCodeAction()
    {
        //search user in site
        $arUser = $this->obUserData->searchUserByPhoneNumber($this->arRequestPost['phoneUser']);
        if (empty($arUser) || !is_array($arUser)) {
            $this->obResult->addError(Loc::getMessage('UE_ERROR_USER_EMPTY'), 0001, 'auth');
            $this->addMessageToLog(Loc::getMessage('UE_ERROR_USER_EMPTY') . "\n" .
                $this->arRequestPost['phoneUser']);
            return false;
        }
        if (count($arUser) > 1) {
            $this->obResult->addError(Loc::getMessage('UE_ERROR_USER_MORE'), 0002, 'auth');
            $this->addMessageToLog(Loc::getMessage('UE_ERROR_USER_MORE') . "\n" .
                $this->arRequestPost['phoneUser']);
            return false;
        }

        //generate confirm code
        $resultData = $this->getCodeConfirm();
        if (empty($resultData) || !is_array($resultData)) {
            $this->obResult->addError(Loc::getMessage('UE_ERROR_CODE_GEN'), 0003, 'auth');
            $this->addMessageToLog(Loc::getMessage('UE_ERROR_CODE_GEN') . "\n" .
                $this->arRequestPost['phoneUser']);
            return false;
        }
        // send confirm code
        $phone = $this->preparePhoneUser($this->arRequestPost['phoneUser']);

        // check captcha
        $errorMessages = GoogleReCaptcha::checkClientResponse();
        if (!$errorMessages) {
          $captcha_res = true;
          $resultSend = $this->sendCodeForConfirm($phone, $resultData['code']);
        }

        // add error message to log
        if (!$resultSend) {
          if (!$captcha_res) {
            $this->obResult->addError(Loc::getMessage('UE_ERROR_CAPTCHA_CODE'), 0004, 'auth');
            $this->addMessageToLog(Loc::getMessage('UE_ERROR_CAPTCHA_CODE') . "\n" .
              'phone: ' . $this->arRequestPost['phoneUser'] . ', code: ' . $resultData['code'] . ' (auth)');
          } else {
            $this->obResult->addError(Loc::getMessage('UE_ERROR_CODE_SEND'), 0004, 'auth');
            $this->addMessageToLog(Loc::getMessage('UE_ERROR_CODE_SEND') . "\n" .
              'phone: ' . $this->arRequestPost['phoneUser'] . ', code: ' . $resultData['code'] . ' (auth)');
          }
          return false;
    }

    $resultData['phoneUser'] = $this->arRequestPost['phoneUser'];

		unset($resultData['code']);
    $this->obResult->setData($resultData);
    $this->obResult->addNotice(Loc::getMessage('UE_SUCCESS_CODE_SEND'), 0001, 'auth');

    return true;
    }


    /**
     * Method get code for confirm phone user
     *
     * @return array|bool
     */
    private function getCodeConfirm()
    {
        $codeData = array();
        //get numeric confirm code
        $codeData['code'] = $this->obCodeGenerate->generateConfirmCode();
        if ($codeData['code']) {
            //get crypt confirm code for send to html form for compare
            $encryptionObject = new Custom\Security\Encryption\Workers\HashAddEncryptionSymmetric();
            $this->obCodeGenerate->setEncryptionObject($encryptionObject);
            $codeData['crypt'] = rawurlencode($this->obCodeGenerate->generateConfirmCodeCrypt($codeData['code']));
            if (!empty($codeData['code']) && !empty($codeData['crypt'])) {
                return $codeData;
            }
        }

        return false;
    }

    private function preparePhoneUser($phone)
    {
        return '8' . strval($phone);
    }

    /**
     * Method send code confirm user contact
     *
     * @param $contact
     * @param $code
     *
     * @return mixed
     */
    private function sendCodeForConfirm($contact, $code)
    {
        $notification = new Custom\Notification\Services\MegafonSms();

        $notification->setContact($contact);
        $notification->setMessage($code);
        $notification->setSenderName('projectname');
        $notification->setViaComponent($this->GetName());

        if (!$notification->sendData()) {
            $notification->setViaComponent($this->GetName() . ' from reserve');
            return $notification->sendDataViaReserveServer();
        } else {
            return true;
        }

    }

    /**
     * Action method enter phone code
     */
    private function enterCodeAuthAction()
    {
        $originalCode = rawurldecode($this->arRequestPost['originalCode']);
        $enterCode = intval($this->arRequestPost['enterCode']);

        $encryptionObject = new Custom\Security\Encryption\Workers\HashAddEncryptionSymmetric();
        $this->obCodeGenerate->setEncryptionObject($encryptionObject);

        if (!$this->obCodeGenerate->validateEnterCode(html_entity_decode($originalCode), $enterCode)) {
            $this->obResult->addError(Loc::getMessage('UE_ERROR_CODE_INCORRECT'), 0001, 'enterCode');
            $this->addMessageToLog(Loc::getMessage('UE_ERROR_CODE_INCORRECT') . "\n" .
                'phone: ' . $this->arRequestPost['phoneUser'] . ', orig. code: ' . html_entity_decode($originalCode) .
                ', entered code: ' . $enterCode);
            return false;
        }

        $arUser = $this->obUserData->searchUserByPhoneNumber($this->arRequestPost['phoneUser']);
        if (empty($arUser)) {
            $this->obResult->addError(Loc::getMessage('UE_ERROR_USER_EMPTY'), 0002, 'enterCode');
            return false;
        }

        $resultAuth = User::authorizeUserById($arUser[0]['ID'], $this->GetName());

        $this->obResult->setData(array('auth' => $resultAuth));
        $this->obResult->addNotice(Loc::getMessage('UE_SUCCESS_CODE_ENTER'), 0001, 'enterCode');
    }

    /**
     * Method output ajax response
     *
     */
    protected function showAjaxResult()
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();

        echo($this->obResult->getResult());

        die();
    }


    /**
     * Method add log message to common auth log
     *
     * @param $message
     */
    private function addMessageToLog($message)
    {
        $logger = new FileLogger(\Custom\Notification\Services\MegafonSms::LOG_FILE_SENDING);
        $logger->insertToLog($message);
    }
}
