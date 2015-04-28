<?php


class AppError {

  static function handle($errno, $errstr, $errfile, $errline, $errcontext){
    if(error_reporting()==0){
      // TODO: for @func_call like situation 
      return; 
    }
    $message = sprintf("Error: [%d] %s @ %s:%d %s\n",$errno, $errstr, $errfile, $errline, $_SERVER['REQUEST_URI']);
    if(isset($_GET['_debug']) || AppConfig::get('debug')){
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
      error_log($message, 3, AppConfig::get('error.log.file').'.notice');
      return;
    }
    if($errno & E_STRICT){
      error_log($message, 3, AppConfig::get('error.log.file').'.strict');
      return;
    }
    if($errno & E_WARNING){
      error_log($message, 3, AppConfig::get('error.log.file').'.warn');
      return;
    }
    error_log($message, 3, AppConfig::get('error.log.file'));
    // TODO: show an user friendly error page
  } 

  static function exception($exception){
    error_log(print_r($_SERVER,true)."\n", 3, AppConfig::get('error.log.file').'.exception');
    error_log($exception."\n", 3, AppConfig::get('error.log.file').'.exception');
    // $exception->getMessage()){
  }
}
