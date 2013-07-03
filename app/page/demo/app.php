<?php
 
class Page_Impl extends HttpPage {

  //  $action, $tpl, $cache, $acl, $acl_action, $form, $validate_action
  var $schema = array(
    'route'=> array(
	array('GET', '^/hello(/.*)', 'act_hello'),
	array('GET', '^/env$',      'act_env'),
	array('POST','^/auth$',     'act_view', '.', null, 'authed', 'act_auth'),
	array('GET', '^/cache$',     'act_view_cache', 'cache.tpl',900),
	array('GET', '^/$',          'act_view', '.')
    ),
    'form'=>array(
    )
  );

  function onLoad(){
    
  }

  function act_view_cache(){
    $this->now = time();
  }

  //function auth($path,$param){
  function auth(){
    if($_POST['passcode'] == '9999'){
       HttpRequest::roles('authed');
    }
  }

  function act_view(){
  }
  
  function act_auth(){
    echo 'Auth Fail!';
    $this->view->tpl(null);
  }

  function act_hello($name){
    echo "Hello $name!";
  }
  function act_env(){
      var_export($this->env);
  }


  /*
  function display() {
    echo '0. Hello World!';
    return 1;
  } 
  */


  
}

