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
 *  Если этот скрипт указать в кроне, то он будет пробегать файлы с описанием страниц с tilda.cc и скачивать обновления.
 *  Чтобы скрипт узнавал об обновлениях, нужно указать скрипт webhook.php на сайте tilda.cc в настройках проекта.
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
try {
    $local = new Tilda\LocalProject(
        array(
            'projectDir' => 'tilda',
        )
    );
} catch (Exception $e) {
    die('Error in TildaProject: ' . $e->getMessage() . PHP_EOL);
}

/* Пробегаем список страниц и отбираем те, которые изменились */
$arExportPages = array();
$dir = $local->getProjectFullDir() . 'meta';
if (file_exists($dir)) {
    $d = dir($dir);

    while (false !== ($entry = $d->read())) {
        if ($entry != '.' && $entry != '..' && !is_dir($dir . $entry)) {
            $pageNumber = substr($entry, 4, -4);
            if (
                intval($pageNumber) . '' == $pageNumber
                && $pageNumber > 0
                && substr($entry, 0, 4) == 'page'
            ) {
                $arPage = include $d->path . DIRECTORY_SEPARATOR . $entry;
                if (!empty($arPage['needsync'])) {
                    $arExportPages[] = intval($pageNumber);
                }
            }
        }
    }
    $d->close();
}

/* если все таки есть, что экспортировать */
if (count($arExportPages)) {


    /*  берем данные по общим файлам проекта */
    $arProject = $api->getProjectInfo(TILDA_PROJECT_ID);
    if (!$arProject) {
        die('Not found project [' . $api->lastError . ']');
    }
    $local->setProject($arProject);

    /* создаем основные директории проекта (если еще не созданы) */
    if ($local->createBaseFolders() === false) {
        die("Error for create folders" . PHP_EOL . $local->lastError . PHP_EOL);
    }

    echo '<pre>';

    /* копируем общие IMG файлы */
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

            if (!empty($tildaPage['alias']) && file_exists($local->getProjectFullDir() . '.htaccess')) {
                $rules = @file_get_contents($local->getProjectFullDir() . '.htaccess');
                if ($rules > '' && strpos($rules, ' ' . $tildaPage['id'] . '.html') === false) {
                    $rules .= "\nRewriteRule ^" . $tildaPage['alias'] . "$ " . $tildaPage['id'] . ".html" . PHP_EOL;
                    $rules .= "\nRewriteRule ^page" . $tildaPage['id'] . ".html$ " . $tildaPage['id'] . ".html" . PHP_EOL;
                    echo 'Modify htaccess<br>' . PHP_EOL;
                    file_put_contents($local->getProjectFullDir() . '.htaccess', $rules);
                }
            }
        } catch (Exception $e) {
            echo 'Error [' . $countExport . '] tilda page dont export ' . $pageid . ' [' . $e->getMessage() . ']' . PHP_EOL;
        }
        $countExport++;
    }

    unset($arExportPages);
} else {
    die('Nothing not found for sync');
}