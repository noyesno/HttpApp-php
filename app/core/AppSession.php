<?php

class AppSession {
  static $started = 0;
  
  public static function start(){
    if(self::$started) return;
    session_start();
    self::clean();
  }
  
  public static function set($key, $value){
    self::start();
    
    #TODO: handle form of $key1 $key2 $key3 
    $_SESSION[$key] = $value;
  }
  
  public static function get($key, $value=null){
    self::start();
    
    #TODO: handle form of $key1 $key2 $key3 
    return isset($_SESSION[$key])?$_SESSION[$key]:$value;
  }
  
  public static function expire($lifetime, $key){
    $keys = func_get_args(); 
    array_shift($keys);
    $_SESSION['@expire'][implode(' ',$keys)] = time() + $lifetime;
  }
  
  public static function clean(){
    $now = time();
    foreach($_SESSION['@expire'] as $key=>$expire){
      if($expire>$now) continue
        
      $toks = explode(' ',$key);  
      for($i=0, $n=count($toks)-1, $arr =& $_SESSION; $i<=$n; $i++){
        $k = $toks[$i];
        if(!isset($arr[$k])) break;
        if($i==$n)  unset($arr[$k]);
        else        $arr =& $arr[$k];
      } 
    }
  }
}