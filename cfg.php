<?php
// change this file when you change the environment (mySQL password & ....)
function quoteStr($sql) {
  global $db;
  return "'".$db->real_escape_string($sql)."'";
}
$db_time_fmt='Y-m-d H:i:s';
date_default_timezone_set('Asia/Taipei');
$db=new mysqli('p:127.0.0.1','edge_service','edge','edge_service'); //use localhost for TEST
if($db->connect_errno) die("-CONNECT_DB");
$db->set_charset('utf8');
session_start();
?>