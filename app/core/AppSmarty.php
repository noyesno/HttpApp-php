<?php

// class AppSmarty
function smarty_block_dynamic($param, $content, &$smarty, &$repeat) {
    return $content;
}

function smarty_block_hide($param, $content, &$smarty, &$repeat) {
    return '';
}

function smarty_block_auth($param, $content, &$smarty, &$repeat) {
  if($repeat) return; // open tag

  if(!AuthUtil::is_login()){
    return '';
  }

  return $content;
}

function compact_css($content, $with_date=true){
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

function smarty_block_css($param, $content, &$smarty) {
 if($repeat) return; // open tag
 if($_GET['_debug']) return $content;
 return compact_css($content);
}

function smarty_optimize_template($tpl_source, $smarty){
  $lines = split("\n",$tpl_source);
  $tlines = array(); 
  $slines = array(); 
  foreach($lines as $line){
    if($in_script){
      $in_script++;
      $slines[] = $line;
      if(preg_match('#</script>\s*$#',$line)){
        $in_script = 0;
      }
    }else if(preg_match('#^\s*<script\b#',$line)){
      $slines[] = $line;
      if(preg_match('#</(script|noscript)>\s*$#',$line)){
        $in_script = 0;
      }else{
        $in_script = 1;
      }
    }else if(preg_match('#^\s*</body>#',$line)){
      $tlines[] = join("\n",$slines);
      $tlines[] = $line;
    }else{
      $tlines[] = $line;
    }
  }// end for
  return join("\n",$tlines);
}  

define('SMARTY_DIR', AppConfig::get('smarty.dir'));
#-- require_once(SMARTY_DIR.'Smarty.class.php');
require(SMARTY_DIR.'Smarty.class.php');

class AppSmarty extends Smarty {
  var $client_max_age = 0;  

  static $inst = null;

  public static function instance(){

    if(self::$inst == null){
      self::$inst = new AppSmarty();
    }
    return self::$inst;
  }


  static function is_cached($tpl,$cache_id){
    $smarty = self::instance();
        
    $rv = $smarty->isCached($tpl,$cache_id);
    return isset($_GET['_nocache'])?0:$rv;
  }
          
  
  static function use_cache($time=600){// seconds
    $smarty = self::instance();
    $smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
    $smarty->cache_lifetime = $time;

    if(isset($_GET['_nocache'])){
      $smarty->compile_check = true;
      $smarty->caching = 1;
      $smarty->cache_lifetime = 0;      
      return 0;
    }         
    return 1;
  }

/*
  function tpl_path($tpl){
    //getTemplateDir()
    //templateExists
    return $this->template_dir.'/'.$tpl;
  }
*/

  public function __construct(){
    parent::__construct();

    $this->setTemplateDir(HTTP_APP.'/page');
    $this->setCompileDir(HTTP_APP.'/cache/__compile__');
    $this->setCacheDir(HTTP_APP.'/cache/__page__');
    $this->addPluginsDir(HTTP_APP.'/lib/smarty/plugins');
    $this->setConfigDir(HTTP_APP.'/page');
    $this->use_sub_dirs  = true; //true; 
    $this->compile_check = Smarty::COMPILECHECK_CACHEMISS; //false; // true; // Smarty::COMPILECHECK_CACHEMISS
    // $this->compile_check = true;
    $this->cache_modified_check = true;
    //$this->caching_type = 'sfile';
    if(1 || isset($_GET['_sqlite'])) $this->caching_type = 'sqlite';
    $this->default_modifiers = array('escape:"html"');

    if(!empty($_GET['_nocache'])){
      $this->compile_check = true;
    }

    if(isset($_GET['theme'])){
      $theme = $_GET['theme'];
      $path = HttpRequest::$root;
      setcookie('theme', $theme, time()+3600*24*7, $path);
      $_COOKIE['theme'] = $theme;
    }else if(isset($_COOKIE['theme'])){
      $theme = $_COOKIE['theme'];
    }

    if(isset($_COOKIE['theme'])){
      list($theme, $style) = explode('/', $_COOKIE['theme']);
      $this->compile_id = $theme;
      if($theme != 'default' && $theme!='') $this->setTemplateDir(HTTP_APP."/theme/$theme");
    }

     // {$var nofilter}
    /*
    $smarty->debugging      = false;
    // shows debug console only on localhost ie
    // http://localhost/script.php?foo=bar&SMARTY_DEBUG
    $smarty->debugging_ctrl = ($_SERVER['SERVER_NAME'] == 'localhost') ? 'URL' : 'NONE';
    */
    
    // TODO:
    //$cfgfile = AppConfig::get('smarty.cfg');
    //if($file = AppConfig::get('smarty.cfg')) $this->configLoad($file);
    //TODO: $this->configLoad(HTTP_APP.'/conf/smarty.cfg');

    $this->registerPlugin("block",'dynamic', 'smarty_block_dynamic', false);
    $this->registerPlugin("block",'auth',    'smarty_block_auth', false, array('cache-inside'));
    $this->registerPlugin("block",'hide',    'smarty_block_hide', false);
    $this->registerPlugin("block",'css',     'smarty_block_css', true);
    //$this->registerFilter('output','smarty_optimize_template');
  }


   //public function fetch($template, $cache_id = null, $compile_id = null, $parent = null, $display = false)
   public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false){
     if (!empty($cache_id) && is_object($cache_id)) {
         $parent = $cache_id;
         $cache_id = null;
     }
     if ($parent === null) {
         // get default Smarty data object
         $parent = $this;
     }
     // create template object if necessary
     ($template instanceof $this->template_class)? $_template = $template :
     $_template = $this->createTemplate ($template, $cache_id, $compile_id, $parent);

     if($this->cache_modified_check && $this->caching && $display && $_template->isCached()) {
       $_gmt_mtime = gmdate('D, d M Y H:i:s T', $_template->getCachedTimestamp());
       //$view_prefix = '"'.AuthUtil::uid().'@';
       $view_prefix = '"'.'0'.'@';
       $etag_prefix = isset($_SERVER['HTTP_IF_NONE_MATCH'])?$_SERVER['HTTP_IF_NONE_MATCH']:'';
       $modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:'';
       if($_gmt_mtime == $modified_since && 
         strncmp($etag_prefix, $view_prefix, strlen($view_prefix))==0
       ){
         // use cache  
         //header('Pragma:'); header('Expires:'); header('Cache-Control:');
         header((php_sapi_name()=='cgi'?'Status:':$_SERVER["SERVER_PROTOCOL"]).' 304 Not Modified');
         return;
       }
     }
     // send_etag($html);

     $html = parent::fetch($template, $cache_id, $compile_id, $parent, $display);

     return $html;
   } // end fetch(...)
} // end class AppSmarty
?>
