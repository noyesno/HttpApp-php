<?php
/**
%require HTTP_APP const
*/

if(!defined('HTTP_APP')) define('HTTP_APP', dirname(__DIR__));

AppAutoload::register();

class AppAutoload {
  public static function register() {
    spl_autoload_register('AppAutoload::load');
    register_shutdown_function('AppAutoload::shutdown');
  }

  public static function load($class_name) {
    if(preg_match('/Model/', $class_name)){
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

    //-- $loaders = spl_autoload_functions();
    //-- if(!in_array('AppAutoload::postload', $loaders)){
    //--   spl_autoload_register('AppAutoload::postload');
    //-- }
  }

  public static function postload($class_name){
    //TODO
  }

  public static function shutdown() {
    // TODO
    $error = error_get_last(); // PHP 5 >= 5.2.0
    if($error != null){
      if(isset($_GET['_debug']) || AppConfig::get('debug')){
        // print_r($error); // TODO: save to log instead of print
      }
    }
  }
}
