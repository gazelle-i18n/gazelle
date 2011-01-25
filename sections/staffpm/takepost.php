<?
if ($Message = db_string($_POST['message'])) {
	if ($Subject = db_string($_POST['subject'])) {
		// New staff pm conversation
		$Level = db_string($_POST['level']);
		$DB->query("
			INSERT INTO staff_pm_conversations 
				(Subject, Status, Level, UserID, Date)
			VALUES
				('$Subject', 'Unanswered', $Level, ".$LoggedUser['ID'].", '".sqltime()."')"
		);
		
		// New message
		$ConvID = $DB->inserted_id();
		$DB->query("
			INSERT INTO staff_pm_messages
				(UserID, SentDate, Message, ConvID)
			VALUES
				(".$LoggedUser['ID'].", '".sqltime()."', '$Message', $ConvID)"
		);
		
		header('Location: staffpm.php');
		
	} elseif ($ConvID = (int)$_POST['convid']) {
		// Check if conversation belongs to user
		$DB->query("SELECT UserID, AssignedToUser FROM staff_pm_conversations WHERE ID=$ConvID");
		list($UserID, $AssignedToUser) = $DB->next_record();
		
		if ($UserID == $LoggedUser['ID'] || $IsFLS || $UserID == $AssignedToUser) {
			// Response to existing conversation
			$DB->query("
				INSERT INTO staff_pm_messages
					(UserID, SentDate, Message, ConvID)
				VALUES
					(".$LoggedUser['ID'].", '".sqltime()."', '$Message', $ConvID)"
			);
			
			// Update conversation
			if ($IsFLS) {
				// FLS/Staff
				$DB->query("UPDATE staff_pm_conversations SET Date='".sqltime()."', Unread=true, Status='Open' WHERE ID=$ConvID");
			} else {
				// User
				$DB->query("UPDATE staff_pm_conversations SET Date='".sqltime()."', Unread=true, Status='Unanswered' WHERE ID=$ConvID");
			}
			
			header("Location: staffpm.php?action=viewconv&id=$ConvID");
		} else {
			// User is trying to respond to conversation that does no belong to them
			error(403);
		}
		
	} else {
		// Message but no subject or conversation id
		header("Location: staffpm.php?action=viewconv&id=$ConvID");
		
	}
} elseif ($ConvID = (int)$_POST['convid']) {
	// No message, but conversation id
	header("Location: staffpm.php?action=viewconv&id=$ConvID");
	
} else {
	// No message or conversation id
	header('Location: staffpm.php');
}


?>