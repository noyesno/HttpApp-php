<?php

class DbUtil_mysql {
    static $n_query = 0;

    var $dburl;
    var $dblnk = null;

    var $errno ;
    var $errstr; 

    static function rmeta(){
        return array(
            'dbutil.n_query' => DbUtil::$n_query
        );
    }

    public static function date(){
        return date('Y-m-d');
    }

    public static function datetime(){
        return date('Y-m-d H:i:s');
    }

    public static function decode($url){
      $urlpattern = '|(.*?)://([^:]*?)(:[^:]*?)?@(.*?)(:.*?)?/([^/]*?)$|';
      if (!preg_match($urlpattern, $url, $matches)) {
        return null;
      }
      
      array_shift($matches);  
      $matches[2] = substr($matches[2],1); // $password
      $matches[4] = substr($matches[4],1); // $port
      return $matches;
    }


    
    public function __construct($url) {
        $this->dburl = $url;
    }

    public function __destruct() {
    }      


    private function connect() {
        if(isset($this->dblnk)) return;

        list($driver,
             $username, $password, $host, $port,
             $db_name
	 ) = self::decode($this->dburl);


	$db_charset = 'utf8';
        
	@$dblnk = mysql_connect($host,$username,$password);

        if(!$dblnk){
          header("Status: 500 Internal Server Error");
          print('<div style="text-align:center;color:red">System Internal Error! Please try again later!</div>');
          exit(0); 
        }
	mysql_select_db($db_name,$dblnk) or die("Could not select database");
        if(function_exists('mysql_set_charset')) mysql_set_charset($db_charset,$dblnk);
        else mysql_query("SET NAMES '$db_charset'",$dblnk);  
        mysql_query("SET time_zone =  'Asia/Shanghai'",$dblnk);  

        $this->dblnk = $dblnk;
    }

    function escape($txt) {
      $this->connect();
      $value = mysql_real_escape_string($txt,$this->dblnk);
      return $value;
    }

    function quote($txt) {
      $this->connect();
      $value = mysql_real_escape_string($txt,$this->dblnk);
      if(is_numeric($value)) return $value;
      return "'$value'";
    }

    function sql($sql) {
      $this->connect();
      $params = func_get_args();
      array_shift($params);
      $n_param = count($params);
      if(empty($params)){
        return $sql;
      }else if($n_param==1 && is_array($params[0])){
        return strtr($sql, $params[0]);
      } else { 
        $parts = explode('?', $sql);
        $_sql = array();
        for($i=0, $n=count($parts); $i<$n; $i++) {
          $_sql[] = $parts[$i]; 
          if($i<$n-1) $_sql[] = $this->quote($params[$i]); 
        }
        return implode('', $_sql);
      } 
      return "'$value'";
    }

    function last_id() {
      $this->connect();
      return mysql_insert_id($this->dblnk);
    }

    private function error($sql=null){
      $this->connect();

      $this->errno  = mysql_errno($this->dblnk);
      $this->errstr = mysql_error($this->dblnk);

      $message = 'MySql '.$this->errno.': '.$this->errstr;
      if($sql) $message .= "\n\tsql = $sql\n";
      throw new ErrorException($message);
    }

    public function execute($sql){
      $this->connect();
      $result = @mysql_query($sql,$this->dblnk);
      if($result===false) return $this->error($sql);
      self::$n_query ++;
      return $result;
    }
    
    public function next($result, $type='{}'){ 
        if(is_object($type)){
          $record = @mysql_fetch_assoc($result); 
          if(!$record) return $type;
          foreach ($record as $key => $value){
            $type->$key = $value;
          }
	  return $type;
	}            

	switch($type){
          case 'hash' :
          case '{}' :
              return @mysql_fetch_assoc($result); 
	  case 'array':
          case '[]' :
              return @mysql_fetch_array($result,MYSQL_NUM); 
          case '<>' :
          case '()' :
              return @mysql_fetch_object($result); 
	  default:
              return @mysql_fetch_object($result, $type); 
	}
    }
    


    public function begin() {
      $this->connect();
      if (!@mysql_query('BEGIN', $this->dblnk)){
        return $this->error('BEGIN');
      }
      return;
    }

    public function commit() {
      $this->connect();
      if (!@mysql_query('COMMIT', $this->dblnk)){
          return $this->error('COMMIT');
      }
      return;
    }

    public function rollback() {
      $this->connect();
      @mysql_query('ROLLBACK', $this->dblnk) or $this->error('ROLLBACK');
      return;
    }
                                    
    ###################################################
    # Add by Sean Zhang at 2009-02
    ###################################################


    function build_sql_part($params, $op=' AND ', $schema=null) {
	$parts = array();
	foreach($params as $k=>$v){
	    if(!isset($v)) continue;
            if(preg_match('/^[._]/',$k)) continue;
            if(!preg_match('/^`.+`$/',$v)){
                $v = "'".$this->escape($v)."'";
	    }
            if(preg_match('/^(\w+)\.(\w+)$/',$k,$matches)){
                $tbl = $matches[1]; $col = $matches[2];
                $parts[] = "`$tbl`.`$col`=$v";
            }else{
                $parts[] = "`$k`=$v";
            }
	}

	return  implode($op,$parts);
    }

    function build_sql_pair($params, $op=', ', $schema=null) {
	$cols = array(); $vals = array();
	foreach($params as $k=>$v){
	    if(!isset($v)) continue;
            if(preg_match('/^[._]/',$k)) continue;
            if(!preg_match('/^`.+`$/',$v)){
                $v = "'".$this->escape($v)."'";
	    }
            $cols[] = "`$k`";
            $vals[] = $v;
	}

	return  array(implode($op,$cols), implode($op,$vals));
    }

    function build_sql_in($values) {
	foreach($values as $v){
	  $v = "'".$this->escape($v)."'";
	  $parts[] = $v ;
	}

	return  implode(',',$parts);
    }

    function build_sql_insert($tbl, $params, $op=', ', $schema=null) {
	$cols = array(); $vals = array();
	foreach($params as $k=>$v){
	    if(!isset($v)) continue;
            if(preg_match('/^[._]/',$k)) continue;
            if(!preg_match('/^`.+`$/',$v)){
                $v = "'".$this->escape($v)."'";
	    }
            $cols[] = "`$k`";
            $vals[] = $v;
	}

        $sql = "INSERT INTO $tbl (".implode($op,$cols).") VALUES (".implode($op,$vals).")";
        return $sql;
    }

    //----------------------------------------------------------------//
    // Operation: by_id                                               //
    //----------------------------------------------------------------//
    function select_by_id($tbl, $cols='*', $value=null, $id='id', $limit=1){
      $where = 1;
      if(isset($value)) $where="$id='$value'";
      $sql = "SELECT $cols FROM $tbl WHERE $where LIMIT $limit";
      $result = $this->execute($sql) or $this->error($sql);
      $row  = $this->next($result,'{}');
      return $row;
    }

    function update_by_id($tbl, $cols, $value, $id='id'){
        if(isset($cols[$id])) unset($cols[$id]);
    
            
        $sql_set = $this->build_sql_part($cols,$op=', ');
        // TODO
	if(is_array($value)){
	  $value = $this->build_sql_in($value);
          $sql = "UPDATE $tbl SET $sql_set WHERE $id IN ($value)";
	}else{
	  $value = $this->escape($value);
          $sql = "UPDATE $tbl SET $sql_set WHERE $id='$value'";
	}
            
        $result = $this->execute($sql);
        return ($result?1:0);
    }


    function delete_by_id($tbl, $value, $id='id'){
	if(!isset($value)) return 0;
	$value = $this->escape($value);
        $sql = "DELETE FROM $tbl WHERE $id='$value'";
        $result = $this->execute($sql);
        return ($result?1:0);
    }

    //----------------------------------------------------------------//
    // Operation                                                      //
    //----------------------------------------------------------------//

    // SQL: INSERT 
    function insert($tbl,$cols) {
      $sql = $this->build_sql_insert($tbl, $cols);
      try{
        $this->execute($sql);
      }catch(Exception $e){
        if($this->errno==1062){ // Duplicate Record
          return false;
        }
        throw $e;
      }
      return $this->last_id();
    }

    function update($tbl, $cols, $where){
      $sql_set = $this->build_sql_part($cols,$op=', ');
      $sql = "UPDATE $tbl SET $sql_set WHERE $where";     
      $result = $this->execute($sql);
      return mysql_affected_rows($this->dblnk);
    }

    function delete($tbl, $where){
      $sql = "DELETE FROM $tbl WHERE $where";     
      $result = $this->execute($sql);
      return mysql_affected_rows($this->dblnk);
    }

    //----------------------------------------------------------------//
    // Operation                                                      //
    //----------------------------------------------------------------//
    // SQL: SELECT
    function select_many($sql, $type='{}',$key=null){
        $result = $this->execute($sql) or $this->error($sql);
        $recordset = array();
        //$key = trim($type,'[]{}<>()');
        switch($type[0]){
          case '[' : $type = '[]'; break;
          case '{' : $type = '{}'; break;
          case '<' : $type = '<>'; break;
          case '(' : $type = '()'; break;
          default:   $type = $type;
        }
        while ($row = $this->next($result,$type)) {
            if(!empty($key)){
                $idx = is_object($row)?($row->$key):$row[$key];
                $recordset[$idx] = $row;
            } else {
                $recordset[] = $row;
            }
        }
        mysql_free_result($result);
        return $recordset;
    }

    function select_one($sql, $type='{}', $force_uniq=false){
        $result = $this->execute($sql) or $this->error($sql);
        $recordset = array();
        $row  = $this->next($result,$type);
        if($force_uniq && mysql_num_rows($result)>1) {
          return null;
        }
        return $row;
    }

} // end class
