<?
show_header('Staff PMs', 'staffpm');
$View = ($_GET['view'] == 'resolved') ? 'Resolved' : 'Open';

// Get messages
$StatusCompare = ($View == 'Open') ? "IN ('Open', 'Unanswered')" : "='Resolved'";
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
	WHERE UserID=".$LoggedUser['ID']." 
	AND Status $StatusCompare
	ORDER BY Date DESC"
);

// Start page
?>
<div class="thin">
	<h2><?=$View?> Staff PMs</h2>
	<div class="linkbox">
		<a href="#" onClick="$('#compose').toggle();">[Toggle compose]</a>
		<a href="staffpm.php">[Open]</a>
		<a href="staffpm.php?view=resolved">[Resolved]</a>
		<br />
		<br />
	</div>
	<div id="compose" class="hidden">
		<form action="staffpm.php" method="post">
			<input type="hidden" name="action" value="takepost" />
			
			<label for="subject"><h3>Subject</h3></label>
			<input size="95" type="text" name="subject" id="subject" />
			<br />
			
			<label for="message"><h3>Message</h3></label>
			<textarea rows="10" cols="95" name="message" id="message"></textarea>
			<br />
			
			<strong>Send to: </strong>
			<select name="level">
				<option value="0" selected="selected">First Line Support</option>
				<option value="700">Staff</option>
			</select>
			<input type="submit" value="Send message" />
		</form>
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
	if ($View != 'Resolved') { 
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
<? 				if ($View != 'Resolved') { ?>
					<td width="10"><input type="checkbox" onclick="toggleChecks('messageform',this)" /></td>
<? 				} ?>
					<td width="50%">Subject</td>
					<td>Date</td>
					<td>Assigned to</td>
				</tr>
<?
	// List messages
	$Row = 'a';
	while(list($ID, $Subject, $UserID, $Status, $Level, $AssignedToUser, $Date, $Unread) = $DB->next_record()) {
		if($Unread === '1') {
			$RowClass = 'unreadpm';
		} else {
			$Row = ($Row === 'a') ? 'b' : 'a';
			$RowClass = 'row'.$Row;
		}
		
		// Get assigned
		$Assigned = ($Level == 0) ? "First Line Support" : $ClassLevels[$Level]['Name'];
		// No + on Sysops
		if ($Assigned != 'Sysop') { $Assigned .= "+"; }
			
		// Table row
?>
				<tr class="<?=$RowClass?>">
<? 				if ($View != 'Resolved') { ?>
					<td class="center"><input type="checkbox" name="id[]" value="<?=$ID?>" /></td>
<? 				} ?>
					<td><a href="staffpm.php?action=viewconv&amp;id=<?=$ID?>"><?=display_str($Subject)?></a></td>
					<td><?=time_diff($Date, 2, true)?></td>
					<td><?=$Assigned?></td>
				</tr>
<?
		$DB->set_query_id($StaffPMs);
	}

	// Close table and multiresolve form
?>
			</table>
<? 		if ($View != 'Resolved') { ?>
			<input type="submit" value="Resolve selected" />
<? 		} ?>
		</form>
<?

}

?>
	</div>
</div>
<?

show_footer();

?>