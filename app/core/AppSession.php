<?php

class AppSession {
  static $started = false;

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

  static function close(){
    if(self::$started) {
      session_write_close();
    }
  }

  static function start(){
    if(self::$started) return;
    // PHP>=5.4.0: session_status() === PHP_SESSION_ACTIVE

    // TODO:
    // ini_set('session.gc_probability', 1);
    // ini_set('session.gc_divisor', 100);
    // ini_set('session.save_path', '/...');       // To avoid race with other app on the same host/server
    // ini_set('session.gc_maxlifetime', 3600);
    // session_set_cookie_params(3600);

    // session_name('NOYESNOSID');
    // XXX: session_id()
    if(!isset($_SESSION)) session_start();
    self::$started = true;
    # force update session timestamp.
    # Ref: http://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes
    $_SESSION['mtime'] = time(); //TODO: use $_SERVER['REQUEST_TIME']

    // TODO: session_name(AppConfig::get('session.name'));
    // TODO: session_cache_limiter('nocache');
    // TODO: session.referer_check
    session_cache_expire(AppConfig::get('session.timeout', 300));
  }

  # maybe needed after login to avoid CSRF attack
  static function restart(){
    # TODO:
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
