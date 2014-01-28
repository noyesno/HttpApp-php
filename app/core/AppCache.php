<?php

require_once('Cache/Lite.php');
/***************************************************
* Usage:
****************************************************
$cacheutil = CacheUtil::instance(600);
if($data = $cacheutil->get($id)){
  // use data
}else{
  $data = '...';
  $cacheutil->save($data);
}

****************************************************/

class AppCache {
  static public function instance($lifetime=3600){
    $dir = AppCfg::get('cache.dir');
    $options = array(
    'automaticSerialization'=>true,
    'cacheDir' => $dir,
    'lifeTime' => $lifetime
    );
    if(!empty($_GET['_nocache'])){
      $options['caching'] = false;
    }

    $Cache_Lite = new Cache_Lite($options);
    return $Cache_Lite;
  }

  static function path($file){
    $dir = AppCfg::get('cache.dir');
    if($file[0]=='/'){
      return $file;
    }else{
      return rtrim($dir,'/')."/$file";
    }
  }

  static function newer($timestamp, $basefile){
    $dir = AppCfg::get('cache.dir');
    $basefile = self::path($basefile);
    return !file_exists($basefile) || $timestamp>filemtime($basefile); // TODO
  }
}
