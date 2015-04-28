<?php

class Http {
  static function query($url, $query, $noserver=false){
    $parts = parse_url($url);

    if(!is_array($query)) parse_str($query, $query);

    parse_str($parts['query'], $toks);
    $query = http_build_query(array_merge($toks, $query));

    $url = preg_replace('#\?.*#', '', $url);
    if($noserver){
      $url = preg_replace('#^http://[^/]+#', '', $url);
    }

    if(strlen($query)>0) $url .= '?'.$query;

    return $url;
  }
}
