<?php

# CAPTCHA = Completely Automated Public Turing test 
class AppCaptcha {
  static function get($method='math'){
    $class = 'AppCaptcha'.ucfirst($method);
    return new $class();  
  }

  static function token(){
    $parts = array(
      $_SERVER['REMOTE_ADDR'],
      $_SERVER['REMOTE_PORT'],
      $_SERVER['HTTP_USER_AGENT'],
      $_SERVER['REQUEST_TIME'],
      $_SERVER['LOCAL_ADDR'],
      $_SERVER['LOCAL_PORT'],
      uniqid("captcha", true)
    );
    return md5(join('',$parts));
  }
  
  static function verify($token=null, $input=null, $value=null){
    if(strlen($input)==0) return false;

    if(!is_null($token)){  // empty($token) ???
      AppSession::start();

      if(!isset($_SESSION['@captcha'][$token])) return false;
      
      $value = $_SESSION['@captcha'][$token];
      unset($_SESSION['@captcha'][$token]);
    }

    return 0===strcasecmp($input, $value); 
  }

  function next($token=null){
    AppSession::start();

    if(is_null($token)) $token = AppCaptcha::token();
    
    list($answer, $question) = $this->make();
    $_SESSION['@captcha'][$token] = $answer;

    return array('token'=>$token, 'image'=>$question, 'value'=>$answer);
  }
}

class AppCaptchaChar extends AppCaptcha {

  function make(){
    # A-Z: 41-5a / a-z: 61-7a
    $chars = array();
    for($i=0; $i<4; $i++){
      $chars[] = chr(rand(0x41,0x5a));
    }
    $text = join('',$chars);
    $answer = $text;

    $img_width = 4*14;
    $im = @imagecreate($img_width, 20) or die("Cannot Initialize new GD image stream");
    $bgd_color = imagecolorallocate($im, 0, 0, 0);
    $text_color = imagecolorallocate($im, 233, 233, 91);
    imagestring($im, 5, 6, 2,  $text, $text_color);
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

class AppCaptchaMath extends AppCaptcha {

  function make(){
    $a = rand(0,50); $b = rand(0,50);
    $op = rand(1,100)>50?'+':'-';
    $v = ($op == '+')?($a+$b):($a-$b);
    $answer = strval($v);
    $choices = array("$a $op $b = ?", "? = $a $op $b", "$op$b + $a = ?");
    $text = $choices[array_rand($choices)];

    $im = @imagecreate(110, 20) or die("Cannot Initialize new GD image stream");
    $bgd_color = imagecolorallocate($im, 0, 0, 0);
    $text_color = imagecolorallocate($im, 233, 233, 91);
    imagestring($im, 4, 6, 2,  $text, $text_color);
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