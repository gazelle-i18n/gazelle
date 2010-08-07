<?
// Main image proxy page
// The image proxy does not use script_start.php, its code instead resides entirely in image.php in the document root
// Bear this in mind when you try to use script_start functions.

if (!check_perms('site_proxy_images')) { error('forbidden'); }
$URL = htmlspecialchars_decode($_GET['i']);

if (!extension_loaded('openssl') && strtoupper($URL[4]) == 'S') { error('badprotocol'); }

if (!preg_match('/^https?:\/\/(localhost(:[0-9]{2,5})?|[0-9]{1,3}(\.[0-9]{1,3}){3}|([a-zA-Z0-9\-\_]+\.)+([a-zA-Z]{1,5}[^\.]))(:[0-9]{2,5})?(\/[^<>]+)+\.(jpg|jpeg|gif|png|tif|tiff|bmp)$/is',$URL,$Matches)) { error('invalid'); }

if (isset($_GET['c'])) {
	list($Data,$Type) = $Cache->get_value('image_cache_'.md5($URL));
	$Cached = true;
}
if(!isset($Data) || !$Data) {
	$Cached = false;
	$Data = @file_get_contents($URL,0,stream_context_create(array('http'=>array('timeout'=>15))));
	if (!$Data || empty($Data)) {
		error('timeout');
	}

	$Supported = false;
	if (substr($Data,6,4) == 'JFIF') { // Todo: Support exif
		if(function_exists('imagecreatefromjpeg')) { $Supported = true; }
		$Type = 'jpeg';
	} elseif (substr($Data,0,3) == 'GIF') {
		if(function_exists('imagecreatefromgif')) { $Supported = true; }
		$Type = 'gif';
	} elseif (substr($Data,1,3) == 'PNG') {
		if(function_exists('imagecreatefrompng')) { $Supported = true; }
		$Type = 'png';
	} elseif ((substr($Data,0,2) == 'II' || substr($Data,0,2) == 'MM')) {
		$Type = 'tiff';
	} elseif (substr($Data,0,2) == 'BM') {
		$Type = 'bmp';
	}
	
	
	if ($Supported) {
		$Image = imagecreatefromstring($Data);
		if (invisible($Image)) {
			error('invisible');
		}
		if (verysmall($Image)) {
			error('small');
		}
	}
	
	
	
	if (isset($_GET['c']) && strlen($Data) < 262144) {
		$Cache->cache_value('image_cache_'.md5($URL), array($Data,$Type), 3600*24*7);
	} else {
		//require_once(SERVER_ROOT.'/classes/class_mysql.php');
		//$DB = new
	}
	
	
}

// Enforce avatar rules
if (isset($_GET['avatar'])) {
	if(!is_number($_GET['avatar'])) { die(); }
	$UserID = $_GET['avatar'];
	
	$Height = image_height($Type, $Data);
	if(strlen($Data)>256*1024 || $Height>400) {
		// Sometimes the cached image we have isn't the actual image
		if($Cached) {
			$Data2 = @file_get_contents($URL,0,stream_context_create(array('http'=>array('timeout'=>15))));
		} else {
			$Data2 = $Data;
		}
		if(strlen($Data2)>256*1024 || image_height($Type, $Data2)>400) {
		
			require_once(SERVER_ROOT.'/classes/class_mysql.php');
			require_once(SERVER_ROOT.'/classes/class_time.php'); //Require the time class
			
			$DB = new DB_MYSQL;
			
			
			$DBURL = db_string($URL);
			
			// Reset avatar, add mod note
			$UserInfo = $Cache->get_value('user_info_'.$UserID);
			$UserInfo['Avatar'] = '';
			$Cache->cache_value('user_info_'.$UserID, $UserInfo, 2592000);
			
			$DB->query("UPDATE users_info SET Avatar='', AdminComment=CONCAT('".sqltime()." - Avatar reset automatically (Size: ".number_format((strlen($Data))/1024)."kb, Height: ".$Height."px). Used to be $DBURL

', AdminComment) WHERE UserID='$UserID'");
		
		
			// Send PM
			
			send_pm($UserID,0,"Your avatar has been automatically reset","The following avatar rules have been in effect for months now: 

[b]Avatars must not exceed 256kB or be vertically longer than 400px. [/b]

Your avatar at $DBURL has been found to exceed these rules. As such, it has been automatically reset. You are welcome to reinstate your avatar once it has been resized down to an acceptable size.");
			
			
		}
	}
}

/*
TODO: solve this properl for photoshop output images which prepend shit to the image file. skip it or strip it
if (!isset($Type)) {
	error('timeout');
}
*/
if (isset($Type)) {
	header('Content-type: image/'.$Type);
}
echo $Data;
?>
