<?
authorize();

$InviteKey = db_string($_GET['invite']);
$DB->query("SELECT InviterID FROM invites WHERE InviteKey='$InviteKey'");
list($UserID) = $DB->next_record();
if($DB->record_count() == 0 || $UserID!=$LoggedUser['ID']){ error(404); }

$DB->query("DELETE FROM invites WHERE InviteKey='$InviteKey'");

if(!check_perms('site_send_unlimited_invites')){
	$DB->query("UPDATE users_main SET Invites=Invites+1 WHERE ID='$UserID'");
	$Cache->begin_transaction('user_info_heavy_'.$UserID);
	$Cache->update_row(false, array('Invites'=>'+1'));
	$Cache->commit_transaction(0);
}
header('Location: user.php?action=invite');

?>