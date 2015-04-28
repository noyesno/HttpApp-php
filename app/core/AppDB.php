<?


class AppDB {
  static function get($method='math'){
    $class = 'AppDB_'.ucfirst($method);
    return new $class();
  }
}

class AppDB_PDO {
  var $dbh;
 
  function sql_insert($record){
    $cols = array();
    $vals = array();
    $dbh = $this->dbh;
    foreach($record as $k=>$v){
      if(is_null($v)) continue;
      $cols[] = $k;
      $vals[] = is_null($v)?'NULL':$dbh->quote($v);
    }
    $sql = "INSERT INTO $table (".implode(',',$cols).") VALUES (".implode(',',$vals).")";
  
    return $sql;
  }
}
