<?php
/*
Скрипт по созданию свойства тип товара для всех товаров для реализации удобной фильтрации по нему в корневых разделах каталога. 
В дальнейшем это свойство заполнялось вручную при создании карточки в 1С контент-менеджером
*/

$_SERVER["DOCUMENT_ROOT"] = '/home/bitrix/ext_www/';

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define("NO_AGENT_CHECK", true);
define('LID', "s1");

//Константы ID и CODE свойства Тип товара
const PROPERTY_ID = 123;
const PROPERTY_CODE = 'TYPE_OF_PRODUCT';

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
\Bitrix\Main\Loader::IncludeModule('iblock');
set_time_limit(0);
$time = -(time());?>
<?
//ID инфоблока каталога; секции, которые не добавляем по имени и ID; счётчик товаров для отчёта
$CATALOG = 2;
$MINUSSECT = array(2542, 5474, 4272);
$SALE = 'Распродажа';
$COUNT=0;

//Создаём новый файл, куда добавляем товары, привязанные к категории первого или второго уровня для перепривязки
$fileNew = $_SERVER["DOCUMENT_ROOT"] . '/test/with_reference_to_the_second_level' . '.csv';

$fn = fopen($fileNew, 'w+');

//Отправляем заголовки ID и названия товара, названия категории и артикула в 1С
if (INSERT_CSV_HEADERS) {
	fprintf($fn, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($fn, array(
		'ID',
        'Name',
        'Category name',
        'Article in 1c'
    ), ';');
}

//Getlist по всем активным и доступным товарам с выборкой ID, названия, ID главной секции и артикула в 1С
$elements = CIBlockElement::GetList (
	Array("ID" => "ASC"),
	Array("IBLOCK_ID" => $CATALOG, "ACTIVE" => 'Y', "CATALOG_AVAILABLE" => 'Y'),
	false,
	false,
	Array('ID', 'NAME', 'PROPERTY_CML2_ARTICLE', 'IBLOCK_SECTION_ID')
);

//Проходим по  товарам с учётом фильтрации
while($ar_elem_fields = $elements->GetNext()) { 
	//Выводим ID и название товара
	echo 'Товар, ID ' . $ar_elem_fields['ID'] . ', название ' . $ar_elem_fields['NAME'];
	//Получаем все привязанные к товару категории
	$db_old_groups = CIBlockElement::GetElementGroups($ar_elem_fields['ID'], true);
	while($ar_group = $db_old_groups->Fetch()) {
		//Не учитываем брендовые и технические секции и разделы распродажи 
		if (!in_array($ar_group['IBLOCK_SECTION_ID'], $MINUSSECT) && ($ar_group['NAME'] != $SALE)) {
			//Если к товару привязан раздел больше 3 уровня вложенности
			if ($ar_group['DEPTH_LEVEL']>3) {
				//Кешируем ID и имя секции родителя
				if (!isset($ar_parent_name[$ar_group['ID']])) {
					//Получаем информацию о секции
					$res = CIBlockSection::GetByID($ar_group['IBLOCK_SECTION_ID']);
					if($ar_res = $res->GetNext()) {
						echo '<pre>'; print_r('Имя секции - ' . $ar_res['NAME'] . '<br><br>'); echo '</pre>';
						//Кешируем имя родительского раздела для минимизации запросов в базу
						$ar_parent_name[$ar_group['ID']] = $ar_res['NAME'];
					}
				} else {
					//Достаем из кеша, если он есть, имя родительского раздела
					$ar_res['NAME'] = $ar_parent_name[$ar_group['ID']];
				}
				//Если товар привязан к разделу выше 3 уровня вложенности, записываем в массив имя родительского раздела
				$elemgroups[$ar_group['ID']] = array("ID"=>$ar_group['IBLOCK_SECTION_ID'], "NAME"=>$ar_res['NAME'], "DEPTH"=>$ar_group['DEPTH_LEVEL']);
			} else {
				//Если товар привязан к разделу ниже или равного 3 уровню вложенности, записываем в массив имя самого раздела
				$elemgroups[$ar_group['ID']] = array("ID"=>$ar_group['ID'], "NAME"=>$ar_group['NAME'], "DEPTH"=>$ar_group['DEPTH_LEVEL']);
			}
		}
	}
	//Объявляем массив ID значений свойства Тип продукта для последующей записи в базу
	$types_of_product=array();
	//Проходимся по всем привязанным к товару разделам
	foreach ($elemgroups as $key=>$elemgroup) {
		echo '<pre>'; print_r('Имя секции - ' . $elemgroup['NAME'] . '<br><br>'); echo '</pre>';
		//Если глубина вложенности меньше 3, записываем в файл информацию о товаре для последующей перепривязки
		if ($elemgroup['DEPTH'] < 3) {
			//Если товара еще нет в файле, создаём массив с информацией для последующей записи в файл
			if (!isset($id)) {
				$id = (int)$ar_elem_fields['ID'];
				$name = $ar_elem_fields['NAME'];
				$category_name = $elemgroup['NAME'];
				$article_1c = $ar_elem_fields['PROPERTY_CML2_ARTICLE_VALUE'];
				//Непосредственно запись в файл
				fputcsv($fn, array(
					$id,
					$name,
					$category_name,
					$article_1c
				), ';');
			}
		}
		//Получаем значение свойства Тип товара с данным привязанным к товару разделом
		$property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$CATALOG, 
			"CODE"=>PROPERTY_CODE, "VALUE"=>$elemgroup['NAME']));
		while($enum_fields = $property_enums->GetNext()) {
			//Записываем ID данного значения для последующей привязки к товару
			$exist = $enum_fields['ID'];
		}
		//Если значение свойства Тип товара с данным привязанным к товару разделом не существует, создаём его
		if (!$exist) {
			$ibpenum = new CIBlockPropertyEnum;
			if($PropID = $ibpenum->Add(Array('PROPERTY_ID'=>PROPERTY_ID, 'VALUE'=>strval($elemgroup['NAME']))));
			//Записываем ID данного значения для последующей привязки к товару
			$exist = $PropID;
		}
		//Записываем все ID значений разделов для данного товара в массив для последующего обновления свойств
		$types_of_product[]=$exist;
		unset($exist);
	}
	//На всякий случай делаем массив всех ID значений разделов для данного товара уникальным
	$types_of_product=array_unique($types_of_product);
	echo '<pre>'; print_r($types_of_product); echo '</pre>';
	//Устанавливаем значения свойства Тип товара для данного элемента
	CIBlockElement::SetPropertyValuesEx($ar_elem_fields['ID'], $CATALOG, array(PROPERTY_CODE =>$types_of_product));
	echo '<br><br>';
	//Ансеттим переменные
	unset ($elemgroups, $types_of_product, $id, $name, $category_name, $code_1c);
	//Увеличиваем счётчик для подсчёта обработанных элементов
	$COUNT++;
}
//Закрываем файл на запись и ансеттим переменные
fclose($fn);
unset($fn, $fileNew);
//Считаем затраченное на скрипт время и количество обработанных элементов
echo "\nTotal exec. time: " . round((time() + $time) / 60, 2) . " minute(s)\n";
echo "\nТоваров обработано: " . $COUNT;
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>
