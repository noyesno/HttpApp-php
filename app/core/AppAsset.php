<?php


class AppAsset {
  static function url($path, $rev=true){
    $mask = crc32($path) & 0x01;
    if($rev && file_exists($path)){
      $mtime = filemtime($path);
      $path = preg_replace('/(\.\w+)$/', '+'.date('ymdhi',$mtime).'$1', $path);
    }
 
    $sites = explode(' ', AppConfig::get('site.static.cluster'));
    $site  = $sites[$mask];
    return $site.'/'.$path;
  }
}

