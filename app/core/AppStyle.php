<?php

class AppStyle {
  static function compact($content, $with_date=true){
    $lines = preg_split('/[\r\n]+/',$content);
    
    for($i=0,$n=count($lines); $i<$n; $i++){
      $line = trim($lines[$i]);
      $line = preg_replace("/\/\/(.*)/u",'',$line); // single line comment
      $line = preg_replace('/\s+/', ' ', $line);   // merge white space
      $line = str_replace(': ',':',$line);
      $line = str_replace('; ',';',$line);	
      $lines[$i] = $line;
    }
    $str = implode('',$lines);
    $str = preg_replace("/\/\*(.*?)\*\//u","",$str);   // multiple line commment    
    if($with_date) $str = '/* '.date('c').' */'.$str;
   
    return $str;
  }

}

/*
function smarty_block_css($param, $content, &$smarty) {
 if($repeat) return; // open tag
 if($_GET['_debug']) return $content;
 return compact_css($content);
}
*/
