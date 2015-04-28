<?php


// A Router

class AppRouteDelegate {
  var $stop = true; 

  function __construct(){
  }

  public static function make(){
    return new AppRouteDelegate();
  }

  function route($path){
    //if(empty($path)) $path='home';

    # TODO
    $file_lut = array(
      '/page'=>'app'
    ); 

    $app_type = '.app';
    $app_path = '';

    //$app_path=''; $app_type = '';
    $toks = empty($path)?array():explode('/',$path); // TODO: use strlen($path)==0 to avoid '0', 'null'
    foreach($toks as $name){
      $_path = "$app_path/$name";
      if(isset($file_lut[$_path])){
        $app_type = $file_lut[$_path];
      }else if($name != 'app' && file_exists(HTTP_APP."/page$app_path/$name.php")){
        $app_type = '.php';
      }else if(file_exists(HTTP_APP."/page$app_path/$name.html")){
        $app_type = '.html';
      }else if(file_exists(HTTP_APP."/page$app_path/$name/app.php")){
        $app_type = '.app';
      }else if(file_exists(HTTP_APP."/page$app_path/$name/app.ini")){
        // go deep into the directory
      }else{
        break;
      }   
      $app_path .= "/$name";
      if($app_type == 'app'){
        $app_type = '.app';
        break;
      }
    } // end foreach

    $env =& AppRegistry::get('env');

    AppLog::debug("app_path = $app_path , app_type = $app_type");
    $env['app']['dir']     = HTTP_APP."/page$app_path"; 

    $app_path = trim($app_path,'/');
    switch($app_type){
      case '.php':
        $app_php   = "$app_path.php";     $app_tpl   = "$app_path.tpl";
        break;
      case '.html':
        $app_php   = "$app_path.php";     $app_tpl   = "$app_path.html";
        break;
      case '.app':
        $app_php   = "$app_path/app.php"; $app_tpl   = "$app_path/app.tpl";
        break;
      default:
        $app_php   = "404.php"; $app_tpl   = "404.tpl"; $app_type='404';
    }

    $app_php = ltrim($app_php, '/'); $app_tpl = ltrim($app_tpl, '/');

    $app_args = trim(substr($path, strlen($app_path)),'/');
    $app_argv = strlen($app_args)==0?array():explode('/', $app_args);


    /* e.g. :
      urn      = 021/friend/3456.html/edit
      url      = http://example.com/love/021/love/friend/3456.html/edit
      base_urn = 021/friend
      base_url = http://example.com/love/021/love/friend
     */
    $env['app']['type']     = $app_type;
    $env['app']['path']     = $app_path;
    $env['app']['name']     = $app_path;    // TODO: remove this???
    $env['app']['argv']     = $app_argv;
    $env['app']['argc']     = count($app_argv);

/*
    $app_base = rtrim($this->env['request']['root'].'/'.substr(
	$this->env['request']['path'], 0, strlen($this->env['request']['path'])-strlen($app_args)),
	'/');
*/

    $request_url = rtrim($env['request']['url'],'/');
    $env['app']['base']     = rtrim(substr($request_url.'/', 0, -strlen($app_args)-1),'/');
    $env['app']['root']     = rtrim(substr($request_url.'/', 0, -strlen(trim("$app_path/$app_args",'/'))-1),'/');
    //TODO:
    $env['app']['php']      = $app_php;   
    $env['app']['tpl']      = $app_tpl;   

    $node = array(
      'type'=> $app_type,
      'path'=> $app_path,
      'php' => $app_php,
      'tpl' => $app_tpl,
      'argv'=> $app_argv
    );
    //var_export($env);
    $this->deletgate($node);
    return $node;
    //return array($app_type, $app_path, $app_php, $app_tpl, $app_argv);
  }

  function deletgate($node){
      $env =& AppRegistry::get('env');

//var_export($node);
      switch($node['type']){
        case '.html' :
          $app = new HttpPage($env);
          $app->view->cache(3600*24*7);
          break;
        case '.tpl' :
          $app = new HttpPage($env);
          break;
        case '.php'  : case '.app'  :
          $app = $this->load_page($node['php']);
          break;
        case '404':
          $app = new HttpWindPage($env);
          break;
      }

      $app->onLoad(); // TODO: move to page class??
      $app->display();
      $app->onUnload();
  }

  /*****************************************************
   * Page Handling
   ****************************************************/
  function load_page($app_php){
      $env =& AppRegistry::get('env');

      $file_php = HTTP_APP.'/page/'.$app_php;
      #-- require_once($file_php);
      require($file_php);
      $app_name = basename($app_php,'.php');
      $app = null;
      foreach(array('IHttpPage', 'Page_'.ucfirst(strtolower($app_name)), 'Page_App', 'Page_Impl', 'Page_Inst',) as $class_name){
        if(class_exists($class_name, false)){
          $app = new $class_name($env);
          break;
        }
      }
      if(!isset($app)){
        print("Error: can not find page!");
        throw new SystemExit();
        return 0;
      }

      //-- // TODO: move to page constructor();
      //-- $app_model = dirname($file_php).'/'.$app_name.'.model';
      //-- if(file_exists($app_model)){
      //--   $ret = @require_once($app_model);
      //--   if($ret === 1){
      //--     $app->model = new IHttpPageModel(); // TODO: drop this usage
      //--   }else{
      //--     $app->model = $ret;
      //--   }
      //-- }
      return $app;
  }
}

class AppRoutePreroute {
  var $stop = true; 

  function __construct(){
  }

  public static function make(){
    return new AppRoutePreroute();
  }

  function route($path){
    $env =& AppRegistry::get('env');

    $path = $env['request']['path'];

    $toks = explode('/',$path,3);
    if(empty($toks)) return;
    
    // TODO: maybe should remove this
    switch($toks[0]){
      case 's' :
        list($app,$mtime,$file) = $toks;
        $file = HTTP_APP.'/'.$file;
        HttpResponse::sendStaticFile($file);
        throw new SystemExit();
        return 1;
      case '.phpinfo' :
        phpinfo();
        PhpUtil::check_ini_setting();
        throw new SystemExit();
        return 1;
    }
    return 0;
  }
}

class AppRoutePrelude {
  var $stop = true; 

  function __construct(){
  }

  public static function make(){
    return new AppRoutePrelude();
  }

  /* called by prefilter */
  function auth(){ // TODO: rename to function roles() ?????
      // prepare roles for ACL
  }

  function route($path){
    $env    =& AppRegistry::get('env');
    $schema =& AppRegistry::get('schema');

    $path = $env['app']['path'];
    if(empty($schema['route'])) return;

    foreach($schema['route'] as $rule){
      list($pattern, $acl, $acl_action) = array_pad($rule, 3, null);
      if(!preg_match('#'.$pattern.'#', $path, $matches)) continue;
      
      if($acl){
        $this->auth();
        if(!AuthUtil::acl($acl)->check()){ // ACL FAIL
          echo 'auth fail';
          return HttpApp::brake();

          if($acl_action[0]=='/'){ // it's a URL
            $url = $env['root'].$acl_action;
            HttpResponse::redirect($url);
          }else{
            call_user_func($acl_action, $this);
          }
          throw new SystemExit();
          return;
        }            
      }
  
      foreach(array_filter(array_keys($matches), 'is_string') as $k){
	$env['app']['args'][$k] = $matches[$k];
      }
      if(isset($env['app']['args']['path'])){
	 $env['app']['path'] = $env['app']['args']['path'];
      }
      break;
    }// end foreach
  }
}

class AppRouteWatchdog {
  var $stop = true; 

  function __construct(){
  }

  public static function make(){
    return new AppRouteWatchdog();
  }

  function route($path){
  }
}

class AppRouteCaptcha {
  var $stop = true; 

  function __construct(){
  }

  public static function make(){
    return new AppRouteCaptcha();
  }

  function route($path){
    $method = $_SERVER['REQUEST_METHOD'];
    if($method == 'POST'){
      //-- if(AuthUtil::verify_form_hash() <= 0){
      //--   return false;
      //-- }
    }
    return true;
        // print("Auth FAIL!");
        // throw new SystemExit();
  }
}

