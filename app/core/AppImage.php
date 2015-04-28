<?php

class AppImage {
  static function size($file){
    if(file_exists($file)) return null;
    $size=getimagesize($file);
    if($size===false){
      return false;
    }

    if(is_array($size)){
      $size['width'] = $size[0]; $size['height'] = $size[1];
    }
    return $size;
  }

  static function resize($file, $width, $height){
  
  }

}
