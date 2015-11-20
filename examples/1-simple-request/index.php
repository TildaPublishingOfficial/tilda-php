<?php
include ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Tilda" . DIRECTORY_SEPARATOR . "Api.php";

define('TILDA_PUBLIC_KEY', 'gbl764s077xne9v81ic2');
define('TILDA_SECRET_KEY', 'ob8akjcdowy47jceo4fv');

use \Tilda;

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