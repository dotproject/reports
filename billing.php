<?php

require_once("tree.class.php");

$bill_category = dPgetSysVal( "BillingCategory");
$work_category = dPgetSysVal( "WorkCategory" );

$show_details       = $AppUI->getState( 'show_details_billing' );
$list_by_task       = $AppUI->getState( 'list_by_task_billing' );
$list_by_project    = $AppUI->getState( 'list_by_project_billing' );
$list_by_company    = $AppUI->getState( 'list_by_company_billing' );
$list_by_department = $AppUI->getState( 'list_by_department_billing' );
$list_by_employee   = $AppUI->getState( 'list_by_employee_billing' );

$task       = $AppUI->getState( 'billing_report_task' );
$project    = $AppUI->getState( 'billing_report_project' );
$company    = $AppUI->getState( 'billing_report_company' );
$department = $AppUI->getState( 'billing_report_department' );
$employee   = $AppUI->getState( 'billing_report_employee' );

$start_date = $AppUI->getState( 'start_date_billing' );
$end_date   = $AppUI->getState( 'end_date_billing' );


$select_sql = "select task_log_hours, task_log_bill_category, ";
$displayed_columns = array();

// select sql
if ($show_details) {
	$select_sql .= " task_log_work_category as task_log_cost_code, task_log_description as task_log_summary,";
	$displayed_columns[] = SUMMARY;
}
if ($list_by_task) {
	$select_sql .= " task_name, task_id,";
	$displayed_columns[] = TASK;
}
if ($list_by_project) {
	$select_sql .= " project_name, project_id,";
	$displayed_columns[] = PROJECT;
}
if ($list_by_company) {
	$select_sql .= " company_name, company_id,";
	$displayed_columns[] = COMPANY;
}
if ($list_by_department) {
	$select_sql .= " dept_name as department_name,";
	$displayed_columns[] = DEPARTMENT;
}
if ($list_by_employee) {
	$select_sql .= " concat(user_first_name, ' ', user_last_name) as employee_name,";
	$displayed_columns[] = EMPLOYEE;
}

if ( !count($displayed_columns) ) {
	$AppUI->setMsg("Please select at least one category for view", UI_MSG_ERROR);
	$AppUI->redirect("m=reports");
}

if ( $start_date == '' ) {
	$AppUI->setMsg("Please choose a start date", UI_MSG_ERROR);
	$AppUI->redirect("m=reports");
}

if ( $end_date == '' ) {
	$AppUI->setMsg("Please choose an end date", UI_MSG_ERROR);
	$AppUI->redirect("m=reports");
}

$start_date = new CDate( $start_date );
$start_date = $start_date->format( FMT_DATETIME_MYSQL );
$end_date = new CDate( $end_date );
$end_date = $end_date->format( FMT_DATETIME_MYSQL );

$select_sql = substr($select_sql, 0, strlen($select_sql) - 1);

// from sql
$from_sql = " from task_log";

// join sql
$join_sql = "";
if ($list_by_task or $list_by_project or $list_by_company)
	$join_sql .= " LEFT JOIN tasks on task_id = task_log_task";
if ($list_by_project or $list_by_company)
	$join_sql .= " LEFT JOIN projects on project_id = task_project";
if ($list_by_company)
	$join_sql .= " LEFT JOIN companies on company_id = project_company";
if ($list_by_employee or $list_by_department)
	$join_sql .= " LEFT JOIN users on user_id = task_log_creator";
if ($list_by_department)
	$join_sql .= " LEFT JOIN departments on dept_id = user_department";
	
// where sql
$where_sql = " where task_log_date >= '" . $start_date . " 00:00:00' and task_log_date <= '" . $end_date . " 00:00:00'";
if ($task and $list_by_task)
	$where_sql .= " and task_id = " . $task;
if ($project and $list_by_project)
	$where_sql .= " and project_id = " . $project;
if ($company and $list_by_company)
	$where_sql .= " and company_id = " . $company;
if ($employee and $list_by_employee)
	$where_sql .= " and user_id = " . $employee;
if ($department and $list_by_department)
	$where_sql .= " and dept_id = " . $department;

//echo $select_sql . "<br>" . $from_sql . "<br>" . $join_sql . "<br>" . $where_sql;

$sql = $select_sql . $from_sql . $join_sql . $where_sql;

$results = db_LoadList($sql);

$tree = make_new_tree();


// add task log
foreach ( $results as $row ) {
	
	// create hours array
	$hours = array();
	foreach ( $bill_category as $i=>$name ) {
		if ( $row['task_log_bill_category'] == $i )
			$hours[$name] = $row['task_log_hours'];
		else
			$hours[$name] = 0;
	}
	
	$row['company_name'] = "<a href=\"index.php?m=companies&a=view&company_id=${row['company_id']}\">${row['company_name']}</a>";
	$row['project_name'] = "<a href=\"index.php?m=projects&a=view&project_id=${row['project_id']}\">${row['project_name']}</a>";
	$row['task_name']    = "<a href=\"index.php?m=tasks&a=view&task_id=${row['task_id']}\">${row['task_name']}</a>";

	if ( $show_details )
		$row['task_log_cost_code'] = $work_category[$row['task_log_cost_code']];
	
	add_task_log( $tree, $row,  $hours);
}

echo "<br>\n";

print_tree( $tree, $displayed_columns );

/*
$sql = "

select task_log_work_category as task_log_cost_code, task_log_description as task_log_summary, task_log_hours, task_log_bill_category, task_name, project_name, company_name, user_username as employee_name, dept_name as department_name
from task_log
LEFT JOIN tasks ON task_id = task_log_task
LEFT JOIN projects ON project_id = task_project
LEFT JOIN companies ON company_id = project_company
LEFT JOIN users ON user_id = task_log_creator
LEFT JOIN departments ON dept_id = user_department
where task_log_date >= '2003-07-21' and task_log_date <= '2003-08-11'

";
*/

?>