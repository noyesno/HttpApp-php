<?php

class HttpResponse {
  /* 
   * text/html; charset=utf-8
   * charset=utf-8
   */
  static $data  = null;
  static $cache = false;
  static $gzip  = false;

  public static function finish(){
    // fastcgi_finish_request();
  }

  static function header($key, $val=null){
    if(is_null($val)){
      # TODO: remove ??
    }
    header("$key: $val");
  }

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

    if(is_null($name)) return $headers;

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
      //header('Pragma:');
      header('Vary: Accept');
      // TODO: better not use both
      //header('Expires: '.gmdate('D, d M Y H:i:s T', $mtime+$max_age));
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
      '202'=>'Accepted',
      '204'=>'No Content',
      '205'=>'Reset Content',
      '301'=>'Moved Permanently',
      '302'=>'Found',
      '303'=>'See Other',
      '304'=>'Not Modified',
      '307'=>'Temporary Redirect',
      '400'=>'Bad Request',
      '401'=>'Unauthorized', // Not Authorized
      '403'=>'Forbidden',
      '404'=>'Not Found',
      '500'=>'Internal Server Error',
      '503'=>'Service Unavailable',
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
    $data = '';
    if(!is_null(self::$data)){
      $data = self::$data;
    }else{
      $data = ob_get_contents();
      if($clean_ob) ob_end_clean();
      //TODO: if($data===false) $data = '';
    }

    $last_modified = self::getLastModified();
    $etag = md5($data);

    # TODO:
    if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && strcmp($etag, $_SERVER['HTTP_IF_NONE_MATCH'])==0){
      if(!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || strcmp($last_modified, $_SERVER['HTTP_IF_MODIFIED_SINCE'])==0){
        AppLog::debug('send 304 If-None-Match');
        self::status(304);
      }else{
        echo $data;
      }
    }else{    
      $size = strlen($data);
      //header("Content-Length: $size");
      //TODO: self::setETag($etag);
      AppLog::debug('echo data');
      echo $data;
    }
    AppLog::debug('flush');
    flush(); /* TODO: is this help? */
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

