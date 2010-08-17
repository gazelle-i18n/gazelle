<?
show_header('Recover Password','validate');
echo $Validate->GenerateJS('recoverform');
?>
<form name="recoverform" id="recoverform" method="post" action="" onsubmit="return formVal();">
	<div style="width:320px;">
		<font class="titletext">e - e 1</font><br /><br />
<?
if(empty($Sent) || (!empty($Sent) && $Sent!=1)) {
	if(!empty($Err)) {
?>
		<font color="red"><strong><?=$Err ?></strong></font><br /><br />
<?	} ?>
	e<br /><br />
		<table cellpadding="2" cellspacing="1" border="0" align="center">
			<tr valign="top">
				<td align="right">e&nbsp;</td>
				<td align="left"><input type="text" name="email" id="email" class="inputtext" /></td>
			</tr>
			<tr>
				<td colspan="2" align="right"><input type="submit" name="reset" value="e" class="submit" /></td>
			</tr>
		</table>
<? } else { ?>
	e
<? } ?>
	</div>
</form>
<?
show_footer();
?>
