<?

function get_group_info($GroupID, $Return = true, $RevisionID = 0) {
	global $Cache, $DB;
	if(!$RevisionID) {
		$TorrentCache=$Cache->get_value('torrents_details_'.$GroupID);
	}
	
	//TODO: Remove LogInDB at a much later date.
	if($RevisionID || !is_array($TorrentCache) || !isset($TorrentCache[1][0]['LogInDB'])) {
		// Fetch the group details

		$SQL = "SELECT ";

		if(!$RevisionID) {
			$SQL.="
				g.WikiBody,
				g.WikiImage, ";
		} else {
			$SQL.="
				w.Body,
				w.Image, ";
		}

		$SQL .= "
			g.ID,
			g.Name,
			g.Year,
			g.RecordLabel,
			g.CatalogueNumber,
			g.ReleaseType,
			g.CategoryID,
			g.Time,
			GROUP_CONCAT(DISTINCT tags.Name SEPARATOR '|'),
			GROUP_CONCAT(DISTINCT tags.ID SEPARATOR '|'),
			GROUP_CONCAT(tags.UserID SEPARATOR '|'),
			GROUP_CONCAT(tt.PositiveVotes SEPARATOR '|'),
			GROUP_CONCAT(tt.NegativeVotes SEPARATOR '|')
			FROM torrents_group AS g
			LEFT JOIN torrents_tags AS tt ON tt.GroupID=g.ID
			LEFT JOIN tags ON tags.ID=tt.TagID";

		if($RevisionID) {
			$SQL.="
				LEFT JOIN wiki_torrents AS w ON w.PageID='".db_string($GroupID)."' AND w.RevisionID='".db_string($RevisionID)."' ";
		}

		$SQL .="
			WHERE g.ID='".db_string($GroupID)."'
			GROUP BY NULL";

		$DB->query($SQL);
		$TorrentDetails=$DB->to_array();

		// Fetch the individual torrents

		$DB->query("
			SELECT
			t.ID,
			t.Media,
			t.Format,
			t.Encoding,
			t.Remastered,
			t.RemasterYear,
			t.RemasterTitle,
			t.RemasterRecordLabel,
			t.RemasterCatalogueNumber,
			t.Scene,
			t.HasLog,
			t.HasCue,
			t.LogScore,
			t.FileCount,
			t.Size,
			t.Seeders,
			t.Leechers,
			t.Snatched,
			t.FreeTorrent,
			t.Time,
			t.Description,
			t.FileList,
			t.FilePath,
			t.UserID,
			um.Username,
			t.last_action,
			tbt.TorrentID,
			tbf.TorrentID,
			t.LastReseedRequest,
			tln.TorrentID AS LogInDB
			FROM torrents AS t
			LEFT JOIN users_main AS um ON um.ID=t.UserID
			LEFT JOIN torrents_bad_tags AS tbt ON tbt.TorrentID=t.ID
			LEFT JOIN torrents_bad_folders AS tbf on tbf.TorrentID=t.ID
			LEFT JOIN torrents_logs_new AS tln ON tln.TorrentID=t.ID
			WHERE t.GroupID='".db_string($GroupID)."'
			AND flags != 1
			GROUP BY t.ID
			ORDER BY t.Remastered ASC, (t.RemasterYear <> 0) DESC, t.RemasterYear ASC, t.RemasterTitle ASC, t.RemasterRecordLabel ASC, t.RemasterCatalogueNumber ASC, t.Format DESC, t.Encoding, t.ID");

		$TorrentList = $DB->to_array();
		if(count($TorrentList) == 0) {
			//error(404,'','','',true);
			if(isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
				error_message("Cannot find the torrent with the ID ".$_GET['torrentid']);
				header("Location: log.php?search=Torrent+".$_GET['torrentid']);
			} else {
				error(404);
			}
			die();
		}
		if(in_array(0, $DB->collect('Seeders'))) {
			$CacheTime = 600;
		} else {
			$CacheTime = 3600;
		}
		// Store it all in cache
		if(!$RevisionID) {
			$Cache->cache_value('torrents_details_'.$GroupID,array($TorrentDetails,$TorrentList),$CacheTime);
		}
	} else { // If we're reading from cache
		$TorrentDetails=$TorrentCache[0];
		$TorrentList=$TorrentCache[1];
	}

	if($Return) {
		return array($TorrentDetails,$TorrentList);
	}
}

//Check if a givin string van be validated as a torrenthash
function is_valid_torrenthash($Str) {
	//6C19FF4C 6C1DD265 3B25832C 0F6228B2 52D743D5
	$Str = str_replace(' ', '', $Str);
	if(preg_match('/^[0-9a-fA-F]{40}$/', $Str))
		return $Str;
	return false;
}

