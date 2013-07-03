<?php

class Timer {
  static $start = 0.0;

  static function mark(){
    self::$start = microtime(true);
  }
  static function elapse(){
    return microtime(true) - self::$start;
  }
}

Timer::mark();

/*
  date_default_timezone_set('Asia/Shanghai');
*/



define('HTTP_APP',dirname(__FILE__).'/app');

/**************** Autoload & Bootstrap **********/


/********** Set include_path ******************/
set_include_path(implode(PATH_SEPARATOR, array(
  HTTP_APP.'/lib',
  HTTP_APP
)).PATH_SEPARATOR.get_include_path());


require(HTTP_APP.'/core/HttpApp.php');

