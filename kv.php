<?php 
/**
@class kvstore key/value wrapper for php/pdo
@author teodor kulej <teodor.kulej@gmail.com>
@example http://rashell.pl/kv_example.php

based on:
@link https://github.com/ntemple/mysql-keyvalue
*/
class kvstore {

  /** @var pdo database handle */
  private $db;
  private $table = 'table-0';
  private $locking = false;
  private $locked = false;
  
  /** Create database handle
  * @param PDO $pdo handle
  * @param string $table table name, optional
  */
  public function __construct($PDO, $table = 'table-0') {
    $this->db = $PDO;
    $this->table = $table;
  }
  
  /** On errors, throw exception
  */
  public function errorWarning(){
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
  }
  
  /** On errors, throw exception
  *
  */
  public function errorExceptions(){
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  
  /** On errors, do nothing
  *
  */
  public function errorSilent(){
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
  }
  
  /** Delete everything from table
  * @param string $table = null, if null uses default
  */
  public function deleteAllFromTable($table=null){
    if($table)
      $t=$table;
    else {
        $t = $this->table;
    }
    $this->db->exec("delete from $t");
  }
  
  /** Drop table
  * @param string $table = null, if null uses default
  */
  public function dropTable($table=null){
    if($table)
      $t=$table;
    else {
        $t = $this->table;
    }
    $this->db->exec("drop table if exists `$t`");
  }
  
  
  /** Create table usable with this class
  * @param string $table = null, if null uses default
  */
  public function createTable($table=null) {
    if($table)
      $t=$table;
    else {
        $t = $this->table;
    }
    $this->db->exec("CREATE TABLE IF NOT EXISTS `$t` ( `k` varchar(255) NOT NULL, `v` longblob NOT NULL, PRIMARY KEY (`k`) ) DEFAULT CHARSET=utf8");  
  }

  /** Sets default table name
  * @param string $table table name to set as default
  */
  public function setTable($table){
    $this->table=$table;
  }
  
  private function _lock() {
    if ($this->locked) return;
    $this->locked = true;

    if ($this->locking) {
      $this->db->exec("LOCK TABLES `{$this->table}` WRITE");    
    }
  }

  private function _unlock() {   
    if (! $this->locked) return;
    $this->locked = false;

    if ($this->locking) $this->db->exec("UNLOCK TABLES");        
  }

  /** Sets $k = $v
  * @param string $k key
  * @param string $v value
  */
  public function set($k, $v) {
    $sql="REPLACE INTO `{$this->table}` (k,v) values(:k,:v)";
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':k', $k);
    $stmt->bindParam(':v', $v);
    $stmt->execute();
  }

  /** Retrive value for given key
  *
  */
  public function get($k) {
  $stmt = $this->db->prepare("SELECT v from `{$this->table}` where k=:k");
    $stmt->execute(array(':k'=> $k));
  $result = $stmt->fetchAll();
    return $result[0][0];
  }
  
  /** Find values like key
  *
  */
  function getLike($k) {
  $stmt = $this->db->prepare("SELECT * from `{$this->table}` where k like :k");
    $stmt->execute(array(':k'=> $k));
  $result = $stmt->fetchAll();
  $r=array();
  foreach($result as $row)
    $r[$row[0]]=$row[1];
  return $r;
  }
  
  /** Return decoded json object for given key
  *
  */
  function getJSON($k){
    return json_decode($this->get($k));
  }

  /** Set array $mkv of kv pairs
  *
  */
  function mset($mkv) {
    $this->_lock();
    foreach ($mkv as $k => $v) {
      $this->set($k, $v);
    }
    $this->_unlock();    
  }

  /** Return array for given $mk array of keys as: [[k=>v],[k=>v]]
  *
  */
  public function mget($mk) {
    $results = array();
    foreach ($mk as $k) {
      $results[$k] = $this->get($k);
    }
    return $results;
  }


  
  /** Get assoc arrray like [[k,v],[k,v]] for given array of keys
  * @param array mk of keys to retrive
  */
  public function mget_assoc($mk) {
    $in =  implode("','", $mk);
    $stmt = $this->db->prepare("select k,v from `{$this->table}` where k in ('$in')");
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $result;
  }

  /** Increment $k by $x
  *
  */
  public function incrby($k, $x) {
    $this->_lock();
    $v = $this->get($k);
    $v += $x;
    $this->set($k, $v);
    $this->_unlock();
    return $v;
  }

  /** Find keys like pattern
  *
  */
  function keys($pattern) {
    $stmt = $this->db->prepare("SELECT k from `{$this->table}` where k like :pattern ");
    $stmt->execute(array(':pattern'=>$pattern));
    $result = $stmt->fetchAll();
    return $result[0];  
  }

  /** Increment $k
  *
  */
  public function incr($k) {
    return $this->incrby($k, 1);    
  }

  
  /** Decrement $k
  *
  */
  public function decr($k) {
    return $this->incrby($k, -1);    
  }

  /** Decrement $k by $x
  *
  */
  public function decrby($k, $x) {
    $this->incrby($k, $x * -1);
  }

  /** Check if given key exists
  *
  */
  public function exists($k) {
    return $this->getValue("SELECT count(*) from `{$this->table}` where k=:k", $k,':k'); // should return 1 or 0    
  }

  
  /** Delete given key from db
  *
  */
  public function del($k) {
   $stmt = $this->db->prepare("DELETE from `{$this->table}` where k=:k");
   $stmt->bindParam(':k',$k);
   return     $stmt->execute(); 
   //shall return 1 or 0
  }

  /** Set a key to a string returning the old value of the key 
  *
  */
  public function getset($k, $v) {
    $this->_lock();
    $old_v = $this->get($k);
    $this->set($k, $v);
    $this->_unlock();
    return $v;
  }

  public function setnx($k, $v) {
    $success = false;
    $this->_lock();
    if ($this->exists($k)) {
      $success = false;
    } else {
      $this->set($k, $v);
      $success = true;
    }
    $this->_unlock();
    return $success;
  }

  
  /**
  * appends $v to existing $k as list
  * 
  * @param string $k key
  * @param string $v value to append
  */
  public function append($k, $v)  {
    $this->_lock();
    $cv = $this->get($v);
    $v = $cv . $v;
    $this->set($v);
    $this->unlock();
  }

  private function _lget($k) {
    $cv = $this->get($k);    
    if ($cv) {
      $a = json_decode($cv);
    } else {
      $a = array();
    }
    return $a;
  }

  private function _lset($k, $a) {
    $this->set($k, json_encode($a));
  }

  public function rpush($k, $v) {
    $this->_lock();
    $a = $this->_lget($k);
    array_push($a, $v);
    $this->_lset($k, $a);
    $this->_unlock();    
  }

  public function lpush($k, $v) {
    $this->_lock();
    $a = $this->_lget($k);
    array_unshift($a, $v);
    $this->_lset($k, $a);
    $this->_unlock();    
  }

  public function rpop($k) {
    $this->_lock();
    $a = $this->_lget($k);
    if (count(a) > 0) {
      $v = array_pop($a);
    } else {
      $v = null;
    }
    $this->_lset($k, $a);
    $this->_unlock();    
    return $v;    
  }

  public function lpop($k) {
    $this->_lock();
    $a = $this->_lget($k);
    if (count(a) > 0) {
      $v = array_unshift($a);
    } else {
      $v = null;
    }
    $this->_lset($k, $a);
    $this->_unlock();    

    return $v;        
  }
  
  /**
  * Gets single result of query with max 2 parameters
  * @param $query string
  * @param $a string parameter
  * @param $b  string second parameter or pattern for $a
  * @param $patternA string pattern for matching $a
  * @param $patternB string pattern for matching $b 
  */
  public function getValue($query, $a=null,$b=null,$patternA=null, $patternB=null){
    $stmt = $this->db->prepare($query);
    
    if($a&&$b && $patternA && $patternB){
      $stmt->execute(array(
	$patternA=>$a,
	$patternB=>$b
      ));
    }else if($a && ($b||$patternA)){
      if($b){
	$stmt->execute(array($b=>$a));
      }else {
        $stmt->execute(array($patternA=>$a));
      }
    }else{
      $stmt->execute();
    }
    $result = $stmt->fetchAll();
    return $result[0];
  }
  
  /**
  * Gets all results of query with max 2 parameters
  * @param $query string
  * @param $a string parameter
  * @param $b  string second parameter or pattern for $a
  * @param $patternA string pattern for matching $a
  * @param $patternB string pattern for matching $b 
  */
  public function getAllValues($query, $a=null,$b=null,$patternA=null, $patternB=null){
    $stmt = $this->db->prepare($query);
    
    if($a&&$b && $patternA && $patternB){
      $stmt->execute(array(
	$patternA=>$a,
	$patternB=>$b
      ));
    }else if($a && ($b||$patternA)){
      if($b){
	$stmt->execute(array($b=>$a));
      }else {
        $stmt->execute(array($patternA=>$a));
      }
    }else{
      $stmt->execute();
    }
    $result = $stmt->fetchAll();
    return $result;
  }
  
} 
