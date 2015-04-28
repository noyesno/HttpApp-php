<?php

AppSession::start();
//AppUser::login();

class AppUser {
  // static $meta  = array();
  static $roles = array(); // root owner admin super login guest robot exclude 

  // TODO: use '#role' stand for non persisted
  static function role($role, $val=null, $persist=true){
    if(is_null($val)){
      if(isset($_SESSION['roles'][$role])){
        return $_SESSION['roles'][$role];
      }else if(isset(self::$roles[$role])){
        return self::$roles[$role];
      }else{
         return false;
      }
    }else{
      if($val===false){
        unset($_SESSION['roles'][$role]);
        unset(self::$roles[$role]);
      }else{
        if($persist){
          $_SESSION['roles'][$role] = $val;
        }else{
          self::$roles[$role] = $val;
        }
      }
    }

    return isset($_SESSION['roles'][$role])?$_SESSION['roles'][$role]:false;
  }

  static function is($role){
    return isset($_SESSION['roles'][$role]) || isset(self::$roles[$role]);
  }

  static function logout($role='login'){
    unset($_SESSION['user']);
    self::role($role, false);
  }

  static function login($meta=array(), $role='login'){
    if(!empty($meta)){
      foreach($meta as $k=>$v) $_SESSION['user'][$k] = $v; 
    }
    if(!empty($role)){
      self::role($role, $meta);
    }
    //if(isset($_SESSION['user'])){
    //  self::role('login', true);
    //}
  }

  static function get($name, $val=null){
    // TODO: add default value support
    return isset($_SESSION['user'][$name])?$_SESSION['user'][$name]:$val;
  }

  #TODO: set(k1, k2, k3, value) / set('k1.k2.k3', value);
  static function set($name, $value){
    $_SESSION['user'][$name] = $value;
  }

  static function merge($dict, $overwrite=true){
    if(empty($dict)) return;
    foreach($dict as $k=>$v) if($overwrite || !isset($_SESSION['user'][$k])) $_SESSION['user'][$k] = $v; 
  }
}


class AppRequest {
  // TODO
}

class AppResponse {
  // TODO
}
