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
 *  Если этот скрипт указать в Tilda.cc на странице https://tilda.cc/projects/api/?projectid=XXXX (где XXXX - номер
 *  проекта) в блоке Webhook, то при публикации страницы на Tilda.cc, Tilda вызовет этот скрипт и сообщит, какая
 *  страница опубликована. Скрипт эту информацию запишет в каталог meta, чтобы другой скрипт sync.php смог загрузить
 *  все опубликованные изменения
 */
///////////////////////////////////////////////////////////////////////////////

include '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Tilda' . DIRECTORY_SEPARATOR . 'Api.php';
include '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Tilda' . DIRECTORY_SEPARATOR . 'LocalProject.php';

const TILDA_PUBLIC_KEY = '???';
const TILDA_SECRET_KEY = '???';
const TILDA_PROJECT_ID = '???';

set_time_limit(0);

try {
    $local = new Tilda\LocalProject(
        array(
            'projectDir' => 'tilda',
        )
    );
} catch (Exception $e) {
    die(json_encode(array('error' => $e->getMessage())));
}

if (
    empty($_GET['pageid'])
    || empty($_GET['projectid'])
    || empty($_GET['publickey'])
    || $_GET['publickey'] != TILDA_PUBLIC_KEY
) {
    $local->errorEnd('Wrong parameter for sync query');
}

$projectid = intval($_GET['projectid']);
$pageid = intval($_GET['pageid']);

/* проверяем, наш ли проект обновился */
if ($projectid != TILDA_PROJECT_ID) {
    $local->errorEnd('Incorrect project ID');
}

/* название файла, в котором хранятся данные по странице */
$filename = $local->getProjectFullDir() . 'meta' . DIRECTORY_SEPARATOR . 'page' . $pageid . '.php';

/* если такого файла не существует, значит это новая страница */
if (!file_exists($filename)) {
    $arPage = array(
        'id'         => $pageid,
        'title'      => '',
        'alias'      => '',
        'descr'      => '',
        'img'        => '',
        'featureimg' => '',
    );
} else {
    $arPage = include $filename;
}

/* помечаем, что страница обновилась */
$arPage['needsync'] = 1;

/* и сохраняем данные обратно */
$local->saveMetaPage($arPage);

/* сообщаем, что все хорошо */
$local->successEnd('Add to synchronization query page ' . $pageid . ' in project ' . $projectid);
