<?

//******************************************************************************//
//----------------- Take request -----------------------------------------------//

authorize();


if($_POST['action'] != "takenew" &&  $_POST['action'] != "takeedit") {
	error(0);
}

$NewRequest = ($_POST['action'] == "takenew");

if(!$NewRequest) {
	$ReturnEdit = true;
}

if($NewRequest) {
	if(!check_perms('site_submit_requests') || $LoggedUser['BytesUploaded'] < 250*1024*1024){
		error(403);
	}
} else {
	$RequestID = $_POST['requestid'];
	if(!is_number($RequestID)) {
		error(0);
	}
	
	$Request = get_requests(array($RequestID));
	$Request = $Request['matches'][$RequestID];
	if(empty($Request)) {
		error(404);
	}
	
	list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Year, $Image, $Description, $CatalogueNumber, $ReleaseType,
	$BitrateList, $FormatList, $MediaList, $LogCue, $FillerID, $FillerName, $TorrentID, $TimeFilled) = $Request;
	$VoteArray = get_votes_array($RequestID);
	$VoteCount = count($VoteArray['Voters']);
	
	$IsFilled = !empty($TorrentID);

	$CategoryName = $Categories[$CategoryID - 1];
	
	$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0) || ($CategoryName == "Music" && $Year == 0)));
	$CanEdit = ((!$IsFilled && $LoggedUser['ID'] == $RequestorID && $VoteCount < 2) || $ProjectCanEdit || check_perms('site_moderate_requests'));
	
	if(!$CanEdit) {
		error(403);
	}
}

// Validate
if(empty($_POST['type'])) {
	error(0);
}

$CategoryName = $_POST['type'];
$CategoryID = (array_search($CategoryName, $Categories) + 1);

if(empty($CategoryID)) {
	error(0);
}

if(empty($_POST['title'])) {
	$Err = "You forgot to enter the title!";
} else {
	$Title = trim($_POST['title']);
}

if(empty($_POST['tags'])) {
	$Err = "You forgot to enter any tags!";
} else {
	$Tags = trim($_POST['tags']);
}

if($NewRequest) {
	if(empty($_POST['amount'])) {
		$Err = "You forgot to enter any bounty!";
	} else {
		$Bounty = trim($_POST['amount']);
		if(!is_number($Bounty)) {
			$Err = "Your entered bounty is not a number";
		} elseif($Bounty < 100*1024*1024) {
			$Err = "Minumum bounty is 100MB";
		}
		$Bytes = $Bounty; //From MB to B
	}
}

if(empty($_POST['image'])) {
	$Image = "";
} else {
	if(preg_match("/".IMAGE_REGEX."/", trim($_POST['image'])) > 0) {
			$Image = trim($_POST['image']);
	} else {
		$Err = $_POST['image']." does not appear to be a valid link to an image.";
	}
}

if(empty($_POST['description'])) {
	$Err = "You forgot to enter any description!";
} else {
	$Description = trim($_POST['description']);
}

if($CategoryName == "Music") {
	if(empty($_POST['artists'])) {
		$Err = "You didn't enter any artists";
	} else {
		$Artists = $_POST['artists'];
		$Importance = $_POST['importance'];
	}
	
	if(!is_number($_POST['releasetype']) || !array_key_exists($_POST['releasetype'], $ReleaseTypes)) {
		$Err = "Please pick a release type";
	}

	$ReleaseType = $_POST['releasetype'];
	
	if(empty($_POST['all_formats']) && count($_POST['formats']) != count($Formats)) {
		$FormatArray = $_POST['formats'];
		if(count($FormatArray) < 1) {
			$Err = "You must require at least one format";
		}
	} else {
		$AllFormats = true;
	}
	
	if(empty($_POST['all_bitrates']) && count($_POST['bitrates']) != count($Bitrates)) {
		$BitrateArray = $_POST['bitrates'];
		if(count($BitrateArray) < 1) {
			$Err = "You must require at least one bitrate";
		}
	} else {
		$AllBitrates = true;
	}
	
	if(empty($_POST['all_media']) && count($_POST['media']) != count($Media)) {
		$MediaArray = $_POST['media'];	
		if(count($MediaArray) < 1) {
			$Err = "You must require at least one type of media";
		}
	} else {
		$AllMedia = true;
	}
	
	//$Bitrates[1] = FLAC
	if(!empty($FormatArray) && in_array(1, $FormatArray)) {
		$NeedLog = empty($_POST['needlog']) ? false : true;
		if($NeedLog) {
			if($_POST['minlogscore']) {
				$MinLogScore = trim($_POST['minlogscore']);
			} else {
				$MinLogScore = 0;
			}
			if(!is_number($MinLogScore)) {
				$Err = "You've entered a minimum log score that isn't a number";
			}
		} 
		$NeedCue = empty($_POST['needcue']) ? false : true;
		//FLAC was picked, require either Lossless or 24 bit Lossless
		if(!$AllBitrates && !in_array(9, $BitrateArray) && !in_array(10, $BitrateArray)) {
			$Err = "You selected FLAC as a format but no possible bitrate to fill it (Lossless or 24 bit Lossless)";
		}

		if (($NeedCue || $NeedLog)) {
			if (!empty($_POST['all_media']) || $MediaArray[0] != 0) {
				$Err = "Only CD is allowed as media for FLAC Log/Cue Requests.";
			}
		}
	} else {
		$NeedLog = false;
		$NeedCue = false;
		$MinLogScore = false; 
	}
	
	//Not required
	if(!empty($_POST['editioninfo'])) {
		$EditionInfo = trim($_POST['editioninfo']);
	} else {
		$EditionInfo = "";
	}
	if(!empty($_POST['cataloguenumber'])) {
		$CatalogueNumber = trim($_POST['cataloguenumber']);
	} else {
		$CatalogueNumber = "";
	}
}

if($CategoryName == "Music" || $CategoryName == "Audiobooks" || $CategoryName == "Comedy") {
	if(empty($_POST['year'])) {
		$Err = "You forgot to enter the year!";
	} else {
		$Year = trim($_POST['year']);
		if(!is_number($Year)) {
			$Err = "Your entered year is not a number";
		}
	}
}

//For refilling on error
if($CategoryName == "Music") {
	$MainArtistCount = 0;
	$ArtistNames = array();
	$ArtistForm = array(
		1 => array(),
		2 => array(),
		3 => array()
	);
	for($i = 0, $il = count($Artists); $i < $il; $i++) {
		if(trim($Artists[$i]) != "") {
			if(!in_array($Artists[$i], $ArtistNames)) {
				$ArtistForm[$Importance[$i]][] = array('name' => trim($Artists[$i]));
				if($Importance[$i] == 1) {
					$MainArtistCount++;
				}
				$ArtistNames[] = trim($Artists[$i]);
			}
		}
	}
	if($MainArtistCount < 1) {
		$Err = "Please enter at least one main artist";
	}
	if(!isset($ArtistNames[0])) {
		unset($ArtistForm);
	}
}

if(!empty($Err)) {
	error_message($Err);
	$Div = $_POST['unit'] == 'mb' ? 1024*1024 : 1024*1024*1024;
	$Bounty /= $Div;
	include(SERVER_ROOT.'/sections/requests/new_edit.php');
	die();
}

//Databasify the input
if($CategoryName == "Music") {
	if(empty($AllBitrates)) {
		foreach($BitrateArray as $Index => $MasterIndex) {
			if(array_key_exists($Index, $Bitrates)) {
				$BitrateArray[$Index] = $Bitrates[$MasterIndex];
			} else {
				//Hax
				error(0);
			}
		}
		$BitrateList = implode("|", $BitrateArray);
	} else {
		$BitrateList = "Any";
	}

	if(empty($AllFormats)) {
		foreach($FormatArray as $Index => $MasterIndex) {
			if(array_key_exists($Index, $Formats)) {
				$FormatArray[$Index] = $Formats[$MasterIndex];
			} else {
				//Hax
				error(0);
			}
		}
		$FormatList = implode("|", $FormatArray);
	} else {
		$FormatList = "Any";
	}

	if(empty($AllMedia)) {
		foreach($MediaArray as $Index => $MasterIndex) {
			if(array_key_exists($Index, $Media)) {
				$MediaArray[$Index] = $Media[$MasterIndex];
			} else {
				//Hax
				error(0);
			}
		}
		$MediaList = implode("|", $MediaArray);
	} else {
		$MediaList = "Any";
	}
	
	$LogCue = "";
	if($NeedLog) {
		$LogCue .= "Log";
		if($MinLogScore > 0) {
			if($MinLogScore >= 100) {
				$LogCue .= " (100%)";
			} else {
				$LogCue .= " (>= ".$MinLogScore."%)";
			}
		}
	}
	if($NeedCue) {
		if($LogCue != "") {
			$LogCue .= " + Cue";
		} else {
			$LogCue = "Cue";
		}
	}
}

//Query time!
if($CategoryName == "Music") {
	if($NewRequest) {
		$DB->query("INSERT INTO requests (     
						UserID, TimeAdded, LastVote, CategoryID, Title, Year, Image, Description,
						CatalogueNumber, ReleaseType, BitrateList, FormatList, MediaList, LogCue, Visible)
					VALUES
						(".$LoggedUser['ID'].", '".sqltime()."', '".sqltime()."', ".$CategoryID.", '".db_string($Title)."', ".$Year.", '".db_string($Image)."', '".db_string($Description)."',
					 	'".db_string($CatalogueNumber)."', ".$ReleaseType.", '".$BitrateList."','".$FormatList."', '".$MediaList."', '".$LogCue."', '1')");
		
		$RequestID = $DB->inserted_id();
	} else {
		$DB->query("UPDATE requests 
					SET CategoryID = ".$CategoryID.",
						Title = '".db_string($Title)."', 
						Year = ".$Year.", 
						Image = '".db_string($Image)."',
						Description = '".db_string($Description)."',
						CatalogueNumber = '".db_string($CatalogueNumber)."',
						ReleaseType = ".$ReleaseType.",
						BitrateList = '".$BitrateList."',
						FormatList = '".$FormatList."',
						MediaList = '".$MediaList."',
						LogCue = '".$LogCue."'
					WHERE ID = ".$RequestID);
		
		//I almost didn't think of this, we need to be able to delete artists / tags
		$DB->query("SELECT ArtistID FROM requests_artists WHERE RequestID = ".$RequestID);
		$RequestArtists = $DB->to_array();
		foreach($RequestArtists as $RequestArtist) {
			$Cache->delete_value('artists_requests_'.$RequestArtist);
		}
		$DB->query("DELETE FROM requests_artists WHERE RequestID = ".$RequestID);
		$Cache->delete_value('request_artists_'.$RequestID);
	}
	
	/*
	 * Multiple Artists!
	 * For the multiple artists system, we have 3 steps:
	 * 1. See if each artist given already exists and if it does, grab the ID.
	 * 2. For each artist that didn't exist, create an artist.
	 * 3. Create a row in the requests_artists table for each artist, based on the ID. 
	 */


	foreach($ArtistForm as $Importance => $Artists) {
		foreach($Artists as $Num => $Artist) {
			//1. See if each artist given already exists and if it does, grab the ID.
			$DB->query("
				SELECT
				aa.ArtistID,
				aa.AliasID,
				aa.Redirect
				FROM artists_alias AS aa
				WHERE aa.Name LIKE '".db_string($Artist['name'])."'");
			
			if($DB->record_count() > 0){
				list($ArtistID, $AliasID, $Redirect) = $DB->next_record();
				if($Redirect) {
					$AliasID = $Redirect;
				}
				$ArtistForm[$Importance][$Num] = array('id' => $ArtistID, 'aliasid' => $AliasID, 'name' => $Artist['name']);
				$Cache->delete_value('artist_'.$ArtistID);
			} else {
				//2. For each artist that didn't exist, create an artist.
				$DB->query("INSERT INTO artists_group (Name) VALUES ('".db_string($Artist['name'])."')");
				$ArtistID = $DB->inserted_id();
	
				$Cache->increment('stats_artist_count');
	
				$DB->query("INSERT INTO artists_alias (ArtistID, Name) VALUES (".$ArtistID.", '".db_string($Artist['name'])."')");
				$AliasID = $DB->inserted_id();
	
				$ArtistForm[$Importance][$Num] = array('id' => $ArtistID, 'aliasid' => $AliasID, 'name' => $Artist['name']);
			}
		}
	}
	
	
	//3. Create a row in the requests_artists table for each artist, based on the ID. 
	foreach($ArtistForm as $Importance => $Artists) {
		foreach($Artists as $Num => $Artist) {
			$DB->query("INSERT IGNORE INTO requests_artists (RequestID, ArtistID, AliasID, Importance) VALUES (".$RequestID.", ".$Artist['id'].", ".$Artist['aliasid'].", '".$Importance."')");
			$Cache->increment('stats_album_count');
			$Cache->delete_value('artists_requests_'.$Artist['id']);
		}
	}
	
	//End Music only
	
} else {
	//Not a music request anymore, delete music only fields.
	if(!$NewRequest) {
		$DB->query("SELECT ArtistID FROM requests_artists WHERE RequestID = ".$RequestID);
		$OldArtists = $DB->collect('ArtistID');
		foreach($OldArtists as $ArtistID) {
			if(empty($ArtistID)) { continue; }
			//Get a count of how many groups or requests use the artist ID
			$DB->query("SELECT COUNT(ag.ArtistID)
						FROM artists_group as ag 
							LEFT JOIN requests_artists AS ra ON ag.ArtistID=ra.ArtistID 
						WHERE ra.ArtistID IS NOT NULL
							AND ag.ArtistID = '$ArtistID'");
			list($ReqCount) = $DB->next_record();
			$DB->query("SELECT COUNT(ag.ArtistID)
						FROM artists_group as ag 
							LEFT JOIN torrents_artists AS ta ON ag.ArtistID=ta.ArtistID 
						WHERE ta.ArtistID IS NOT NULL
							AND ag.ArtistID = '$ArtistID'");
			list($GroupCount) = $DB->next_record();
			if(($ReqCount + $GroupCount) == 0) {
				//The only group to use this artist
				delete_artist($ArtistID);
			} else {
				//Not the only group, still need to clear cache
				$Cache->delete_value('artist_'.$ArtistID);
				$Cache->delete_value('artists_requests_'.$ArtistID);
			}
		}
		$DB->query("DELETE FROM requests_artists WHERE RequestID = ".$RequestID);
	}

	if($CategoryName == "Audiobooks" || $CategoryName == "Comedy") {
		//These types require a year field.
		if($NewRequest) {
			$DB->query("INSERT INTO requests (     
							UserID, TimeAdded, LastVote, CategoryID, Title, Year, Image, Description, Visible)
						VALUES
							(".$LoggedUser['ID'].", '".sqltime()."', '".sqltime()."', ".$CategoryID.", '".db_string($Title)."', ".$Year.", '".db_string($Image)."', '".db_string($Description)."', '1')");
			
			$RequestID = $DB->inserted_id();
		} else {
			$DB->query("UPDATE requests 
				SET CategoryID = ".$CategoryID.",
					Title = '".db_string($Title)."', 
					Year = ".$Year.", 
					Image = '".db_string($Image)."',
					Description = '".db_string($Description)."'
				WHERE ID = ".$RequestID);
		}
	} else {
		if($NewRequest) {
			$DB->query("INSERT INTO requests (     
							UserID, TimeAdded, LastVote, CategoryID, Title, Image, Description, Visible)
						VALUES
							(".$LoggedUser['ID'].", '".sqltime()."', '".sqltime()."',  ".$CategoryID.", '".db_string($Title)."', '".db_string($Image)."', '".db_string($Description)."', '1')");
				
			$RequestID = $DB->inserted_id();
		} else {
				$DB->query("UPDATE requests 
				SET CategoryID = ".$CategoryID.",
					Title = '".db_string($Title)."', 
					Image = '".db_string($Image)."',
					Description = '".db_string($Description)."'
				WHERE ID = ".$RequestID);
		}
	}
}

//Tags
if(!$NewRequest) {
	$DB->query("DELETE FROM requests_tags WHERE RequestID = ".$RequestID);
}

$Tags = array_unique(explode(',', $Tags));
foreach($Tags as $Index => $Tag) {
	$Tag = sanitize_tag($Tag);
	$Tags[$Index] = $Tag; //For announce
	
	$DB->query("INSERT INTO tags 
					(Name, UserID)
				VALUES 
					('".$Tag."', ".$LoggedUser['ID'].") 
				ON DUPLICATE KEY UPDATE Uses=Uses+1");
	
	$TagID = $DB->inserted_id();
	
	$DB->query("INSERT IGNORE INTO requests_tags
					(TagID, RequestID)
				VALUES 
					(".$TagID.", ".$RequestID.")");
}

if($NewRequest) {
	//Remove the bounty and create the vote
	$DB->query("INSERT INTO requests_votes 
					(RequestID, UserID, Bounty)
				VALUES
					(".$RequestID.", ".$LoggedUser['ID'].", ".($Bytes * (1 - $RequestTax)).")");
	
	$DB->query("UPDATE users_main SET Uploaded = (Uploaded - ".$Bytes.") WHERE ID = ".$LoggedUser['ID']);
	$Cache->delete_value('user_stats_'.$LoggedUser['ID']);

	
	
	if($CategoryName == "Music") {
		$Announce = "'".$Title."' - ".display_artists($ArtistForm, false, false)." http://".NONSSL_SITE_URL."/requests.php?action=view&id=".$RequestID." - ".implode(" ", $Tags);
	} else {
		$Announce = "'".$Title."' - http://".NONSSL_SITE_URL."/requests.php?action=view&id=".$RequestID." - ".implode(" ", $Tags);
	}
	send_irc('PRIVMSG #'.NONSSL_SITE_URL.'-requests :'.$Announce);
	
} else {
	$Cache->delete_value('request_'.$RequestID);
	$Cache->delete_value('request_artists_'.$RequestID);
}

update_sphinx_requests($RequestID);

header('Location: requests.php?action=view&id='.$RequestID);
?>
