<?php

# CAPTCHA = Completely Automated Public Turing test 
class AppCaptcha {
  static function get($method='math'){
    $class = 'AppCaptcha'.ucfirst($method);
    return new $class();  
  }
  static function verify($actual, $expect){
    $ok = (strlen($actual) && $actual===$expect);
    return $ok;
  }
}

class AppCaptchaMath {

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


