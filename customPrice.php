<?php

/*
A small class with a single method that adds a fixed margin or a percentage margin to all products in the feed
*/

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class CustomPrice extends Price {

	const MARGIN = '10%';

	protected function formatValue($value, array $context = [], Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$this->resolveValueRatio($settings);

		if (self::MARGIN && self::MARGIN !== '') {

			if (mb_strpos(self::MARGIN, '%')) {
				$margin = rtrim(self::MARGIN, '%')/100;
				$value = $value + $value*(float)$margin;
				echo $value;
			} else if (is_numeric(self::MARGIN) && !mb_strpos(self::MARGIN, '.')) {
				$value = $value + (int)self::MARGIN;
				echo $value;
			} else if (mb_strpos(self::MARGIN, '.')) {
				$value = $value + (float)self::MARGIN;
				echo $value;
			} else {
				\Local\Init\ServiceHandler::writeToLog('Значение наценки MARGIN ' . self::MARGIN . 
					' установлено неверно.', '/local/logs/mylog.txt', 'CustomPrice Yandex Market');
			}
		}

		return parent::formatValue(intval($value), $context, $nodeResult, $settings);
	}

}
