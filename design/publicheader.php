<?
global $LoggedUser, $Languages, $SSL;
define('FOOTER_FILE',SERVER_ROOT.'/design/publicfooter.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><?=display_str($PageTitle)?></title>
	<meta http-equiv="X-UA-Compatible" content="chrome=1;IE=edge" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="favicon.ico" />
<? if ($Mobile) { ?>
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0, user-scalable=no;"/>
	<link href="<?=STATIC_SERVER ?>styles/mobile/style.css?v=<?=filemtime(SERVER_ROOT.'/static/mobile/style.css')?>" rel="stylesheet" type="text/css" />
<? } else { ?>
	<link href="<?=STATIC_SERVER ?>styles/public/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/public/style.css')?>" rel="stylesheet" type="text/css" />
<? } ?>
	<script src="<?=STATIC_SERVER?>functions/sizzle.js" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/script_start.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/script_start.js')?>" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/class_ajax.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/class_ajax.js')?>" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/class_cookie.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/class_cookie.js')?>" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/class_storage.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/class_storage.js')?>" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/global.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/global.js')?>" type="text/javascript"></script>
<? if ($Mobile) { ?>
	<script src="<?=STATIC_SERVER?>styles/mobile/style.js?v=<?=filemtime(SERVER_ROOT.'/static/mobile/style.js')?>" type="text/javascript"></script>
<? } 

?>
</head>
<body>
<div id="head">
<?=($SSL)?'<span>SSL</span>':''?>
<?
?>
</div>
<table id="maincontent">
	<tr>
		<td align="center" valign="middle">
			<div id="logo">
				<ul>
					<li><a href="index.php<?=($Lang != DEFAULT_LOCALE ? '?lang='.$Lang : '')?>">Home</a></li>
					<li><a href="login.php<?=($Lang != DEFAULT_LOCALE ? '?lang='.$Lang : '')?>">Login</a></li>
<? if (OPEN_REGISTRATION) { ?> 
					<li><a href="register.php<?=($Lang != DEFAULT_LOCALE ? '?lang='.$Lang : '')?>">Register</a></li>
				</ul>
<? } ?> 
			</div>
