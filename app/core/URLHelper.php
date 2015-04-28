<?php

class URLHelper {
  static function join ($prefix, $suffix)){
    if(  strncmp("http://", $suffix,7)==0 
      || strncmp("https://", $suffix,8)==0 
    ) {
      return $suffix;
    }    
    return rtrim($prefix,'/').'/'.ltrim($suffix,'/');
  }
}
