<?php

// A simple firewall
class AppWall {

  static function run(){
    if(isset($_POST['captcha']) && strlen($_POST['captcha'])==0) {
      HttpResponse::status(403); // Forbidden   
    }
  }
}
