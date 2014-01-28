<?php

class AppConfig {
  static $cfg = null;

  static function init(){
     self::$cfg = parse_ini_file(HTTP_APP."/app.ini",false);
  }

  static function get($k=null, $value=null){
    if(!isset(self::$cfg)) self::init();
    if($k) return isset(self::$cfg[$k])?(self::$cfg[$k]):$value;
    return self::$cfg;
  }

  static function set($k=null, $value=null){
    if(!isset(self::$cfg)) self::init();
    self::$cfg[$k] = $value;
  }

  static function read($k){
    $file = HTTP_APP.'/conf/app.ini';
    $cfg = @unserialize(file_get_contents($file));
    if(!is_array($cfg)) $cfg = array();
    return isset($cfg[$k])?$cfg[$k]:null;
  }
  static function save($key, $value){
    $file = HTTP_APP.'/conf/app.ini';
    $cfg = @unserialize(file_get_contents($file));
    if(!is_array($cfg)) $cfg = array();
    $cfg[$key] = $value;
    file_put_contents(serialize($cfg));
    return;
  }
}

