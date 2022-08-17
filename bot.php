<?php
/*
Бот для telegram, созданный для курьеров, которые отправляют номера доставленных заказов, а операторы их подтверждают вручную на стороне 1С. 
Изменения по подтверждённым заказам при нажатии "Подтвердить" автоматически отображаются в чате, чтобы понимать какие заказы обработаны.
*/

// определяем кодировку
header('Content-type: text/html; charset=utf-8');
// Создаем объект бота
$bot = new Bot();
// Обрабатываем пришедшие данные
$bot->init('php://input');

/**
 * Class Bot
 */
class Bot
{
    // <bot_token> - созданный токен для нашего бота от @BotFather
    private $botToken = "1234567890:QwErTYUIOpasdfGHJKlZxcVbNM";
    // адрес для запросов к API Telegram
    private $apiUrl = "https://api.telegram.org/bot";

    public function init($data)
    {
		// Массив админов и операторов
		$admins = ['AdminNick'];
        // создаем массив из пришедших данных от API Telegram
        $arrData = $this->getData($data);

        // лог
		//$this->setFileLog($arrData);

        if (array_key_exists('message', $arrData)) {
            $chat_id = $arrData['message']['chat']['id'];
            $message = $arrData['message']['text'];
			$photo = $arrData['message']['photo'];

			$who = $arrData['message']['from']['username'];
			if ($arrData['message']['from']['first_name'] && $arrData['message']['from']['last_name']) {
				$who_delivery = $arrData['message']['from']['first_name'] . " " . $arrData['message']['from']['last_name'];
			} else {
				$who_delivery = $arrData['message']['from']['username'];
			}

        } elseif (array_key_exists('callback_query', $arrData)) {
            $chat_id = $arrData['callback_query']['message']['chat']['id'];
            $message = $arrData['callback_query']['data'];

			$who = $arrData['callback_query']['from']['username'];
			if ($arrData['callback_query']['from']['first_name'] && $arrData['callback_query']['from']['last_name']) {
				$who_accept = $arrData['callback_query']['from']['first_name'] . " " . $arrData['callback_query']['from']['last_name'];
			} else {
				$who_accept = $arrData['callback_query']['from']['username'];
			}
        }

        $justKeyboard = $this->getKeyBoard([[["text" => "Отправить номер доставленного заказа"], ["text" => "Сфотографировать накладную"], ["text" => "Помощь"]]]);

        $inlineKeyboard = $this->getInlineKeyBoard([[
			['text' => 'Подтвердить', 'callback_data' => 'accept']/*,
			['text' => 'Удалить', 'callback_data' => 'delete']*/
        ]]);

        switch ($message) {
            case '/start':
                $dataSend = array(
                    'text' => "Приветствую, давайте начнем.",
                    'chat_id' => $chat_id,
                    'reply_markup' => $justKeyboard,
                );
                $this->requestToTelegram($dataSend, "sendMessage");
                break;
            case 'Отправить номер доставленного заказа':
                $dataSend = array(
                    'text' => "Введите номер заказа",
                    'chat_id' => $chat_id
                );
				if (!in_array($who, $admins)) {
                	$this->requestToTelegram($dataSend, "sendMessage");
				}
                break;
			case 'Сфотографировать накладную':
                $dataSend = array(
                    'text' => "Отправьте фото накладной",
                    'chat_id' => $chat_id
                );
				if (!in_array($who, $admins)) {
                	$this->requestToTelegram($dataSend, "sendMessage");
				}
                break;
			case (is_numeric($message)):
				$dataSend = array(
					'text' => "Заказ <b>№" . $message . "</b> доставлен курьером <a href='t.me/" . $who . "'>" . $who_delivery . "</a>",
					'parse_mode' => 'HTML',
					'chat_id' => $chat_id,
					'disable_web_page_preview' => 'true',
					'reply_markup' => $inlineKeyboard,
				);
				if ($message != '') {
                	$this->requestToTelegram($dataSend, "sendMessage");
				}
                break;
            case 'Помощь':
				if (in_array($who, $admins)) {
					$dataSend = array(
						'text' => "Просто подтверждайте доставленные заказы после изменения статуса в 1С",
						'chat_id' => $chat_id,
					);
				} else {
					$dataSend = array(
						'text' => "Просто отправляйте номера доставленных заказов или фото накладных",
						'chat_id' => $chat_id,
					);
				}
                $this->requestToTelegram($dataSend, "sendMessage");
                break;
            case (preg_match('/^accept/', $message) ? true : false):
                $dataSend = array(
                    'message_id' => $arrData['callback_query']['message']['message_id'],
					'text' => "<del><b>Заказ " . preg_replace("/[^0-9]/", "", $arrData['callback_query']['message']['text']) . "</b> подтверждён" . 
						" пользователем <a href='t.me/" . $who . "'>" . $who_accept . "</a></del>",
					'parse_mode' => 'HTML',
					'disable_web_page_preview' => 'true',
                    'chat_id' => $chat_id,
                );
				if (in_array($who, $admins)) {
                	$this->requestToTelegram($dataSend, "editMessageText");
				}
                break;
			/*case (preg_match('/^delete/', $message) ? true : false):
                $dataSend = array(
                    'message_id' => $arrData['callback_query']['message']['message_id'],
                    'chat_id' => $chat_id,
                );
                $this->requestToTelegram($dataSend, "deleteMessage");
				break;*/
            default:
                $dataSend = array(
                    'text' => "Не верно введено, введите ТОЛЬКО номер доставленного заказа без букв вначале.",
                    'chat_id' => $chat_id,
                );
                $this->requestToTelegram($dataSend, "sendMessage");
                break;
        }

		if (isset($photo)) {
			$dataSend = array(
				'text' => "&#11014; Заказ доставлен курьером <a href='t.me/" . $who . "'>" . $who_delivery . "</a>",
				'parse_mode' => 'HTML',
				'chat_id' => $chat_id,
				'disable_web_page_preview' => 'true',
				'reply_markup' => $inlineKeyboard
			);
			$this->requestToTelegram($dataSend, "sendMessage");
		}
    }

    /**
     * создаем inline клавиатуру
     * @return string
     */
    private function getInlineKeyBoard($data)
    {
        $inlineKeyboard = array(
            "inline_keyboard" => $data,
        );
        return json_encode($inlineKeyboard);
    }

    /**
     * создаем клавиатуру
     * @return string
     */
    private function getKeyBoard($data)
    {
        $keyboard = array(
            "keyboard" => $data,
            "one_time_keyboard" => false,
            "resize_keyboard" => true
        );
        return json_encode($keyboard);
    }

    private function setFileLog($data)
    {
        $fh = fopen('log.txt', 'a') or die('can\'t open file');
        ((is_array($data)) || (is_object($data))) ? fwrite($fh, print_r($data, TRUE) . "\n") : fwrite($fh, $data . "\n");
        fclose($fh);
    }

    /**
     * Парсим что приходит преобразуем в массив
     * @param $data
     * @return mixed
     */
    private function getData($data)
    {
        return json_decode(file_get_contents($data), TRUE);
    }

    /** Отправляем запрос в Телеграмм
     * @param $data
     * @param string $type
     * @return mixed
     */
    private function requestToTelegram($data, $type)
    {
        $result = null;

        if (is_array($data)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $this->botToken . '/' . $type);
            curl_setopt($ch, CURLOPT_POST, count($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $result = curl_exec($ch);
            curl_close($ch);
        }
        return $result;
    }
}
