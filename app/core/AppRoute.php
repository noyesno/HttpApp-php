<?php

/*
AppRoute::add('/user/:id', null);
AppRoute::add('/user/{id}', null);
AppRoute::add('/user/(?P<id>\d+)', null);

AppRoute::add('^/', 'AppRouteCaptcha'); 
AppRoute::add('^/', 'AppRoutePreroute'); 
AppRoute::add('^/', 'AppRoutePrelude'); 
AppRoute::add('^/', 'AppRouteDefault'); 
AppRoute::add('^/', 'AppRouteWatchdog'); 
*/

class AppRoute {
  static $lut = array();

  static function add($pattern, $router=null){
    array_push(self::$lut, array($pattern, $router)); 
  }  

  static function filter($name, $action=null){
  }

  static function route($path){
    $env =& AppRegistry::get('env');

    $path = trim($path, '/');
    foreach(self::$lut as $rule){
      list($pattern, $router) = $rule;
      if(!preg_match('#'.$pattern.'#', '/'.$path, $matches)) continue;

      if(isset($matches['/'])){
	 $path = trim($matches['/'], '/');
         unset($matches['/']);
      }

      foreach(array_filter(array_keys($matches), 'is_string') as $k){
	$env['app']['args'][$k] = $matches[$k];
      }

      if(!empty($router)){
        $router = new $router();
        $status = $router->route($path); // use pass by reference to modify $path value 
        if($status === false) break;
      }
    }
  }

  /*
   * '(?<$1>[^/]+)' syntax is only starting from PHP 5.2.2
   */

  static function expand($uri, $dfn=array()){
     $pattern     = array('/:(\w+)/',       '/\{(\w+)\}/');
     $replacement = array('(?P<$1>[^/]+)',   '(?P<$1>[^/]+)');
     // '/\(\?\<(\w+)\>/' => '(?P<$1>'
     if(version_compare(PHP_VERSION, '5.2.2') >= 0){
       $pattern[]     = '/\(\?\<(\w+)\>/';
       $replacement[] = '(?P<$1>'; 
     }
     return '#'.preg_replace($pattern, $replacement, $uri).'#'; 
  }
}




