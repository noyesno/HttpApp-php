<?php
/*
* AppRegistry::set('name', $obj);
* AppRegistry::get('name');
*/

class AppRegistry {
  static private $_store    = array();
  static private $callbacks = array();

  static public function set($name, &$object){
    $name = is_null($name)?get_class($object):$name;

    $return = null;
    if(isset(self::$_store[$name])){
      $return = self::$_store[$name];
    }
    self::$_store[$name] = &$object;
    return $return;
  }

  static public function &get($name, $value=null){
    if(self::has($name)){
      return self::$_store[$name];
    }
    if(func_num_args()==1){
      throw new Exception("Object does not exist in registry");
    }else{
      return $value;
    }
  }

  static public function has($name){
    if(isset(self::$_store[$name])) return true;

    if(isset(self::$callbacks[$name])){
      self::set($name, self::$callbacks[$name]());
    }
    return isset(self::$_store[$name]);
  }

  static public function del($name){
    if(isset(self::$_store[$name])) unset(self::$_store[$name]);
  }

  static public function register($name, $callback){
    self::$callbacks[$name] = $callback;
  }
}

