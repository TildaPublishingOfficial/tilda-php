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

namespace Tilda;

class Api
{

    protected $apiUrl = "http://api.tildacdn.info/v1/";
    
    /**
     * Curl handler
     *
     * @var handler
     */
    protected $ch;
    
    /**
     * Query timeout
     *
     * @var int
     */
    public $timeout = 20;
    
    /**
     * Tilda public key
     * 
     * @var string 
     */
    protected $publicKey;
    
    /**
     * Tilda secret key
     * 
     * @var string 
     */
    protected $secretKey;
    
    /**
     * Need for store last error
     * 
     * @var string 
     */
    public $lastError = '';
        
    /**
     * инициализируем класс
     *
     * $arOptions - массив дополнительных параметров
     **/
    public function __construct($publicKey, $secretKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Tilda-php');
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_POST, 0);
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    
    /**
     * Функция возвращает спиок проектов пользователя
     *
     * @return array - список проектов  пользователя
     **/
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


    /**
     * Метод обращаяется к API Tilda и возвращает обратно ответ
     * 
     * @param string $method название метода
     * @param array $params список параметров, которые нужно передать в Tilda API
     * @return array массив данных или false в случае ошибки
     **/
    public function call($method, $params)
    {
        $this->lastError = '';
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
        try {
            curl_setopt($this->ch, CURLOPT_URL, $this->apiUrl . $method .'/?' . $query);
            $result = curl_exec($this->ch);
            $reqErr = curl_errno( $this->ch );
            $reqHeader = curl_getinfo( $this->ch );
        } catch(Exception $e) {
            $this->lastError = 'Network error';
            return false;
        }


        /* проверяем, полученный результат, декодируем его из JSON и отдаем пользователю */
        if ($result && substr($result,0,1) == '{') {
            $result = json_decode($result,true);

            if (isset($result['status'])) {
                if ($result['status'] == 'FOUND') {
                    return $result['result'];
                } elseif (isset($result['message'])) {
                    $this->lastError = $result['message'];
                    
                } else {
                    $this->lastError = 'Not found data';
                }
                return false;
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

}


