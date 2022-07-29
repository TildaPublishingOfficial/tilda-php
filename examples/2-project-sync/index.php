<?php
///////////////////////////////////////////////////////////////////////////////
/**
 * Tilda Publishing
 *
 * @copyright (C) 2015 Оbukhov Nikita Valentinovich. Russia
 * @license       MIT
 *
 * @author        Michael Akimov <michael@island-future.ru>
 *
 * Описание:
 *  Скрипт скачивает страницы с опубликованного через Tilda.cc сайта
 */
///////////////////////////////////////////////////////////////////////////////
include '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Tilda' . DIRECTORY_SEPARATOR . 'Api.php';
include '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Tilda' . DIRECTORY_SEPARATOR . 'LocalProject.php';

const TILDA_PUBLIC_KEY = '???';
const TILDA_SECRET_KEY = '???';
const TILDA_PROJECT_ID = '???';

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
}

$api = new Tilda\Api(TILDA_PUBLIC_KEY, TILDA_SECRET_KEY);

/* Запрашиваем список страниц проекта и сохраняем ID страниц */
$arExportPages = array();
$arPages = $api->getPagesList(TILDA_PROJECT_ID);
if (!$arPages) {
    die('Error working with API: ' . $api->lastError);
}

/* собираем список ID страниц */
foreach ($arPages as $arPage) {
    $arExportPages[] = $arPage['id'];
}
unset($arPages);

/* если все таки есть, что экспортировать */
if (count($arExportPages)) {
    try {
        $local = new Tilda\LocalProject(
            array(
                'projectDir' => 'tilda',
                /*
                 'buglovers' => 'dev@example.ru', // email for send mail with error or exception
                 'baseDir' => '/var/www/example.ru/'  //  absolute path for sites files
                */
            )
        );
    } catch (Exception $e) {
        die('Error in TildaProject: ' . $e->getMessage() . PHP_EOL);
    }

    /*  берем данные по общим файлам проекта */
    $arProject = $api->getProjectInfo(TILDA_PROJECT_ID);
    if (!$arProject) {
        die('Not found project [' . $api->lastError . ']');
    }
    $local->setProject($arProject);

    /* создаем основные директории проекта (если еще не созданы) */
    if ($local->createBaseFolders() === false) {
        die('Error for create folders' . PHP_EOL);
    }

    echo '<pre>';

    /* копируем общие IMG файлы */
    $imagesPath = !empty($local->arProject['export_imgpath']) ? $local->arProject['export_imgpath'] : 'images';
    $arFiles = $local->copyImagesFiles($local->getPath('images', false));
    if (!$arFiles) {
        die('Error in copy IMG files [' . $api->lastError . ']');
    }
    print_r($arFiles);

    $countExport = 0;
    /* перебираем теперь страницы и скачиваем каждую по одной */
    foreach ($arExportPages as $pageid) {
        try {
            echo 'Export page ' . $pageid . PHP_EOL;

            /* запрашиваем все данные для экспорта страницы */
            $tildaPage = $api->getPageFullExport($pageid);
            if (!$tildaPage || empty($tildaPage['html'])) {
                echo 'Error: cannot get page [' . $pageid . '] or page is not published' . PHP_EOL;
                continue;
            }

            $tildaPage['needsync'] = 0;

            /* сохраним страницу (при сохранении также происходит копирование картинок/js/css использованных на странице) */
            $tildaPage = $local->savePage($tildaPage);
            echo 'Save page ' . $pageid . ' - success' . PHP_EOL;

            $tildaPage = $local->saveMetaPage($tildaPage);

            echo '<br>============ ' .
                '<a href="/' . $local->getProjectDir() . '/' . $tildaPage['id'] . '.html" target="_blank">' .
                'View page ' . $tildaPage['id'] .
                '</a>' .
                '<br>' . PHP_EOL;
        } catch (Exception $e) {
            echo 'Error [' . $countExport . '] tilda page dont export ' . $pageid . ' [' . $e->getMessage() . ']' . PHP_EOL;
        }
        $countExport++;
    }

    unset($arExportPages);
}