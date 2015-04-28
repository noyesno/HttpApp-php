<?php

class AppToken {
  var $token;

  # expr 26*pow(36,8) = 73,348,857,593,856
  public static function id($length=9) {
    // $special_chars = array('-', '_', '=');
    //$alphas    = array_merge(range('a', 'z'), range('A','Z'));
    $alphas    = array_merge(range('a', 'z'));
    $alphanums = array_merge($alphas, range('0', '9'));

    shuffle($alphas);
    shuffle($alphanums);

    $key = '';
    for ($i = 0; $i < $length; $i++) {
      $pool = ($i==0?$alphas:$alphanums);
      $key .= $pool[mt_rand(0, count($pool)-1)];
    }

    return $key;
  }

  public static function make($name='_token'){
    return new AppToken($name);
  }

  static function verify($name='_token'){
    return isset($_POST[$name]) && isset($_COOKIE[$name]) 
        && strlen($_POST[$name])>9 && strlen($_POST[$name])<256 
        && strcmp($_POST[$name], $_COOKIE[$name])==0 ;
  }


  function __construct($name='_token'){
    $this->name = $name;
    $this->token = md5(uniqid('token'));
    return $this->token;
  }



  function html(){
    return "<input type='hidden' name='{$this->name}' value='{$this->token}'/>";
  }

  function cookie($path=null, $expire=3600){
    if(is_null($path)){
      list($path) = explode('?', $_SERVER['REQUEST_URI']);
    }
    setcookie($this->name, $this->token, time() + $expire, $path); // TODO
  }
}
