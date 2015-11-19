<?php
/***
 * Скрипт предназначен для экспорта данных из Тильды. Вызывается из консоли или по крону.
 * При вызове через веб - возможны сбои из-за не хватки времени работы.
 * 
 * @example php
 *  все страницы проекта закачать
 *  php tildasync.php all
 *
 *  одну страницу (pageid: 1235) проекта
 *  php tildasync.php 1235
 *
 *  все страницы, которые изменились на Тильде (если у вас настроен webhook)
 *  php tildasync.php
 *
 */
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
include $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'config.php';
set_time_limit(0);
global $argv;
$arExportPages=array();
$isAllPages = false;

/* считываем параметры вызова из которых узнаем, что экспортировать */
if ( !empty($argv[1]) ) {
    if ($argv[1] == 'all' && $argv[1] = 'pages') {
        $isAllPages = true;
    } else if (intval($argv[1]) > 0) {
        $arExportPages[] = intval($argv[1]);
    }
}

echo "Start synchronization === " . date('Y-m-d H:i:s') . "\n";
$tilda = new Tilda(
    $TILDA_PUBLIC_KEY,
    $TILDA_SECRET_KEY,
    array(
        'baseDir' => $TILDA_BASE_DIR,
        'projectDir' => $TILDA_PROJECT_DIR,
        'buglovers' => $EMAIL_FOR_BUGS
    )
);

/* берем полный путь проекта */
$dir = $tilda->getProjectFullDir();

/* определяем id страниц, которые нужно скачать (экспортировать) */
if ($isAllPages) {
    foreach ($tilda->getPagesList($TILDA_PROJECT_ID) as $arPage) {
        $arExportPages[] = $arPage['id'];
    }
} elseif (sizeof($arExportPages) == 0 && file_exists($dir.'meta')) {
    $d = dir($dir.'meta');
    /**/
    while (false !== ($entry = $d->read())) {
        if($entry != '.' && $entry != '..' && !is_dir($dir.$entry)) {
            $pagestr = substr($entry,7,-4);
            if (intval($pagestr).'' == $pagestr && $pagestr > 0) {
                $arPage = include $d->path . DIRECTORY_SEPARATOR . $entry;
                if (!empty($arPage['needsync'])) {
                    $arExportPages[] = intval($pagestr);
                }
            }
        }
    }
    $d->close();
}

$countexport = 0;

/* если все таки есть, что экспортировать */
if (sizeof($arExportPages) > 0) {
    /* создаем основные директории проекта (если еще не созданы) */
    if ($tilda->createBaseFolders() === false) {
        die("Error for create folders\n");
    }
    
    /*  берем данные по общим файлам проекта */
    $arProject = $tilda->getProjectExport($TILDA_PROJECT_ID);
    $arSearchFiles = array();
    $arReplaceFiles = array();

    $upload_path = '/' . $tilda->getProjectDir();
    if (DIRECTORY_SEPARATOR != '/') {
        $upload_path = str_replace(DIRECTORY_SEPARATOR,'/', $upload_path);
    }

    //css
    for ($i=0;$i<count($arProject['css']);$i++) {
        if ($tilda->copyFile($arProject['css'][$i]['from'], 'css' . DIRECTORY_SEPARATOR . $arProject['css'][$i]['to'])) {
            die("Error for coping:" . $tilda->lastError);
        }
        
        if (substr($arProject['css'][$i]['to'],0,4) != 'http') {
            if ($arProject['export_csspath'] > '') {
                $arSearchFiles[] = '|' . $arProject['export_csspath'] . '/' . $arProject['css'][$i]['to'] . '|i';
            } else {
                $arSearchFiles[] = '|' . $arProject['css'][$i]['to'] . '|i';
            }
            $arReplaceFiles[] =  $upload_path.'css/'.$arProject['css'][$i]['to'];
        }
    }
    
    //js
    for ($i=0;$i<count($arProject['js']);$i++){
        $tilda->copyFile($arProject['js'][$i]['from'], 'js' . DIRECTORY_SEPARATOR . $arProject['js'][$i]['to']);

        if (substr($arProject['js'][$i]['to'],0,4) != 'http') {
            if ($arProject['export_jspath'] > '') {
                $arSearchFiles[] = '|' . $arProject['export_jspath'] . '/' . $arProject['js'][$i]['to'] . '|i';
            } else {
                $arSearchFiles[] = '|' . $arProject['js'][$i]['to'] . '|i';
            }
            $arReplaceFiles[] =  $upload_path.'js/'.$arProject['js'][$i]['to'];
        }
    }

    //images
    for ($i=0; $i < count($arProject['images']); $i++) {
        $newfile = $tilda->copyImageTo($arProject['images'][$i]['from'], 'img' . DIRECTORY_SEPARATOR );
        
        if ($arProject['export_imgpath'] > '') {
            $arSearchFiles[] = '|' . $arProject['export_imgpath'] . '/' . $arProject['images'][$i]['to'] . '|i';
        } else {
            $arSearchFiles[] = '|' . $arProject['images'][$i]['to'] . '|i';
        }
        $arReplaceFiles[] =  $upload_path.'img/' . $newfile;
    }

    
    /* перебеираем теперь страницы и скачиваем каждую по одной */
    foreach ($arExportPages as $pageid) {
        try {
            echo "Export page " . $pageid . "\n";
            $tildapage = $tilda->getPageFullExport($pageid);
            if (! $tildapage || empty($tildapage['html'])) {
                echo "Error: cannot get page [$pageid] or page not publish\n";
                continue;
            }
    
            $tildapage['export_imgpath'] = $arProject['export_imgpath'];
            $tildapage['needsync'] = '0';
            
            $html = preg_replace($arSearchFiles, $arReplaceFiles, $tildapage['html']);
            if ($html > '') {
                $tildapage['html'] = $html;
            }
            
            $tildapage = $tilda->savePage($tildapage);
            echo "Save page $pageid - success\n";

            $badgeurl =  $tildapage['featureimg'] > '' ? $tildapage['featureimg'] : $tildapage['img'];

            /*
            if(empty($tildapage['socnetimg']) && $badgeurl > '') {
                echo "Create badge [$badgeurl]\n";
                Tilda::createSocnetBadge($badgeurl, $tildapage['title'], $pageid);
                $socpath = Application::one()->PATH_PUBLIC.'upload'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$pageid.DIRECTORY_SEPARATOR.'socnet.png';
                if (file_exists($socpath)) {
                    $tildapage['socnetimg'] = '/upload/pages/'.$pageid.'/socnet.png';
                }
            }
            */
            
            $tildapage = $tilda->saveMetaPage($tildapage);
        } catch (Exception $e) {
            echo "Error [".$countexport."] tilda page dont export ".$pageid." [".$e->getMessage()."]\n";
        }
        $countexport++;
    }

    unset($arExportPages);
}

echo "Exported [$countexport]!\n";