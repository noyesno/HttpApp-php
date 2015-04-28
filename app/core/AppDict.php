<?php

class AppDict {
  static $lut  = array();
  static $rlut = array();

  static function get($key, $val=null){
    if(isset(self::$lut[$key])) return self::$lut[$key];
    return is_null($val)?$key:$val;
  }

  static function rget($key, $val=null){
    if(isset(self::$rlut[$key])) return self::$rlut[$key];
    return is_null($val)?$key:$val;
  }

  static function set($key, $val, $rset=false){
    if(is_null($key) || is_null($val)) return;
    self::$lut[$key]  = $val;
    if($rset) self::$rlut[$val] = $key;
  }

  static function del($key){
    if(is_null($key)) return;
    $val = $self::$lut[$key];
    #TODO: check existance
    unset(self::$lut[$key]);
    unset(self::$rlut[$val]);
  }

}
