<?php

class AppTag {
  static function split($text, $sp=null){
    if(empty($sp)){
      return new AppTag($text);
    }else{
      return new AppTag($text, $sp);
    }
  }

  var $tags  = array();
  var $itags = array();
  # TODO: auto separator 
  function __construct($text, $sp='/,\s*/'){
    $sp = '/\s+/';
    if(strpos($text,',')>0) $sp='/,\s*/';

    $text =  trim($text);
    $tags = preg_split($sp, $text);
    foreach($tags as $t){
      if(strlen($t)==0) continue;
      if($t[0]=='/')  $this->itags[] = $t;
      else            $this->tags[]  = $t;
    }
  }
}
