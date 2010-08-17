<? show_header('Login'); ?>
	<span id="no-cookies" class="hidden warning">e<br /><br /></span>
	<noscript><span class="warning">e</span><br /><br /></noscript> 
<?
if(strtotime($BannedUntil)<time() && !$BanID) {
?>
	<form id="loginform" method="post" action="login.php">
<?
//</strip>
if(!empty($_REQUEST['lang'])) { ?>
		<input type="hidden" name="lang" value="<?=$_REQUEST['lang']?>" />
<? }
//</strip>
	if(!empty($BannedUntil) && $BannedUntil != '0000-00-00 00:00:00') {
		$DB->query("UPDATE login_attempts SET BannedUntil='0000-00-00 00:00:00', Attempts='0' WHERE ID='".db_string($AttemptID)."'");
		$Attempts = 0;
	}
	if(isset($Err)) {
?>
	<span class="warning"><?=$Err?><br /><br /></span>
<? } ?>
<? if ($Attempts > 0) { ?>
	e <span class="info"><?=(6-$Attempts)?></span> e.<br /><br />
	<strong>e:</strong> e<br /><br />
<? } ?>
	<table>
		<tr>
			<td>e&nbsp;</td>
			<td colspan="2"><input type="text" name="username" id="username" class="inputtext" required="required" maxlength="20" pattern="[A-Za-z0-9_?]{1,20}" autofocus="autofocus" /></td>
		</tr>
		<tr>
			<td>e&nbsp;</td>
			<td colspan="2"><input type="password" name="password" id="password" class="inputtext" required="required" maxlength="40" pattern=".{6,40}" /></td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" id="keeplogged" name="keeplogged" value="1"<? if(isset($_REQUEST['keeplogged']) && $_REQUEST['keeplogged']) { ?> checked="checked"<? } ?> />
				<label for="keeplogged">e</label>
			</td>
			<td><input type="submit" name="login" value="e" class="submit" /></td>
		</tr>
	</table>
	</form>
<?
} else {
	if($BanID) {
?>
	<span class="warning">e.</span>
<? } else { ?>
	<span class="warning">e <?=time_diff($BannedUntil)?>.</span>
<?
	}
}

if ($Attempts > 0) {
?>
	<br /><br />
	e? <a href="login.php?act=recover">e</a>
<? } ?>
<script type="text/javascript">
cookie.set('cookie_test',1,1);
if (cookie.get('cookie_test') != null) {
	cookie.del('cookie_test');
} else {
	$('#no-cookies').show();
}
</script>
<? show_footer(); ?>
