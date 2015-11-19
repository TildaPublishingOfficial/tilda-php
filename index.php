<?php
include $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'Tilda.php';

//здесь проверяем, может есть статья с таким названием
if (substr($_SERVER['SCRIPT_NAME'],-1) == '/') {
    $articleName = substr($_SERVER['SCRIPT_NAME'],1,-1);
} else {
    $articleName = substr($_SERVER['SCRIPT_NAME'],1);
}
return Tilda::showpage($articleName);
