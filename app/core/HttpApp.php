<?php

/*===============================================================*
* error_reporting(E_ERROR | E_WARNING | E_PARSE);
* error_reporting(E_ALL | E_STRICT);
* set_exception_handler('happ_exception_handler');
*================================================================*/

/*
* Development Cycle
*  1. dev
*  2. test 
*  3. lca   Limited Customer Available
*  4. prod
*/

class AppCfg {
  static $cfg = null;

  static function init(){
     self::$cfg = parse_ini_file(HTTP_APP."/app.ini",false);
  }

  static function get($k=null, $default=null){
    if(!isset(self::$cfg)) self::init();
    if($k) return isset(self::$cfg[$k])?(self::$cfg[$k]):$default;
    return self::$cfg;
  }

  static function read($k){
    $file = HTTP_APP.'/conf/app.ini';
    $cfg = @unserialize(file_get_contents($file));
    if(!is_array($cfg)) $cfg = array();
    return isset($cfg[$k])?$cfg[$k]:null;
  }
  static function save($key, $value){
    $file = HTTP_APP.'/conf/app.ini';
    $cfg = @unserialize(file_get_contents($file));
    if(!is_array($cfg)) $cfg = array();
    $cfg[$key] = $value;
    file_put_contents(serialize($cfg));
    return;
  }
} // end class: AppCfg


/***********************************************************
 * HttpApp - Global Controller
 ***********************************************************/

class HttpApp {
  function __construct(){
    spl_autoload_register('HttpApp::autoload');
    set_error_handler("HttpApp::error_handler");
    if(AppCfg::get('debug')){
      error_reporting(E_ALL | E_STRICT);
    }else{
      error_reporting(E_ALL);
    }
  }

  static function instance(){
    static $inst = null;
    if($inst==null){
      $inst = new HttpApp();
    }
    return $inst;
  }

  static function autoload($class_name) {
    if(preg_match('/Model$/', $class_name)){
      $file = HTTP_APP.'/model/'.$class_name.'.php';
      //@require_once($file);
      require($file);
      return;
    }

    foreach(array('core','lib') as $dir){
      $file = HTTP_APP."/$dir/".$class_name.'.php';
      if(file_exists($file)){ 
        //require_once($file);
        require($file);
        return;
      }
    }
  }

  static function error_handler($errno,$errstr,$errfile,$errline,$errcontext){
    $message = sprintf("Error: [%d] %s @ %s:%d %s\n",$errno, $errstr, $errfile, $errline, $_SERVER['REQUEST_URI']);
    if(isset($_GET['_debug']) || AppCfg::get('debug')){
      print('<div>'.$message."</div>\n");
      if($errno & ~E_NOTICE){
        echo "\n###<pre class=\"backtrace\">\n";
        debug_print_backtrace();
        echo "\n###</pre>\n";
      }
    }else{
      //error_log("<html><h2>stuff</h2></html>",1,"eat@joe.com","subject  :lunch\nContent-Type: text/html; charset=ISO-8859-1");
    }
  
    if($errno & E_NOTICE){
      error_log($message, 3, AppCfg::get('error.log.file').'.notice');
      return;
    }
    if($errno & E_STRICT){
      error_log($message, 3, AppCfg::get('error.log.file').'.strict');
      return;
    }
    if($errno & E_WARNING){
      error_log($message, 3, AppCfg::get('error.log.file').'.warn');
      return;
    }
    error_log($message, 3, AppCfg::get('error.log.file'));
    // TODO: show an user friendly error page
  } 

  var $schema = array(
      'route'=> array( //format: pattern  param_names acl acl_fail_action
        //-- array('^/(0\d+)/?(.*)', array('cid','.')),
        //-- array('^/cities$'),
        //-- array('^/help/.*'),
        //-- array('^/admin/login$'),
        //-- array('^/admin/?.*',                          null, 'admin', '/admin/login'),
        //-- array('^/(event|agency|friend|marriage)/?.*', null, '!all', 'guess_visitor_city')
      )
  );       


  var $path;
  var $env  = array();
  // var $args = array();
  /*
   * URL = 
   *   + $env['request']['schema']
   *   + $env['request']['server']
   *   + $env['request']['script']
   *   + $env['request']['root']        #  /subsite 
   *   + $env['request']['path']        #  ==app
   *   + $env['request']['qstring']
   *   + $env['request']['rewrite']     #  TBD
   *
   *   Can be from HttpRequest
   *
   * path =
   *   + $env['app']['realm']
   *   + $env['app']['args']
   *   + $env['app']['path']
   *     + $env['app']['argv']
   *
   * $env['app']['php']
   * $env['app']['tpl']
   */
 
  /*
   *                              base
   *                       /------------------\
   *                      /      args[]        \
   *                     /      +-----+         \
   *                    /       |     |          \
   * http://www.site.com/subsite/c-021/app/subapp/arg1/arg2?q=qstring
   *       \___________/\______/\____/\_________/\________/ \_______/
   *          server      root  |realm    app      argv[] |  qstring
   *                            |_________________________|
   *                                      path
   *
   * 
   * url          : http://server.com/app/action?p=123
   * urn          : app/action?p=123
   * uri          : /app/action?p=123
   * root?        : /
   * root_url     : http://server.com/app
   *
   * app          : app name
   * php          : app php
   * tpl          : app tpl
   * path         : app path (params)
   * argv         : app path as array
   * base_url     : /app
   * base_urn     : 
   * prefix_url   : 
   */

  function init($path){
    HttpRequest::init();

    /******************* Init Request Info *****************/

    $server = $_SERVER['SERVER_NAME']; $script = $_SERVER['SCRIPT_NAME'];
    $toks   =  explode('?',$_SERVER["REQUEST_URI"]);
    $url =  urldecode(array_shift($toks));
    $this->env['request'] = array(
	'path'   => trim($path,'/'),
	'url'    => $url,
        'server' => $server,
        'script' => $script,
	'qstring'=> $_SERVER['QUERY_STRING']
    );

    if(strncmp($_SERVER["REQUEST_URI"], $script, strlen($script))!=0){ // URL Rewrite Used
      $this->env['request']['rewrite'] = 'rewrite';
      $this->env['request']['root']    = dirname($script);
    }else{
      $this->env['request']['rewrite'] = 'path_info';
      // TODO:
    }      

    /******************* Init App Setting *****************/

    $this->env['app'] = array(
	'path' => $this->env['request']['path'],
	'args' => array()
    );

    /******************* Init View Setting *****************/
    $this->env['tpl'] = array(
	'dir'  =>HTTP_APP.'/page',
	'theme'=>'default',
	'style'=>'default'
    );

    if(isset($_GET['theme'])){
      $theme = $_GET['theme'];
      $path = $this->env['root'];
      setcookie('theme', $theme, time()+3600*24*7, $path);
      $_COOKIE['theme'] = $theme;
    }

    if(isset($_COOKIE['theme'])){
      list($theme, $style) = explode('/', $_COOKIE['theme']);
      $this->env['tpl']['theme'] = $theme; 
      $this->env['tpl']['style'] = $style;
      if($theme != 'default' && !empty($theme)){ 
	$this->env['tpl']['dir'] = HTTP_APP."/theme/$theme";
      }
    }
  } // end init

  function verify_captcha(){
    $method = $_SERVER['REQUEST_METHOD'];
    if($method == 'POST'){
      //-- if(AuthUtil::verify_form_hash() <= 0){
      //--   return false;
      //-- }
    }
    return true;
  }

  function preroute(){
    $path = $this->env['request']['path'];

    $toks = explode('/',$path,3);
    if(empty($toks)) return;
    
    // TODO: maybe should remove this
    switch($toks[0]){
      case 's' :
        list($app,$mtime,$file) = $toks;
        $file = HTTP_APP.'/'.$file;
        HttpResponse::sendStaticFile($file);
        return 1;
      case '.phpinfo' :
        phpinfo();
        PhpUtil::check_ini_setting();
        return 1;
    }
    return 0;
  }

  /* called by prefilter */
  function auth(){ // TODO: rename to function roles() ?????
      // prepare roles for ACL
  }

  function prefilter(){
    $path = $this->env['app']['path'];
    if(empty($this->schema['route'])) return;

    foreach($this->schema['route'] as $rule){
      list($pattern, $acl, $acl_action) = array_pad($rule, 3, null);
      if(!preg_match('#'.$pattern.'#', $path, $matches)) continue;
      
      if($acl){
        $this->auth();
        if(!AuthUtil::acl($acl)->check()){ // ACL FAIL
            echo 'auth fail';exit(0);
          if($acl_action[0]=='/'){ // it's a URL
            $url = $this->env['root'].$acl_action;
            HttpResponse::redirect($url);
          }else{
            call_user_func($acl_action, $this);
          }
          throw new SystemExit();
          return;
        }            
      }
  
      foreach(array_filter(array_keys($matches), 'is_string') as $k){
	$this->env['app']['args'][$k] = $matches[$k];
      }
      if(isset($this->env['app']['args']['path'])){
	 $this->env['app']['path'] = $this->env['app']['args']['path'];
      }
      break;
    }// end foreach
  }          

  function route(){
    $path = $this->env['app']['path'];
    if(empty($path)) $path='home';

    # TODO
    $file_lut = array(
      '/page'=>'app'
    ); 

    $toks = explode('/',$path);
    $app_path=''; $app_type = '';
    foreach($toks as $name){
      // TODO:
      $_path = "$app_path/$name";
      if(isset($file_lut[$_path])){
        $app_type = $file_lut[$_path];
      }else if(file_exists(HTTP_APP."/page$app_path/$name.html")){
        $app_type = '.html';
      }else if($name != 'app' && file_exists(HTTP_APP."/page$app_path/$name.php")){
        $app_type = '.php';
      }else if(file_exists(HTTP_APP."/page$app_path/$name/app.php")){
        $app_type = '.app';
      }else if(file_exists(HTTP_APP."/page$app_path/$name/app.ini")){
        // go deep into the directory
      }else{
        break;
      }   
      $app_path .= "/$name";
      if($app_type == 'app'){
        $app_type = '.app';
        break;
      }
    } // end foreach

    $this->env['app']['dir']     = HTTP_APP."/page$app_path"; 

    $app_path = trim($app_path,'/');
    switch($app_type){
      case '.php':
        $app_php   = "$app_path.php";     $app_tpl   = "$app_path.tpl";
        break;
      case '.html':
        $app_php   = "$app_path.php";     $app_tpl   = "$app_path.html";
        break;
      case '.app':
        $app_php   = "$app_path/app.php"; $app_tpl   = "$app_path/app.tpl";
        break;
      default:
        $app_php   = "404.php"; $app_tpl   = "404.tpl"; $app_type='404';
    }

    $app_args = substr($path, strlen($app_path)+1);
    $app_argv = strlen($app_args)==0?array():explode('/', $app_args);


    /* e.g. :
      urn      = 021/friend/3456.html/edit
      url      = http://example.com/love/021/love/friend/3456.html/edit
      base_urn = 021/friend
      base_url = http://example.com/love/021/love/friend
     */
    $this->env['app']['type']     = $app_type;
    $this->env['app']['path']     = $app_path;
    $this->env['app']['name']     = $app_path;    // TODO: remove this???
    $this->env['app']['argv']     = $app_argv;
    $this->env['app']['argc']     = count($app_argv);

/*
    $app_base = rtrim($this->env['request']['root'].'/'.substr(
	$this->env['request']['path'], 0, strlen($this->env['request']['path'])-strlen($app_args)),
	'/');
*/

    $this->env['app']['base']     = rtrim(substr($this->env['request']['url'].'/', 0, -strlen($app_args)-1),'/');

    $this->env['app']['php']      = $app_php;   
    $this->env['app']['tpl']      = $app_tpl;   

    return array($app_type, $app_path, $app_php, $app_tpl, $app_argv);
  }


  function watchdog(){ }

  function dispatch($path='/'){
      $this->init($path);

      HttpResponse::setContentType('text/html','utf-8');

      if(!$this->verify_captcha()){
        print("Auth FAIL!");
        throw new SystemExit();
      }

      if($this->preroute()){
        throw new SystemExit();
        return 1;
      }

      // extract $args[] & $env['app']['path']
      $this->prefilter();

      list($app_type, $app_path, $app_php, $app_tpl, $app_argv) = $this->route();

      $this->watchdog();
      // TODO: need a callback here to verify inputs???
      ######################  Load Page #######################


      switch($app_type){
        case '.html' :
          $app = new HttpPage($this->env);
          $app->view->cache(3600*24*7);
          break;
        case '.tpl' :
          $app = new HttpPage($this->env);
          break;
        case '.php'  : case '.app'  :
          $app = $this->load_page($app_php);
          break;
        case '404':
          $app = new HttpPage($this->env);
          break;
      }

      $app->onLoad();
      $app->display();
      $app->onUnload();
  } // end run


  /*****************************************************
   * Page Handling
   ****************************************************/
  function load_page($app_php){
      $file_php = HTTP_APP.'/page/'.$app_php;
      #-- require_once($file_php);
      require($file_php);
      $app_name = basename($app_php,'.php');
      $app = null;
      foreach(array('IHttpAppPage', 'IHttpPage', 'Page_'.ucfirst(strtolower($app_name)), 'Page_Impl', 'Page_Inst') as $class_name){
        if(class_exists($class_name, false)){
          $app = new $class_name($this->env);
          break;
        }
      }
      if(!isset($app)){
        print("Error: can not find page!");
        throw new SystemExit();
        return 0;
      }

      // TODO: move to page constructor();
      $app_model = dirname($file_php).'/'.$app_name.'.model';
      if(file_exists($app_model)){
        @require_once($app_model);
        $app->model = new IHttpPageModel();
      }
      return $app;
  }
} // end class: HttpApp





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
      header('Pragma:');
      header('Vary: Accept');
      header('Expires: '.gmdate('D, d M Y H:i:s T', $mtime+$max_age));
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
      self::setETag($etag);
      echo $data;
    }
    flush();
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


#==============================================================================#
/**************** Autoload & Bootstrap **********/

define('HTTP_TPL', HTTP_APP.'/page');

class SystemExit extends Exception {}

$appc = HttpApp::instance();
$appc->schema = array(
      'route'=> array( //format: pattern  param_names acl acl_fail_action
        //-- array('^/(0\d+)/?(.*)', array('cid','.')),
        //-- array('^/cities$'),
        //-- array('^/help/.*'),
        //-- array('^/admin/login$'),
        //-- array('^/admin/?.*',                          null, 'admin', '/admin/login'),
        //-- array('^/(event|agency|friend|marriage)/?.*', null, '!all', 'guess_visitor_city')
      )
);       


try {
  /* code code */
  
  //ob_start();
  HttpResponse::capture();
  //session_start();
  
  $path = isset($_GET['@page'])?$_GET['@page']:'/';
  $appc->dispatch($path);
  //HttpResponse::setData($data);
  //var_export(headers_list());
  HttpResponse::send();
  //var_export(headers_list());
}catch (SystemExit $e){
  /* do nothing */
}catch (Exception $e){
  echo 'Exception: ', $e;
}
