<?php

class AppTimer {
  static $lut    = array();
  static $elapse = array();

  static function mark($name='default', $append=false){
    self::$lut[$name] = microtime(true);
    # TODO: if $append, array_push(...) 
  }

  static function elapse($name='default', $ret=false){
    if($ret) return self::$elapse[$name];

    $elapse = microtime(true) - self::$lut[$name];
    self::$elapse[$name] = $elapse;
    return $elapse;
  }
}
