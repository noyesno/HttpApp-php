<?php

require('vendor/PasswordHash.php');

class AppPasswd {
  static function hash($password, &$error=null) {
    $hasher = new PasswordHash(8, false);

    // Passwords should never be longer than 72 characters to prevent DoS attacks
    if (strlen($password) > 72) { 
      $error = "Password must be 72 characters or less"; 
      return false;
    }

    // The $hash variable will contain the hash of the password
    $hash = $hasher->HashPassword($password);


    if (strlen($hash) >= 20) {
      return $hash;
    } else {
      // something went wrong
      $error = 'something went wrong';
      return false;
    }
  }


  static function check($password, $stored_hash){
    $hasher = new PasswordHash(8, false);

    if (strlen($password) > 72) { 
      $error = "Password must be 72 characters or less"; 
      return false;
    }

    // Check that the password is correct, returns a boolean
    $check = $hasher->CheckPassword($password, $stored_hash);

    return $check;
  }
}
