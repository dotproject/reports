<STYLE>

TABLE.tbl TD.hilite {
	 //background-color: #EAF4FF;
	 background-color: #F4FAFF;
}
</STYLE>

<?php

$start_date = $AppUI->getState( 'start_date_payroll' );
$end_date   = $AppUI->getState( 'end_date_payroll' );

$bill_category = dPgetSysVal( "BillingCategory");
$work_category = dPgetSysVal( "WorkCategory");

if ( $start_date == '' ) {
	$AppUI->setMsg("Please choose a start date", UI_MSG_ERROR);
	$AppUI->redirect("m=reports");
}

if ( $end_date == '' ) {
	$AppUI->setMsg("Please choose an end date", UI_MSG_ERROR);
	$AppUI->redirect("m=reports");
}

$start_date = new CDate( $start_date );
//$start_date = $start_date->format( FMT_DATETIME_MYSQL );
$end_date = new CDate( $end_date );
//$end_date = $end_date->format( FMT_DATETIME_MYSQL );

if ( $AppUI->getState('show_timesheet_payroll') ) {
	echo "<br>\n";
	echo "<table border=\"0\" width=\"75%\" bgcolor=\"#f4efe3\" cellpadding=\"3\" cellspacing=\"1\" class=\"tbl\">";
	echo "<tr>";
	echo "\n\t<th colspan=\"6\">Payroll for " . $start_date->format("%b/%d/%Y") . " - " . $end_date->format("%b/%d/%Y") . "</th>";
	// ->format("%b/%d/%Y")
	echo "\n</tr>";
	
	if ( $AppUI->getState( 'employee_payroll' ) == 0 ) {
		
		$sql = "
		SELECT user_id, user_first_name, user_last_name
		FROM users
		WHERE user_company =  $AppUI->user_company
		order by user_last_name, user_first_name
		";
		
		$result = db_loadList( $sql );
		foreach ($result as $row) {
			traverse_employees($row['user_id'], $row['user_first_name'], $row['user_last_name']);
		}
	
	
	} else {

		$sql = "
		SELECT user_id, user_first_name, user_last_name
		FROM users
		WHERE user_id = " . $AppUI->getState('employee_payroll') ;
		
		db_loadObject($sql, $row);
		
		traverse_employees($row->user_id, $row->user_first_name, $row->user_last_name);
	}
	
	echo "\n</table>\n";
	echo "<p />\n";
}
if ( $AppUI->getState( 'show_project_payroll' ) ) {
	$sql = "
	SELECT project_id, project_name, SUM(task_log_hours) as task_log_hours, task_log_bill_category
	FROM task_log
	LEFT JOIN tasks ON task_id = task_log_task
	LEFT JOIN projects ON project_id = task_project " .
	" WHERE " . ($AppUI->getState('employee_payroll') ? "task_log_creator = " . $AppUI->getState('employee_payroll') . " and " : "") . "task_log_date >= '" . $start_date->format( FMT_DATETIME_MYSQL ) . "' and task_log_date <= '" . $end_date->format( FMT_DATETIME_MYSQL ) . "'
	GROUP BY project_id, task_log_bill_category
	ORDER BY project_name, project_id
	";
	
	$projects = db_loadList($sql);
	
	$total_hours = array();

	echo "\n<table border=\"0\" width=\"75%\" bgcolor=\"#f4efe3\" cellpadding=\"3\" cellspacing=\"1\" class=\"tbl\">";
	echo "\n<tr>";
	echo "\n\t<th>Project</th>";
	
	for ($i = 0; $i < sizeof($bill_category); $i++) {
		$total_hours[$i] = 0;
		echo "\n\t<th>" . $bill_category[$i] . "</th>";
	}
	echo "\n\t<th>Total</th>";
	echo "\n</tr>";
	
	$prev_project_id = 0; // keep track of last project since we need to match up billed and unbilled hours which are in different rows.
	
	for ($i = 0; $i < sizeof($projects); $i++) {
		$project_row = $projects[$i];
		
		if (!$prev_project_id) $prev_project_id = $project_row['project_id'];
		
		if ($prev_project_id == $project_row['project_id']) {
			//echo $total_hours[$project_row['task_log_bill_category']] . " is the total hours for " . $project_row['project_name'] . "<BR />\n";
			$total_hours[$project_row['task_log_bill_category']] = $project_row['task_log_hours'];
		} else {
			$prev_project_row = $projects[$i - 1];
			
			print_project_row( $prev_project_row['project_name'], $total_hours );
			
			$total_hours[$project_row['task_log_bill_category']] = $project_row['task_log_hours'];
			
			$prev_project_id = $project_row['project_id'];
		}
		
		if ( $i == sizeof($projects) - 1) 
			print_project_row( $project_row['project_name'], $total_hours );
	}
	
	echo "\n</table>\n";
	echo "<p />\n";
}

if ( $AppUI->getState( 'show_work_categories_payroll' ) ) {

	$sql = "
		SELECT SUM(task_log_hours) as sum_hours, task_log_work_category, task_log_bill_category 
		FROM task_log 
		LEFT JOIN tasks ON task_id = task_log_task 
		WHERE " . ($AppUI->getState('employee_payroll') ? "task_log_creator = " . $AppUI->getState('employee_payroll') . " and " : "") . "task_log_date >= '" . $start_date->format( FMT_DATETIME_MYSQL ) . "' and task_log_date <= '" . $end_date->format( FMT_DATETIME_MYSQL ) . "'
		GROUP BY task_log_work_category, task_log_bill_category 
		ORDER BY task_log_work_category
		";

	$categories = db_loadList($sql);

	echo "\n<table border=\"0\" width=\"75%\" bgcolor=\"#f4efe3\" cellpadding=\"3\" cellspacing=\"1\" class=\"tbl\">";
	echo "\n<tr>";
	echo "\n\t<th>Work Category</th>";
	
	for ($i = 0; $i < sizeof($bill_category); $i++) {
		echo "\n\t<th>" . $bill_category[$i] . "</th>";
	}
	echo "\n\t<th>Total</th>";
	echo "\n</tr>";
	
	for ( $i=0; $i < sizeof($categories); $i++) {
		$wk_cat_row = $categories[$i];
			
		if ( !isset( $prev_wk_cat ) ) $prev_wk_cat = $wk_cat_row['task_log_work_category'];
		
		if ( $prev_wk_cat == $wk_cat_row['task_log_work_category'] ) {
			$hrs[$wk_cat_row['task_log_bill_category']] = $wk_cat_row['sum_hours'];
		} else {
			
			print_project_row( $work_category[$prev_wk_cat], $hrs );
			
			$hrs[$wk_cat_row['task_log_bill_category']] = $wk_cat_row['sum_hours'];
			
			$prev_wk_cat = $wk_cat_row['task_log_work_category'];
		}
		
		if ( $i == sizeof($categories) - 1) 
			print_project_row( $work_category[$prev_wk_cat], $hrs );
	}

	echo "</table>";
	
}

function print_project_row( $project_name, &$total_hours ) {
	
	global $bill_category;
	
	$total_hours['total'] = 0;
	echo "\n<tr>";
	echo "\n\t<td align=\"center\">$project_name</td>";
	for ($i = 0; $i < sizeof($bill_category); $i++) {
		echo "\n\t<td align=\"center\">". sprintf( "%.2f", $total_hours[$i] ) . "</td>";
		$total_hours['total'] += $total_hours[$i];
		$total_hours[$i] = 0;
	}
	echo "\n\t<td align=\"center\">". sprintf( "%.2f", $total_hours['total'] ) . "</td>";
}

function traverse_employees( $employee_id, $employee_first_name, $employee_last_name ) {

	global $AppUI, $start_date, $end_date, $bill_category, $work_category;
	
	// select timesheets
	$sql = "
	SELECT * 
	FROM timesheet
	WHERE user_id = $employee_id and timesheet_date >= '" . $start_date->format( FMT_DATETIME_MYSQL ) . "' and timesheet_date <= '" . $end_date->format( FMT_DATETIME_MYSQL ) . "'
	ORDER BY timesheet_date
	";
	
	$timesheets = db_loadList($sql);
	list(, $timesheet_row) = each( $timesheets );
	
	// select task logs
	$sql = "
	SELECT task_log.*, tasks.task_name, projects.project_short_name, companies.company_name
	FROM task_log
	LEFT JOIN users ON user_id = task_log_creator
	LEFT JOIN tasks ON task_id = task_log_task
	LEFT JOIN projects ON project_id = task_project
	LEFT JOIN companies ON company_id = project_company
	WHERE task_log_creator = $employee_id and task_log_date >= '" . $start_date->format( FMT_DATETIME_MYSQL ) . "' and task_log_date <= '" . $end_date->format( FMT_DATETIME_MYSQL ) . "'
	ORDER BY task_log_date
	";
	
	$task_logs = db_loadList($sql);
	list(, $task_log_row) = each( $task_logs );
	
	// date stuff
	$start = new CDate($start_date);
	$end = new CDate($end_date);
	$idate = new CDate($start);
	
	$total_days = $end->dateDiff($start);
	
	// define total
	$total_hours = 0;
	$total_minutes = 0;

	echo <<<myOutput
	<tr>
		<th colspan="6" align="center">$employee_first_name $employee_last_name</th>
	</tr>
	<tr>
		<th width="100">Date</th>
		<th width="80">Day of Week</th>
		<th width="50">In</th>
		<th width="50">Out</th>
		<th width="50">Break</th>
		<th width="50">Time</th>
	</tr>
myOutput;
	
	
	for ($day = 0; $day <= $total_days; $day++) {
		echo "\n<tr>";
		echo "\n\t<td align=\"center\" class=\"hilite\">" . $idate->format("%d %B") . "</td>";
		echo "\n\t<td align=\"center\" class=\"hilite\">" . $idate->format("%A") . "</td>";
		//echo $timesheet_row['timesheet_date'] . " =? " . $idate->format("%Y-%m-%d") . "<br />";
		if ($timesheet_row and $timesheet_row['timesheet_date'] == $idate->format("%Y-%m-%d")) {
			$time_in = new CDate('0000-00-00 ' . $timesheet_row['timesheet_time_in']);
			$time_out = new CDate('0000-00-00 ' . $timesheet_row['timesheet_time_out']);
			$time_break = new CDate('0000-00-00 ' . $timesheet_row['timesheet_time_break']);
			
			// output time in, time out, time break
			echo "\n\t<td align=\"center\" class=\"hilite\">" . ((intval($time_in->hour) || intval($time_in->minute) ) ? $time_in->format("%H:%M") : "--" ). "</td>";
			echo "\n\t<td align=\"center\" class=\"hilite\">" . ((intval($time_out->hour) || intval($time_out->minute) ) ? $time_out->format("%H:%M") : "--") . "</td>";
			echo "\n\t<td align=\"center\" class=\"hilite\">" . ((intval($time_break->hour) || intval($time_break->minute) ) ? $time_break->format("%H:%M") : "--") . "</td>";
			
			// output total time
			if ((intval($time_out->hour) or intval($time_out->minute)) and (intval($time_in->hour) or intval($time_in->minute))) {
				$time_in->addSeconds($time_break->second + $time_break->minute * 60 + $time_break->hour * 60 * 60);
				$time_out->subtractSeconds($time_in->second + $time_in->minute * 60 + $time_in->hour * 60 * 60);
				
				echo "\n\t<td align=\"center\" class=\"hilite\"><b>" . $time_out->format("%H:%M") . "</b></td>";
				
				// add to total time for employee
				$total_hours += $time_out->hour;
				$total_minutes += $time_out->minute;
			} else echo "\n\t<td align=\"center\" class=\"hilite\">--</td>";
			echo "\n</tr>";
			
			list(, $timesheet_row) = each($timesheets);
			
		} else {
			echo "\n\t<td align=\"center\" class=\"hilite\">--</td>";
			echo "\n\t<td align=\"center\" class=\"hilite\">--</td>";
			echo "\n\t<td align=\"center\" class=\"hilite\">--</td>";
			echo "\n\t<td align=\"center\" class=\"hilite\">--</td>";
			echo "\n</tr>";
		}
		
		if ($task_log_row and $task_log_row['task_log_date'] == $idate->format("%Y-%m-%d 00:00:00")) {
			
			echo "\n<tr>";
			echo "\n\t<td><br /></td>";
			echo "\n\t<td colspan=\"5\" align=\"center\">";
			echo "\n<table border=\"0\" width=\"100%\" cellpadding=\"3\" cellspacing=\"1\" class=\"std\">";
			echo "\n<tr>";
			echo "\n\t<td style=\"text-align: center; width: 60px;\"><b>Company</b></td>";
			echo "\n\t<td style=\"text-align: center; width: 60px;\"><b>Project</b></td>";
			echo "\n\t<td style=\"text-align: center; width: 60px;\"><b>Task</b></td>";
			//echo "\n\t<td width=\"100\"><b>Task Log Summary</b></td>";
			echo "\n\t<td style=\"text-align: center; width: 20px;\"><b>Hrs</b></td>";
			if ( $AppUI->getState('show_work_category_column') )
				echo "\n\t<td style=\"text-align: center; width: 60px;\"><b>Work Category</b></td>";
			if ( $AppUI->getState('show_billing_category_column') )
				echo "\n\t<td style=\"text-align: center; width: 60px;\"><b>Bill Category</b></td>";
			echo "\n\t<td style=\"text-align: center; width: 200px;\"><b>Comments</b></td>";
			echo "\n</tr>";
		
			while ($task_log_row and $task_log_row['task_log_date'] == $idate->format("%Y-%m-%d 00:00:00")) {
				echo "\n<tr>";
				echo "\n\t<td>" . $task_log_row['company_name'] . "</td>";
				echo "\n\t<td>" . $task_log_row['project_short_name'] . "</td>";
				echo "\n\t<td>" . $task_log_row['task_name'] . "</td>";
				//echo "\n\t<td>" . $task_log_row['task_log_name'] . "</td>";
				echo "\n\t<td>" . sprintf( "%.2f", $task_log_row['task_log_hours'] )  . "</td>";
				if ( $AppUI->getState('show_work_category_column') )
					echo "\n\t<td>" . $work_category[$task_log_row['task_log_work_category']] . "</td>";
				if ( $AppUI->getState('show_billing_category_column') )
					echo "\n\t<td>" . $bill_category[$task_log_row['task_log_bill_category']] . "</td>";
				echo "\n\t<td>" . $task_log_row['task_log_description'] . "</td>";
				echo "\n</tr>";
				
				list(, $task_log_row) = each( $task_logs );
			}
			
			echo "\n</table>";
			echo "\n</td></tr>";
		}
		
		$idate->addSeconds(1 * 24 * 60 * 60);
	}
	
	// wrap time
	$total_hours += floor($total_minutes / 60);
	$total_minutes -= floor($total_minutes / 60) * 60;
	
	echo "\n<tr>";
	echo "\n\t<td align=\"right\"><b>Total:</b></td>";
	echo "\n\t<td colspan=\"4\"><br /></td>";
	echo "\n\t<td align=\"center\"><b>" . $total_hours . ":" . $total_minutes . "</b></td>";
	echo "\n</tr>";
}

?>