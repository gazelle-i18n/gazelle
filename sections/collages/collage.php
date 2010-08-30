<?
//~~~~~~~~~~~ Main collage page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

function compare($X, $Y){
	return($Y['count'] - $X['count']);
}

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

$CollageID = $_GET['id'];
if(!is_number($CollageID)) { error(0); }

$Data = $Cache->get_value('collage_'.$CollageID);

if($Data) {
	$Data = unserialize($Data);
	list($K, list($Name, $Description, $CollageDataList, $TorrentList, $CommentList, $Deleted, $CollageCategoryID, $CreatorID)) = each($Data);
} else {
	$DB->query("SELECT Name, Description, UserID, Deleted, CategoryID, Locked, MaxGroups, MaxGroupsPerUser FROM collages WHERE ID='$CollageID'");
	if($DB->record_count() > 0) {
		list($Name, $Description, $CreatorID, $Deleted, $CollageCategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser) = $DB->next_record();
		$TorrentList='';
		$CollageList='';
	} else {
		$Deleted = '1';
	}
}

if($Deleted == '1') {
	header('Location: log.php?search=Collage+'.$CollageID);
	die();
}

if($CollageCategoryID == 0 && !check_perms('site_collages_delete')) {
	if(!check_perms('site_collages_personal') || $CreatorID!=$LoggedUser['ID']) {
		$Locked = true;
	}
}

show_header($Name,'browse,collage');
show_message();
// Build the data for the collage and the torrent list

if(!is_array($TorrentList)) {
	$DB->query("SELECT ct.GroupID,
			tg.WikiImage,
			tg.CategoryID,
			um.ID,
			um.Username
			FROM collages_torrents AS ct
			JOIN torrents_group AS tg ON tg.ID=ct.GroupID
			LEFT JOIN users_main AS um ON um.ID=ct.UserID
			WHERE ct.CollageID='$CollageID'
			ORDER BY ct.Sort");
	
	$GroupIDs = $DB->collect('GroupID');
	$CollageDataList=$DB->to_array('GroupID', MYSQLI_ASSOC);
	if(count($GroupIDs)>0) {
		$TorrentList = get_groups($GroupIDs);
		$TorrentList = $TorrentList['matches'];
	} else {
		$TorrentList = array();
	}
}

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = array();
$TorrentTable = '';

$NumGroups = 0;
$NumGroupsByUser = 0;
$Artists = array();
$Tags = array();
$Users = array();
$Number = 0;

foreach ($TorrentList as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TagList, $ReleaseType, $Torrents, $GroupArtists) = array_values($Group);
	list($GroupID2, $Image, $GroupCategoryID, $UserID, $Username) = array_values($CollageDataList[$GroupID]);
	
	// Handle stats and stuff
	$Number++;
	$NumGroups++;
	if($UserID == $LoggedUser['ID']) {
		$NumGroupsByUser++;
	}
	
	if($GroupArtists) {
		foreach($GroupArtists as $Artist) {
			if(!isset($Artists[$Artist['id']])) {
				$Artists[$Artist['id']] = array('name'=>$Artist['name'], 'count'=>1);
			} else {
				$Artists[$Artist['id']]['count']++;
			}
		}
	}
	
	if($Username) {
		if(!isset($Users[$UserID])) {
			$Users[$UserID] = array('name'=>$Username, 'count'=>1);
		} else {
			$Users[$UserID]['count']++;
		}
	}
	
	$TagList = explode(' ',str_replace('_','.',$TagList));

	$TorrentTags = array();
	foreach($TagList as $Tag) {
		if(!isset($Tags[$Tag])) {
			$Tags[$Tag] = array('name'=>$Tag, 'count'=>1);
		} else {
			$Tags[$Tag]['count']++;
		}
		$TorrentTags[]='<a href="torrents.php?taglist='.$Tag.'">'.$Tag.'</a>';
	}
	$PrimaryTag = $TagList[0];
	$TorrentTags = implode(', ', $TorrentTags);
	$TorrentTags='<br /><div class="tags">'.$TorrentTags.'</div>';

	$DisplayName = $Number.' - ';
	if(count($GroupArtists)>0) {
		$DisplayName .= display_artists(array('1'=>$GroupArtists));
	}
	$DisplayName .= '<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName = $DisplayName. ' ['. $GroupYear .']';}
	
	// Start an output buffer, so we can store this output in $TorrentTable
	ob_start();
	if(count($Torrents)>1 || $GroupCategoryID==1) {
			 // Grouped torrents
?>
			<tr class="group discog" id="group_<?=$GroupID?>">
				<td class="center">
					<div title="View" id="showimg_<?=$GroupID?>" class="show_torrents">
						<a href="#" class="show_torrents_link" onclick="$('.groupid_<?=$GroupID?>').toggle(); return false;"></a>
					</div>
				</td>
				<td class="center">
					<div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>"></div>
				</td>
				<td colspan="5">
					<strong><?=$DisplayName?></strong>
					<?=$TorrentTags?>
				</td>
			</tr>
<?
		$LastRemasterYear = '-';
		$LastRemasterTitle = '';
		$LastRemasterRecordLabel = '';
		$LastRemasterCatalogueNumber = '';
		
		foreach ($Torrents as $TorrentID => $Torrent) {
			
			if($Torrent['RemasterTitle'] != $LastRemasterTitle || $Torrent['RemasterYear'] != $LastRemasterYear ||
			$Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel || $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber) {
				if($Torrent['RemasterTitle']  || $Torrent['RemasterYear'] || $Torrent['RemasterRecordLabel'] || $Torrent['RemasterCatalogueNumber']) {
					
					$RemasterName = $Torrent['RemasterYear'];
					$AddExtra = " - ";
					if($Torrent['RemasterRecordLabel']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterRecordLabel']); $AddExtra=' / '; }
					if($Torrent['RemasterCatalogueNumber']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterCatalogueNumber']); $AddExtra=' / '; }
					if($Torrent['RemasterTitle']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterTitle']); $AddExtra=' / '; }
					
?>
	<tr class="group_torrent groupid_<?=$GroupID?><? if(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; } ?>">
		<td colspan="7" class="edition_info"><strong><?=$RemasterName?></strong></td>
	</tr>
<?
				} else {
					$MasterName = "Original Release";
					$AddExtra = " / ";
					if($GroupRecordLabel) { $MasterName .= $AddExtra.$GroupRecordLabel; $AddExtra=' / '; }
					if($GroupCatalogueNumber) { $MasterName .= $AddExtra.$GroupCatalogueNumber; $AddExtra=' / '; }
?>
	<tr class="group_torrent groupid_<?=$GroupID?><? if (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; }?>">
		<td colspan="7" class="edition_info"><strong><?=$MasterName?></strong></td>
	</tr>
<?
				}
			}
			$LastRemasterTitle = $Torrent['RemasterTitle'];
			$LastRemasterYear = $Torrent['RemasterYear'];
			$LastRemasterRecordLabel = $Torrent['RemasterRecordLabel'];
			$LastRemasterCatalogueNumber = $Torrent['RemasterCatalogueNumber'];
?>
<tr class="group_torrent groupid_<?=$GroupID?><? if(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; } ?>">
		<td colspan="3">
			<span>
				[<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>]
			</span>
			&nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=torrent_info($Torrent)?></a>
		</td>
		<td class="nobr"><?=get_size($Torrent['Size'])?></td>
		<td><?=number_format((int)$Torrent['Snatched'])?></td>
		<td<?=((int)$Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format((int)$Torrent['Seeders'])?></td>
		<td><?=number_format((int)$Torrent['Leechers'])?></td>
	</tr>
<?
		}
	} else {
		// Viewing a type that does not require grouping
		
		list($TorrentID, $Torrent) = each($Torrents);
		
		$DisplayName = '<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
		
		if(!empty($Torrent['FreeTorrent'])) {
			$DisplayName .=' <strong>Freeleech!</strong>'; 
		}
?>
	<tr class="torrent" id="group_<?=$GroupID?>">
		<td></td>
		<td class="center">
			<div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>">
			</div>
		</td>
		<td>
			<span>
				[<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
				| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a>]
			</span>
			<strong><?=$DisplayName?></strong>
			<?=$TorrentTags?>
		</td>
		<td class="nobr"><?=get_size($Torrent['Size'])?></td>
		<td><?=number_format((int)$Torrent['Snatched'])?></td>
		<td<?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format((int)$Torrent['Seeders'])?></td>
		<td><?=number_format((int)$Torrent['Leechers'])?></td>
	</tr>
<?
	}
	$TorrentTable.=ob_get_clean();
	
	// Album art
	
	ob_start();
	
	$DisplayName = '';
	if(!empty($GroupArtists)) {
		$DisplayName.= display_artists(array('1'=>$GroupArtists), false);
	}
	$DisplayName .= $GroupName;
	if($GroupYear>0) { $DisplayName = $DisplayName. ' ['. $GroupYear .']';}
?>
		<td>
			<a href="#group_<?=$GroupID?>">
<?	if($Image) { ?>
				<img src="<?=$Image?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?>" width="117" />
<?	} else { ?>
				<div style="width:107px;padding:5px"><?=$DisplayName?></div>
<?	} ?>
			</a>
		</td>
<?
	$Collage[]=ob_get_clean();
	
}

if(($MaxGroups>0 && $NumGroups>=$MaxGroups)  || ($MaxGroupsPerUser>0 && $NumGroupsByUser>=$MaxGroupsPerUser)) {
	$Locked = true;
}

?>
<div class="thin">
	<h2><?=$Name?></h2>
	<div class="linkbox">
		<a href="collages.php">[List of collages]</a> 
<? if (check_perms('site_collages_create')) { ?>
		<a href="collages.php?action=new">[New collage]</a> 
<? } ?>
<? if (check_perms('site_edit_wiki') && !$Locked) { ?>
		<a href="collages.php?action=edit&amp;collageid=<?=$CollageID?>">[Edit description]</a> 
<? } ?>
<? if (check_perms('site_collages_manage') && !$Locked) { ?>
		<a href="collages.php?action=manage&amp;collageid=<?=$CollageID?>">[Manage torrents]</a> 
<? } ?>
	<a href="reports.php?action=report&amp;type=collage&amp;id=<?=$CollageID?>">[Report Collage]</a>
<? if (check_perms('site_collages_delete') || $CreatorID == $LoggedUser['ID']) { ?>
		<a href="collages.php?action=delete&amp;collageid=<?=$CollageID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" onclick="return confirm('Are you sure you want to delete this collage?.');">[Delete]</a> 
<? } ?>
	</div>
	<div class="sidebar">
		<div class="box">
			<div class="head"><strong>Category</strong></div>
			<div class="pad"><a href="collages.php?action=search&amp;cats[<?=(int)$CollageCategoryID?>]=1"><?=$CollageCats[(int)$CollageCategoryID]?></a></div>
		</div>
		<div class="box">
			<div class="head"><strong>Description</strong></div>
			<div class="pad"><?=$Text->full_format($Description)?></div>
		</div>
<?

?>
		<div class="box">
			<div class="head"><strong>Stats</strong></div>
			<ul class="stats nobullet">
				<li>Torrents: <?=$NumGroups?></li>
<? if(count($Artists) >0) { ?>	<li>Artists: <?=count($Artists)?></li> <? } ?>
				<li>Built by <?=count($Users)?> user<?=(count($Users)>1) ? 's' : ''?></li>
			</ul>
		</div>
		<div class="box">
			<div class="head"><strong>Top tags</strong></div>
			<div class="pad">
				<ol style="padding-left:5px;">
<?
uasort($Tags, 'compare');
$i = 0;
foreach ($Tags as $TagName => $Tag) {
	$i++;
	if($i>5) { break; }
?>
					<li><a href="collages.php?action=search&amp;tags=<?=$TagName?>"><?=$TagName?></a> (<?=$Tag['count']?>)</li>
<?
}
?>
				</ol>
			</div>
		</div>
<? if(!empty($Artists)) { ?>		
		<div class="box">
			<div class="head"><strong>Top artists</strong></div>
			<div class="pad">
				<ol style="padding-left:5px;">
<?
uasort($Artists, 'compare');
$i = 0;
foreach ($Artists as $ID => $Artist) {
	$i++;
	if($i>10) { break; }
?>
					<li><a href="artist.php?id=<?=$ID?>"><?=$Artist['name']?></a> (<?=$Artist['count']?>)</li>
<?
}
?>
				</ol>
			</div>
		</div>
<? } ?>
		<div class="box">
			<div class="head"><strong>Top contributors</strong></div>
			<div class="pad">
				<ol style="padding-left:5px;">
<?
uasort($Users, 'compare');
$i = 0;
foreach ($Users as $ID => $User) {
	$i++;
	if($i>5) { break; }
?>
					<li><?=format_username($ID, $User['name'])?> (<?=$User['count']?>)</li>
<?
}
?>
				</ol>
			
			</div>
		</div>
<? if(check_perms('site_collages_manage') && !$Locked) { ?>
		<div class="box">
			<div class="head"><strong>Add torrent</strong></div>
			<div class="pad">
				<form action="collages.php" method="post">
					<input type="hidden" name="action" value="add_torrent" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="collageid" value="<?=$CollageID?>" />
					<input type="text" size="20" name="url" />
					<input type="submit" value="+" />
					<br />
					<i>Enter the URL of a torrent on the site.</i>
				</form>
			</div>
		</div>
<? } ?>
		<h3>Comments</h3>
<?
if(empty($CommentList)) {
	$DB->query("SELECT 
		cc.ID, 
		cc.Body, 
		cc.UserID, 
		um.Username,
		cc.Time 
		FROM collages_comments AS cc
		LEFT JOIN users_main AS um ON um.ID=cc.UserID
		WHERE CollageID='$CollageID' 
		ORDER BY ID DESC LIMIT 15");
	$CommentList = $DB->to_array();	
}
foreach ($CommentList as $Comment) {
	list($CommentID, $Body, $UserID, $Username, $CommentTime) = $Comment;
?>
		<div class="box">
			<div class="head">By <?=format_username($UserID, $Username) ?> <?=time_diff($CommentTime) ?> <a href="reports.php?action=report&amp;type=collages_comment&amp;id=<?=$CommentID?>">[Report Comment]</a></div>
			<div class="pad"><?=$Text->full_format($Body)?></div>
		</div>
<?
}
?>
		<div class="box pad">
			<a href="collages.php?action=comments&amp;collageid=<?=$CollageID?>">All comments</a>
		</div>
<?
if(!$LoggedUser['DisablePosting']) {
?>
		<div class="box">
			<div class="head"><strong>Add comment</strong></div>
			<form action="collages.php" method="post">
				<input type="hidden" name="action" value="add_comment" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="collageid" value="<?=$CollageID?>" />
				<div class="pad">
					<textarea name="body" cols="24" rows="5"></textarea>
					<br />
					<input type="submit" value="Add comment" />
				</div>
			</form>
		</div>
<?
}
?>
	</div>
	<div class="main_column">	
<?	
if(!$LoggedUser['HideCollage']) { ?>
		<table class="collage" id="collage_table" cellpadding="0" cellspacing="0" border="0">
			<tr>
<?
	$x = 0;
	foreach($Collage as $Group) {
		echo $Group;
		$x++;
		if($x%5==0) {
?>
			</tr>
			<tr>
<?
	}
}
	if($x%5!=0) { // Padding
?>
				<td colspan="<?=7-($x%7)?>"> </td>
<? 	} ?>
			
			</tr>
		</table>
<? } ?>
		<table class="torrent_table" id="discog_table">
			<tr class="colhead_dark">
				<td><!-- expand/collapse --></td>
				<td><!-- Category --></td>
				<td width="70%"><strong>Torrents</strong></td>
				<td>Size</td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" alt="Snatches" title="Snatches" /></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/seeders.png" alt="Seeders" title="Seeders" /></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/leechers.png" alt="Leechers" title="Leechers" /></td>
			</tr>
<?=$TorrentTable?>
		</table>
	</div>
</div>
<?
show_footer();

$Cache->cache_value('collage_'.$CollageID, serialize(array(array($Name, $Description, $CollageDataList, $TorrentList, $CommentList, $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser))), 3600);
?>
