# Tilda PHP

Эта библиотека для работы с [Tilda](https://tilda.cc/). Описание [API](http://help-ru.tilda.ws/api).

## Требования

* php-5.3+
* php-json
* php-curl

## Установка

Скопируйте архив с исходниками и разархивируйте его на веб-сервере, например в папку tilda-php

Установите значения констант публичного/секретного ключей и номера проекта

```php
const TILDA_PUBLIC_KEY = '???';
const TILDA_SECRET_KEY = '???';
const TILDA_PROJECT_ID = '???';
```

Подключите библиотеку внутри вашего проекта на PHP

```php
include "tilda-php/classes/Tilda/Api.php";
```

Все готово, и можно приступать к работе:

```php
$api = new Tilda\Api(TILDA_PUBLIC_KEY, TILDA_SECRET_KEY);
```
Это главный объект, через который можно обращаться к API сервиса Tilda

## Примеры

Все примеры находятся в каталоге `examples`

* `1-simple-request` - показывает как подключать класс Tilda\Api и получить например список проектов.
* `2-project-sync` - синхронизирует проект (скачивает страницы, картинки и скрипты с опубликованного сайта)
* `3-mini-site` - набор скриптов для показа страниц, сохранения уведомлений об обновлении страниц (webhook) на tilda.cc и синхронизации

## Запросы к API

Все запросы к API работают по одному принципу: в случае успеха возвращается массив с данными, в случае неудачи возвращается `false` и в текст ошибки помещается `$api->lastError`

-----

Получить список всех проектов:

```php
$arProjects = $api->getProjectsList();
```

Возвращает список массивов с описанием проектов

```php
array(
    array(
        'id'    => '',
        'name'  => '',
        'descr' => ''
    ),
    ...
)
```

-----

Получить информацию о проекте:

```php
$arProject = $api->getProjectInfo(TILDA_PROJECT_ID);
```

Возвращает массив с описанием запрошенного проекта

```php
array(
    'id'             => '',
    'title'          => '',
    'descr'          => '',
    'customdomain'   => '',
    'export_csspath' => '',
    'export_jspath'  => '',
    'export_imgpath' => '',
    'indexpageid'    => '',
    'customcsstext'  => 'y',
    'favicon'        => 'https://static.tildacdn.com/img/tildafavicon.ico',
    'page404id'      => '0',
    'images'         => array(
        array(
            'from' => '',
            'to'   => '',
        ),
        ...
    ),
    'htaccess'       => '',
)
```

-----

Получить список страниц проекта:

```php
$arPages = $api->getPagesList(TILDA_PROJECT_ID);
```

Возвращает список массивов с описанием страниц проекта

```php
array (
    array(
        'id'         => '1001',
        'projectid'  => '0',
        'title'      => 'Page title first',
        'descr'      => '',
        'img'        => '',
        'featureimg' => '',
        'alias'      => '',
        'date'       => '2014-05-16 14:45:53',
        'sort'       => '80',
        'published'  => '1419702868',
        'filename'   => 'page1001.html',
    ),
    ...
)
```

-----

Получить всю информацию о странице для экспорта:

```php
$arPage = $api->getPageFullExport($pageid);
```

Возвращает массив с описанием страницы

```php
array(
    'id'              => '1001',
    'projectid'       => '0',
    'title'           => 'Page title',
    'descr'           => '',
    'img'             => '',
    'featureimg'      => '',
    'alias'           => '',
    'date'            => '2014-05-16 14=>45=>53',
    'sort'            => '80',
    'published'       => '1419702868',
    'export_jspath'   => '',
    'export_csspath'  => '',
    'export_imgpath'  => '',
    'export_basepath' => '',
    'project_alias'   => '',
    'page_alias'      => '',
    'project_domain'  => 'domain.ru',
    'images'          => array(
        array('from' => '', 'to' => ''),
        array('from' => '', 'to' => ''),
        ...
    ),
    'js'              => array(
        array('from' => '', 'to' => ''),
        array('from' => '', 'to' => ''),
        ...
    ),
    'css'             => array(
        array('from' => '', 'to' => ''),
        array('from' => '', 'to' => ''),
        ...
    ),
    'html'            => 'full page html-code with local links to files',
    'filename'        => 'page1001.html',
)
```

