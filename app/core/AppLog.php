<?php

class AppLog {
    static $fout=null;
    static $prefix=null;

    static function init($file=null){
        if(!is_null(self::$fout)) return;
        if(is_null($file)){
          $file = AppConfig::get('log.file');
        }
        self::$fout = fopen($file, 'a');
        self::$prefix =  date('Ymd H:i:s').' ';
    }

    static function close(){
        fclose(self::$fout);
    }

    static function info($msg){
        self::init();
        fputs(self::$fout, self::$prefix."Info: $msg\n");
    }
    static function warn($msg){
        self::init();
        fputs(self::$fout, self::$prefix."Warn: $msg\n");
    }
    static function error($msg){
        self::init();
        fputs(self::$fout, self::$prefix."Error: $msg\n");
    }
    static function debug($msg){
        self::init();
        fputs(self::$fout, self::$prefix."DEBUG: $msg\n");
    }
}
