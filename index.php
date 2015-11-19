<?php
include $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'config.php';

//здесь проверяем, может есть статья с таким названием
if (substr($_SERVER['SCRIPT_NAME'],-1) == '/') {
    $pagename = substr($_SERVER['SCRIPT_NAME'],1,-1);
} else {
    $pagename = substr($_SERVER['SCRIPT_NAME'],1);
}

$tilda = new Tilda(
    $TILDA_PUBLIC_KEY,
    $TILDA_SECRET_KEY,
    array(
        'baseDir' => $TILDA_BASE_DIR,
        'projectDir' => $TILDA_PROJECT_DIR,
        'buglovers' => $EMAIL_FOR_BUGS
    )
);

return $tilda->showpage($pagename);
