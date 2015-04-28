<?php

AppLog::init();

class AppLog {
    static $stream  = null;
    static $prefix  = null;
    static $level   = null;
    # ALL TRACE DEBUG INFO WARN ERROR FATAL NONE
    # 9   6     5     4    3    2     1     0

    const LEVEL_NONE  = 0;
    const LEVEL_FATAL = 1;
    const LEVEL_ERROR = 2;
    const LEVEL_WARN  = 3;
    const LEVEL_INFO  = 4;
    const LEVEL_DEBUG = 5;
    const LEVEL_TRACE = 6;
    const LEVEL_ALL   = 9;

    static function init($file=null){
        self::$level = self::LEVEL_INFO;
        self::$prefix =  date('Ymd H:i:s').' '.$_SERVER["REMOTE_ADDR"].':'.$_SERVER['REMOTE_PORT'].' ';
    }

    static function open($file=null){
        if(!is_null(self::$stream)) return;
        if(is_null($file)){
          $file = AppConfig::get('log.file');
        }

        self::$stream = fopen($file, 'a');

        self::debug('REQUEST '.$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI']);
    }

    static function close(){
        fclose(self::$stream);
    }

    static function audit($msg){
      static $stream = null;
      if(is_null($stream)){
        $file = AppConfig::get('log.audit.file');
        $stream = fopen($file, 'a');
      } 

      if(is_null($stream)) return; // TODO: error

      fputs($stream, self::$prefix."AUDIT: $msg\n");
    }

    static function trace($msg){
        if(self::LEVEL_TRACE > self::$level) return;
        if(is_null(self::$stream)) self::open();
        fputs(self::$stream, self::$prefix."TRACE: $msg\n");
    }
    static function debug($msg){
        if(self::LEVEL_DEBUG > self::$level) return;
        if(is_null(self::$stream)) self::open();
        fputs(self::$stream, self::$prefix."DEBUG: $msg\n");
    }

    static function info($msg){
        if(self::LEVEL_INFO > self::$level) return;
        if(is_null(self::$stream)) self::open();
        fputs(self::$stream, self::$prefix."INFO: $msg\n");
    }

    static function warn($msg){
        if(self::LEVEL_WARN > self::$level) return;
        if(is_null(self::$stream)) self::open();
        fputs(self::$stream, self::$prefix."WARN: $msg\n");
    }

    static function error($msg){
        if(self::LEVEL_ERROR > self::$level) return;
        if(is_null(self::$stream)) self::open();
        fputs(self::$stream, self::$prefix."ERROR: $msg\n");
    }

    static function fatal($msg){
        if(self::LEVEL_FATAL > self::$level) return;
        if(is_null(self::$stream)) self::open();
        fputs(self::$stream, self::$prefix."FATAL: $msg\n");
    }
}
