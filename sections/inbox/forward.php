<?
authorize();

$UserID = $LoggedUser['ID'];
$ConvID = $_POST['convid'];
$ReceiverID = $_POST['receiverid'];
if(!is_number($ConvID) || !is_number($ReceiverID)) { error(404); }
$DB->query("SELECT DISTINCT UserID FROM pm_conversations_users WHERE (UserID='$UserID' OR UserID='$ReceiverID') AND ConvID='$ConvID'");
if($DB->record_count() != 1) { error(0); }

$DB->query("UPDATE pm_conversations_users SET
	UserID='$ReceiverID', UnRead='1'
	WHERE ConvID='$ConvID' AND UserID='$UserID'");
$Cache->delete_value('inbox_new_'.$ReceiverID);
header('Location: inbox.php');
?>
