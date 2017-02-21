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
 *  Если этот скрипт указать в кроне, то он будет пробегать файлы с описание страниц с tilda.cc и скачивать обновления.
 *  Чтобы скрипт узнавал об обновлениях, нужно указать скрипт webhook.php на сайте tilda.cc в настройках проекта.
 * 
 **/
///////////////////////////////////////////////////////////////////////////////

include ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Tilda" . DIRECTORY_SEPARATOR . "Api.php";
include ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Tilda" . DIRECTORY_SEPARATOR . "LocalProject.php";

define('TILDA_PUBLIC_KEY', '???');
define('TILDA_SECRET_KEY', '???');
define('TILDA_PROJECT_ID', '???');

use \Tilda;

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
}

$api = new Tilda\Api(TILDA_PUBLIC_KEY, TILDA_SECRET_KEY);
$local = new Tilda\LocalProject(array(
        'projectDir' => 'tilda',
    )
);


/* Пробегаем список страниц и отбираем те, которые изменились */
$arExportPages = array();
if (sizeof($arExportPages) == 0 && file_exists($local->getProjectFullDir().'meta')) {
    $d = dir($local->getProjectFullDir().'meta');
    /**/
    while (false !== ($entry = $d->read())) {
        if($entry != '.' && $entry != '..' && !is_dir($dir.$entry)) {
            $pagestr = substr($entry,4,-4);
            if (intval($pagestr).'' == $pagestr && $pagestr > 0 && substr($entry,0,4) == 'page') {
                $arPage = include $d->path . DIRECTORY_SEPARATOR . $entry;
                if (!empty($arPage['needsync'])) {
                    $arExportPages[] = intval($pagestr);
                }
            }
        }
    }
    $d->close();
}

/* если все таки есть, что экспортировать */
if (sizeof($arExportPages) > 0) {
    $local = new Tilda\LocalProject(array(
            'projectDir' => 'tilda'
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

            /* временный фикс */
            $html = preg_replace(array('|//static.tildacdn.com/js/|','|//static.tildacdn.com/css/|'),array('',''), $tildapage['html']);
            if ($html > '') {
                $tildapage['html'] = $html;
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
            
            if (!empty($tildapage['alias']) && file_exists($local->getProjectFullDir().'.htaccess')) {
                $rules = @file_get_contents($local->getProjectFullDir().'.htaccess');
                if ($rules > '' && strpos($rules, ' '.$tildapage['id'].'.html')===false) {
                    $rules .= "\nRewriteRule ^".$tildapage['alias']."$ ".$tildapage['id'].".html\n";
                    echo "Modify htaccess<br>\n";
                    file_put_contents($local->getProjectFullDir().'.htaccess', $rules);
                }
            }
        } catch (Exception $e) {
            echo "Error [".$countexport."] tilda page dont export ".$pageid." [".$e->getMessage()."]\n";
        }
        $countexport++;
    }

    unset($arExportPages);
} else {
    die("Nothing not found for sync");
}


?>