<?
//Include all the basic stuff...

enforce_login();
if(isset($_GET['method'])) {
	switch($_GET['method']) {
		case 'transcode':
			include(SERVER_ROOT.'/sections/better/transcode.php');
			break;
		case 'snatch':
			include(SERVER_ROOT.'/sections/better/snatch.php');
			break;
		case 'artistless':
			include(SERVER_ROOT.'/sections/better/artistless.php');
			break;			
		case 'tags':
			include(SERVER_ROOT.'/sections/better/tags.php');
			break;			
		case 'folders':
			include(SERVER_ROOT.'/sections/better/folders.php');
			break;
		case 'upload':
			include(SERVER_ROOT.'/sections/better/upload.php');
			break;
		default:
			error(404);
			break;
	}
} else {
	include(SERVER_ROOT.'/sections/better/better.php');
}
?>
