<?

show_header('Staff Inbox');

$View = display_str($_GET['view']);
$UserLevel = $LoggedUser['Class'];

// Setup for current view mode
switch ($View) {
	case 'unanswered':
		$ViewString = "Unanswered";
		$WhereCondition = "WHERE (Level <= $UserLevel OR AssignedToUser='".$LoggedUser['ID']."') AND Status='Unanswered'";
		break;
	case 'open':
		$ViewString = "All open";
		$WhereCondition = "WHERE (Level <= $UserLevel OR AssignedToUser='".$LoggedUser['ID']."') AND Status IN ('Open', 'Unanswered')";
		break;
	case 'resolved':
		$ViewString = "Resolved";
		$WhereCondition = "WHERE (Level <= $UserLevel OR AssignedToUser='".$LoggedUser['ID']."') AND Status='Resolved'";
		break;
	case 'my':
		$ViewString = "My unanswered";
		$WhereCondition = "WHERE (Level = $UserLevel OR AssignedToUser='".$LoggedUser['ID']."') AND Status='Unanswered'";
		break;
	default:
		if ($IsStaff) {
			$ViewString = "My unanswered";
			$WhereCondition = "WHERE (Level = $UserLevel OR AssignedToUser='".$LoggedUser['ID']."') AND Status='Unanswered'";
		} else {
			// FLS
			$ViewString = "Unanswered";
			$WhereCondition = "WHERE (Level <= $UserLevel OR AssignedToUser='".$LoggedUser['ID']."') AND Status='Unanswered'";
		}
		break;
}

// Get messages
$StaffPMs = $DB->query("
	SELECT 
		ID, 
		Subject, 
		UserID, 
		Status, 
		Level, 
		AssignedToUser, 
		Date, 
		Unread 
	FROM staff_pm_conversations 
	$WhereCondition 
	ORDER BY Date DESC
");

$Row = 'a';

// Start page
?>
<div class="thin">
	<h2><?=$ViewString?> Staff PMs</h2>
	<div class="linkbox">
<? 	if ($IsStaff) { 
?>		<a href="staffpm.php">[My unanswered]</a>
<? 	} ?>
		<a href="staffpm.php?view=unanswered">[All unanswered]</a>
		<a href="staffpm.php?view=open">[Open]</a>
		<a href="staffpm.php?view=resolved">[Resolved]</a>
		<br />
		<br />
	</div>
	<div class="box pad" id="inbox">
<?

if ($DB->record_count() == 0) {
	// No messages
?>
		<h2>No messages</h2>
<?

} else {
	// Messages, draw table
	if ($ViewString != 'Resolved' && $IsStaff) { 
		// Open multiresolve form
?>
		<form method="post" action="staffpm.php">
			<input type="hidden" name="action" value="multiresolve" />
			<input type="hidden" name="view" value="<?=strtolower($View)?>" />
<? 
	} 
	
	// Table head
?>
			<table>
				<tr class="colhead">
<? 				if ($ViewString != 'Resolved' && $IsStaff) { ?>
					<td width="10"><input type="checkbox" onclick="toggleChecks('messageform',this)" /></td>
<? 				} ?>
					<td width="50%">Subject</td>
					<td>Sender</td>
					<td>Date</td>
					<td>Assigned to</td>
				</tr>
<?

	// List messages
	while(list($ID, $Subject, $UserID, $Status, $Level, $AssignedToUser, $Date, $Unread) = $DB->next_record()) {
		$Row = ($Row === 'a') ? 'b' : 'a';
		$RowClass = 'row'.$Row;
		
		$UserInfo = user_info($UserID);
		$UserStr = format_username($UserID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'], $UserInfo['PermissionID']);
		
		// Get assigned
		if ($AssignedToUser == '') {
			// Assigned to class
			$Assigned = ($Level == 0) ? "First Line Support" : $ClassLevels[$Level]['Name'];
			// No + on Sysops
			if ($Assigned != 'Sysop') { $Assigned .= "+"; }
				
		} else {
			// Assigned to user
			$UserInfo = user_info($AssignedToUser);
			$Assigned = format_username($UserID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'], $UserInfo['PermissionID']);
			
		}
		
		// Table row
?>
				<tr class="<?=$RowClass?>">
<? 				if ($ViewString != 'Resolved' && $IsStaff) { ?>
					<td class="center"><input type="checkbox" name="id[]" value="<?=$ID?>" /></td>
<? 				} ?>
					<td><a href="staffpm.php?action=viewconv&amp;id=<?=$ID?>"><?=display_str($Subject)?></a></td>
					<td><?=$UserStr?></td>
					<td><?=time_diff($Date, 2, true)?></td>
					<td><?=$Assigned?></td>
				</tr>
<?
		
		$DB->set_query_id($StaffPMs);
	}
	
	// Close table and multiresolve form
?>
			</table>
<? 		if ($ViewString != 'Resolved' && $IsStaff) { ?>
			<input type="submit" value="Resolve selected" />
<?		} ?>
		</form>		
<?
	
}

?>
	</div>
</div>
<?

show_footer();

?>