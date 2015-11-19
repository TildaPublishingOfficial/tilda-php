# Tilda PHP

Эта библиотека для работы с [Tilda](https://tilda.cc/). Описание [API](http://help-ru.tilda.ws/api).

## Требования

* php-5.3+
* php-json
* php-curl

## Установка

Копируете архив с исходниками и разархивируете его на веб-сервере, например в папку tilda-php

Установите константы публичного и секретного ключей

```php
define('TILDA_PUBLIC_KEY', 'gbl764s077xne9v81ic2');
define('TILDA_SECRET_KEY', 'ob8akjcdowy47jceo4fv');
define('TILDA_PROJECT_ID', '???');
```

Подключите библиотеку внутри Вашего проекта на PHP и используйте пространство имен Tilda (namespace \Tilda)

```php
include "tilda-php" . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Tilda" . DIRECTORY_SEPARATOR . "Api.php";
use \Tilda;
```

Все готово и можно приступать к работе:

```php
$api = new Tilda\Api(TILDA_PUBLIC_KEY, TILDA_SECRET_KEY);
```
Это главный объект, через который можно обращаться к API сервиса Tilda

## Примеры

Все примеры находятся в каталоге `examples`

* 1-simple-request - показывает как подключать класс Tilda\Api и получить например список проектов.
* 2-project-sync - синхронизирует проект (скачивает страницы, картинки и скрипты с tilda.cc)
* 3-mini-site - набор скриптов для показа страниц, сохранения уведомлений об обновлении страниц (webhook) на tilda.cc и синхронизация

## Запросы к API

Все запросы к API работают по одному принципу: в случае успеха возвращается массив с данными, в случае неудачи возвращается `false` и в текст ошибки помещается `$api->lastError`

-----

Получить все проекты:

```php
$arProjects = $api->getProjectsList();
```

Возвращает спсиок массивов с описанием проектов

```php
array (
    array (
        'id'   =>'',
        'name' =>'',
        'descr'=>''
    ),
    ...
)
```

-----

Получить данные по проекту:

```php
$arProject = $api->getProject(TILDA_PROJECT_ID);
```

Возвращает массив с описанием запрошенного проекта

```php
Array (
    'id' => '',
    'title' => '',
    'descr' => '',
    'customdomain' => '',
    'css' => Array (
        ...
    )

    'js' => Array (
        ...
    )

)
```

