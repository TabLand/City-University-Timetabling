function populate_calendar(){
	var cal = $("#calendar");
	var append = "";
	for(week=0; week<=5; week++){
		append += '<tr class="week">';
		for(day=0; day<=6; day++){
			append += '<td class="day" id="'+week + "" + day +'">' + day + week + "</td>";
		}
		append += '</tr>';
	}
	cal.append(append);
}
populate_calendar();