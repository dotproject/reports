<?php 
require_once dPgetConfig( 'root_dir' ).'/modules/ticketsmith/common.inc.php';


// may need so more thought as to which company to pull.  (maybe just search for 'internal' type companies)

// companies
$sql = "
SELECT company_id, company_name
FROM companies
ORDER BY company_name
";
$company_res = db_exec( $sql );

// projects
$sql = "
SELECt project_id, project_name, project_company
FROM projects
ORDER BY project_name
";
$project_res = db_exec( $sql );


// tasks
$sql = "
SELECT task_id, task_name, task_project
FROM tasks
ORDER by task_name
";
$task_res = db_exec( $sql );

// departments
$sql = "
SELECT dept_id, dept_name, dept_company
FROM departments
WHERE dept_company = $AppUI->user_company
ORDER by dept_name
";
$department_res = db_exec( $sql );

// employees
$sql = "
SELECT user_id, user_username, user_first_name, user_last_name, user_department
FROM users
WHERE user_company = $AppUI->user_company
ORDER by user_last_name, user_first_name
";

$employee_res = db_exec( $sql );

//$tasks = array( '0' => "[0, 0, 'All']");
$tasks = array();
//$project = array( '0' => "[0, 0, 'All']");
$project = array();
$companies = array( '0' => "All");
$departments = array( '0' => "All");
//$employees = array( '0' => "[0, 0, 'All']" );
$employees = array();

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

while ($row = db_fetch_assoc( $company_res ) ) {
	// collect companies in normal format
	//$companies[] = "[{$row['company_id']}, '{$row['company_name']}']";
	$companies[$row['company_id']] = escape_string($row['company_name']);
}

while ($row = db_fetch_assoc( $project_res ) ) { 
	// collect projects in js format
	$projects[] = "[{$row['project_company']},{$row['project_id']},'" . escape_string($row['project_name']) . "']";
}

while ($row = db_fetch_assoc( $task_res ) ) {
	// collect tasks in js format
	$tasks[] = "[{$row['task_project']},{$row['task_id']},'" . escape_string($row['task_name']) . "']";
}

while ($row = db_fetch_assoc( $department_res ) ) {
	// collect departments in normal format
	//$departments[] = "[{$row['dept_id']},'{$row['dept_name']}']";
	$departments[$row['dept_id']] = escape_string($row['dept_name']);
}

while ($row = db_fetch_assoc( $employee_res ) ) {
	// collect employees in js format
	$employees[] = "[{$row['user_department']},{$row['user_id']},'{$row['user_first_name']} {$row['user_last_name']}']";
}


//$crumbs = array();
//$crumbs["?m=timetrack&timesheet_id=$timesheet_id"] = "timesheets list";
//$crumbs["?m=timetrack&a=view&timesheet_id=$timesheet_id"] = "view this timesheet";

##
## Set up JavaScript arrays
##
$ua = $_SERVER['HTTP_USER_AGENT'];
$isMoz = strpos( $ua, 'Gecko' ) !== false;

$s = "\nvar tasks = new Array(".implode( ",\n", $tasks ).")";
$s .= "\nvar projects = new Array(".implode( ",\n", $projects ).")";
$s .= "\nvar employees = new Array(".implode( ",\n", $employees ).")";

echo "<script language=\"javascript\">$s</script>";


$sql = "
SELECT user_id, user_username, user_first_name, user_last_name
FROM users
WHERE user_company = $AppUI->user_company
ORDER by user_last_name, user_first_name
";
$result = db_loadList($sql);

?>

<script language="javascript">
function setColor(color) {
	var f = document.editFrm;
	if (color) {
		f.project_color_identifier.value = color;
	}
	test.style.background = f.project_color_identifier.value;
}

function setShort() {
	var f = document.editFrm;
	var x = 10;
	if (f.project_name.value.length < 11) {
		x = f.project_name.value.length;
	}
	if (f.project_short_name.value.length == 0) {
		f.project_short_name.value = f.project_name.value.substr(0,x);
	}
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// List Handling Functions
function emptyList( list ) {
<?php if ($isMoz) { ?>
	list.options.length = 0;
<?php } else { ?>
	while( list.options.length > 0 ) {
		//list.options.remove(0); CHANGED -- doesn't work with Konqueror
		list.options[0] = null;
	}
<?php } ?>
}

function addToList( list, text, value ) {
	//alert( list+','+text+','+value );
<?php if ($isMoz) { ?>
	list.options[list.options.length] = new Option(text, value);
<?php } else { ?>
	var newOption = document.createElement("OPTION");
	newOption.text = text;
	newOption.value = value;
	list.add( newOption, list.options.length );
<?php } ?>
}

function changeList( listName, source, target ) {
//	alert(listName+'  -,-  '+source+'  -,-  '+target);return;
	var f = document.reportForm;
	var list = eval( 'f.'+listName );

// clear the options

	emptyList( list );
	
// refill the list based on the target
// add a blank first to force a change
	addToList( list, 'All', '0' );

	for (var i=0, n = source.length; i < n; i++) {
		if( source[i][0] == target ) {
			addToList( list, source[i][2], source[i][1] );
		}
	}
}

// select an item in the list by target value
function selectList( listName, target ) {
	var f = document.reportForm;
	var list = eval( 'f.'+listName );

	for (var i=0, n = list.options.length; i < n; i++) {
//alert(listName+','+target+','+list.options[i].value);
		if( list.options[i].value == target ) {
			list.options.selectedIndex = i;
			return;
		}
	}
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function checkBillingFields() {
	
	var f = document.reportForm;
		
	if ( f.start_date_billing.value == '' ) {
		alert ('Please enter a start date');
		return false;
	}
	if ( f.end_date_billing.value == '' ) {
		alert ('Please enter an end date');
		return false;
	}
	
	if ( !( f.listByCompany.checked || f.listByProject.checked || f.listByTask.checked || f.listByDepartment.checked || f.listByEmployee.checked ) ) {
		alert ('Please select at least one category for view');
		return false;
	}
	
}

function runCalendar(element) {
	return popCalendar(element);
}

var calendarField = '';
	
function popCalendar( field ){
	calendarField = field;
	idate = eval( 'document.reportForm.format_' + field + '.value' );
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'top=250,left=250,width=250, height=220, scollbars=false' );
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = eval( 'document.reportForm.format_' + calendarField );
	fld_fdate = eval( 'document.reportForm.' + calendarField );
	fld_date.value = idate;
	fld_fdate.value = fdate;
}

</script>

<form name="reportForm" action="index.php?m=reports" method="post" onsubmit="return checkBillingFields()">

<table border="0" width="600">
	<tr>
		<td>Date Range</td>
		<td>
			<?php 
				$fmt_start_date = new CDate( $AppUI->getState( 'start_date_billing' ) );
				$fmt_end_date = new CDate( $AppUI->getState( 'end_date_billing' ) );
				$view_start_date_billing = $fmt_start_date->format( $df );
				$view_end_date_billing   = $fmt_end_date->format( $df );
			?>
			
			<input type="hidden" name="format_start_date_billing" value="<?php echo $fmt_start_date->format( FMT_TIMESTAMP_DATE );?>">
			<input type="text" class="text" name="start_date_billing" id="date1_billing" disabled="disabled" value="<?php echo $view_start_date_billing ?>">
			<a href="#" onClick="return popCalendar('start_date_billing');">
				<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
			</a>
			&nbsp;&nbsp;
			-
			&nbsp;&nbsp;
			<input type="hidden" name="format_end_date_billing" value="<?php echo $fmt_end_date->format( FMT_TIMESTAMP_DATE );?>">
			<input type="text" class="text" name="end_date_billing" id="date2_billing" disabled="disabled" value="<?php echo $view_end_date_billing ?>">
			<a href="#" onClick="return popCalendar('end_date_billing');">
				<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
			</a>
		</td>
	</tr>
	<tr>
		<td>Options</td>
		<td>
			
			<?php 
				$companyChecked =    $AppUI->getState( 'list_by_company_billing' ) ? " checked" : "";
				$projectChecked =    $AppUI->getState( 'list_by_project_billing' ) ? " checked" : "";
				$taskChecked =       $AppUI->getState( 'list_by_task_billing' ) ? " checked" : "";
				$departmentChecked = $AppUI->getState( 'list_by_department_billing') ? " checked" : "";
				$employeeChecked =   $AppUI->getState( 'list_by_employee_billing' ) ? " checked" : "";
				$detailsChecked =    $AppUI->getState( 'show_details_billing' ) ? " checked" : "";
			?>
			<?php
				$selectMe = $AppUI->getState('billing_report_employee') ? $AppUI->getState('billing_report_employee') : 0;
			?>

			<table border="0" width="300">
				<tr>
					<td width="150">
						<input type="checkbox" name="listByCompany" value="1" <?php echo $companyChecked ?>> List By Company
					</td>
					<td width="150">
						<?php 
						$params = "size=\"1\" style=\"width:150px;\" class=\"text\" onChange=\"changeList('project',projects, this.options[this.selectedIndex].value)\"";
						
						echo arraySelect( $companies, 'company', $params, @$AppUI->getState('billing_report_company'));
						?>

					</td>
				</tr>

				<tr>
					<td>
						<input type="checkbox" name="listByProject" value="1"<?php echo $projectChecked ?>> List By Project
					</td>
					<td>
						<select name="project" size="1" style="width:150px;" class="text" onChange="changeList('task',tasks, this.options[this.selectedIndex].value)">
						</select>
					</td>
				</tr>

				<tr>
					<td>
						<input type="checkbox" name="listByTask" value="1"<?php echo $taskChecked ?>> List By Task
					</td>
					<td>
						<select name="task" size="1" style="width:150px;" class="text">
						</select>
					</td>
				</tr>

				<?php
				
				if ($AppUI->user_type > 0 and $AppUI->user_type < 7) {
				
					echo "\t\t\t\t<tr>\n";
						echo "\t\t\t\t\t<td colspan=\"2\"><hr noshade=\"noshade\" size=\"1\"></td>\n";
					echo "\t\t\t\t</tr>\n";
	
					echo "\t\t\t\t<tr>\n";
						echo "\t\t\t\t\t<td>\n";
							echo "\t\t\t\t\t\t<input type=\"checkbox\" name=\"listByDepartment\" value=\"1\"$departmentChecked > List By Department\n";
						echo "\t\t\t\t\t</td>\n";
						echo "\t\t\t\t\t<td>\n";
							
							$params = "size=\"1\" style=\"width:150px;\" class=\"text\" onChange=\"changeList('employee',employees, this.options[this.selectedIndex].value)\"";
							
							echo arraySelect( $departments, 'department', $params, @$AppUI->getState('billing_report_department'));
						echo "\t\t\t\t\t</td>\n";
					echo "\t\t\t\t</tr>\n";
				
					echo "\t\t\t\t<tr>\n";
						echo "\t\t\t\t\t<td>\n";
							echo "\t\t\t\t\t\t<input type=\"checkbox\" name=\"listByEmployee\" value=\"1\" $employeeChecked > List By Employee\n";
						echo "\t\t\t\t\t</td>\n";
						echo "\t\t\t\t\t<td>\n";
							echo "\t\t\t\t\t\t<select name=\"employee\" size=\"1\" style=\"width:150px;\" class=\"text\">\n";
							echo "\t\t\t\t\t\t</select>\n";
						echo "\t\t\t\t\t</td>\n";
					echo "\t\t\t\t</tr>\n";
				
				} else {
					echo "<input type=\"hidden\" name=\"listByEmployee\" value=\"1\">\n";
					echo "<input type=\"hidden\" name=\"employee\" size=\"1\" value=\"" . $AppUI->user_id . "\">\n";
				}
				?>
				
				<tr>
					<td colspan="2"><hr noshade="noshade" size="1"></td>
				</tr>
				
				<tr>
					<td>
						<input type="checkbox" name="showDetails" value="1"<?php echo $detailsChecked ?>> Show Details
					</td>
					<td>
						<br>
					</td>
				</tr>
			</table>

		</td>
	</tr>
	<tr>
		<td colspan="2" align="right">
			<input type="submit" name="do_billing_report" value="Submit" class="button">
		</td>
</table>

</form>

<script language="javascript">
	changeList('project', projects, <?php echo $AppUI->getState('billing_report_company') ? $AppUI->getState('billing_report_company') : 0;?>);
	changeList('task', tasks, <?php echo $AppUI->getState('billing_report_project') ? $AppUI->getState('billing_report_project') : 0;?>);
	<?php if ($AppUI->user_type > 0 and $AppUI->user_type < 7) {?>
	changeList('employee', employees, <?php echo $AppUI->getState('billing_report_department') ? $AppUI->getState('billing_report_department') : 0;?>);
	<?php } ?>
	selectList( 'project', <?php echo $AppUI->getState('billing_report_project') ? $AppUI->getState('billing_report_project') : 0;?> );
	selectList( 'task', <?php echo $AppUI->getState('billing_report_task') ? $AppUI->getState('billing_report_task') : 0;?> );
	<?php if ($AppUI->user_type > 0 and $AppUI->user_type < 7) {?>
	selectList( 'employee', <?php echo $AppUI->getState('billing_report_employee') ? $AppUI->getState('billing_report_employee') : 0;?> );
	<?php } ?>
</script>