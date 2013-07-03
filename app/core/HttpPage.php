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

    function __get($name){
      switch($name){
        case 'helper':
          $this->helper   = new HttpPageHelper($this);
	  return $this->helper;
        case 'view':
          $this->view   = new HttpPageView($this);
	  return $this->view;
        case 'model':
          $this->model = new HttpPageModel($this);
	  return $this->model;
        default:
	  return null;
      }
    }

    /* =================== Method ==================== */  


    // Step 1
    function onLoad(){}
    // step 2 
    function render(){
    }
    function display(){
      if(isset($this->schema['route']))
          $this->helper->dispatch();
      else
          $this->render();

      $this->view->display();
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

    ### STEP 1: ACL Check
    if($acl){    // RBAC: Role Based Access Control
      $this->page->auth($path, $argv);
      if(! AuthUtil::acl($acl)->check()){ // ACL FAIL
        if($acl_action[0] == '/'){
          return $this->dispatch($method, $acl_action);
        }else{
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
    ### STEP 4: Cache Check
    if($tpl && isset($cache) && !isset($_GET['_nocache'])){
      // TODO: make it more inteligent
      if(is_numeric($cache)) {
        $cache_life = $cache;
        $max_age    = max(10,min(300,$cache*0.1)); // 10s ~ 5min
      }else if(is_string($cache)){
        list($cache_life, $max_age, $smax_age) = array_pad(implode('/',$cache),null,3);
      }
      if($max_age>0){
        //HttpResponse::setCacheControl('public',$max_age,false);
        $this->page->view->cache_control = array( 'public','max-age'=>$max_age);
      }
      if($cache_life > 0){
        $this->page->view->cache($cache_life);
        if($this->page->view->isCached()) return;
      }
    }
    ### STEP 5 : Execute
    if(!empty($action)){
      call_user_func_array(array($this->page, $action),$argv);
    }
    return;
  }


  function url(){ /*** variable length args ***/
    $base = $this->page->env['app']['base'];
    $parts = func_get_args();
    $path = trim(implode('/',$parts),'/');
    $url = rtrim($base.'/'.$path, '/');
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
  var $data = array();
  var $cache_control = null;

  function __construct(&$page){
    $this->page   =& $page;
    // TODO:
    $this->tpl($page->env['app']['tpl']);
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
    foreach(array($path, dirname($path),'.') as $dir){
      //$file = preg_replace('#^\./#','',$dir.'/'.$tpl);
      $file = ltrim($dir.'/'.$tpl,'./');
      if(file_exists($base.'/'.$file)){
        $this->tpl = $file;
        return $this->tpl;
      }
    }

    print("Tpl [$tpl] not found\n");
    throw new SystemExit();
  }


  #====================== Cache ======================#
  function cache($time=600){ // seconds, default to 10 minutes
      $smarty = AppSmarty::instance();

      if($time<=0){
        $smarty->caching = Smarty::CACHING_OFF;
	return 0;
      }

      $smarty->caching        = Smarty::CACHING_LIFETIME_CURRENT;
      $smarty->caching        = Smarty::CACHING_LIFETIME_SAVED;
      $smarty->cache_lifetime = $time;

      if(isset($_GET['_nocache'])){
        $smarty->compile_check = true;
        $smarty->cache_lifetime = 5; 
      }
       
      return 1;
  }

  function isCached(){

      $smarty = AppSmarty::instance();
      $tpl = $this->tpl;
      $cache_id = $this->cacheID();

      if(isset($_GET['_nocache'])){
        $smarty->clearCache($tpl,$cache_id);
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
    $cache_id = str_replace(array('%2F','%3A'), array('/',':'), urlencode($cache_id));

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
      if(AppCfg::get('debug')){
        $smarty->compile_check = true;
      }


      $tpl = $this->tpl;
      $cache_id = $this->cacheID();
      $tpl_dir  = $this->page->env['tpl']['dir'];
      $tpl_file = $tpl_dir.'/'.$tpl;

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




      //@require('lib/smarty.helper.php'); // TODO: what is this used for?
      //TORM: echo "<!-- $tpl $cache_id  -->";
      //$smarty->display($tpl, $cache_id);
      //echo " $tpl + $cache_id ";
      //if(isset($_GET['_nocache'])) $smarty->clearCache($tpl, $cache_id);

      $smarty->addPluginsDir($this->page->env['app']['dir'].'/plugin');
      if(isset($_GET['_debug'])) echo $tpl,'@',$cache_id;
      $output = $smarty->fetch($tpl, $cache_id);
      if(!empty($this->cache_control)){
        HttpResponse::setCacheControl($this->cache_control);
      }
      echo $output;
      //echo "[$tpl][$cache_id]";
      //echo '6:',HttpResponse::getHeader('cache-control');
  }

}// end class


