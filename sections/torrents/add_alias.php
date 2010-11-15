<?
authorize();

$UserID = $LoggedUser['ID'];
$GroupID = db_string($_POST['groupid']);
$Importances = $_POST['importance'];
$AliasNames = $_POST['aliasname'];

if(!is_number($GroupID) || !$GroupID) {
	error(0);
}
/*if(!check_perms('torrents_edit')) {
	error(403);
}*/

$Changed = false;

for($i = 0; $i < count($AliasNames); $i++) {
	$AliasName = trim(db_string($AliasNames[$i]));
	$Importance = $Importances[$i];
	
	if($Importance!='1' && $Importance!='2' && $Importance!='3') {
		break;
	}
	
	if(strlen($AliasName) > 0) {
		$DB->query("SELECT AliasID, ArtistID, Redirect, Name FROM artists_alias WHERE Name LIKE '$AliasName'");
		if($DB->record_count() == 0) {
			$DB->query("INSERT INTO artists_group (Name) VALUES ('$AliasName')");
			$ArtistID = $DB->inserted_id();
			$DB->query("INSERT INTO artists_alias (ArtistID, Name) VALUES ('$ArtistID', '$AliasName')");
			$AliasID = $DB->inserted_id();
		} else {
			list($AliasID, $ArtistID, $Redirect, $AliasName) = $DB->next_record();
			if($Redirect) {
				$AliasID = $Redirect;
			}
		}
		
		$DB->query("SELECT Name FROM torrents_group WHERE ID=".$GroupID);
		list($GroupName) = $DB->next_record();

		$DB->query("SELECT Name FROM artists_group WHERE ArtistID=".$ArtistID);
		list($ArtistName) = $DB->next_record();
		
		$DB->query("SELECT AliasID FROM torrents_artists WHERE GroupID='$GroupID' AND ArtistID='$ArtistID'");
		
		if($DB->record_count() == 0) {
			$Changed = true;
			
			$DB->query("INSERT INTO torrents_artists 
			(GroupID, ArtistID, AliasID, Importance, UserID) VALUES 
			('$GroupID', '$ArtistID', '$AliasID', '$Importance', '$UserID')");
			
			$DB->query("INSERT INTO torrents_group (ID, NumArtists) 
					SELECT ta.GroupID, COUNT(ta.ArtistID) 
					FROM torrents_artists AS ta 
					WHERE ta.GroupID='$GroupID' 
					AND ta.Importance='1'
					GROUP BY ta.GroupID 
				ON DUPLICATE KEY UPDATE 
				NumArtists=VALUES(NumArtists);");
			
			write_log("Artist ".$ArtistID." (".$ArtistName.") was added to the group ".$GroupID." (".$GroupName.") by user ".$LoggedUser['ID']." (".$LoggedUser['Username'].")");
		} else {
			list($OldAliasID) = $DB->next_record();
			if($OldAliasID == 0) {
				$Changed = true;
				$DB->query('UPDATE torrents_artists SET AliasID='.$AliasID.' WHERE GroupID='.$GroupID.' AND ArtistID='.$ArtistID);
			}
		}
	}
}

if($Changed) {
	$Cache->delete_value('torrents_details_'.$GroupID);
	$Cache->delete_value('groups_artists_'.$GroupID); // Delete group artist cache
	update_hash($GroupID);
}


header('Location: '.$_SERVER['HTTP_REFERER']);
?>
