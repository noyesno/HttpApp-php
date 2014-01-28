<?php
/*
* AppRegistry::set('name', $obj);
* AppRegistry::get('name');
*/

class AppRegistry {
  static private $_store = array();

  static public function set($name, &$object){
    $name = is_null($name)?get_class($object):$name;

    $return = null;
    if(isset(self::$_store[$name])){
      $return = self::$_store[$name];
    }
    self::$_store[$name] = &$object;
    return $return;
  }

  static public function &get($name){
    if(!self::has($name)){
      throw new Exception("Object does not exist in registry");
    }
    return self::$_store[$name];
  }

  static public function has($name){
    return isset(self::$_store[$name]);
  }

  static public function del($name){
    if(isset(self::$_store[$name])) unset(self::$_store[$name]);
  }
}

