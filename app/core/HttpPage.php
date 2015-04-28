<?php

/***********************************************************************
* class: HttpPage
* Steps:
*   onLoad()
*   run()
*   onExit()
***********************************************************************/
class HttpPage {
    //XXX: var $urn;   // URN

    const NOOP                = 100;   // const
    const CACHE_PRE_CHECK     = 101;   // const
    const CACHE_POST_CHECK    = 103;   // const
    const CACHE_NOT_MODIFIED  = 104;   // const
    const CACHE_PURGE         = 105;   // const
    const RENDER              = 200;   // const
    const RENDER_POST_PROCESS = 203;   // const

    var $stage = null;

    var $env;   // App specific data
    var $argc;  // number of URL args
    var $argv;  // URL args
    var $args;  // Page specific args

    var $data   = array();  // Page Specific Data, for display. TORM?
    var $schema = null;     // Route Table, etc
    var $form;

    /************** Init in __get() **********************
     * var $helper;   // Helper Object       
     * var $model;
     * var $view;
     *****************************************************/

    function __construct($env){
      $this->env  = $env;
      $this->args = $env['app']['args'];
      $this->argv = $env['app']['argv'];
      $this->argc = $env['app']['argc'];

    }

    function __get_model(){
      $file_php = HTTP_APP.'/page/'.$this->env['app']['php'];
      $app_name = basename($file_php, '.php');
      $app_model = dirname($file_php).'/'.$app_name.'.model';
      if(file_exists($app_model)){
        $ret = @require($app_model);
        if($ret === 1){
          $this->model = new HttpPageModel($this); // TODO: drop this usage
        }else{
          $this->model = $ret;
        }
      }
      return $this->model;
    }

    function __get($name){
      switch($name){
        case 'helper':
          $this->helper   = new HttpPageHelper($this);
	  return $this->helper;
        case 'view':
          $this->view   = new HttpPageView($this);
	  return $this->view;
        case 'model':
          return $this->__get_model();
          // $this->model = new HttpPageModel($this);
	  // return $this->model;
        default:
	  return null;
      }
    }

    function _dummy_(){
    }

    /* =================== Method ==================== */  


    // Step 1
    function onLoad(){}
    // step 2 
    function render(){
    }
    function display(){
      //echo AppTimer::elapse(); # used much time already
      AppLog::debug('-> page.dispatch');
      AppTimer::mark('page.dispatch');

      if(isset($this->schema['route']))
          $this->helper->dispatch();
      else
          $this->render();

      AppTimer::elapse('page.dispatch');
      AppLog::debug('<- page.dispatch');
      //echo AppTimer::elapse(); # used much time already
      AppLog::debug('-> page.display');
      AppTimer::mark('page.display');

      $this->view->display();

      AppTimer::elapse('page.display');
      AppLog::debug('<- page.display');
    }
    // Step 3. 
    function onUnload(){}



    //================= For ACL =========================/
    function auth(){}

    //================= Other Method =========================/

} // end class HttpPage

/***********************************************************************
* Helper Class for HttpPage
***********************************************************************/

class HttpPageHelper {
  var $page;
  var $message = array();

  function __construct(&$page){
    $this->page   =& $page;
  }             

  //================= Route Method =========================/
  
  function dispatch($method=null, $path=null){
    if(!isset($method)) $method = HttpRequest::method();
    if(!isset($path))   $path   = implode('/',$this->page->argv);
    $path = rtrim('/'.trim($path),'/').'/';  # make path like /page/page2/
    $path = '/'.trim('/'.trim($path),'/');  # make path like /page/page2

    $last_p = null;
    foreach($this->page->schema['route'] as $schema){
      list($m, $p) = $schema;
      if($p==null) $p = $last_p; else $last_p = $p;
      if($m != $method) continue;
      if(!preg_match('#'.$p.'#', $path, $matches)) continue;
      
      $argv = array();
      foreach($matches as $k=>$v){
	if(is_string($k)){
	  //$this->page->args[$k] = $v;
	}else if($k>0){
	  $argv[] = trim($v,'/');
	}
      }
      $this->run($path, $schema, $argv);
      return;
    }

    HttpResponse::status(404);
    $this->page->view->tpl('/404.tpl');
  }

  function run($path, $schema, $argv){
    list($method, $pattern, $action, $tpl, $cache, $acl, $acl_action, $form, $validate_action) 
       = array_pad($schema, 9,  null);
    if(empty($action)) $action = '_dummy_';

    ### STEP 1: ACL Check
    if($acl){    // RBAC: Role Based Access Control
      $this->page->auth($path, $argv);
      if(! AuthUtil::acl($acl)->check()){ // ACL FAIL
        if($acl_action[0] == '/'){
          return $this->dispatch($method, $acl_action);
        }else{
          #TODO: use AUTH_PRE_CHECK
          call_user_func_array(array($this->page, $acl_action),$argv);
        }
        return;
      }
    }
    ### STEP 2: Validate Form
    if($form && isset($this->page->schema['form'][$form]) && $method != 'GET') {
      $validator = new FormValidator($this->page->schema['form'][$form]);
      if($validator->validate()){
        //TORM: HttpRequest::$form = $validator->form;
        $this->page->form = $validator->form;
      }elseif($validate_action){
        // TODO
        return;
      }else{
        // TODO
        // Status: 400 Bad Request
        $this->validator = $validator; // TODO: move validator to other place
        $this->page->view->tpl('400.tpl');
        # print($validator->html_validity());
        return;
      }
    }

    ### STEP 3: Init TPL File
    $this->page->view->tpl($tpl); 

    // TODO: reset: $this->page->view->reset()
    ### STEP 3: Cache Check
    $cache_life = 0; $max_age = 0; 
    $cache_pre_check = 0; $cache_purge = 0; $is_cached = 0;

    ## TODO: instead of _nocache, use _purge here?
    if(isset($cache) && empty($_GET['_nocache'])){
      // TODO: make it more inteligent
      if(is_int($cache)) {
        $cache_life = $cache;
        #XXX: calcuate client side cach from serverside cache
        $max_age    = max(30, min(360,$cache*0.15)); // 30s ~ 6min
      }else if(is_string($cache)){
        $cache_pre_check = (substr($cache,-1)=='|');
        list($cache_life, $max_age, $smax_age) = array_pad(explode('/',trim($cache,'|')), 3, null);
      }else if(is_array($cache)){
        list($cache_life, $max_age, $smax_age) = array_pad($cache,3, null);
      }
    }
    $this->page->view->cache['lifetime'] = $cache_life;

    if($max_age>0){
      //HttpResponse::setCacheControl('public',$max_age,false);
      $this->page->view->cache_control = array('public','max-age'=>$max_age);
    }

    # TODO:
    $page = $this->page;
    if($cache_pre_check && !empty($action)){
      AppLog::debug('-> CACHE_PRE_CHECK');
      $this->page->STATE = $page::CACHE_PRE_CHECK;
      $ret = call_user_func_array(array($this->page, $action),$argv);
      AppLog::debug('<- CACHE_PRE_CHECK');
      switch($ret){
        case $page::CACHE_PURGE :
          $cache_purge = 1;
          $cache_life  = $this->page->view->cache['lifetime']; // purge cache ???  ! always regenerate
          break;
        case $page::CACHE_NOT_MODIFIED :
          $is_cached = 1;
          AppLog::debug('-> CACHE_NOT_MODIFIED');
          HttpResponse::status(304);
          AppLog::debug('<- CACHE_NOT_MODIFIED');
          return;
      } 
    }
    # TODO: post cache check

    ### STEP 4: Init TPL File
    #TODO: skip when CACHE_NOT_MODIFIED
    if($tpl){
      $this->page->view->tpl($tpl); 
      $this->page->view->cache($cache_life);

      #TODO: change to $use_cache?
      $is_cached = (empty($_GET['_nocache']) && ($is_cached || ($cache_life>0 && $this->page->view->isCached())))?1:0;
    }

    ### STEP 5 : Execute
    // HttpResponse::header('X-Date',  gmdate('D, d M Y H:i:s T'));
    HttpResponse::header('X-Debug', "cached=$is_cached, purge=$cache_purge");
    AppLog::debug('X-Debug: ' . "cached=$is_cached, purge=$cache_purge");

    if($is_cached){
      AppLog::debug('use cache');  // X-Debug: Use Cache
      //-- HttpResponse::status(304);
    } else {
      if(!empty($action)){
        AppLog::debug('-> RENDER');
        $this->page->STATE = $page::RENDER;
        call_user_func_array(array($this->page, $action),$argv);
        AppLog::debug('<- RENDER');
      }
    }

    /*
    # TODO: a bug found here, many operations are called twice
    AppLog::debug('-> RENDER_POST_PROCESS');
    $this->page->STATE = $page::RENDER_POST_PROCESS;
    $ret = call_user_func_array(array($this->page, $action),$argv);
    AppLog::debug('<- RENDER_POST_PROCESS');
    */

    return;
  }


  function url(){ /*** variable length args ***/
    // TODO: use app.root if like /def
    $parts = func_get_args();
    $suburl = implode('/',$parts);
    if($suburl[0] == '/'){
      $url = $this->page->env['app']['root'].'/'.trim($suburl,'/');
    }else{
      $url = $this->page->env['app']['base'].'/'.trim($suburl,'/');
    }
    $url = rtrim($url, '/');
    return $url;
  }
} // end class: HttpPageHelper

class HttpPageModel {
  var $page;

  function __construct(&$page){
    $this->page   =& $page;
  }             

  function load($name){
    $dir = dirname(HTTP_APP.'/page/'.$this->page->env['app']['php']);
    foreach(array("$dir/model/$name.php","$dir/$name.model") as $file){
      if(file_exists($file)) require_once($file);
    }
  }
} // end class: HttpPageModel

/*****************************************************************
 * class: HttpPageView
 *
 * Used for template, display, caching, etc
 *****************************************************************/

class HttpPageView {
  var $page;                    // page instance
  var $tpl;

  var $cache = array();
  var $stat  = array();

  var $data = array();
  var $cache_control = null;

  function __construct(&$page){
    $this->page   =& $page;
    // TODO:
    $this->tpl($page->env['app']['tpl']);
  }

  function checkStatTime($mtime){
    $modified_since = HttpRequest::getLastModified();
    if(HttpRequest::checkModifiedSince($mtime)){
      AppLog::debug("-> view.checkStatTime $mtime");
      # Need Purge
      $this->cache['lifetime']       = max(1,min($this->cache['lifetime'], time() - $mtime));
      $this->cache['modified_since'] = $modified_since;
      $this->stat['mtime']           = $mtime;
      AppLog::debug('<- view.checkStatTime '.$this->cache['lifetime']);
      return true;
    }
    return false;
  }

  function assign($name,$value){
    $this->data[$name] = $value;
  }

  function tpl($tpl='.'){
    if(is_null($tpl)){
      $this->tpl = null;
      return $this->tpl;
    }
    if($tpl=='.') {
      return $this->tpl($this->page->env['app']['tpl']);
    }

    if($tpl[0]=='/'){
      $this->tpl = substr($tpl,1);
      return $this->tpl;
    }

    $path = $this->page->env['app']['path'];
    $base = $this->page->env['tpl']['dir'];
    # TODO:
    $smarty = AppSmarty::instance();
    $tpl_dirs = $smarty->getTemplateDir();
    foreach(array($path, dirname($path),'.') as $dir){
      //$file = preg_replace('#^\./#','',$dir.'/'.$tpl);
      $file = ltrim($dir.'/'.$tpl,'./');
      foreach($tpl_dirs as $base){
        #-- echo "--debug $base / $file --\n";
        $base = rtrim($base,'/');
        //AppLog::debug("tpl(): check $base / $file");
        if(file_exists($base.'/'.$file)){
          $this->tpl = "$base/$file"; //$file;

          return $this->tpl;
        }
      }
    }

    #//TODO: print("Tpl [$tpl] not found\n");
    //throw new SystemExit();
    return false; 
  }


  #====================== Cache ======================#
  function cache($time=600, $saved=false){ // seconds, default to 10 minutes
      $smarty = AppSmarty::instance();

      if($time===false){
        $smarty->caching = Smarty::CACHING_OFF;
	return 0;
      }

      if($time===0){
        $smarty->force_cache = true;
      }

      $smarty->cache_lifetime = $time;

      if($saved){
        $smarty->caching        = Smarty::CACHING_LIFETIME_SAVED;
      } else {
        $smarty->caching        = Smarty::CACHING_LIFETIME_CURRENT;
      }

      if(isset($_GET['_nocache'])){
        $smarty->compile_check = true;
        //-- $smarty->cache_lifetime = 5; 
      }
       
      return 1;
  }

  function isCached(){

      $smarty = AppSmarty::instance();
      $tpl = $this->tpl;
      $cache_id = $this->cacheID();

      if(isset($_GET['_nocache'])){
        //-- $smarty->clearCache($tpl,$cache_id);
        return false;
      }

      $rv = $smarty->isCached($tpl, $cache_id);
      return isset($_GET['_nocache'])?0:$rv;
  }

  function cacheID(){
    // $qstring = preg_replace('/\.\w+=\w+&?/','',$this->page->env['request']['qstring']);
    // TODO: make order independent

    $params = array();
    foreach($_GET as $k=>$v){
      if($k[0]=='@' || $k[0]=='_') continue;
      $params[$k] = $v;
    }
    ksort($params);
    $qstring = http_build_query($params);
    $cache_id = strtr($this->page->env['request']['path'],'/','|').'|'.$qstring;
    $cache_id = trim($cache_id,'|?');
    $cache_id = str_replace(array('%7C','%2F','%3A'), array('|','/',':'), urlencode($cache_id));

    return $cache_id;
  }

  function purge($path, $tpl=null){
    $smarty = AppSmarty::instance();

    /*
    if(!isset($tpl)){
      $ptpl = $this->tpl();
      $tpl  = $this->tpl($tpl);
      $this->tpl($ptpl);
    }
    */
    $urn  = $this->page->env['base_urn'].'/'.trim($path, '/');
    $urn  = trim($urn, '/');
    $smarty->clearCache($tpl,$urn);
    //throw new SystemExit();
  }

  function display(){
      if(is_null($this->tpl)) return 1;

      $smarty = AppSmarty::instance();

      if(isset($_GET['_compile'])){
        $smarty->force_compile = true;
      }
      if(isset($_GET['_nocache'])){
        $smarty->compile_check = true;
      }
      if(AppConfig::get('debug')){
        $smarty->compile_check = true;
      }


      #-- $tpl = $this->tpl;
      #-- $tpl_dir  = $this->page->env['tpl']['dir'];
      #-- $tpl_file = $tpl_dir.'/'.$tpl;

      $tpl_file = $tpl = $this->tpl; 

      $cache_id = $this->cacheID();

      $this->page->env['tpl']['tpl']  = $tpl;
      $this->page->env['tpl']['path'] = $tpl_file;

      $smarty->assign("lastModified", filemtime($tpl_dir));
      $smarty->assignByRef("page",$this->page);
      $smarty->assign('env',      $this->page->env);
      $smarty->assign('model',    $this->page->model);
      $smarty->assign('helper',   $this->page->helper);
      $smarty->assign('view',     $this->data);

      /*
      $smarty->assign('argc',     $this->page->argc);
      $smarty->assign('argv',     $this->page->argv);
      $smarty->assign('args',     $this->page->args);
      $smarty->assign('data',     $this->page->data);
      $smarty->assign('form',     $this->page->form);
      $smarty->assign('schema',   $this->page->schema);
       */

      if(!is_file($tpl_file)){
        HttpResponse::status(404);
        throw new SystemExit();
      }




      @require('lib/smarty.helper.php'); // TODO: what is this used for?
      //TORM: echo "<!-- $tpl $cache_id  -->";
      //$smarty->display($tpl, $cache_id);
      //echo " $tpl + $cache_id ";
      if(isset($_GET['_nocache'])) $smarty->clearCache($tpl, $cache_id);

      $smarty->addPluginsDir($this->page->env['app']['dir'].'/plugin');
      if(isset($_GET['_debug'])) echo $tpl,'@',$cache_id;
      //$output = $smarty->fetch($tpl, $cache_id);

      if(!empty($this->cache_control)){
        HttpResponse::setCacheControl($this->cache_control);
      }
      //echo $output;

      AppLog::debug("-> smarty.display $tpl | {$smarty->cache_lifetime} | $cache_id");
      //AppLog::debug($smarty);
      try {
        $smarty->display($tpl, $cache_id);
      } catch (Exception $e){
        AppLog::debug($e);
      }
      AppLog::debug('<- smarty.display');

      //echo "[$tpl][$cache_id]";
      //echo '6:',HttpResponse::getHeader('cache-control');
  }

}// end class

