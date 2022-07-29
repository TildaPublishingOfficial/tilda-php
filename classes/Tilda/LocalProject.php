<?php
///////////////////////////////////////////////////////////////////////////////
/**
 * Tilda Publishing
 *
 * @copyright (C) 2015 Оbukhov Nikita Valentinovich. Russia
 * @license       MIT
 *
 * @author        Nikita Obukhov <hello@tilda.cc>
 * @author        Michael Akimov <michael@island-future.ru>
 *
 * Описание:
 * Класс для работы с API tilda.cc
 */
///////////////////////////////////////////////////////////////////////////////
namespace Tilda;

use Exception;

class LocalProject
{
    /**
     * Корневая директория Вашего сайта (абсолютный путь)
     */
    public $baseDir = '';

    /**
     * Директория, куда будут сохраняться данные проекта (указываем путь относительно корневой директории)
     */
    public $projectDir = '';

    /**
     * Данные по проекту
     */
    public $arProject = array();

    /**
     * Массив, куда собираются названия файлов в HTML файле страницы
     */
    public $arSearchFiles = array();

    /**
     * Массив, куда собираются новые названия файлов на которые нужно поменять, те что в HTML
     */
    public $arReplaceFiles = array();

    public $emailFrom = 'postmaster';
    public $buglovers = 'you@mail.there';

    public $lastError = '';

    /**
     * Инициализируем класс
     *
     * @throws Exception
     * @var array $arOptions - массив дополнительных параметров
     */
    public function __construct($arOptions = array())
    {
        /* базовая директория, относительно которой все и создается */
        if (!empty($arOptions['baseDir'])) {
            $this->baseDir = $arOptions['baseDir'];
        } elseif (empty($_SERVER['DOCUMENT_ROOT'])) {
            $this->baseDir = dirname(__DIR__);
        } else {
            $this->baseDir = $_SERVER['DOCUMENT_ROOT'];
        }

        if (substr($this->baseDir, -1) != DIRECTORY_SEPARATOR && substr($this->baseDir, -1) != '/') {
            $this->baseDir .= DIRECTORY_SEPARATOR;
        }

        /* у каждого проекта есть свой набор стилей и скриптов - храним их отдельно */
        if (!empty($arOptions['projectDir'])) {
            $this->projectDir = $arOptions['projectDir'];
            if (
                substr($this->projectDir, 0, 1) == DIRECTORY_SEPARATOR
                || substr($this->projectDir, 0, 1) == '/'
            ) {
                $this->projectDir = substr($this->projectDir, 1);
            }

            if (!file_exists($this->baseDir . $this->projectDir)) {
                if (!mkdir($this->baseDir . $this->projectDir, 0776, true)) {
                    throw new Exception('Cannot create Project dir [' . $this->baseDir . $this->projectDir . ']' . PHP_EOL);
                }
            }

            if (
                substr($this->projectDir, -1) != DIRECTORY_SEPARATOR
                && substr($this->projectDir, -1) != '/'
            ) {
                $this->projectDir .= DIRECTORY_SEPARATOR;
            }
        } else {
            $this->projectDir = '';
        }

        if (isset($arOptions['buglovers'])) {
            $this->buglovers = $arOptions['buglovers'];
        }
    }

    public function __destruct()
    {
    }

    /**
     * Возвращает относительный путь проекта
     *
     * @return string
     */
    public function getProjectDir()
    {
        return $this->projectDir;
    }

    /**
     * Возвращает абсолютный путь до директорий проекта
     *
     * @return string
     */
    public function getProjectFullDir()
    {
        return $this->baseDir . $this->projectDir;
    }

    /**
     * @param $arProject
     * @return void
     */
    public function setProject($arProject)
    {
        $this->arProject = $arProject;
    }

    /**
     * @param string $subDirectory
     * @return array|false
     */
    public function copyCssFiles($subDirectory)
    {
        return $this->copyFiles($subDirectory, 'css', 'export_csspath');
    }

    /**
     * @param string $subDirectory
     * @return array|false
     */
    public function copyJsFiles($subDirectory)
    {
        return $this->copyFiles($subDirectory, 'js', 'export_jspath');
    }

    /**
     * @param string $subDirectory
     * @return array|false
     */
    public function copyImagesFiles($subDirectory)
    {
        return $this->copyFiles($subDirectory, 'images', 'export_imgpath');
    }

    /**
     * @param string $subDirectory
     * @param string $type
     * @param string $projectPathKey
     * @return array|false
     */
    public function copyFiles($subDirectory = '', $type = '', $projectPathKey = '')
    {
        $this->lastError = '';

        if (!in_array($type, array('images', 'js', 'css'))) {
            $this->lastError = 'Invalid files type';
            return false;
        }

        if (!in_array($projectPathKey, array('export_imgpath', 'export_jspath', 'export_csspath'))) {
            $this->lastError = 'Invalid export path type';
            return false;
        }

        if (empty($this->arProject) || empty($this->arProject[$type])) {
            $this->lastError = 'Not found project or empty ' . $type;
            return false;
        }

        $letter = substr($subDirectory, 0, 1);
        if ($letter == '/' || $letter == DIRECTORY_SEPARATOR) {
            $subDirectory = substr($subDirectory, 1);
        }

        $letter = substr($subDirectory, -1);
        if ($letter == '/' || $letter == DIRECTORY_SEPARATOR) {
            $subDirectory = substr($subDirectory, 0, -1);
        }

        $arResult = array();
        for ($i = 0; $i < count($this->arProject[$type]); $i++) {
            $newFile = $this->copyFile(
                $this->arProject[$type][$i]['from'],
                $subDirectory . DIRECTORY_SEPARATOR . $this->arProject[$type][$i]['to']
            );
            if (!$newFile) {
                //die("Error for coping:" . $this->lastError);
                return false;
            }

            $arResult[] = $newFile;
        }

        return $arResult;
    }

    /**
     * Создаем базовые папки, для хранения css, js, img
     *
     * @return boolean В случае ошибки возвращается FALSE и текст ошибки помещается в Tilda::$lastError
     */
    public function createBaseFolders()
    {
        $flag = true;
        $this->lastError = '';

        $baseDir = $this->baseDir;
        $fullProjectDir = $this->getProjectFullDir();

        if ($baseDir != '') {
            if (!file_exists($baseDir)) {
                if (mkdir($baseDir, 0776, true)) {
                    echo 'Folder created: ' . $baseDir . PHP_EOL;
                } else {
                    $this->lastError .= 'Failed folder creation: ' . $baseDir . PHP_EOL;
                    $flag = false;
                }
            }

            if (!is_writable($baseDir)) {
                $this->lastError .= 'Folder must be writable: ' . $baseDir . ' Please change folder attribute to 0776' . PHP_EOL;
                $flag = false;
            }
        }

        if ($fullProjectDir != '') {
            if (!file_exists($fullProjectDir)) {
                if (mkdir($fullProjectDir, 0776, true)) {
                    echo 'Folder created: ' . $fullProjectDir . PHP_EOL;
                } else {
                    $this->lastError .= 'Failed folder creation: ' . $fullProjectDir . PHP_EOL;
                    $flag = false;
                }
            }
        }

        $cssPath = $this->getPath('css');
        if (!file_exists($cssPath)) {
            if (mkdir($cssPath, 0776, true)) {
                echo 'Folder created: ' . $cssPath . PHP_EOL;
            } else {
                $this->lastError .= 'Failed folder creation: ' . $cssPath . PHP_EOL;
                $flag = false;
            }
        }

        $jsPath = $this->getPath('js');
        if (!file_exists($jsPath)) {
            if (mkdir($jsPath, 0776, true)) {
                echo 'Folder created: ' . $jsPath . PHP_EOL;
            } else {
                $this->lastError .= 'Failed folder creation: ' . $jsPath . PHP_EOL;
                $flag = false;
            }
        }

        $imagesPath = $this->getPath('images');
        if (!file_exists($imagesPath)) {
            if (mkdir($imagesPath, 0776, true)) {
                echo 'Folder created: ' . $imagesPath . PHP_EOL;
            } else {
                $this->lastError .= 'Failed folder creation: ' . $imagesPath . PHP_EOL;
                $flag = false;
            }
        }

        if (!file_exists($fullProjectDir . 'meta')) {
            if (mkdir($fullProjectDir . 'meta', 0776, true)) {
                echo 'Folder created: ' . $fullProjectDir . 'meta' . PHP_EOL;
            } else {
                $this->lastError .= 'Failed folder creation: ' . $fullProjectDir . 'meta' . PHP_EOL;
                $flag = false;
            }
        }

        return $flag;
    }

    /**
     * Копируем файлы извне в указанную директорию относительно директории проекта
     *
     * @param string $from
     * @param string $to
     * @return false|string
     */
    function copyFile($from, $to)
    {
        $this->lastError = '';
        if ($from == '') {
            $this->lastError = 'Error. Source file url is empty!' . PHP_EOL;
            return false;
        }

        if ($to == '') {
            $this->lastError = 'Error. File name is empty!' . PHP_EOL;
            return false;
        }

        $fullProjectDir = $this->baseDir . $this->projectDir;
        $newFile = $fullProjectDir . $to;

        if (copy($from, $newFile)) {
            if (substr(sprintf('%o', fileperms($newFile)), -4) !== '0776') {
                if (!chmod($newFile, 0776)) {
                    $this->lastError = '. But can\'t set permission for file to 0776 because ' . sprintf('%o', fileperms($newFile));
                    return false;
                }
            }
            return $newFile;
        } else {
            $this->lastError = '(a) Copy failed: ' . $newFile;
            return false;
        }
    }

    /**
     * @param string $to
     * @param string $str
     * @return void
     */
    function createPage($to, $str)
    {
        $fullProjectDir = $this->baseDir . $this->projectDir;
        $newFile = $fullProjectDir . $to;

        if (file_put_contents($newFile, $str)) {
            echo('<li>File created: ' . $newFile);
            if (!chmod($newFile, 0776)) {
                echo('. But can\'t set permission for file to 0776');
            }
        } else {
            echo 'file create failed: ' . $newFile . PHP_EOL;
        }

    }


    /**
     * Показываем страницу, если она есть
     *
     * @param string $name
     * @return bool
     */
    public function showPage($name)
    {
        $this->lastError = '';
        if (file_exists($this->getProjectFullDir() . 'meta' . DIRECTORY_SEPARATOR . $name . '.php')) {
            $arPage = include $this->getProjectFullDir() . 'meta' . DIRECTORY_SEPARATOR . $name . '.php';
        } elseif (file_exists($this->getProjectFullDir() . 'meta' . DIRECTORY_SEPARATOR . 'page' . intval($name) . '.php')) {
            $arPage = include $this->getProjectFullDir() . 'meta' . DIRECTORY_SEPARATOR . 'page' . intval($name) . '.php';
        } else {
            $this->lastError = 'Page config file not found';
            return false;
        }

        if (!empty($arPage['id']) && file_exists($this->getProjectFullDir() . $arPage['id'] . '.html')) {
            include $this->getProjectFullDir() . $arPage['id'] . '.html';
        } else {
            $this->lastError = 'Html file not found';
            return false;
        }
        return true;
    }

    /**
     * Сохраняем страницу
     *
     * @param array $tildaPage
     * @return array
     */
    public function savePage($tildaPage)
    {
        $filename = $tildaPage['id'] . '.html';

        $upload_path = $this->getProjectFullDir();

        if (!file_exists($upload_path)) {
            mkdir($upload_path, '0776', true);
        }

        $imagesPath = $this->getPath('images', false);
        foreach ($tildaPage['images'] as $image) {
            echo 'Copy image [' . $image['from'] . '] ' . PHP_EOL;
            $this->copyFile(
                $image['from'],
                $imagesPath . DIRECTORY_SEPARATOR . $image['to']
            );
        }

        $jsPath = $this->getPath('js', false);
        foreach ($tildaPage['js'] as $js) {
            echo 'Copy js [' . $js['from'] . '] ' . PHP_EOL;
            $this->copyFile(
                $js['from'],
                $jsPath . DIRECTORY_SEPARATOR . $js['to']
            );
        }

        $cssPath = $this->getPath('css', false);
        foreach ($tildaPage['css'] as $css) {
            echo 'Copy css [' . $css['from'] . '] ' . PHP_EOL;
            $this->copyFile(
                $css['from'],
                $cssPath . DIRECTORY_SEPARATOR . $css['to']
            );
        }

        /* сохраняем HTML */
        file_put_contents($this->getProjectFullDir() . $filename, $tildaPage['html']);

        return $tildaPage;
    }

    /**
     * Сохраняем мета данные о странице (нужно ли обновлять, заголовок, обложку и т.п.)
     *
     * @param array $page
     * @throws Exception
     * @return array
     */
    public function saveMetaPage($page)
    {
        if (empty($page['needsync'])) {
            $page['needsync'] = '0';
        }

        if (empty($page['socnetimg'])) {
            $page['socnetimg'] = '';
        }
        $phpContent = <<<EOT
<?php
return array(
    'id' => '{$page['id']}',
    'title' => '{$page['title']}',
    'alias' => '{$page['alias']}',
    'descr' => '{$page['descr']}',
    'img' => '{$page['img']}',
    'featureimg' => '{$page['featureimg']}',
    'socnetimg' => '{$page['socnetimg']}',
    'needsync' => '{$page['needsync']}'
);
?>
EOT;
        $metaName = 'page' . $page['id'] . '.php';

        $metaDir = $this->getProjectFullDir() . 'meta';

        if (!file_exists($metaDir)) {
            if (!mkdir($metaDir, 0776, true)) {
                throw new Exception('Cannot create Project dir [' . $metaDir . ']' . PHP_EOL);
            }
        }

        file_put_contents($metaDir . DIRECTORY_SEPARATOR . $metaName, $phpContent);

        if ($page['alias'] > '') {
            file_put_contents(
                $metaDir . DIRECTORY_SEPARATOR . $page['alias'] . '.php',
                '<?php return include "' . $metaName . '"; ?>'
            );
        }

        return $page;
    }

    /**
     * В случае ошибки отправляет сообщение, выводит JSON сообщение об ошибке и завершает работу скрипта
     *
     * @param string $message
     * @return void
     */
    public function errorEnd($message)
    {
        if ($this->buglovers > '') {
            $headers = 'From: ' . $this->emailFrom;
            @mail($this->buglovers, 'Tilda Sync Error', $message, $headers);
        }
        die(json_encode(array('error' => $message)));
    }

    /**
     * В случае успеха, выводит JSON сообщение и завершает работу скрипта
     *
     * @param string $message
     * @return void
     */
    public function successEnd($message = 'OK')
    {
        if ($this->buglovers > '') {
            $headers = 'From: ' . $this->emailFrom;
            @mail($this->buglovers, 'Tilda Sync OK', $message, $headers);
        }
        die('{"result":"OK"}');
    }

    /**
     * @param string $type
     * @param bool   $full
     * @return string
     */
    public function getPath($type = '', $full = true)
    {
        $path = $full ? $this->getProjectFullDir() : '';

        switch ($type) {
            case 'css':
                if (!empty($this->arProject['export_csspath'])) {
                    $path .= $this->arProject['export_csspath'];
                } else {
                    $path .= 'css';
                }
                break;
            case 'js':
                if (!empty($this->arProject['export_jspath'])) {
                    $path .= $this->arProject['export_jspath'];
                } else {
                    $path .= 'js';
                }
                break;
            case 'images':
                if (!empty($this->arProject['export_imgpath'])) {
                    $path .= $this->arProject['export_imgpath'];
                } else {
                    $path .= 'images';
                }
                break;
            default:
                break;
        }

        return $path;
    }
}

