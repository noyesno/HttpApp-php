<?php

class AppStatus {
  var $message;
  var $status;     // 'succ' | 'fail'
  var $reason;
  var $data = array();

  static $last  = null;
  static $queue = array();

# TODO: add AppStatus::reset()
  function __construct($status, $reason=null, $message=null){
    $this->message = $message;
    $this->status  = $status;
    $this->reason  = $reason;
  }

  static function make($status, $reason=null, $message=null){
    $inst = new AppStatus($status, $reason, $message);
    self::$last = $inst;
    return $inst;
  } 

  static function add($status, $reason=null, $message=null){
    $inst = new AppStatus($status, $reason, $message);
    self::$last = $inst;
    self::$queue[] = $inst;
    return $inst;
  } 

  function data($name,$value=null){
    switch(func_num_args()){
      case 1: return $this->data[$name];   break;
      case 2: $this->data[$name] = $value; break;
      default: // error
    }
  }
}
