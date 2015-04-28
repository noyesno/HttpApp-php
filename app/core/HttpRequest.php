<?php

class HttpRequest {
  static $form  = array();
  static $roles = array();

  /*
  static $server  = null;
  static $script  = null;
  static $uri     = null;
  static $mode    = null; // redirect or path_info
  static $root    = null;
  static $path    = null;
  static $qstring = null; // $uri = $root + $path + '?' + $qstring
  */

  /*
  static $location = array();

  static function location($name){
    // host hostname href pathname port protocol search
    // qstring
    //# URL
    
    if(isset(self::$location[$name])) return self::$location[$name];
  
    self::$server = $_SERVER['SERVER_NAME'];
    self::$script = $_SERVER['SCRIPT_NAME'];
    self::$uri    = $_SERVER['REQUEST_URI'];

    if(strncmp(self::$uri, self::$script, strlen(self::$script))){ // redirect
      self::$mode = 'redirect';  self::$root = dirname(self::$script);
    }else{
      self::$mode = 'path_info'; self::$root = self::$script;
    }      

    $p = strpos(self::$uri,'?');
    if($p>0){
      self::$qstring = substr(self::$uri, $p+1);
      self::$path = '/'.trim(substr(self::$uri,strlen(self::$root),-1-strlen(self::$qstring)),'/');
    }else{
      self::$path = '/'.trim(substr(self::$uri,strlen(self::$root)),'/');
    }

    self::$location['host']    = $_SERVER['SERVER_NAME'];
    //self::$location['port']    = $_SERVER['SERVER_NAME'];
    self::$location['search']  = $_SERVER['QUERY_STRING'];
    self::$location['qstring'] = $_SERVER['QUERY_STRING'];
    self::$location['href']    = $_SERVER['REQUEST_URI'];
    self::$location['pathname']= $_SERVER['REQUEST_URI'];  // TODO: remove QSTRING
    self::$location['root']    = self::$root;
    self::$location['path']    = self::$path;
  
    if(isset(self::$location[$name])) return self::$location[$name];
    return null;
  }
  */

  static function init(){
    //# Form
    if(self::is_post()){
      self::$form = $_POST;
    }
  }

  static function roles($role=null){
    if(empty(self::$roles)){
      self::$roles[] = 'all';
      @session_start();
      if(is_array($_SESSION['roles'])){
        foreach($_SESSION['roles'] as $r) self::$roles[] = $r; 
      }
    }
    if(!is_null($role) && !in_array($role,self::$roles)) self::$roles[] = $role;

    return self::$roles;
  }



  static function ip(){
    return $_SERVER['REMOTE_ADDR']; 
  }

  static function method(){
    $method = $_SERVER['REQUEST_METHOD'];
    
    if(isset($_POST['_method'])){
      $qmethod = strtoupper($_POST['_method']);
      if($method == 'POST' && $qmethod != 'GET'){
        $method = $qmethod;  
      }
    }

    return $method;
  }


  static function getLastModified(){
    //return isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:null; 
    return empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])?0:strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
  }

  # return true when $mtime > If-Modifed-Since
  static function checkModifiedSince($mtime, $check_expired=true){
    if(empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) return false;
    $modified_since = HttpRequest::getLastModified();

    if($check_expired){
      return ($modified_since>0 && $mtime>$modified_since);
    } else {
      return ($modified_since>0 && $mtime<=$modified_since);
    }
  }

  static function ask_json(){
    return strpos($_SERVER['HTTP_ACCEPT'],'application/json')!==false;
  }

  static function ask_wml(){
    return strpos($_SERVER['HTTP_ACCEPT'],'text/vnd.wap.wml')!==false;
  }

  static function user_agent($chk=null){ 
    if($chk == null) 
      return $_SERVER["HTTP_USER_AGENT"]; 
    else
      return stripos($_SERVER["HTTP_USER_AGENT"], $chk) !== false;
  }

  static function is_mobile(){ 
     return preg_match('/Symbian|Opera Mobi|Android|Mobile/',
           $_SERVER["HTTP_USER_AGENT"]);
  }

  static function is_ajax(){ return $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest' || $_GET['_ajax']; }

  static function is_post()  { return strcmp($_SERVER['REQUEST_METHOD'], 'POST')  ==0; }
  static function is_get()   { return strcmp($_SERVER['REQUEST_METHOD'], 'GET')   ==0; }
  static function is_delete(){ return strcmp($_SERVER['REQUEST_METHOD'], 'DELETE')==0; }
  static function is_put()   { return strcmp($_SERVER['REQUEST_METHOD'], 'PUT')   ==0; }
}
