<?php
/*
* Registry::add($obj,'hello');
* Registry::get('hello');
*/

class Registry {
  static private $_store = array();

  static public function add($object, $name=null){
    $name = is_null($name)?get_class($object):'';
    $name = strtolower($name);

     $return = null;
     if(isset(self::$_store[$name])){
       $return = self::$_store[$name];
     }
     self::$_store[$name] = $object;
     return $return;
  }

  static public function get($name){
    if(!self::contains($name)){
      throw new Exception("Object does not exist in registry");
    }
    return self::$_store[$name];
  }

  static public function contains($name){
    return isset(self::$_store[$name]);
  }

  static public function remove($name){
    if(isset(self::$_store[$name])) unset(self::$_store[$name]);
  }
}
