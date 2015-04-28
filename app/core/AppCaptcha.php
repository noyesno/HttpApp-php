<?php

# CAPTCHA = Completely Automated Public Turing test
class AppCaptcha {
  var $phrase = null;

  static function get($method='math'){
    $class = 'AppCaptcha_'.ucfirst($method);
    return new $class();
  }
  // TODO: use word 'phrase'


  static function verify($token=null, $input=null, $value=null){
    if(empty($token)) return false; // must have token !!! ???

    if(!is_null($token)){  // empty($token) ???
      AppSession::start();

      if(!array_key_exists($token, $_SESSION['captcha'])) return false;
      $value = $_SESSION['captcha'][$token];
      unset($_SESSION['captcha'][$token]);

      if(is_null($value)) return true;  // token only type
    }

    if(strlen($input)==0) return false;
    return (0===strcasecmp($input, $value)); 
  }

  function next($token=null){
    AppSession::start();

    $parts = array(
      $_SERVER['REMOTE_ADDR'],
      $_SERVER['REMOTE_PORT'],
      $_SERVER['HTTP_USER_AGENT'],
      $_SERVER['REQUEST_TIME'],
      $_SERVER['LOCAL_ADDR'],
      //$_SERVER['LOCAL_PORT'],
      uniqid("captcha", true)
    );
    if(is_null($token)) $token = md5(join('',$parts));
    list($answer, $question) = $this->make();
    $_SESSION['captcha'][$token] = $answer;

    return array('token'=>$token, 'image'=>$question);
    // return array('token'=>$token, 'image'=>$question, 'value'=>$answer);
  }
}

class AppCaptcha_Token extends AppCaptcha {
  function make(){
    $answer   = md5(uniqid('token'));
    $question = $answer;
    return array(null, null);
    //return array($answer, null);
  }
}

class AppCaptcha_Char extends AppCaptcha {

  function make(){
    # A-Z: 41-5a / a-z: 61-7a
    $chars = array();
    for($i=0; $i<4; $i++){
      $chars[] = chr(rand(0x41,0x5a));
    }
    $text = join('',$chars);
    $answer = $text;

    $img_width = 4*14;
    $im = @imagecreate($img_width, 32) or die("Cannot Initialize new GD image stream");
    $bgd_color = imagecolorallocate($im, 0, 0, 0);
    $text_color = imagecolorallocate($im, 233, 233, 91);
    imagestring($im, 5, 8, 8,  $text, $text_color);
    /*
     $angle = 20;
     $angle = rand(-10,+10);
     $im = imagerotate($im, $angle, $bgd_color);
    */
    ob_start();
    imagepng($im);
    $data= ob_get_clean();
    imagedestroy($im);
    $question = 'data:image/png;base64,'.base64_encode($data);
    return array($answer, $question);
  }
}
class AppCaptcha_Math extends AppCaptcha {

  function make(){
    $a = rand(0,50); $b = rand(0,50);
    $op = rand(1,100)>50?'+':'-';
    $v = ($op == '+')?($a+$b):($a-$b);
    $answer = strval($v);
    $choices = array("$a $op $b = ?", "? = $a $op $b", "$op$b + $a = ?");
    $text = $choices[array_rand($choices)];

    $im = @imagecreate(110, 32) or die("Cannot Initialize new GD image stream");
    $bgd_color = imagecolorallocate($im, 0, 0, 0);
    $text_color = imagecolorallocate($im, 233, 233, 91);
    imagestring($im, 4, 6, 8,  $text, $text_color);
    #-- $angle = 20;
    #-- $angle = rand(-10,+10);
    #-- $im = imagerotate($im, $angle, $bgd_color);
    ob_start();
    imagepng($im);
    $data= ob_get_clean();
    imagedestroy($im);
    $question = 'data:image/png;base64,'.base64_encode($data);
    return array($answer, $question);
  }
}
