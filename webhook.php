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
 *  Если этот скрипт указать в Tilda.cc на странице https://tilda.cc/identity/apikeys/ в блоке Webhook,
 *  то при публикации страницы на Tilda.cc, Tilda вызовет этот скрипт и сообщит, какая страница опубликована.
 *  Скрипт эту информацию запишет в каталог meta, чтобы другой скрипт tildasync смог загрузить все обновления
 * 
 **/
///////////////////////////////////////////////////////////////////////////////

include $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'config.php';
set_time_limit(0);

if (
    empty($_GET['pageid'])
    || empty($_GET['projectid'])
    || empty($_GET['publickey'])
    || $_GET['publickey'] != $TILDA_PUBLIC_KEY
) {
    errorEnd('Wrong parametr for sync query');
    return ;
}

$projectid = intval($_GET['projectid']);
$pageid = intval($_GET['pageid']);

/* проверяем, наш ли проект обновился */
if ($projectid != $TILDA_PROJECT_ID) {
    errorEnd('Aliens Project ID');
    return ;    
}

$tilda = new Tilda($TILDA_PUBLIC_KEY, $TILDA_SECRET_KEY, array('baseDir'=>$TILDA_BASE_DIR, 'projectid' => $TILDA_PROJECT_ID));

/* название фалй, в котором хранятся данные по странице */
$fname = $tilda->getProjectFullDir() . 'meta' . DIRECTORY_SEPARATOR . 'page' . $pageid . '.php';

/* если такого файла не существует, значит это новая страница */
if (! file_exists($fname)) {
    $arPage = array(
        'id' => $pageid,
        'title' => '',
        'alias' => '',
        'descr' => '',
        'img' => '',
        'featureimg' => '',
        'needsync' => '1'
    );
} else {
    $arPage = include $fname;
}

/* говорим, что страница обновилась */
$arPage['needsync'] = 1;

/* и сохраняем данные обратно */
$tilda->saveMetaPage($arPage);

/* сообщаем, что все хорошо */
successEnd('Add to synchronization query page ' . $pageid . ' in project ' . $projectid);
