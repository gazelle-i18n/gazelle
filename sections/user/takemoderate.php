<?
/*************************************************************************\
//--------------Take moderation -----------------------------------------//



\*************************************************************************/

// Are they being tricky blighters?
if (!$_POST['userid'] || !is_number($_POST['userid'])) {
	error(404);
} elseif (!check_perms('users_mod')) {
	error(403);
}
authorize();
// End checking for moronity

$UserID = $_POST['userid'];

// Variables for database input
$Class = (int)$_POST['Class'];
$Username = db_string($_POST['Username']);
$Title = db_string($_POST['Title']);
$AdminComment = db_string($_POST['AdminComment']);
$Donor = (isset($_POST['Donor']))? 1 : 0;
$Artist = (isset($_POST['Artist']))? 1 : 0;
$Visible = (isset($_POST['Visible']))? 1 : 0;
$Invites = (int)$_POST['Invites'];
$SupportFor = db_string($_POST['SupportFor']);
$Pass = db_string($_POST['ChangePassword']);
$Warned = (isset($_POST['Warned']))? 1 : 0;
if(isset($_POST['Uploaded']) && isset($_POST['Downloaded'])) {
	$Uploaded = ($_POST['Uploaded']  == "" ? 0 : $_POST['Uploaded']); 
	if(!is_number($Uploaded)) { 
		error(0);
	}

	$Downloaded = ($_POST['Downloaded'] == "" ? 0 : $_POST['Downloaded']);
	if(!is_number($Downloaded)) { 
		error(0); 
	}
}

$WarnLength = (int)$_POST['WarnLength'];
$ExtendWarning = (int)$_POST['ExtendWarning'];
$WarnReason = $_POST['WarnReason'];
$UserReason = $_POST['UserReason'];
$DisableAvatar = (isset($_POST['DisableAvatar']))? 1 : 0;
$DisableInvites = (isset($_POST['DisableInvites']))? 1 : 0;
$DisablePosting = (isset($_POST['DisablePosting']))? 1 : 0;
$DisableForums = (isset($_POST['DisableForums']))? 1 : 0;
$DisableTagging = (isset($_POST['DisableTagging']))? 1 : 0;
$DisableUpload = (isset($_POST['DisableUpload']))? 1 : 0;
$DisableWiki = (isset($_POST['DisableWiki']))? 1 : 0;
$DisablePM = (isset($_POST['DisablePM']))? 1 : 0;
$DisableIRC = (isset($_POST['DisableIRC']))? 1 : 0;
$DisableLeech = (isset($_POST['DisableLeech'])) ? 0 : 1;
$EnableUser = (int)$_POST['UserStatus'];
$ResetRatioWatch = (isset($_POST['ResetRatioWatch']))? 1 : 0;
$ResetPasskey = (isset($_POST['ResetPasskey']))? 1 : 0;
$ResetAuthkey = (isset($_POST['ResetAuthkey']))? 1 : 0;
$SendHackedMail = (isset($_POST['SendHackedMail']))? 1 : 0;
if($SendHackedMail && !empty($_POST['HackedEmail'])) {
	$HackedEmail = $_POST['HackedEmail'];
} else {
	$SendHackedMail = false;
}
$Reason = db_string($_POST['Reason']);

$HeavyUpdates = array();
$LightUpdates = array();

// Get user info from the database



$DB->query("SELECT
	m.Username,
	m.IP,
	m.Email,
	m.PermissionID,
	p.Level AS Class,
	m.Title,
	m.Enabled,
	m.Uploaded,
	m.Downloaded,
	m.Invites,
	m.can_leech,
	m.Visible,
	i.AdminComment,
	i.Donor,
	i.Artist,
	i.Warned,
	i.SupportFor,
	DisableAvatar,
	DisableInvites,
	DisablePosting,
	DisableForums,
	DisableTagging,
	DisableUpload,
	DisableWiki,
	DisablePM,
	DisableIRC
	FROM users_main AS m
	JOIN users_info AS i ON i.UserID = m.ID
	LEFT JOIN permissions AS p ON p.ID=m.PermissionID
	WHERE m.ID = $UserID");

if ($DB->record_count() == 0) { // If user doesn't exist
	header("Location: log.php?search=User+".$UserID);
}

$Cur = $DB->next_record(MYSQLI_ASSOC, false);

//NOW that we know the class of the current user, we can see if one staff member is trying to hax0r us.
if(!check_perms('users_mod', $Cur['Class'])) {
	//Son of a fucking bitch
	error(403);
	die();
}

// Gotten user info


// If we're deleting the user, we can ignore all the other crap

if ($_POST['UserStatus']=="delete" && check_perms('users_delete_users')) {
	write_log("User account ".$UserID." (".$Cur['Username'].") was deleted by ".$LoggedUser['Username']);
	$DB->query("DELETE FROM users_main WHERE id=".$UserID);
	$DB->query("DELETE FROM users_info WHERE UserID=".$UserID);
	$Cache->delete_value('user_info_'.$UserID);
	
	header("Location: log.php?search=User+".$UserID);
	die();
}

// User was not deleted. Perform other stuff.

$UpdateSet = array();
$EditSummary = array();

if ($_POST['ResetRatioWatch'] && check_perms('users_edit_reset_keys')) {
	$DB->query("UPDATE users_info SET RatioWatchEnds='0000-00-00 00:00:00', RatioWatchDownload='0', RatioWatchTimes='0' WHERE UserID='$UserID'");
	$EditSummary[]='RatioWatch history reset';
}

if ($_POST['ResetIPHistory'] && check_perms('users_edit_reset_keys')) {

	$DB->query("DELETE FROM users_history_ips WHERE UserID='$UserID'");
	$DB->query("UPDATE users_main SET IP='127.0.0.1' WHERE ID='$UserID'");
	$EditSummary[]='IP history cleared';
}

if ($_POST['ResetEmailHistory'] && check_perms('users_edit_reset_keys')) {
	$DB->query("DELETE FROM users_history_emails WHERE UserID='$UserID'");
	$DB->query("INSERT INTO users_history_emails (UserID, Email, Time, IP) VALUES ('$UserID','$Username@".SITE_URL."','0000-00-00 00:00:00','".$Cur['IP']."')");
	$DB->query("UPDATE users_main SET Email='$Username@".SITE_URL."' WHERE ID='$UserID'");
	$EditSummary[]='Email history cleared';
}

if ($_POST['ResetSnatchList'] && check_perms('users_edit_reset_keys')) {
	$DB->query("DELETE FROM xbt_snatched WHERE uid='$UserID'");
	$EditSummary[]='Snatch List cleared';
}

if ($_POST['ResetDownloadList'] && check_perms('users_edit_reset_keys')) {
	$DB->query("DELETE FROM users_downloads WHERE UserID='$UserID'");
	$EditSummary[]='Download List cleared';
}

if (($_POST['ResetSession'] || $_POST['LogOut']) && check_perms('users_logout')) {
	$Cache->delete_value('user_info_'.$UserID);
	$Cache->delete_value('user_info_heavy_'.$UserID);
	$Cache->delete_value('user_stats_'.$UserID);
	$Cache->delete_value('enabled_'.$UserID);
	
	if($_POST['LogOut']) {
		$DB->query("SELECT SessionID FROM users_sessions WHERE UserID='$UserID'");
		while(list($SessionID) = $DB->next_record()) {
			$Cache->delete_value('session_'.$UserID.'_'.$SessionID);
		}
		$Cache->delete_value('users_sessions_'.$UserID);
		$DB->query("DELETE FROM users_sessions WHERE UserID='$UserID'");
	}
}



// Start building SQL query and edit summary
if ($Classes[$Class]['Level']!=$Cur['Class'] && (
	($Classes[$Class]['Level'] < $LoggedUser['Class'] && check_perms('users_promote_below', $Cur['Class']))
	|| ($Classes[$Class]['Level'] <= $LoggedUser['Class'] && check_perms('users_promote_to', $Cur['Class']-1)))) {
	$UpdateSet[]="PermissionID='$Class'";
	$EditSummary[]="class changed to ".make_class_string($Class);
	$LightUpdates['PermissionID']=$Class;
}

if ($Username!=$Cur['Username'] && check_perms('users_edit_usernames', $Cur['Class']-1)) {
	$DB->query("SELECT ID FROM users_main WHERE Username = '".$Username."'");
	if($DB->next_record() > 0) {
		list($UsedUsernameID) = $DB->next_record();
		error_message("Username already in use by <a href='user.php?id=".$UsedUsernameID."'>".$Username."</a>");
		header("Location: user.php?id=".$UserID);
		die();
	} else {
		$UpdateSet[]="Username='".$Username."'";
		$EditSummary[]="username changed from ".$Cur['Username']." to ".$Username;
		$LightUpdates['Username']=$Username;
	}
}

if ($Title!=db_string($Cur['Title']) && check_perms('users_edit_titles')) {
	$UpdateSet[]="Title='$Title'";
	$EditSummary[]="title changed to $Title";
	$LightUpdates['Title']=$_POST['Title'];
}

if ($Donor!=$Cur['Donor']  && check_perms('users_give_donor')) {
	$UpdateSet[]="Donor='$Donor'";
	$EditSummary[]="donor status changed";
	$LightUpdates['Donor']=$Donor;
}

if ($Artist!=$Cur['Artist']  && (check_perms('users_promote_below') || check_perms('users_promote_to'))) {
	$UpdateSet[]="Artist='$Artist'";
	$EditSummary[]="artist status changed";
	$LightUpdates['Artist']=$Artist;
	if($Artist == 1) {
		send_pm($UserID, 0, db_string("Artist Class"), db_string("Hi,\n\nCongratulations on your promotion to the artist class. 
			To add a recommendation, please go to [url=http://".NONSSL_SITE_URL."/tools.php?action=recommend]this tool[/url] (for the first time, you may have to go to [url=http://".NONSSL_SITE_URL."/tools.php?action=recommend&resetsession=1]this link[/url]) instead.
			Please only recommend albums that you created yourself, or albums where you officially represent the artist in question.
			\n\nThanks,\n	".SITE_NAME." staff"),'');
	}
}


if ($Visible!=$Cur['Visible']  && check_perms('users_make_invisible')) {
	$UpdateSet[]="Visible='$Visible'";
	$EditSummary[]="visibility changed";
	$LightUpdates['Visible']=$Visible;
}

if ($Uploaded!=$Cur['Uploaded'] && $Uploaded!=$_POST['OldUploaded'] && (check_perms('users_edit_ratio') 
 || (check_perms('users_edit_own_ratio') && $UserID == $LoggedUser['ID']))) {
	$UpdateSet[]="Uploaded='".$Uploaded."'";
	$EditSummary[]="uploaded changed from ".get_size($Cur['Uploaded'])." to ".get_size($Uploaded);
	$Cache->delete_value('users_stats_'.$UserID);
}

if ($Downloaded!=$Cur['Downloaded'] && $Downloaded!=$_POST['OldDownloaded'] && (check_perms('users_edit_ratio') 
 || (check_perms('users_edit_own_ratio') && $UserID == $LoggedUser['ID']))) {
	$UpdateSet[]="Downloaded='".$Downloaded."'";
	$EditSummary[]="downloaded changed from ".get_size($Cur['Downloaded'])." to ".get_size($Downloaded);
	$Cache->delete_value('users_stats_'.$UserID);
}

if ($Invites!=$Cur['Invites'] && check_perms('users_edit_invites')) {
	$UpdateSet[]="invites='$Invites'";
	$EditSummary[]="number of invites changed to $Invites";
	$HeavyUpdates['Invites'] = $Invites;
}

if ($Warned == 1 && $Cur['Warned']=='0000-00-00 00:00:00' && check_perms('users_warn')) {
	send_pm($UserID,0,db_string('You have received a warning'),db_string("You have been [url=http://".NONSSL_SITE_URL."/wiki.php?action=article&id=218]warned for $WarnLength week(s)[/url] by $LoggedUser[Username]. The reason given was: $WarnReason"));
	$UpdateSet[]="Warned='".sqltime()."' + INTERVAL $WarnLength WEEK";
	$Msg = "warned for $WarnLength week(s)";
	if ($WarnReason) { $Msg.=" for $WarnReason"; }
	$EditSummary[]= db_string($Msg);
	$LightUpdates['Warned']=time_plus(3600*24*7*$WarnLength);

} elseif ($Warned == 0 && $Cur['Warned']!='0000-00-00 00:00:00' && check_perms('users_warn')) {
	$UpdateSet[]="Warned='0000-00-00 00:00:00'";
	$EditSummary[]="warning removed";
	$LightUpdates['Warned']='0000-00-00 00:00:00';

} elseif ($Warned == 1 && $ExtendWarning!='---' && check_perms('users_warn')) {
	
	send_pm($UserID,0,db_string('Your warning has been extended'),db_string("Your warning has been extended by $ExtendWarning week(s) by $LoggedUser[Username]. The reason given was: $WarnReason"));
	
	$UpdateSet[]="Warned=Warned + INTERVAL $ExtendWarning WEEK";
	$Msg = "warning extended by $ExtendWarning week(s)";
	if ($WarnReason) { $Msg.=" for $WarnReason"; }
	$EditSummary[]= db_string($Msg);
	$DB->query("SELECT Warned FROM users_info WHERE UserID='$UserID'");
	list($WarnedUntil) = $DB->next_record();
	$LightUpdates['Warned']=$WarnedUntil;
}

if ($SupportFor!=db_string($Cur['SupportFor']) && (check_perms('admin_manage_fls') || (check_perms('users_mod') && $UserID == $LoggedUser['ID']))) {
	$UpdateSet[]="SupportFor='$SupportFor'";
	$EditSummary[]="first-line support status changed to $SupportFor";
}

if ($DisableAvatar!=$Cur['DisableAvatar'] && check_perms('users_disable_any')) {
	$UpdateSet[]="DisableAvatar='$DisableAvatar'";
	$EditSummary[]="avatar status changed";
	$HeavyUpdates['DisableAvatar']=$DisableAvatar;
	if (!empty($UserReason)) {
		send_pm($UserID, 0, db_string('Your avatar privileges have been disabled'),db_string("Your avatar privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]."));
	}
}

if ($DisableLeech!=$Cur['can_leech'] && check_perms('users_disable_any')) {
	$UpdateSet[]="can_leech='$DisableLeech'";
	$EditSummary[]="leeching status changed";
	$HeavyUpdates['DisableLeech']=$DisableLeech;
	if (!empty($UserReason)) {
		send_pm($UserID, 0, db_string('Your leeching privileges have been disabled'),db_string("Your leeching privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]."));
	}
	
}

if ($DisableInvites!=$Cur['DisableInvites'] && check_perms('users_disable_any')) {
	$UpdateSet[]="DisableInvites='$DisableInvites'";
	if ($DisableInvites == 1) { $UpdateSet[]="Invites='0'"; }
	$EditSummary[]="invites status changed";
	$HeavyUpdates['DisableInvites']=$DisableInvites;
	if (!empty($UserReason)) {
		send_pm($UserID, 0, db_string('Your invite privileges have been disabled'),db_string("Your invite privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]."));
	}
}

if ($DisablePosting!=$Cur['DisablePosting'] && check_perms('users_disable_posts')) {
	$UpdateSet[]="DisablePosting='$DisablePosting'";
	$EditSummary[]="posting status changed";
	$HeavyUpdates['DisablePosting']=$DisablePosting;
	if (!empty($UserReason)) {
		send_pm($UserID, 0, db_string('Your forum posting privileges have been disabled'),db_string("Your forum posting privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]."));
	}
}

if ($DisableForums!=$Cur['DisableForums'] && check_perms('users_disable_posts')) {
	$UpdateSet[]="DisableForums='$DisableForums'";
	$EditSummary[]="forums status changed";
	$HeavyUpdates['DisableForums']=$DisableForums;
	if (!empty($UserReason)) {
		send_pm($UserID, 0, db_string('Your forum privileges have been disabled'),db_string("Your forum privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]."));
	}
}

if ($DisableTagging!=$Cur['DisableTagging'] && check_perms('users_disable_any')) {
	$UpdateSet[]="DisableTagging='$DisableTagging'";
	$EditSummary[]="tagging status changed";
	$HeavyUpdates['DisableTagging']=$DisableTagging;
	if (!empty($UserReason)) {
		send_pm($UserID, 0, db_string('Your tagging privileges have been disabled'),db_string("Your tagging privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]."));
	}
}

if ($DisableUpload!=$Cur['DisableUpload'] && check_perms('users_disable_any')) {
	$UpdateSet[]="DisableUpload='$DisableUpload'";
	$EditSummary[]="upload status changed";
	$HeavyUpdates['DisableUpload']=$DisableUpload;
	if ($DisableUpload == 1) {
		send_pm($UserID, 0, db_string('Your upload privileges have been disabled'),db_string("Your upload privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]."));
	}
}

if ($DisableWiki!=$Cur['DisableWiki'] && check_perms('users_disable_any')) {
	$UpdateSet[]="DisableWiki='$DisableWiki'";
	$EditSummary[]="wiki status changed";	
	$HeavyUpdates['DisableWiki']=$DisableWiki;
	$HeavyUpdates['site_edit_wiki']=0;
	if (!empty($UserReason)) {
		send_pm($UserID, 0, db_string('Your site editing privileges have been disabled'),db_string("Your site editing privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]."));
	}
	
}

if ($DisablePM!=$Cur['DisablePM'] && check_perms('users_disable_any')) {
	$UpdateSet[]="DisablePM='$DisablePM'";
	$EditSummary[]="PM status changed";
	$HeavyUpdates['DisablePM']=$DisablePM;
	if (!empty($UserReason)) {
		send_pm($UserID, 0, db_string('Your PM privileges have been disabled'),db_string("Your PM privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]."));
	}
}

if ($DisableIRC!=$Cur['DisableIRC'] && check_perms('users_disable_any')) {
	$UpdateSet[]="DisableIRC='$DisableIRC'";
	$EditSummary[]="IRC status changed";
	$HeavyUpdates['DisableIRC']=$DisableIRC;
	if (!empty($UserReason)) {
		send_pm($UserID, 0, db_string('Your IRC privileges have been disabled'),db_string("Your IRC privileges have been disabled. The reason given was: $UserReason. If you would like to discuss this please join #what.cd-disabled on our IRC network. Instructions can be found [url=http://what.cd/wiki.php?action=article&name=IRC+-+How+to+join]here[/url]. This loss of privileges does not affect the ability to join and talk to staff in #what.cd-disabled."));
	}
}

if ($EnableUser!=$Cur['Enabled'] && check_perms('users_disable_users')) {
	$EditSummary[]='account '.translateUserStatus($Cur['Enabled']).'->'.translateUserStatus($EnableUser);
	if($EnableUser == '2') {
		disable_users($UserID, '', '1');
	} elseif($EnableUser == '1') {
		$Cache->increment('stats_user_count');
		$UpdateSet[]="i.RatioWatchDownload='0'";
		$UpdateSet[]="i.RatioWatchEnds='0000-00-00 00:00:00'";
		$UpdateSet[]="Enabled='1'";
		$CanLeech = 1;
		$UpdateSet[]="m.can_leech='1'";
		$LightUpdates['Enabled'] = 1;
		
	}
	$Cache->replace_value('enabled_'.$UserID, $EnableUser, 0);
}

if ($ResetPasskey == 1 && check_perms('users_edit_reset_keys')) {
	$Passkey = db_string(make_secret());
	$UpdateSet[]="torrent_pass='$Passkey'";
	$EditSummary[]="passkey reset";
	$HeavyUpdates['torrent_pass']=$Passkey;
	
}

if ($ResetAuthkey == 1 && check_perms('users_edit_reset_keys')) {
	$Authkey = db_string(make_secret());
	$UpdateSet[]="AuthKey='$Authkey'";
	$EditSummary[]="authkey reset";
	$HeavyUpdates['AuthKey']=$Authkey;
}

if ($SendHackedMail && check_perms('users_disable_any')) {
	$EditSummary[]="hacked email sent to ".$HackedEmail;
	send_email($HackedEmail, "Your ".SITE_NAME." account.","Your ".SITE_NAME." account appears to have been compromised. As a security measure we have disabled your account. To resolve this please visit us on IRC.

This is the information to connect to our server:
IRC Server: ".BOT_SERVER."
Port: ".BOT_PORT." (6697 SSL)

Once you are connected to our server you'll need to join our disabled channel.
Type: /join #".NONSSL_SITE_URL."-disabled

Please visit us soon so we can help you resolve this matter.");
}

if ($Pass && check_perms('users_edit_password')) {
	$Secret=make_secret();
	$UpdateSet[]="Secret='$Secret'";
	$UpdateSet[]="PassHash='".db_string(make_hash($Pass,$Secret))."'";
	$EditSummary[]='password reset';
       
	$Cache->delete_value('user_info_'.$UserID);
        $Cache->delete_value('user_info_heavy_'.$UserID);
        $Cache->delete_value('user_stats_'.$UserID);
        $Cache->delete_value('enabled_'.$UserID);

        $DB->query("SELECT SessionID FROM users_sessions WHERE UserID='$UserID'");
        while(list($SessionID) = $DB->next_record()) {
		$Cache->delete_value('session_'.$UserID.'_'.$SessionID);
	}
        $Cache->delete_value('users_sessions_'.$UserID);
        $DB->query("DELETE FROM users_sessions WHERE UserID='$UserID'");
        
}

if (empty($UpdateSet) && empty($EditSummary)) {
	if(!$Reason) {
		if (str_replace("\r", '', $Cur['AdminComment']) != str_replace("\r", '', $AdminComment) && check_perms('users_disable_any')) {
			$UpdateSet[]="AdminComment='$AdminComment'";
		} else {
			header("Location: user.php?id=$UserID");
			die();
		}
	} else {
		$EditSummary[]='notes added';
	}
}

$Cache->begin_transaction('user_info_'.$UserID);
$Cache->update_row(false, $LightUpdates);
$Cache->commit_transaction(0);

$Cache->begin_transaction('user_info_heavy_'.$UserID);
$Cache->update_row(false, $HeavyUpdates);
$Cache->commit_transaction(0);

$Summary = '';
// Create edit summary
if ($EditSummary) {
	$Summary = implode(', ', $EditSummary)." by ".$LoggedUser['Username'];
	$Summary = sqltime().' - '.ucfirst($Summary);
	
	if ($Reason){
		$Summary .= "\nReason: ".$Reason;
	}
	
	
	
	$Summary .= "\n\n".$AdminComment;
} elseif (empty($UpdateSet) && empty($EditSummary) && $Cur['AdminComment']==$_POST['AdminComment']) {
	$Summary = sqltime().' - '.'Comment added by '.$LoggedUser['Username'].': '.$Reason."\n\n";
	
	
}


if(!empty($Summary)) {
	$UpdateSet[]="AdminComment='$Summary'";
} else {
	$UpdateSet[]="AdminComment='$AdminComment'";
}

// Update cache


// Build query

$SET = implode(', ', $UpdateSet);

$sql = "UPDATE users_main AS m JOIN users_info AS i ON m.ID=i.UserID SET $SET WHERE m.ID='$UserID'";

// Perform update
//die($sql);
$DB->query($sql);

// redirect to user page
header("location: user.php?id=$UserID");

function translateUserStatus($status) {
	switch($status) {
		case 0:
			return "Unconfirmed";
		case 1:
			return "Enabled";
		case 2:
			return "Disabled";
		default:
			return $status;
	}
}
?>
