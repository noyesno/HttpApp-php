<?php

class DbUtil {
    static $n_query = 0;
    var $errno ;
    var $errstr; 
    private static $_inst_map = array();


    public static function decode($url){
      $urlpattern = '|(.*?)://([^:]*?)(:[^:]*?)?@(.*?)(:.*?)?/([^/]*?)$|';
      if (!preg_match($urlpattern, $url, $matches)) {
        return null;
      }
      
      array_shift($matches);  
      $matches[2] = substr($matches[2],1); // $password
      $matches[4] = substr($matches[4],1); // $port
      return $matches;
    }

    
    private function __construct(){ }



    public static function connect($url, $name='.') {
        list($driver) = self::decode($url);
        switch($driver){
          case 'mysql':
            require_once(dirname(__FILE__).'/DbUtil.mysql.php');
            $inst = new DbUtil_mysql($url); break;
          case 'mysqli':
            $inst = new DbUtil_mysqli($url); break;
          default:
            $inst = new DbUtil_mysql($url);
        }
        self::$_inst_map[$url] = $inst;
        if(!empty($name)) self::$_inst_map[$name] = $inst;
        return;
    }


    public static function instance($name='.') {
      static $_inst_set = array();

      if($name=='.' && !isset(self::$_inst_map[$name])){
        self::connect(AppCfg::get('db.url'), $name);
      }

      if(!isset(self::$_inst_map[$name])){
        throw new ErrorException("DB Connection $name was not found!");
        return null;
      }

      $dbutil = self::$_inst_map[$name];
      //$dbutil->_connect();
      return $dbutil;
    }
} // end class
