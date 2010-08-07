<?
authorize();

if(!check_perms('admin_reports')) {
	error(403);
}

if(empty($_POST['reportid']) && !is_number($_POST['reportid'])) {
	error(403);
}
$ReportID = $_POST['reportid'];

$DB->query("UPDATE reports 
			SET Status='Resolved',
				ResolvedTime='".sqltime()."',
				ResolverID='".$LoggedUser['ID']."'
			WHERE ID='".db_string($ReportID)."'");

send_irc("PRIVMSG #admin :Report ".$ReportID." resolved by ".preg_replace("/^(.{2})/", "$1·", $LoggedUser['Username'])." on site.");

$Cache->delete_value('num_other_reports');

header('Location: reports.php');
?>
