<?php

class AppAutoload {
  public static function load($class_name) {
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
}
