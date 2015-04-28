<?php


class AppClient {
  static $data = array();

  public static function init(){
    static $inited = false;

    if($inited) return;

    $uid = null;
    foreach(array('__cfduid', '__xxxu') as $key){
      if(isset($_COOKIE[$key])){
        $uid = $_COOKIE[$key];
        break;
      }
    }

    self::$data['_uid'] = $uid;

    if(empty($uid)){
      $path  = '/';
      $uid = md5(uniqid('noyesno/', true).var_export($_SERVER,true));
      $lifetime = 3600*24*365;
      setcookie('__xxxu', $uid, time()+$lifetime, $path);
    }

    self::$data['uid'] = $uid;

    $inited = true;
  }

  public static function uid($client=false){
    return $client?self::$data['_uid']:self::$data['uid'];
  }
}


AppClient::init();

