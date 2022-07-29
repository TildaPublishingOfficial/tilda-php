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
 *  скрипт инициализирует класс API и делает один запрос к Tilda.cc
 */
///////////////////////////////////////////////////////////////////////////////
include '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Tilda' . DIRECTORY_SEPARATOR . 'Api.php';

const TILDA_PUBLIC_KEY = '???';
const TILDA_SECRET_KEY = '???';

$api = new Tilda\Api(TILDA_PUBLIC_KEY, TILDA_SECRET_KEY);

$arProjects = $api->getProjectsList();

?>
<!DOCTYPE html>
<html lang="en" class=" is-copy-enabled">
<head>
    <meta charset='utf-8'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="ru">
    <meta name="viewport" content="width=1020">
    <title>Tilda: example: 1-simple-request</title>
</head>
<body>
    <center>
        <h1>$api->getProjectsList()</h1>
        <pre style="width: 90%; text-align: left;">
            <?php print_r($arProjects); ?>
        </pre>
    </center>
</body>
</html>