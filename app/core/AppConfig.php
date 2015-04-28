<?php

if(!defined('HTTP_CONF')) define('HTTP_CONF', HTTP_APP.'/conf/default');

class AppConfig {
  static $cfg = null;

  static function init(){
     self::$cfg = parse_ini_file(HTTP_CONF.'/site.ini', false);
  }

  static function section($name, $raw=false){
     $sections = parse_ini_file(HTTP_CONF.'/site.ini', true, $raw?INI_SCANNER_RAW:INI_SCANNER_NORMAL);
     return $sections[$name];
  }

  static function get($k=null, $value=null){
    if(!isset(self::$cfg)) self::init();
    if(!is_null($k)) return isset(self::$cfg[$k])?(self::$cfg[$k]):$value;
    return self::$cfg;
  }

  static function set($k=null, $value=null){
    if(!isset(self::$cfg)) self::init();
    self::$cfg[$k] = $value;
  }

  static function read($k){ # TODO:
    $file = HTTP_CONF.'/app.ini';
    $cfg = @unserialize(file_get_contents($file));
    if(!is_array($cfg)) $cfg = array();
    return isset($cfg[$k])?$cfg[$k]:null;
  }
  static function save($key, $value){
    $file = HTTP_CONF.'/app.ini';
    $cfg = @unserialize(file_get_contents($file));
    if(!is_array($cfg)) $cfg = array();
    $cfg[$key] = $value;
    file_put_contents(serialize($cfg));
    return;
  }
}

