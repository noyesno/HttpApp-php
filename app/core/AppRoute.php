<?

/*
AppRoute::add('/user/:id', null);
AppRoute::add('/user/{id}', null);
AppRoute::add('/user/(\d+)', null);
*/

class AppRoute {
  static $lut = array();

  static add($pattern, $action=null){
    array_push(self::$lut, array('pattern'=>$pattern, 'action'=>$action)); 
  }  

  static filter($name, $action=null){
  }
}
