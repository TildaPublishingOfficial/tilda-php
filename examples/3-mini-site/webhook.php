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
 *  Скрипт эту информацию запишет в каталог meta, чтобы другой скрипт sync.php смог загрузить все обновления
 *
 **/
///////////////////////////////////////////////////////////////////////////////

include ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Tilda" . DIRECTORY_SEPARATOR . "Api.php";
include ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Tilda" . DIRECTORY_SEPARATOR . "LocalProject.php";

define('TILDA_PUBLIC_KEY', '???');
define('TILDA_SECRET_KEY', '???');
define('TILDA_PROJECT_ID', '???');

use \Tilda;

set_time_limit(0);

$local = new Tilda\LocalProject(array(
        'projectDir' => 'tilda',
    )
);


if (
    empty($_GET['pageid'])
    || empty($_GET['projectid'])
    || empty($_GET['publickey'])
    || $_GET['publickey'] != TILDA_PUBLIC_KEY
) {
    $local->errorEnd('Wrong parametr for sync query');
    return ;
}

$projectid = intval($_GET['projectid']);
$pageid = intval($_GET['pageid']);

/* проверяем, наш ли проект обновился */
if ($projectid != TILDA_PROJECT_ID) {
    $local->errorEnd('Aliens Project ID');
    return ;
}

/* название файла, в котором хранятся данные по странице */
$fname = $local->getProjectFullDir() . 'meta' . DIRECTORY_SEPARATOR . 'page' . $pageid . '.php';

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
$local->saveMetaPage($arPage);

/* сообщаем, что все хорошо */
$local->successEnd('Add to synchronization query page ' . $pageid . ' in project ' . $projectid);
