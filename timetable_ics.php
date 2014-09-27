<?php
require_once("timetable.php");
error_reporting(E_ALL);
ini_set( 'display_errors','1');

$my_timetable = new Timetable ();
if(!isset($_GET["username"])) $_GET["username"] = null; 
$my_timetable->get_timetable($_GET["username"]);
header ( 'Content-type: text/calendar' );

if($_GET["username"]===null) $_GET["username"] = "invalid_username";
header("Content-Disposition: attachment; filename=". $_GET["username"] . ".ics");

$ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:timetabling.ijtaba.me.uk\r\n";
foreach($my_timetable->modules as $module){
	$module_name = $module["name"];
	foreach($module["timetable"] as $timeslot){
		$group = $timeslot["group"];
		$location = $timeslot["location"];
		$starttime = $timeslot["starttime"];
		$endtime = $timeslot["endtime"];
		$dates = $timeslot["dates"];
		foreach($dates as $date){
			$ical .= "BEGIN:VEVENT\r\n"; 
			$ical .= "DTSTAMP:" . icalendar_date() . "\r\n";
			$ical .= "DTSTART:" . icalendar_date_helper($date, $starttime) . "\r\n";
			$ical .= "DTEND:" . icalendar_date_helper($date, $endtime) . "\r\n";
			$ical .= "SUMMARY:$module_name - $group - $location \r\n";
			$ical .= "END:VEVENT\r\n";
		}
	}
}
$ical .= "END:VCALENDAR";

echo $ical;
function icalendar_date($timestamp = null){
	if($timestamp===null) $timestamp = time();
	$ret_date = date("Ymd:His",$timestamp);
	$ret_date = preg_replace("/[:]/", "T", $ret_date);
	return $ret_date;
}
function icalendar_date_helper($date, $time){
	//mm-dd-yy
	$split_date = preg_split("/[-]/",$date);
	$split_time = preg_split("/[:]/",$time);
	$month = $split_date[0];
	$day = $split_date[1];
	$year = $split_date[2];
	$hour = $split_time[0];
	$minute =$split_time[1];
	$stamp = mktime($hour, $minute, 0, $month, $day, $year);
	return icalendar_date($stamp);
}

?>
