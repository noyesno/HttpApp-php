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
    return isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:null; 
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



class HttpResponse {
  /* 
   * text/html; charset=utf-8
   * charset=utf-8
   */


  static function setContentType($content_type='text/plain', $enc='utf-8'){
    header('Content-Type: '.$content_type.'; charset='.$enc);
  }

  static function setLastModified($mtime){
    $s = gmdate('D, d M Y H:i:s T',$mtime);
    header('Last-Modified: '.$s); 
    return isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strcmp($s, $_SERVER['HTTP_IF_MODIFIED_SINCE'])==0; 
  }

  static function getLastModified(){
    return self::getHeader('Last-Modified');
  }          

  static function getHeader($name=null){
    $headers = headers_list();

    $n = strlen($name);
    $value = null;
    foreach($headers as $line){
      if(strncasecmp($name,$line,$n)==0) { 
        $value = substr($line,$n+1);
        break;
      }
    }  
    return $value;
  }



  static function setCacheControl($control, $max_age = 0, $must_revalidate = true){
    if(is_array($control)) {
      $toks = array(); 
      foreach( $control as $key => $val ) {
        $toks[]  = empty($key)?$val:"$key=$val";
      }
      $strategy = $control[0];
      $max_age = $control['max-age'];
    } else {
      $strategy = $control;
      $toks = array($control, "max-age=$max_age");
      if($must_revalidate) $toks[] = 'must-revalidate';
    }
    header('Cache-Control: '.implode(',',$toks));
    if($strategy !='no-cache' && $max_age>0){
      $mtime = time();
      header('Vary: Accept');
      // header('Expires: '.gmdate('D, d M Y H:i:s T', $mtime+$max_age));
    }
  }

  /*
    301 Moved Permanently
    302 Found
    303 See Other
    304 Not Modified
    305 Use Proxy
    306 (Unused)
    307 Temporary Redirect
    401 Unauthorized
    403 Forbidden
    404 Not Found
  */
  static function redirect ($url, $status=302){
    self::status($status);
    header("Location: $url");
    throw new SystemExit();
  }

  static function status($status){
    // TODO:
    static $status_txt_lut = array(
      '200'=>'OK',
      '201'=>'Created',
      '204'=>'No Content',
      '301'=>'Moved Permanently',
      '302'=>'Found',
      '303'=>'See Other',
      '304'=>'Not Modified',
      '307'=>'Temporary Redirect',
      '400'=>'Bad Request',
      '401'=>'Unauthorized', // Not Authorized
      '403'=>'Forbidden',
      '404'=>'Not Found',
      '503'=>'Service Unavailable'
    );

    $status_txt = $status_txt_lut[$status];
    if(php_sapi_name()=='cgi'){
      header('Status:'." $status $status_txt");
    }else{
      header($_SERVER["SERVER_PROTOCOL"]." $status $status_txt");
    }
  }

  static function setETag($etag){
    header("ETag: $etag");
  }

  static $data  = null;
  static $cache = false;
  static $gzip  = false;

  static function setGzip($gzip){
    self::$gzip = $gzip;
  }
  static function setCache($cache){
    self::$cache = $cache;
  }
  static function capture(){
    ob_start();
  }
  static function setData($data){
    self::$data = $data;
  }

  static function send($clean_ob=true){
    if(self::$data){
      $data = self::$data;
    }else{
      $data = ob_get_contents();
      if($clean_ob) ob_end_clean();
    }

    $last_modified = self::getLastModified();
    $etag = md5($data);

    if(isset($_SERVER['HTTP_IF_NONE_MATCH']) 
      && strcmp($etag, $_SERVER['HTTP_IF_NONE_MATCH'])==0){
      if(!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) 
        || strcmp($last_modified, $_SERVER['HTTP_IF_MODIFIED_SINCE'])==0){
        self::status(304);
      }else{
        echo $data;
      }
    }else{    
      $size = strlen($data);
      //header("Content-Length: $size");
      //self::setETag($etag);
      echo $data;
    }
    flush();
  }

  static function sendStaticFile($file, $expire=3600){  // TODO: handle $expire
    $mime  = MIME::mime_content_type($file);
    $mtime = max(filemtime($file),filemtime(__FILE__));
  
    HttpResponse::setContentType($mime);
    HttpResponse::setCacheControl('public', 3600*24*100, false);
    if(HttpResponse::setLastModified($mtime)){
      HttpResponse::status(304);
      throw new SystemExit();
    }
    
    $text = file_get_contents($file); 
    if($mime=='text/css'){
      // TODO:
      // $text = html_compact_css($text);
    }
    
    print($text);
    throw new SystemExit();
  }               


  static function sendFile($file){
    $fp = fopen($file, 'rb');
    header("Content-Type: ".MIME::mime_content_type($file));
    header("Content-Length: " . filesize($file));
    fpassthru($fp);
    throw new SystemExit();
    return;
  }

  static function sendJSON($o){
    HttpResponse::setContentType('text/plain');
    print(json_encode($o));
    throw new SystemExit();
    return;
  }
}

class Http {
  static function query($url, $query, $noserver=false){
    $parts = parse_url($url);

    if(!is_array($query)) parse_str($query, $query);

    parse_str($parts['query'], $toks);
    $query = http_build_query(array_merge($toks, $query));

    $url = preg_replace('#\?.*#', '', $url);
    if($noserver){
      $url = preg_replace('#^http://[^/]+#', '', $url);
    }

    if(strlen($query)>0) $url .= '?'.$query;

    return $url;
  }
}
