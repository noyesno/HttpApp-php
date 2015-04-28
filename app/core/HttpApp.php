<?php

if(!defined('HTTP_CACHE')) define('HTTP_CACHE', HTTP_APP.'/cache');

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


/***********************************************************
 * HttpApp - Global Controller
 ***********************************************************/

include('core/AppAutoload.php');

class HttpApp {

  public static function brake(){
    throw new SystemExit();
  }

  public static function alarm($message){
    throw new Exception($message);
    // fastcgi_finish_request();
  }

  function __construct(){

    if($timezone = AppConfig::get('timezone')){
      date_default_timezone_set($timezone); //e.g. Asia/Shanghai
    }

    // TODO:
    // error_reporting(-1);
    if(AppConfig::get('debug')){
      $level = (E_ALL | E_STRICT) & ~E_NOTICE; // E_STRICT
      $level = (E_ALL  ) & ~E_STRICT & ~E_NOTICE; // E_STRICT
      ini_set("display_errors", 1); 
      ini_set("error_log", 'error_log'); 
    }else{
      $level = E_ALL ^ E_NOTICE;
    }
    error_reporting($level);
    set_error_handler("AppError::handle", $level);
    set_exception_handler('AppError::exception');

    if($value = AppConfig::get('session.cookie_domain')){
      ini_set('session.cookie_domain',$value);
    }
  }

  static function instance(){
    static $inst = null;
    if($inst==null){
      $inst = new HttpApp();
    }
    return $inst;
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
    if(strlen($path)>512){
      return self::alarm("Long Request Path ".strlen($path).' '.substr($path, 0, 128));
    }


    HttpRequest::init();

    /******************* Init Request Info *****************/

    $server = $_SERVER['SERVER_NAME']; $script = $_SERVER['SCRIPT_NAME'];
    $toks   =  explode('?',$_SERVER["REQUEST_URI"]);
    $url =  urldecode(array_shift($toks));
    $this->env['request'] = array(
	'path'   => trim($path,'/'),
	'url'    => '/'.trim($url,'/'),
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
        'lang' => AppConfig::get('lang','zh'),
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

  function dispatch($path='/'){
      $path = trim($path,'/');
      $this->init($path);

      HttpResponse::setContentType('text/html','utf-8');

      AppRegistry::set('env',    $this->env);
      AppRegistry::set('schema', $this->schema);
      $path = $this->env['app']['path'];

      include('core/AppRouteDefault.php'); // TODO

      AppRoute::route($path);
      return;
  } // end run

  public function run($path=null){
    try {
      /* code code */

      // AppWall::run();

      //ob_start();
      HttpResponse::capture();
      //session_start();

      if(is_null($path)) $path = isset($_GET['@page'])?$_GET['@page']:'/';

      AppTimer::mark('dispatch');

      $this->dispatch($path);

      AppTimer::elapse('dispatch');
      // echo AppTimer::$elapse['dispatch'];
      //HttpResponse::setData($data);

      //TODO: $headers = HttpResponse::getHeader();
      AppLog::trace('send() # elapse='.AppTimer::elapse());
      #-- if(!empty($_GET['_profile']) || !empty($_GET['.profile'])){
      #--   HttpResponse::header('X-Profile', print_r(AppTimer::$elapse, true));
      #-- }
      HttpResponse::send();
      AppLog::trace('...... '.date('r').' ......');

    } catch (SystemExit $e) {

      /* do nothing */
    } catch (Exception $e) {

      HttpResponse::status(500); 
      $smarty = AppSmarty::instance();
      $smarty->assign('exception', $e);
      $smarty->display('500.tpl');
      // echo 'Exception: ', $e;
      throw $e;
    }
  }

} // end class: HttpApp





include('core/AppHttp.php'); 

#==============================================================================#
/**************** Autoload & Bootstrap **********/

define('HTTP_TPL', HTTP_APP.'/page');

class SystemExit extends Exception {}
class AppExit extends Exception {} // use this to replace SystemExit
// Infact, we should distinguish request exit and FastCGI exit


//*
AppRoute::add('^/', 'AppRouteCaptcha'); 
AppRoute::add('^/', 'AppRoutePreroute'); 
AppRoute::add('^/', 'AppRoutePrelude'); 
AppRoute::add('^/', 'AppRouteDelegate'); 
AppRoute::add('^/', 'AppRouteWatchdog'); 
//*/

//TODO: fastcgi_finish_request();
