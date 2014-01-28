<?

class AppInput {
  function get($name,$value){
    return isset($_REQUEST[$name])?$_REQUEST[$name]:$value;
  }
  function has($name){
    return isset($_REQUEST[$name]);
  }
  function filter(){
  }
}
