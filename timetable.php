<?php 
require_once("simple_html_dom.php");
// fix date warnings in error log
date_default_timezone_set ( 'Europe/London' );
// This class extensively uses C.Marshal's web apps script
class Timetable {
	// success by default
	public $fail = false;
	public $result = array ();
	// TODO: Log all errors
	public $error = "";
	public $html_dom;
	public $day;
	public $html_dump;
	public $modules = array ();
	public $return_dates = array ();
	
	// in seconds
	const one_day = 86400;
	const one_week = 604800;
	public function get_page($url) {
		// ODO - Grab HTTP Errors
		$this->html_dom = file_get_html ( $url );
		$this->html_dump = $this->html_dom->save ();
	}
	public function validate_username() {
		if (strpos ( $this->html_dump, "Can't find student details" ) !== false) {
			$this->fail = true;
			$this->error .= "Invalid username. Could not find student details \n";
		}
	}

	// what modules does the student study?
	public function get_student_info($username) {
		$this->get_page ( "https://webapps.city.ac.uk/sst/vle/index.html?u=" . $username );
	}
	public function parse_student() {
		foreach ( $this->html_dom->find ( "table tbody tr" ) as $table_row ) {
			$this->parse_student_table_row ( $table_row );
		}
	}
	public function parse_student_table_row($table_row) {
		$tds = $table_row->find ( "td" );
		$this->parse_student_tds ( $tds );
	}
	public function parse_student_tds($tds) {
		if (sizeof ( $tds ) >= 3) {
			$module_name = $tds [0]->plaintext;
			$module_code = $tds [1]->plaintext;
			$module_term = $tds [2]->plaintext;
		}
		if (isset ( $module_code )) {
			$this->modules [$module_code] ["name"] = $module_name;
			$this->modules [$module_code] ["term"] = $module_term;
		}
	}
	public function fill_dates($dates) {
		$date_ranges = explode ( ",", $dates );
		
		foreach ( $date_ranges as $date_range ) {
			// for each range, there are three cases
			$from_tos = explode ( "-", $date_range );
			// 1)Either one or more ranges of start date -> end date
			if (sizeof ( $from_tos ) == 2) {
				$this->fill_start_to_end ( $from_tos );
			}			// 2)Only a single date
			elseif (sizeof ( $from_tos ) == 1) {
				$this->no_fill ( $from_tos );
			} 			// 3)Malformed date - return false in this case
			else
				$this->return_dates = false;
		}
		return $this->return_dates;
	}
	public function fill_start_to_end($from_tos) {
		$from = $this->conv_date ( $from_tos [0] );
		$to = $this->conv_date ( $from_tos [1] );
		
		$from = $this->shift_day_of_week ( $from );
		$to = $this->shift_day_of_week ( $to );
		
		while ( $from <= $to ) {
			$this->return_dates [] = $this->pretty_date ( $from );
			$from += $this::one_week;
		}
	}
	public function no_fill($dates) {
		$date = $this->conv_date ( $dates [0] );
		$date = $this->shift_day_of_week ( $date );
		$this->return_dates [] = $this->pretty_date ( $date );
	}
	public function shift_day_of_week($date) {
		// validate day of week
		if ($this->validate_day ( $this->day )) {
			while ( $this->day_of_week ( $date ) != $this->day ) {
				$date += $this::one_day;
			}
		} else {
			$this->fail = true;
			$this->error .= "Made a call to shift_day_of_week(), with a day value of \"$day\"\n";
		}
		return $date;
	}
	public function pretty_date($date) {
		return date ( "m-d-Y", $date );
	}
	public function day_of_week($date) {
		return date ( "l", $date );
	}
	public function conv_date($date) {
		//arrives in format dd/mm/yy
		$split = preg_split("/[\/]/",$date);
		if(sizeof($split)==3){
			//implicitly onvert to numbers
			$split[0] += 0;
			$split[1] += 0;
			$split[2] += 0;
			//hour, minute, second, month, day, year
			return mktime(0,0,0,$split[1], $split[0], $split[2]);
		}
		else return false;
	}
	public function build_module_list() {
		$module_codes = "";
		foreach ( $this->modules as $module_code => $module ) {
			$module_codes .= ":" . $module_code;
		}
		// there is one extra ":" at the start of module codes, chop it off
		$module_codes = substr ( $module_codes, 1 );
		return $module_codes;
	}
	public function get_modules_info() {
		$url = "https://webapps.city.ac.uk/sst/vle/term.html?ms=" . $this->build_module_list ();
		$this->get_page ($url);
	}
	// grab scheduling and location information of modules being studied
	public function parse_modules() {
		foreach ( $this->html_dom->find ( "table tbody tr" ) as $mod_row ) {
			$this->parse_module_table_row ( $mod_row );
		}
	}
	public function parse_module_table_row($table_row) {
		$day_temp = trim ( $table_row->plaintext );
		if ($this->validate_day ( $day_temp ))
			$this->day = $day_temp;
		unset ( $module_code );
		$module_tds = $table_row->find ( "td" );
		$this->parse_module_tds ( $module_tds );
	}
	public function validate_day($day) {
		$days = array("Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday");
		// validate input to make sure a valid week day is given
		return in_array ( $day, $days );
	}
	public function parse_module_tds($module_tds) {
		for($tdpos = 0; $tdpos < sizeof ( $module_tds ); $tdpos ++) {
			switch ($tdpos) {
				case 0 :
					$starttime = $module_tds [$tdpos]->plaintext;
					break;
				case 1 :
					$endtime = $module_tds [$tdpos]->plaintext;
					break;
				case 2 :
					$module_code = $module_tds [$tdpos]->plaintext;
					break;
				case 3 :
					$group = $module_tds [$tdpos]->plaintext;
					break;
				case 4 :
					$location = $module_tds [$tdpos]->plaintext;
					break;
				case 5 :
					$dates = $module_tds [$tdpos]->plaintext;
					break;
			}
		}
		if (isset ( $module_code ))
			$this->add_booking ( $module_code, $group, $starttime, $endtime, $location, $dates );
	}
	public function add_booking($module_code, $group, $starttime, $endtime, $location, $dates) {
		// dates need to be split by comma
		// then all dates need to be matched to $day by incrementing by a maximum of 6
		// then dates need to be seperated by "-", and gaps need to be filled in.
		$booking = array ();
		$booking ["group"] = $group;
		$booking ["starttime"] = $starttime;
		$booking ["endtime"] = $endtime;
		$booking ["location"] = $location;
		$booking ["day"] = $this->day;
		
		$booking ["dates"] = $this->fill_dates ( $dates );
		if ($booking ["dates"] === false)	$this->error .= " Malformed date provided. Dump $dates. \n";
		$this->modules [$module_code] ["timetable"] [] = $booking;
		//clear previous data
		unset($this->return_dates);
	}
	public function get_timetable($username) {
		if ($username !== null) {
			$this->get_student_info ( $username );
			$this->validate_username ();
			if (! $this->fail) {
				$this->parse_student ();
				$this->get_modules_info ();
				$this->parse_modules ();
			}
			$this->result ["status"] = "success";
			$this->result ["error"] = $this->error;
			$this->result ["modules"] = $this->modules;
		} else {
			$this->fail = true;
			$this->error .= "Student username not given\n";
		}
		if ($this->fail) $this->result ["status"] = "failure";
	}
}
?>
