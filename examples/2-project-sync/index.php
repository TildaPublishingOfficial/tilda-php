<?php
///////////////////////////////////////////////////////////////////////////////
/**
 * Tilda Publishing
 * @copyright (C) 2015 Оbukhov Nikita Valentinovich. Russia
 * @license MIT
 *
 * @author Michael Akimov <michael@island-future.ru>
 * 
 * Описание: 
 *  Скрипт скачивает страницы с опубликованного, через Tilda.cc, сайта
 **/
///////////////////////////////////////////////////////////////////////////////
include ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Tilda" . DIRECTORY_SEPARATOR . "Api.php";
include ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Tilda" . DIRECTORY_SEPARATOR . "LocalProject.php";

define('TILDA_PUBLIC_KEY', '- insert public key -');
define('TILDA_SECRET_KEY', '- insert secret key -');
define('TILDA_PROJECT_ID', '???');

use \Tilda;

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
}

$api = new Tilda\Api(TILDA_PUBLIC_KEY, TILDA_SECRET_KEY);

/* Запрашиваем список страниц проекта и сохраняем ID страниц */
$arExportPages = array();
$arPages = $api->getPagesList(TILDA_PROJECT_ID);
if (! $arPages) {
    die("Error in connected to API: " . $api->lastError);
}

/* собираем IDшники страниц */
foreach ($arPages as $arPage) {
    $arExportPages[] = $arPage['id'];
}
unset($arPages);

/* если все таки есть, что экспортировать */
if (sizeof($arExportPages) > 0) {
    $local = new Tilda\LocalProject(array(
            'projectDir' => 'tilda',
            /*
             'buglovers' => 'dev@example.ru', // email for send mail with error or exception
             'baseDir' => '/var/www/example.ru/'  //  absolute path for sites files
            */
        )
    );
    /* создаем основные директории проекта (если еще не созданы) */
    if ($local->createBaseFolders() === false) {
        die("Error for create folders\n");
    }
    
    /*  берем данные по общим файлам проекта */
    $arProject = $api->getProjectExport(TILDA_PROJECT_ID);
    if (! $arProject) {
        die('Not found project [' . $api->lastError . ']');
    }
    $local->setProject($arProject);
    
    $arSearchFiles = array();
    $arReplaceFiles = array();

    echo "<pre>";

    /* копируем общие CSS файлы */
    $arFiles = $local->copyCssFiles('css');
    if (! $arFiles) {
        die('Error in copy CSS files [' . $api->lastError . ']');
    }
    print_r($arFiles);
    
    /* копируем общие JS файлы */
    $arFiles = $local->copyJsFiles('js');
    if (! $arFiles) {
        die('Error in copy JS files [' . $api->lastError . ']');
    }
    print_r($arFiles);
    
    /* копируем общие ШЬП файлы */
    $arFiles = $local->copyImagesFiles('img');
    if (! $arFiles) {
        die('Error in copy IMG files [' . $api->lastError . ']');
    }
    print_r($arFiles);
    
    /* перебеираем теперь страницы и скачиваем каждую по одной */
    foreach ($arExportPages as $pageid) {
        try {
            echo "Export page " . $pageid . "\n";
            
            /* запрашиваем все данные для экспорта страницы */
            $tildapage = $api->getPageFullExport($pageid);
            if (! $tildapage || empty($tildapage['html'])) {
                echo "Error: cannot get page [$pageid] or page not publish\n";
                continue;
            }

            $tildapage['export_imgpath'] = $local->arProject['export_imgpath'];
            $tildapage['needsync'] = '0';
             
            /* так как мы копировали общие файлы в одни папки, а в HTML они указывают на другие, то произведем замену */
            $html = preg_replace($local->arSearchFiles, $local->arReplaceFiles, $tildapage['html']);
            if ($html > '') {
                $tildapage['html'] = $html;
            }
            
            /* сохраним страницу  (при сохранении также происходит копирование картинок использованных на странице) */
            $tildapage = $local->savePage($tildapage);
            echo "Save page $pageid - success\n";

            $tildapage = $local->saveMetaPage($tildapage);

            echo '<br>============ <a href="/' . $local->getProjectDir().'/'.$tildapage['id'].'.html" target="_blank">View page ' . $tildapage['id'] . '</a><br>'."\n";
        } catch (Exception $e) {
            echo "Error [".$countexport."] tilda page dont export ".$pageid." [".$e->getMessage()."]\n";
        }
        $countexport++;
    }

    unset($arExportPages);
}


?>