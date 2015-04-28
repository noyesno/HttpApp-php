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
    if(is_null(self::$inst)){
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

    $tpl_dir = AppConfig::get('smarty.template_dir', HTTP_APP.'/page');
    $this->setTemplateDir($tpl_dir);
    $this->setConfigDir(  AppConfig::get('smarty.config_dir',   $tpl_dir));
    $this->setCompileDir( AppConfig::get('smarty.compile_dir',  HTTP_CACHE.'/__compile__'));
    $this->setCacheDir(   AppConfig::get('smarty.cache_dir',    HTTP_CACHE.'/__page__'));

    $this->addPluginsDir(HTTP_APP.'/lib/smarty/plugins');

    $this->use_sub_dirs  = true; //true; 
    $this->compile_check = Smarty::COMPILECHECK_CACHEMISS; //false; // true; // Smarty::COMPILECHECK_CACHEMISS
    $this->cache_modified_check = true;
    $this->caching_type = AppConfig::get('smarty.caching_type','file');
    $this->default_modifiers = array('escape:"html"');
    $this->cache_locking = true;   // $locking_timeout = 10

    // TODO: for better debug and development control
    if(AppConfig::get('debug')){
      // $this->force_compile = true;
      $this->compile_check = true;
    }

    if(!empty($_GET['_nocache'])){
      $this->compile_check = true;
    }

    $theme_param   = AppConfig::get('theme.param', 'theme');
    $theme_cookie  = AppConfig::get('theme.cookie', 'theme');
    $theme_default = AppConfig::get('theme.default');

    $theme = null;
    if(isset($_GET[$theme_param])){
      $theme = $_GET[$theme_param];
    }else if(!empty($theme_cookie) && !empty($_COOKIE[$theme_cookie])){
      $theme = $_COOKIE[$theme_cookie];
    }else if(!empty($theme_default)){
      $theme = $theme_default;
    }

    if(!empty($theme)){
      list($layout, $style) = explode('/', $theme);
      $this->compile_id = $theme;
      if($layout != 'default' && $layout!=''){
         $this->setTemplateDir(array(
           'theme'=>HTTP_APP."/theme/$layout",
           'base' =>HTTP_APP."/page"
         ));
      }

      AppConfig::set('theme',  $theme);
      AppConfig::set('style',  $style);
      AppConfig::set('layout', $layout);

      if(!empty($theme_cookie)){
        #TODO:# $path = HttpRequest::$root;
        $path = '/';
        setcookie($theme_cookie, $theme, time()+3600*24*7, $path);
        $_COOKIE[$theme_cookie] = $theme;
      }
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
    #-- try {
    #--   $this->configLoad(HTTP_CONF.'/smarty.cfg');
    #-- } catch (Exception $e){
    #--   echo $e;
    #-- }

    $this->registerPlugin("block",'dynamic', 'smarty_block_dynamic', false);
    $this->registerPlugin("block",'auth',    'smarty_block_auth', false, array('cache-inside'));
    $this->registerPlugin("block",'hide',    'smarty_block_hide', false);
    //$this->registerPlugin("block",'css',     'smarty_block_css', true);
    //$this->registerFilter('output','smarty_optimize_template');
  }

   function purge(){
     $this->force_cache = true;
     $this->cache_lifetime = 0;
   }

   //public function fetch($template, $cache_id = null, $compile_id = null, $parent = null, $display = false)
   public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false){

     if($display){
       $this->assign('config', AppConfig::get()); // TODO:
       $this->assign('theme',  AppConfig::get('theme'));  // TODO:
       $this->assign('style',  AppConfig::get('style'));  // TODO:
       $this->assign('layout', AppConfig::get('layout')); // TODO:
     }

     return parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars, $no_output_filter);
   }

   public function fetch_bak($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false){
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


     if($display){
       if($this->cache_modified_check && $this->caching && $_template->isCached()) {
         $cache_mtime = $_template->cached->timestamp;
         $modified_since = HttpRequest::getLastModified();
         # TODO: if emtpy content? check etag?
         if($cache_mtime>0 && $cache_mtime <= $modified_since && !$_template->has_nocache_code){
           HttpResponse::status(304);
           return;
         }
         //$view_prefix = '"'.AuthUtil::uid().'@';
         $view_prefix = '"'.'0'.'@';
         $etag_prefix = isset($_SERVER['HTTP_IF_NONE_MATCH'])?$_SERVER['HTTP_IF_NONE_MATCH']:'';
         $modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:'';
         if($cache_mtime == $modified_since && 
           strncmp($etag_prefix, $view_prefix, strlen($view_prefix))==0
         ){
           // use cache  
           return;
         }
       }
     }
     // send_etag($html);

     $html = parent::fetch($template, $cache_id, $compile_id, $parent, $display);

     return $html;
   } // end fetch(...)

} // end class AppSmarty
