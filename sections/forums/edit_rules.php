<?
enforce_login();
authorize();
if(!check_perms('site_moderate_forums')) {
	error(403);
}

if(!empty($_POST['act'])) {
	//Add / remove
}


$ForumID = $_GET['forumid'];
if(!is_number($ForumID)) {
	error(404);
}

$DB->query("SELECT ThreadID FROM forums_specific_rules WHERE ForumID = ".$ForumID);
$ThreadIDs = $DB->to_array();

//Output each threadid with delete button
//Output 'add' box

header('Location: forums.php?action=viewforum&amp;forumid='.$ForumID);
