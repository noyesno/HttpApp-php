<?php

class AppEnum {
  var $idx2txt = array();
  var $txt2idx = array();

  public static function build($map){
    $inst = new AppEnum($map);
    return $inst;
  }

  function __construct($map){
    $key = key($map);
    if(is_int($key)){
      foreach($map as $key=>$val){
        $this->idx2txt[$key] = $val; 
        $this->txt2idx[$val] = $key; 
      }
    } else {
      foreach($map as $key=>$val){
        // assert
        $this->idx2txt[$val] = $key; 
        $this->txt2idx[$key] = $val; 
      }
    }
  }

  function idx($key, $default_value=null){
    return isset($this->txt2idx[$key])?$this->txt2idx[$key]:$default_value;
  }

  function txt($key, $default_value=null){
    return isset($this->idx2txt[$key])?$this->idx2txt[$key]:$default_value;
  }

}

return;

$enum = AppEnum::build(['a','b', 7=>'d']);
echo $enum->idx('d'),"\n";
echo $enum->txt(7),"\n";
