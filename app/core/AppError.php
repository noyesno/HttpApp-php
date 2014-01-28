<?php


class AppError {

  static function handle($errno,$errstr,$errfile,$errline,$errcontext){
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
  
}
