<?php
///////////////////////////////////////////////////////////////////////////////
/**
 * Tilda Publishing
 * @copyright (C) 2015 Оbukhov Nikita Valentinovich. Russia
 * @license MIT
 *
 * @author Nikita Obukhov <hello@tilda.cc>
 * @author Michael Akimov <michael@island-future.ru>
 * 
 * Описание: 
 * Класс для работы с API tilda.cc
 * 
 **/
///////////////////////////////////////////////////////////////////////////////

class Tilda
{

    protected $apiUrl = "http://api.tildacdn.info/v1/";
    protected $publicKey;
    protected $secretKey;
    
    /* Корневая директорая Вашего сайта (абсолютный путь) */
    public $baseDir;
    /* директория, куда будут сохраняться данные проекта (указываем путь относительно корневой директории) */
    public $projectDir;
    
    public $emailFrom = 'postmaster';
    public $buglovers = 'you@mail.there';
    
    public $lastError = '';
        
    /**
     * инициализируем класс
     *
     * $arOptions - массив дополнительных параметров
     **/
    public function __construct($publicKey, $secretKey, $arOptions = array())
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        
        /* базовая директория, относительно которой все и создается */
        if (! empty($arOptions['baseDir'])) {
            $this->baseDir = $arOptions['baseDir'];
        } elseif(empty($_SERVER['DOCUMENT_ROOT'])) {
            $this->baseDir = dirname(__DIR__);
        } else {
            $this->baseDir = $_SERVER['DOCUMENT_ROOT'];
        }

        if (substr($this->baseDir,-1) != DIRECTORY_SEPARATOR &&  substr($this->baseDir,-1) != '/') {
            $this->baseDir .= DIRECTORY_SEPARATOR;
        }

        /* у каждого проекта есть свой набор стилей и скриптов - храним их отдельно */
        if (! empty($arOptions['projectDir'])) {
            $this->projectDir = $arOptions['projectDir'];
            if (substr($this->projectDir,0,1) == DIRECTORY_SEPARATOR || substr($this->projectDir,0,1) == '/') {
                $this->projectDir = substr($this->projectDir,1);
            }

            if (! file_exists($this->baseDir . $this->projectDir)) {
                if (!mkdir($this->baseDir . $this->projectDir, 0776, true)) {
                    throw new Exception('Cannot create Project dir [' . $this->baseDir.$this->projectDir . ']'."\n");
                }
            }

            if (substr($this->projectDir,-1) != DIRECTORY_SEPARATOR &&  substr($this->projectDir,-1) != '/') {
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

    /* возвращает относительный путь проекта */
    public function getProjectDir()
    {
        return  $this->projectDir;
    }
    
    /* возвращает абсолютный путь до директорий проекта */
    public function getProjectFullDir()
    {
        return $this->baseDir . $this->projectDir;
        
    }
    
    /* обращение к API Tilda */
    public function call($method, $params)
    {
        /* список методов и обязательный параметров */
        $arTildaMethods = array(
            'getprojectslist' => array(),
            'getproject' => array('required' => array('projectid')),
            'getprojectexport' => array('required' => array('projectid') ),
            'getpageslist' => array('required' => array('projectid') ),
            'getpage' => array('required' => array('pageid') ),
            'getpagefull' => array('required' => array('pageid') ),
            'getpageexport' => array('required' => array('pageid') ),
            'getpagefullexport' => array('required' => array('pageid') ),
        );
        
        /* проверяем, может в API такого метода нет */
        if (! isset($arTildaMethods[$method])) {
            $this->lastError = 'Unknown Method: '. $method;
            return false;
        }
        
        /* проверяем, все ли необходимые параметры указали */
        if (isset($arTildaMethods[$method]['required'])) {
            foreach($arTildaMethods[$method]['required'] as $param) {
                if (!isset($params[$param])) {
                    $this->lastError = 'Param ['.$param.'] required for method ['. $method.']';
                    return false;
                }
            }
        }
        $params['publickey']=$this->publicKey;
        $params['secretkey']=$this->secretKey;
        
        $query = http_build_query($params);
        
        /* отправляем запрос в API */
        $result = file_get_contents($this->apiUrl . $method .'/?' . $query);
        
        /* проверяем, полученный результат, декодируем его из JSON и отдаем пользователю */
        if ($result && substr($result,0,1) == '{') {
            $result = json_decode($result,true);

            if (isset($result['status']) && $result['status'] == 'FOUND') {
                return $result['result'];
            } else {
                $this->lastError = 'Not found data';
                return false;
            }
            return $result;
        } else {
            $this->lastError = 'Unknown Error ['.$result.']';
            return false;
        }
    }

    /* функция возвращает спиок проектов пользователя */
    public function getProjectsList()
    {
        return $this->call('getprojectslist', array());
    }

    /* функция возвращает информацию о проекте */
    public function getProject($projectid)
    {
        return $this->call('getproject', array('projectid' => $projectid));
    }
    
    /* функция возвращает информацию о проекте для экспорта */
    public function getProjectExport($projectid)
    {
        return $this->call('getprojectexport', array('projectid' => $projectid));
    }
    
    /* функция возвращает список страниц проекта */
    public function getPagesList($projectid)
    {
        return $this->call('getpageslist', array('projectid' => $projectid));
    }

    /* функция возвращает информацию о странице (+body html-code) */
    public function getPage($pageid)
    {
        return $this->call('getpage', array('pageid' => $pageid));
    }

    /* функция возвращает информацию о странице (+full html-code) */
    public function getPageFull($pageid)
    {
        return $this->call('getpagefull', array('pageid' => $pageid));
    }

    /* функция возвращает Информация о странице для экспорта (+body html-code) */
    public function getPageExport($pageid)
    {
        return $this->call('getpageexport', array('pageid' => $pageid));
    }

    /* Информация о странице для экспорта (+full html-code) */
    public function getPageFullExport($pageid)
    {
        return $this->call('getpagefullexport', array('pageid' => $pageid));
    }


    /***
     * функции прикладного значения
     */
    
    
    
    /**
     * создаем базовые папки, для хранения css, js, img
     * @return boolean в случае ошибки возвращается FALSE и текст ошибки помещается в Tilda::$lastError
     **/
    public function createBaseFolders()
    {
        $flag=true;
        $this->lastError = '';
        
        $basedir = $this->baseDir;
        $fullprojectdir = $this->getProjectFullDir();

        if ($basedir <> "") {
            if (!file_exists($basedir)) {
                if (mkdir($basedir, 0776, true)){
                    echo "Folder created: ".$basedir . "\n";
                } else {
                    $this->lastError .= "Failed folder creation: ".$basedir."\n";
                    $flag=false;
                }
            }
            
            if (!is_writable($basedir)) {
                $this->lastError .= "Folder must be writable: ".$basedir." Please change folder attribute to 0776\n";
                $flag=false;
            }
        }
        
        if ($fullprojectdir <> "") {
            if (!file_exists($fullprojectdir)) {
                if (mkdir($fullprojectdir, 0776, true)) {
                    echo"Folder created: ".$fullprojectdir."\n";
                } else {
                    $this->lastError .= "Failed folder creation: ".$fullprojectdir."\n";
                    $flag=false;
                }
            }
        }
        
        if (! file_exists($fullprojectdir.'css')) {
            if (mkdir($fullprojectdir.'css', 0776, true)){
                echo "Folder created: ".$fullprojectdir.'css'."\n";
            } else {
                $this->lastError .= "Failed folder creation: ".$fullprojectdir.'css'."\n";
                $flag = false;
            }
        }
        
        if (!file_exists($fullprojectdir.'js')) {
            if (mkdir($fullprojectdir.'js', 0776, true)){
                echo "Folder created: ".$fullprojectdir.'js'."\n";
            }else{
                $this->lastError .= "Failed folder creation: ".$fullprojectdir.'js'."\n";
                $flag=false;
            }
        }
        
        if (!file_exists($fullprojectdir.'img')) {
            if (mkdir($fullprojectdir.'img', 0776, true)) {
                echo "Folder created: ".$fullprojectdir.'img'."\n";
            } else {
                $this->lastError .= "Failed folder creation: ".$fullprojectdir.'img'."\n";
                $flag=false;
            }
        }
        
        if (!file_exists($fullprojectdir.'meta')) {
            if (mkdir($fullprojectdir.'meta', 0776, true)) {
                echo "Folder created: ".$fullprojectdir.'meta'."\n";
            } else {
                $this->lastError .= "Failed folder creation: ".$fullprojectdir.'meta'."\n";
                $flag=false;
            }
        }

        return($flag);
    }


    /**
     * Копируем файлы извне в указанную директорию относительно директории проекта 
     */
    function copyFile($from,$to)
    {
        $this->lastError = '';
        if ($from == '') {
            $this->lastError="Error. Source file url is empty!\n";
            return false;
        }
        
        if ($to == '') {
            $this->lastError = "Error. File name is empty!\n";
            return false;
        }
        
        $fullprojectdir = $this->baseDir.$this->projectDir; 
        $newfile=$fullprojectdir.$to;
        
        echo "Copy [$from] to [$newfile]\n";
        if (copy($from, $newfile)) {
            if(substr(sprintf('%o', fileperms($newfile)), -4) !== "0776"){
                if(!chmod($newfile, 0776)){
                    echo('. But can\'t set permission for file to 0776 because '.sprintf('%o', fileperms($newfile))."\n");
                    die();
                }
            }
        }else{
            echo "<li>(a) Copy failed: ".$newfile."\n";
        }
    }
    
    /**
     * Копируем файлы, если они не существуют, если существуют, то пропускаем
     * 
     * @param string $from - URL картинки
     * @param string $dir - каталог относительно каталога проекта, куда будет помещен файл
     * @param boolean $isRewrite - если установлен в true, то картинка перезаписывается, иначе нет
     * 
     * @return string имя файла под которым сохранено на диске
     */
    public function copyImageTo($from, $dir, $isRewrite=false)
    {
        $fullprojectdir = $this->getProjectFullDir() . $dir;
        $newfile = md5($from);

        if (! file_exists($fullprojectdir)) {
            echo "Create directory ".$fullprojectdir ."\n";
            if (! mkdir($fullprojectdir, '0776', true)) {
                die("Cannot create directory [" . $fullprojectdir . "]\n");
            }
        }
        
        $pos = strrpos($from,'.');
        if ($pos > 0) {
            $ext = strip_tags(addslashes(substr($from,$pos+1)));
        } else {
            $ext = '';
        }
        
        echo "==> copy file from: $from ".($isRewrite ? 'with rewrite' : 'without rewirite')."\n";
        /* если */
        if (file_exists($fullprojectdir.$newfile.'.'.$ext) && $isRewrite==false) {
            echo 'File already exist: ' . $newfile . ".$ext\n"; 
        } else {
            /* закачиваем файл */
            copy($from, $fullprojectdir . $newfile);
            echo "GetImageSize [$from] tmpfile: $newfile\n";
            $size = getimagesize($fullprojectdir . $newfile);

            /* определяем тип изображения */
            if(empty($size['mime'])) {
                $ext = 'jpg';
            } else {
                $img = null;
                if ($size['mime'] == 'image/jpeg') {
                    $ext = 'jpg';
                } elseif ($size['mime'] == 'image/png') {
                    $ext = 'png';
                } elseif ($size['mime'] == 'image/gif') {
                    $ext = 'gif';
                } if ($size['mime'] == 'image/svg') {
                    $ext = 'svg';
                }
            }
            
            echo('File copied: '. $fullprojectdir . $newfile.".$ext\n");
            
            /* переименовываем файл, добавляя ему расширение */
            rename($fullprojectdir  . $newfile, $fullprojectdir . $newfile . '.' . $ext);
            if(substr(sprintf('%o', fileperms($fullprojectdir . $newfile . '.' . $ext)), -4) !== "0776"){
                if(!chmod($fullprojectdir . $newfile . '.' . $ext, 0776)){
                    echo('. But can\'t set permission for file to 0776'.sprintf('%o', fileperms($fullprojectdir . $newfile . '.' . $ext))."\n");
                    die();
                }
            }
        }
        
        /* возвращаем новое название файла */
        return $newfile . '.' . $ext;
    }
    
    function createPage($to,$str)
    {
        $fullprojectdir=$this->baseDir.$this->projectDir;
        $newfile=$fullprojectdir.$to;
        
        if (file_put_contents($newfile, $str)) {
            echo('<li>File created: '.$newfile);
            if (!chmod($newfile, 0776)) {
                echo('. But can\'t set permission for file to 0776');    
            }    
        } else {
            echo "file create failed: ".$newfile."\n";
        }
     
    }

    /* показываем страницу, если она есть */
    public function showPage($name)
    {
        $this->lastError = '';
        if (file_exists($this->getProjectFullDir().'meta'.DIRECTORY_SEPARATOR.$name.'.php')) {
            $arPage = include $this->getProjectFullDir().'meta'.DIRECTORY_SEPARATOR.$name.'.php';
        } elseif (file_exists($this->getProjectFullDir().'meta'.DIRECTORY_SEPARATOR.'page'.intval($name).'.php')) {
            $arPage = include $this->getProjectFullDir().'meta'.DIRECTORY_SEPARATOR.'page'.intval($name).'.php';
        } else {
            $this->lastError = 'Page config file not found';
            return false;
        }
        
        if (! empty($arPage['id']) && file_exists($this->getProjectFullDir() . $arPage['id'] . '.html')) {
            include $this->getProjectFullDir() . $arPage['id'] . '.html';
        } else {
            $this->lastError = 'Html file not found';
            return false;
        }
    }

    /* заменяем все картинки в HTML-страницы, на локальные адреса */
    public function replaceOuterImageToLocal($tildapage, $export_imgpath='', $upload_path='')
    {
        $exportimages = array();
        $replaceimages = array();
        if ($upload_path == '') {
            
            $upload_dir = $this->getProjectFullDir() . 'img'. DIRECTORY_SEPARATOR;
            $upload_path = '/' . $this->getProjectDir() . 'img/';
            if (DIRECTORY_SEPARATOR != '/') {
                $upload_path = str_replace(DIRECTORY_SEPARATOR,'/', $upload_path);
            }

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, '0776', true);
            }
        }
        $uniq = array();
        $html = null;

        if (! empty($tildapage['images']) && sizeof($tildapage['images']) > 0) {
            foreach ($tildapage['images'] as $image) {
                if( isset($uniq[$image['from']]) ){ continue; }
                $uniq[$image['from']] = 1;
                
                if ($export_imgpath > '') {
                    $exportimages[] = '|'.$export_imgpath.'/'.$image['to'].'|i';
                } else {
                    $exportimages[] = '|'.$image['to'].'|i';
                }
                if (!empty($image['local'])) {
                    $to = $image['local'];
                } else {
                    $to = $image['to'];
                }
                if(substr($to,0,1) == '/' && substr($upload_path,-1)=='/') {
                    $replaceimages[] = $upload_path.substr($to,1);
                } else {
                    $replaceimages[] = $upload_path.$to;
                }

            }

            $html = preg_replace($exportimages, $replaceimages, $tildapage['html']);
        } else {
            $html = $tildapage['html'];
        }
        
        if ($html) {
            $tildapage['html'] = $html;
        }
        return $tildapage;
    }

    /**
     * Сохраняем страницу
     */
    public function savePage($tildapage)
    {
        $filename = $tildapage['id'] . '.html';
        
        $upload_path = $this->getProjectFullDir();
        
        if (! file_exists($upload_path)) {
            mkdir($upload_path,'0776', true);
        }
    
        for ($ii = 0; $ii < count($tildapage['images']); $ii++) {
            echo "Copy image [".$tildapage['images'][$ii]['from']."] \n";
            $tildapage['images'][$ii]['local'] = $this->copyImageTo($tildapage['images'][$ii]['from'],  'img' . DIRECTORY_SEPARATOR, true);
        }

        if ($tildapage['img'] > '' && substr($tildapage['img'],0,4) == 'http') {
            $tmp = $this->copyImageTo($tildapage['img'], $upload_path . 'img' . DIRECTORY_SEPARATOR, true);
            $tildapage['images'][] = array(
                'from' => $tildapage['img'],
                'to' => $tildapage['img'],
                'local' => $tmp
            );
            $tildapage['img'] = $tmp;
        }    

        if ($tildapage['featureimg'] > '' && substr($tildapage['featureimg'],0,4) == 'http') {
            $tmp = $this->copyImageTo($tildapage['featureimg'], $upload_path . 'img' . DIRECTORY_SEPARATOR, true);
            $tildapage['images'][] = array(
                'from' => $tildapage['featureimg'],
                'to' => $tildapage['featureimg'],
                'local' => $tmp
            );
            $tildapage['featureimg'] = $tmp;
        }    

        if ($tildapage['fb_img'] > '' && substr($tildapage['fb_img'],0,4) == 'http') {
            $tmp = $this->copyImageTo($tildapage['fb_img'], $upload_path . 'img' . DIRECTORY_SEPARATOR, true);
            $tildapage['images'][] = array(
                'from' => $tildapage['fb_img'],
                'to' => $tildapage['fb_img'],
                'local' =>   $tmp
            );
            $tildapage['fb_img'] = $tmp;
            
        }    
        
        /* заменяем пути до картинок в HTML на новые (куда картинки скачались) */
        $tildapage = $this->replaceOuterImageToLocal($tildapage, $tildapage['export_imgpath'], '');
        
        /* сохраняем HTML */
        file_put_contents($this->getProjectFullDir() . $filename, $tildapage['html']);

        return $tildapage;
    }

    /* сохраняем мета данные о странице (нужно ли обновлять, заголовок, обложку и т.п.) */
    public function saveMetaPage($page)
    {
        if (empty($page['needsync'])) {
            $page['needsync'] = '0';
        }

        if(empty($page['socnetimg'])) { $page['socnetimg'] = '';}
$phpcontent = <<<EOT
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
        $metaname = 'page'.$page['id'] . '.php';
        file_put_contents($this->getProjectFullDir().'meta'  . DIRECTORY_SEPARATOR . $metaname, $phpcontent);
        
        if ($page['alias'] > '') {
            file_put_contents($this->getProjectFullDir().'meta' . DIRECTORY_SEPARATOR . $page['alias'] . '.php', '<?php return include "'.$metaname.'"; ?>');
        }
        
        return $page;
    }
    

    /* в случае ошибки отправляет сообщение, выводит JSON сообщение об ошибке и завершает работу скрипта */
    public function errorEnd($message)
    {
        if ($this->buglovers > '') {
            $headers = 'From: ' . $this->emailFrom;
            $emailto = $this->buglovers;
            @mail($emailto, 'Tilda Sync Error', $message, $headers);
        }
        die('{"error":"'.htmlentities($message).'"}');
    }
    
    /* в случае успеха, выводит JSON сообщение и завершает работу скрипта */
    public function successEnd($message='OK')
    {
        if ($this->buglovers > '') {
            $headers = 'From: ' . $this->emailFrom;
            $emailto = $this->buglovers;
            @mail($emailto, 'Tilda Sync OK', $message, $headers);
        }
        die('{"result":"OK"}');
    }



}


