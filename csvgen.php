<?php


/* Для запуска на Cron'е (автоматически, по расписанию), нужно добавить этот код в файл.
То есть, создаем файл, например, назовем его data_export.php в любой
папке в файловой структуре, помещаем в него весь этот код с тегами <?php ?> и запускаем 
исполнение файла на Cron'е через командную строку BitrixVM, с нужным интервалом/
Также можно создать Агента и добавить данный код в /bitrix/php_interface/init.php
Пример создания Агента: https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2290&LESSON_PATH=3913.4776.4620.4978.2290
*/


// Для запуска на кроне нужно раскомментировать этот участок кода

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/.." ) ;
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('CHK_EVENT', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php" ) ;

@set_time_limit(0);
@ignore_user_abort(true);

/* Для запуска через Командную PHP-строку в панели Администратора достаточно кода ниже,
при этом теги <?php ?> в начале и конце кода не ставятся */


require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/csv_data.php"); // Подключаем библиотеку CCSVData

$filePath = $_SERVER['DOCUMENT_ROOT'] . '/xml/' . 'export.csv'; // Путь до файла, куда записываем данные из инфоблока, от корня сайта
$fp = fopen($filePath, 'w+'); // Очищаем файл CSV, иначе записи будут добавляться к уже имеющимся, причем, вместе с шапкой CSV
@fclose($fp); // Закрываем файл CSV

$fields_type = 'R'; // Дописываем строки в файл
$delimiter = ";"; // Устанавливаем разделитель для CSV файла
$csvFile = new CCSVData($fields_type, false); // Создаём объект – экземпляр класса CCSVData
$csvFile->SetFieldsType($fields_type); // Метод класса CCSVData, задающий тип записи в файл R
$csvFile->SetDelimiter($delimiter); // Метод класса CCSVData, устанавливающий разделитель
$csvFile->SetFirstHeader(false); // Метод класса CCSVData, указывающий, что первая строка будет шапкой

//Создаем шапку и записываем в CSV файл 
$arrHeaderCSV = array("ID","Название товара", "Изображение", "Новинка", "Артикул", "Остаток", "Цена продажи", "Старая цена", "Цена закупки", "Страна производства", "Материал", "Размер", "Бренд", "Количество в упаковке","Штрих-код","Минимальная партия"); // Задаем поля в шапке в CSV файле
$csvFile->SaveFile($filePath, $arrHeaderCSV); // Записываем шапку в CSV файл


// Подключам Модуль "Информационные блоки".
CModule::IncludeModule("iblock");
CModule::IncludeModule("catalog");

// Подключаемся к нужному нам инфоблоку, к его полям и свойствам
$IBLOCK_ID = 7; // Устанавливаем ID инфоблока, с которым хотим работать
$arSort= Array("ID"=>"ASC"); // Сортируем элементы инфоблока по возрастанию, по полю ID. Для регулярного экпорта в другую БД можно это поле пропустить и в CIBlockElement::GetList вместо $arSort написать Array()
$arSelect = Array("ID", "NAME", "DETAIL_PICTURE", "PRICE", "PROPERTY_NOVINKI", "PROPERTY_CML2_ARTICLE", "CATALOG_QUANTITY", "PROPERTY_RRTS", "PRICE", "CURRENCY", "PROPERTY_CML2_MANUFACTURER", "PROPERTY_STARAYA_TSENA_KH2", "PROPERTY_MATERIAL", "PROPERTY_RAZMER", "PROPERTY_BREND", "PROPERTY_KOLICHESTVO_V_UPAKOVKE","PROPERTY_CML2_BAR_CODE", "PROPERTY_MINIMALNAYA_PARTIYA"); // Массив возвращаемых полей элемента, где слово PROPERTY_ указывает на свойство элемента инфоблока
$arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y"); // Выбираем только активные элементы, которые хранятся в инфоблоке с ID 7
$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect); // Функция Битрикс API. Возвращает список элементов по фильтру $arFilter
$domain = 'https://site.com';

// Получаем нужные данные из выбранных полей и записываем в CSV файл
while($ob = $res->GetNextElement()) // Метод возвращает из выборки объект _CIBElement, и передвигает курсор на следующую запись

{
    $arFields = $ob->GetFields(); // Получаем значения полей и свойст инфоблока

    if ($arFields[CATALOG_QUANTITY] >= 1) // Проверяем, остатки
   {
       $exportDATA = array($arFields['ID'], $arFields['NAME'], $domain . CFile::GetPath($arFields["DETAIL_PICTURE"]) , $arFields[PROPERTY_NOVINKI_VALUE], $arFields[PROPERTY_CML2_ARTICLE_VALUE], $arFields['CATALOG_QUANTITY'], $arFields[PROPERTY_RRTS_VALUE],$arFields[PROPERTY_STARAYA_TSENA_KH2_VALUE], $arFields[PROPERTY_RRTS_VALUE]/2 , $arFields[PROPERTY_CML2_MANUFACTURER_VALUE], $arFields[PROPERTY_MATERIAL_VALUE],$arFields[PROPERTY_RAZMER_VALUE],$arFields[PROPERTY_BREND_VALUE],$arFields[PROPERTY_KOLICHESTVO_V_UPAKOVKE_VALUE], $arFields[PROPERTY_CML2_BAR_CODE_VALUE], $arFields[PROPERTY_MINIMALNAYA_PARTIYA_VALUE]); // Присваиваем набор полей и свойств инфоблока переменной $exportDATA
   $csvFile->SaveFile($filePath, $exportDATA); // Записываем результат из переменной $exportDATA в CSV файл
   }
}
?>
