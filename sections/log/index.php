<?
enforce_login();

if (!defined('LOG_ENTRIES_PER_PAGE')) {
	define('LOG_ENTRIES_PER_PAGE', 25);
}
list($Page,$Limit) = page_limit(LOG_ENTRIES_PER_PAGE);

if(!empty($_GET['search'])) {
	$Search = db_string($_GET['search']);
} else {
	$Search = false;
}
$Words = explode(' ', $Search);
$sql = "SELECT
	SQL_CALC_FOUND_ROWS 
	Message,
	Time
	FROM log ";
if($Search) {
	$sql .= "WHERE Message LIKE '%";
	$sql .= implode("%' AND Message LIKE '%", $Words);
	$sql .= "%' ";
}
if(!check_perms('site_view_full_log')) {
	if($Search) {
		$sql.=" AND "; 
	} else {
		$sql.=" WHERE ";
	}
	$sql .= " Time>'".time_minus(3600*24*28)."' ";
}

$sql .= "ORDER BY ID DESC LIMIT $Limit";

show_header("Site log");

$Log = $DB->query($sql);
$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$DB->set_query_id($Log);
?>
<div class="thin">
	<h2>Site log</h2>
	<div>
		<form action="" method="get">
			<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
				<tr>
					<td class="label"><strong>Search for:</strong></td>
					<td>
						<input type="text" name="search" size="60"<? if (!empty($_GET['search'])) { echo ' value="'.display_str($_GET['search']).'"'; } ?> />
						&nbsp;
						<input type="submit" value="Search log" />
					</td>
				</tr>
			</table>	
		</form>
	</div>
	
	
	
	<div class="linkbox">
<?
$Pages=get_pages($Page,$Results,LOG_ENTRIES_PER_PAGE,9);
echo $Pages;
?>
	</div>
	
	<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
		<tr class="colhead">
			<td style="width: 180px;"><strong>Time</strong></td>
			<td><strong>Message</strong></td>
		</tr>

<?
if($DB->record_count() == 0) {
	echo '<tr class="nobr"><td colspan="2">Nothing found!</td></tr>';
}
$Row = 'a';
$Usernames = array();
while(list($Message, $LogTime) = $DB->next_record()) {
	$MessageParts = explode(" ", $Message);
	$Message = "";
	$Color = $Colon = false;
	for ($i = 0; $i < sizeof($MessageParts); $i++) {
		if (strpos($MessageParts[$i], 'https://'.SSL_SITE_URL) === 0 || strpos($MessageParts[$i], 'http://'.NONSSL_SITE_URL) === 0) {
			$MessageParts[$i] = '<a href="'.$MessageParts[$i].'">'.$MessageParts[$i].'</a>';
		}
		switch ($MessageParts[$i]) {
			case "Torrent":
				$TorrentID = $MessageParts[++$i];
				if (is_numeric($TorrentID)) {
					$Message = $Message.' Torrent <a href="torrents.php?torrentid='.$TorrentID.'"> '.$TorrentID.'</a>';
				} else {
					$Message = $Message.' Torrent '.$TorrentID;
				}
				break;
			case "Request":
				$RequestID = $MessageParts[++$i];
				if (is_numeric($RequestID)) {
					$Message = $Message.' Request <a href="requests.php?action=view&id='.$RequestID.'"> '.$RequestID.'</a>';
				} else {
					$Message = $Message.' Request '.$RequestID;
				}
				break;
			case "Artist":
				$ArtistID = $MessageParts[++$i];
				if (is_numeric($ArtistID)) {
					$Message = $Message.' Arist <a href="artist.php?id='.$ArtistID.'"> '.$ArtistID.'</a>';
				} else {
					$Message = $Message.' Artist '.$ArtistID;
				}
				break;
			case "group":
				$GroupID = $MessageParts[++$i];
				$Message = $Message.' group <a href="torrents.php?id='.$GroupID.'"> '.$GroupID.'</a>';
				break;
			case "torrent":
				$TorrentID = substr($MessageParts[++$i], 0, strlen($MessageParts[$i]) - 1);
				$Message = $Message.' torrent <a href="torrents.php?torrentid='.$TorrentID.'"> '.$TorrentID.'</a>,';
				break;
			case "by":
				$UserID = 0;
				$User = "";
				$URL = "";
				if ($MessageParts[$i + 1] == "user") {
					$i++;
					if (is_numeric($MessageParts[$i + 1])) {
						$UserID = $MessageParts[++$i];
					}
					$URL = "user ".$UserID." ".'<a href="user.php?id='.$UserID.'">'.$MessageParts[++$i]."</a>";
				} else {
					$User = $MessageParts[++$i];
					if(substr($User,-1) == ':') {
						$User = substr($User, 0, -1);
						$Colon = true;
					}
					if(!isset($Usernames[$User])) {
						$DB->query("SELECT ID FROM users_main WHERE Username = '".$User."'");
						list($UserID) = $DB->next_record();
						$Usernames[$User] = $UserID;
					} else {
						$UserID = $Usernames[$User];
					}
					$DB->set_query_id($Log);
					$URL = '<a href="user.php?id='.$UserID.'">'.$User."</a>".($Colon?':':'');
				}
				$Message = $Message." by ".$URL;
				break;
			case "uploaded":
				if ($Color === false) {
					$Color = 'green';
				}
				$Message = $Message." ".$MessageParts[$i];
				break;
			case "deleted":
				if ($Color === false || $Color === 'green') {
					$Color = 'red';
				}
				$Message = $Message." ".$MessageParts[$i];
				break;
			case "edited":
				if ($Color === false) {
					$Color = 'blue';
				}
				$Message = $Message." ".$MessageParts[$i];
				break;
			case "un-filled":
				if ($Color === false) {
					$Color = '';
				}
				$Message = $Message." ".$MessageParts[$i];
				break;
			default:
				$Message = $Message." ".$MessageParts[$i];
		}
	}
	$Row = ($Row == 'a') ? 'b' : 'a';
?>
		<tr class="row<?=$Row?>">
			<td class="nobr">
				<?=time_diff($LogTime)?>
			</td>
			<td>
				<span<? if($Color) { ?> style="color: <?=$Color ?>;"<? } ?>><?=$Message?></span>
			</td>
		</tr>
<?
}
?>
	</table>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>
<?
show_footer() ?>
