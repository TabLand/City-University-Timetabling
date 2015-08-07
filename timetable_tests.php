<?php
require_once(dirname(__FILE__) . '/simpletest/autorun.php');
require_once("timetable.php");

class TestOfTimetabling extends UnitTestCase {
	private $timetable;
	function setUp(){
		$this->timetable = new Timetable;
	}
	function tearDown(){
		$this->timetable = null;
	}
    function testDateConversions() {
    	$return = $this->timetable->conv_date("10/11/12");
    	$this->assertEqual($return,1352505600);
    	$return = $this->timetable->conv_date("2/2/14");
    	$this->assertEqual($return,1391299200);
    }
    function testFillDates(){
    	$this->timetable->day = "Wednesday";
    	$this->timetable->fill_dates("1/2/14-28/2/2014");
    	$my_dates = array("05-Feb-2014","12-Feb-2014","19-Feb-2014","26-Feb-2014","05-Mar-2014");
    	$this->assertIdentical($this->timetable->return_dates, $my_dates);
    }
}
?>