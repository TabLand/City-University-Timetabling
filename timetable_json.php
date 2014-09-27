<?php
require_once("timetable.php");
error_reporting(E_ALL);
ini_set( 'display_errors','1');
header ( 'Content-type: text/javascript' );
$my_timetable = new Timetable ();
if(!isset($_GET["username"])) $_GET["username"] = null; 
$my_timetable->get_timetable($_GET["username"]);

echo json_encode($my_timetable->result);
?>