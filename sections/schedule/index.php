<?
set_time_limit(50000);
ob_end_flush();
gc_enable();
//TODO: make it awesome, make it flexible
//INSERT INTO users_geodistribution (Code, Users) SELECT g.Code, COUNT(u.ID) AS Users FROM geoip_country AS g JOIN users_main AS u ON INET_ATON(u.IP) BETWEEN g.StartIP AND g.EndIP WHERE u.Enabled='1' GROUP BY g.Code ORDER BY Users DESC
/*************************************************************************\
//--------------Schedule page -------------------------------------------//

This page is run every 15 minutes, by cron.

\*************************************************************************/

function next_biweek() {
	$Date = date('d');
	if($Date < 22 && $Date >=8) {
		$Return = 22;
	} else {
		$Return = 8;
	}
	return $Return;
}

function next_day() {
	$Tomorrow = time(0,0,0,date('m'),date('d')+1,date('Y'));
	return date('d', $Tomorrow);
}

function next_hour() {
	$Hour = time(date('H')+1,0,0,date('m'),date('d'),date('Y'));
	return date('H', $Hour);
}

if ((!isset($argv[1]) || $argv[1]!=SCHEDULE_KEY) && !check_perms('admin_schedule')) { // authorization, Fix to allow people with perms hit this page.
	error(403);
}

if (check_perms('admin_schedule')) {
	authorize();
	show_header();
	echo '<pre>';
}

$DB->query("SELECT NextHour, NextDay, NextBiWeekly FROM schedule");
list($Hour, $Day, $BiWeek) = $DB->next_record();
$DB->query("UPDATE schedule SET NextHour = ".next_hour().", NextDay = ".next_day().", NextBiWeekly = ".next_biweek());

$sqltime = sqltime();

echo "$sqltime\n";



/*************************************************************************\
//--------------Run every time ------------------------------------------//

These functions are run every time the script is executed (every 15
minutes).

\*************************************************************************/


echo "Ran every-time functions\n";

//------------- Freeleech -----------------------------------------------//

//We use this to control 6 hour freeleeches. They're actually 7 hour, but don't tell anyone. 
/*
$TimeMinus = time_minus(3600*7);

$DB->query("SELECT DISTINCT GroupID FROM torrents WHERE FreeTorrent='1' AND FreeLeechType='3' AND Time<'$TimeMinus'");
while(list($GroupID) = $DB->next_record()) {
	$Cache->delete_value('torrents_details_'.$GroupID);
	$Cache->delete_value('torrent_group_'.$GroupID);
}
$DB->query("UPDATE torrents SET FreeTorrent='0',FreeLeechType='0',flags='2' WHERE FreeTorrent='1' AND FreeLeechType='3' AND Time<'$TimeMinus'");
*/
sleep(5);
//------------- Delete unpopular tags -----------------------------------//
$DB->query("DELETE FROM torrents_tags WHERE NegativeVotes>PositiveVotes");



/*************************************************************************\
//--------------Run every hour ------------------------------------------//

These functions are run every hour.

\*************************************************************************/


if($Hour != next_hour() || $_GET['runhour'] || isset($argv[2])){
	echo "Ran hourly functions\n";
	
	//------------- Front page stats ----------------------------------------//

	//Love or hate, this makes things a hell of a lot faster
	if ($Hour%2 == 0) {
		$DB->query("SELECT COUNT(uid) AS Snatches FROM xbt_snatched");
		list($SnatchStats) = $DB->next_record();
		$Cache->cache_value('stats_snatches',$SnatchStats,0);
	}
	
	$DB->query("SELECT COUNT(uid) FROM xbt_files_users WHERE remaining>0");
	list($LeecherCount) = $DB->next_record();
	$DB->query("SELECT COUNT(uid) FROM xbt_files_users WHERE remaining=0");
	list($SeederCount) = $DB->next_record();
	$Cache->cache_value('stats_peers',array($LeecherCount,$SeederCount),0);

	$DB->query("SELECT COUNT(ID) FROM users_main WHERE Enabled='1' AND LastAccess>'".time_minus(3600*24)."'");
	list($UserStats['Day']) = $DB->next_record();

	$DB->query("SELECT COUNT(ID) FROM users_main WHERE Enabled='1' AND LastAccess>'".time_minus(3600*24*7)."'");
	list($UserStats['Week']) = $DB->next_record();

	$DB->query("SELECT COUNT(ID) FROM users_main WHERE Enabled='1' AND LastAccess>'".time_minus(3600*24*30)."'");
	list($UserStats['Month']) = $DB->next_record();

	$Cache->cache_value('stats_users',$UserStats,0);
	
	//------------- Record who's seeding how much, used for ratio watch
	
	$DB->query("TRUNCATE TABLE users_torrent_history_temp");
	$DB->query("INSERT INTO users_torrent_history_temp 
		(UserID, NumTorrents) 
		SELECT uid, 
		COUNT(DISTINCT fid) 
		FROM xbt_files_users 
		WHERE mtime>unix_timestamp(now()-interval 1 hour) 
		AND Remaining=0
		GROUP BY uid;");
	$DB->query("UPDATE users_torrent_history AS h 
		JOIN users_torrent_history_temp AS t ON t.UserID=h.UserID AND t.NumTorrents=h.NumTorrents 
		SET h.Finished='0', 
		h.LastTime=unix_timestamp(now()) 
		WHERE h.Finished='1' 
		AND h.Date=UTC_DATE()+0;");
	$DB->query("INSERT INTO users_torrent_history
		(UserID, NumTorrents, Date)
		SELECT UserID, NumTorrents, UTC_DATE()+0
		FROM users_torrent_history_temp
		ON DUPLICATE KEY UPDATE
		Time=Time+(unix_timestamp(NOW())-LastTime),
		LastTime=unix_timestamp(NOW());");

	//------------- Promote users -------------------------------------------//
	sleep(5);
	$Criteria = array();
	$Criteria[]=array('From'=>USER, 'To'=>MEMBER, 'MinUpload'=>10*1024*1024*1024, 'MinRatio'=>0.7, 'MinUploads'=>0, 'MaxTime'=>time_minus(3600*24*7));
	$Criteria[]=array('From'=>MEMBER, 'To'=>POWER, 'MinUpload'=>25*1024*1024*1024, 'MinRatio'=>1.05, 'MinUploads'=>5, 'MaxTime'=>time_minus(3600*24*7*2));
	$Criteria[]=array('From'=>POWER, 'To'=>ELITE, 'MinUpload'=>100*1024*1024*1024, 'MinRatio'=>1.05, 'MinUploads'=>50, 'MaxTime'=>time_minus(3600*24*7*4));
	$Criteria[]=array('From'=>ELITE, 'To'=>TORRENT_MASTER, 'MinUpload'=>500*1024*1024*1024, 'MinRatio'=>1.05, 'MinUploads'=>500, 'MaxTime'=>time_minus(3600*24*7*8));
	$Criteria[]=array('From'=>TORRENT_MASTER, 'To'=>POWER_TM, 'MinUpload'=>500*1024*1024*1024, 'MinRatio'=>1.05, 'MinUploads'=>500, 'MaxTime'=>time_minus(3600*24*7*8), 'Extra'=>'(SELECT COUNT(DISTINCT GroupID) FROM torrents WHERE UserID=users_main.ID) >= 500');
	$Criteria[]=array('From'=>24, 'To'=>25, 'MinUpload'=>500*1024*1024*1024, 'MinRatio'=>1.05, 'MinUploads'=>500, 'MaxTime'=>time_minus(3600*24*7*8), 'Extra'=>"(SELECT COUNT(ID) FROM torrents WHERE ((LogScore = 100 AND Format = 'FLAC') OR (Media = 'Vinyl' AND Format = 'FLAC') OR (Media = 'WEB' AND Format = 'FLAC') OR (Media = 'DVD' AND Format = 'FLAC') OR (Media = 'Soundboard' AND Format = 'FLAC') OR (Media = 'Cassette' AND Format = 'FLAC') OR (Media = 'SACD' AND Format = 'FLAC') OR (Media = 'Blu-ray' AND Format = 'FLAC') OR (Media = 'DAT' AND Format = 'FLAC')) AND UserID = users_main.ID) >= 500");

	 foreach($Criteria as $L){ // $L = Level
		$Query = "SELECT ID FROM users_main JOIN users_info ON users_main.ID = users_info.UserID
                        WHERE PermissionID=".$L['From']."
                        AND Warned= '0000-00-00 00:00:00'
                        AND Uploaded>='$L[MinUpload]'
                        AND (Uploaded/Downloaded >='$L[MinRatio]' OR (Uploaded/Downloaded IS NULL))
                        AND JoinDate<'$L[MaxTime]'
                        AND (SELECT COUNT(ID) FROM torrents WHERE UserID=users_main.ID)>='$L[MinUploads]'
                        AND Enabled='1'";
		if (!empty($L['Extra'])) {
			$Query .= ' AND '.$L['Extra'];
		}
		
		$DB->query($Query);

		$UserIDs = $DB->collect('ID');

		if (count($UserIDs) > 0) {
			foreach($UserIDs as $UserID) {
				/*$Cache->begin_transaction('user_info_'.$UserID);
				$Cache->update_row(false, array('PermissionID'=>$L['To']));
				$Cache->commit_transaction(0);*/
				$Cache->delete_value('user_info_'.$UserID);
				$Cache->delete_value('user_info_heavy_'.$UserID);
				$Cache->delete_value('user_stats_'.$UserID);
				$Cache->delete_value('enabled_'.$UserID);
				$DB->query("UPDATE users_info SET AdminComment = CONCAT('".sqltime()." - Class changed to ".make_class_string($L['To'])." by System\n\n', AdminComment) WHERE UserID = ".$UserID);
			}		
			$DB->query("UPDATE users_main SET PermissionID=".$L['To']." WHERE ID IN(".implode(',',$UserIDs).")");
		}

		// Demote users with less than the required uploads
		
		$Query = "SELECT ID FROM users_main JOIN users_info ON users_main.ID = users_info.UserID
			WHERE PermissionID='$L[To]'
			AND ( Uploaded<'$L[MinUpload]'
			OR (SELECT COUNT(ID) FROM torrents WHERE UserID=users_main.ID)<'$L[MinUploads]'";
			if (!empty($L['Extra'])) {
				$Query .= " OR NOT ".$L['Extra'];
			}
			$Query .= ")
			AND Enabled='1'
			AND ID NOT IN (213461)";
		
		$DB->query($Query);
		$UserIDs = $DB->collect('ID');
		
		if (count($UserIDs) > 0) {
			foreach($UserIDs as $UserID) {
				/*$Cache->begin_transaction('user_info_'.$UserID);
				$Cache->update_row(false, array('PermissionID'=>$L['From']));
				$Cache->commit_transaction(0);*/
				$Cache->delete_value('user_info_'.$UserID);
				$Cache->delete_value('user_info_heavy_'.$UserID);
				$Cache->delete_value('user_stats_'.$UserID);
				$Cache->delete_value('enabled_'.$UserID);
				$DB->query("UPDATE users_info SET AdminComment = CONCAT('".sqltime()." - Class changed to ".make_class_string($L['From'])." by System\n\n', AdminComment) WHERE UserID = ".$UserID);
			}
			$DB->query("UPDATE users_main SET PermissionID=".$L['From']." WHERE ID IN(".implode(',',$UserIDs).")");
		}
	}


	//------------- Expire invites ------------------------------------------//
	sleep(3);
	$DB->query("SELECT InviterID FROM invites WHERE Expires<'$sqltime'");
	$Users = $DB->to_array();
	foreach ($Users as $UserID) {
		list($UserID) = $UserID;
		$DB->query("SELECT Invites FROM users_main WHERE ID=$UserID");
		list($Invites) = $DB->next_record();
		if ($Invites < 10) {
			$DB->query("UPDATE users_main SET Invites=Invites+1 WHERE ID=$UserID");
			$Cache->begin_transaction('user_info_heavy_'.$UserID);
			$Cache->update_row(false, array('Invites' => '+1'));
			$Cache->commit_transaction(0);
		}
	}
	$DB->query("DELETE FROM invites WHERE Expires<'$sqltime'");


	//------------- Hide old requests ---------------------------------------//
	sleep(3);
	$DB->query("UPDATE requests SET Visible = 0 WHERE TimeFilled < (NOW() - INTERVAL 7 DAY) AND TimeFilled <> '0000-00-00 00:00:00'");

	//------------- Remove dead peers ---------------------------------------//
	sleep(3);
	$DB->query("DELETE FROM xbt_files_users WHERE mtime<unix_timestamp(now()-interval 6 hour)");

	//------------- Remove dead sessions ---------------------------------------//
	sleep(3);
	
	$AgoDays = time_minus(3600*24*30);	
	$DB->query("SELECT UserID, SessionID FROM users_sessions WHERE LastUpdate<'$AgoDays' AND KeepLogged='1'");
	while(list($UserID,$SessionID) = $DB->next_record()) {
		$Cache->begin_transaction('users_sessions_'.$UserID);
		$Cache->delete_row($SessionID);
		$Cache->commit_transaction(0);
	}
	$DB->query("DELETE FROM users_sessions WHERE LastUpdate<'$AgoDays' AND KeepLogged='1'");
	
	$AgoMins = time_minus(60*30);
	$DB->query("SELECT UserID, SessionID FROM users_sessions WHERE LastUpdate<'$AgoMins' AND KeepLogged='0'");
	while(list($UserID,$SessionID) = $DB->next_record()) {
		$Cache->begin_transaction('users_sessions_'.$UserID);
		$Cache->delete_row($SessionID);
		$Cache->commit_transaction(0);
	}
	$DB->query("DELETE FROM users_sessions WHERE LastUpdate<'$AgoMins' AND KeepLogged='0'");

	
	//------------- Lower Login Attempts ------------------------------------//
	$DB->query("UPDATE login_attempts SET Attempts=Attempts-1 WHERE Attempts>0");
	$DB->query("DELETE FROM login_attempts WHERE LastAttempt<'".time_minus(3600*24*90)."'");

        //------------- Remove expired warnings ---------------------------------//
        $DB->query("SELECT UserID FROM users_info WHERE Warned<'$sqltime'");
        while(list($UserID) = $DB->next_record()) {
                $Cache->begin_transaction('user_info_'.$UserID);
                $Cache->update_row(false, array('Warned'=>'0000-00-00 00:00:00'));
                $Cache->commit_transaction(2592000);
        }

        $DB->query("UPDATE users_info SET Warned='0000-00-00 00:00:00' WHERE Warned<'$sqltime'");

	// If a user has downloaded more than 10 gigs while on ratio watch, banhammer

        $DB->query("SELECT ID FROM users_info AS i JOIN users_main AS m ON m.ID=i.UserID
                WHERE i.RatioWatchEnds!='0000-00-00 00:00:00'
                AND i.RatioWatchDownload+10*1024*1024*1024<m.Downloaded
                And m.Enabled='1'");

        $UserIDs = $DB->collect('ID');
        if(count($UserIDs) > 0) {
                disable_users($UserIDs, "Disabled by ratio watch system for downloading more than 10 gigs on ratio watch\n\n", 2);
        }

}
/*************************************************************************\
//--------------Run every day -------------------------------------------//

These functions are run in the first 15 minutes of every day.

\*************************************************************************/

if($Day != next_day() || $_GET['runday']){
	echo "Ran daily functions\n";
	if($Day%2 == 0) { // If we should generate the drive database (at the end)
		$GenerateDriveDB = true;
	}
	
	//------------- Ratio requirements
	
	
	$DB->query("DELETE FROM users_torrent_history WHERE Date<date('".sqltime()."'-interval 7 day)+0");
	$DB->query("TRUNCATE TABLE users_torrent_history_temp;");
	$DB->query("INSERT INTO users_torrent_history_temp 
		(UserID, SumTime)
		SELECT UserID, SUM(Time) FROM users_torrent_history
		GROUP BY UserID;");
	$DB->query("INSERT INTO users_torrent_history
		(UserID, NumTorrents, Date, Time)
		SELECT UserID, 0, UTC_DATE()+0, 259200-SumTime
		FROM users_torrent_history_temp
		WHERE SumTime<259200;");
	$DB->query("UPDATE users_torrent_history SET Weight=NumTorrents*Time;");
	$DB->query("TRUNCATE TABLE users_torrent_history_temp;");
	$DB->query("INSERT INTO users_torrent_history_temp 
		(UserID, SeedingAvg)
		SELECT UserID, SUM(Weight)/SUM(Time) FROM users_torrent_history
		GROUP BY UserID;");
	$DB->query("DELETE FROM users_torrent_history WHERE NumTorrents='0'");
	$DB->query("TRUNCATE TABLE users_torrent_history_snatch;");
	$DB->query("INSERT INTO users_torrent_history_snatch(UserID, NumSnatches) 
		SELECT 
		xs.uid,
		COUNT(DISTINCT xs.fid)
		FROM
		xbt_snatched AS xs
		join torrents on torrents.ID=xs.fid
		GROUP BY xs.uid;");
	$DB->query("UPDATE users_main AS um
		JOIN users_torrent_history_temp AS t ON t.UserID=um.ID
		JOIN users_torrent_history_snatch AS s ON s.UserID=um.ID
		SET um.RequiredRatioWork=(1-(t.SeedingAvg/s.NumSnatches))
		WHERE s.NumSnatches>0;");
	
	$RatioRequirements = array(
		array(80*1024*1024*1024, 0.60, 0.50),
		array(60*1024*1024*1024, 0.60, 0.40),
		array(50*1024*1024*1024, 0.60, 0.30),
		array(40*1024*1024*1024, 0.50, 0.20),
		array(30*1024*1024*1024, 0.40, 0.10),
		array(20*1024*1024*1024, 0.30, 0.05),
		array(10*1024*1024*1024, 0.20, 0.0),
		array(5*1024*1024*1024,  0.15, 0.0)
	);
	
	$DB->query("UPDATE users_main SET RequiredRatio=0.60 WHERE Downloaded>100*1024*1024*1024");
	
	
	
	$DownloadBarrier = 100*1024*1024*1024;
	foreach($RatioRequirements as $Requirement) {
		list($Download, $Ratio, $MinRatio) = $Requirement;
		
		$DB->query("UPDATE users_main SET RequiredRatio=RequiredRatioWork*$Ratio WHERE Downloaded >= '$Download' AND Downloaded < '$DownloadBarrier'");
		
		$DB->query("UPDATE users_main SET RequiredRatio=$MinRatio WHERE Downloaded >= '$Download' AND Downloaded < '$DownloadBarrier' AND RequiredRatio<$MinRatio");
		
		$DB->query("UPDATE users_main SET RequiredRatio=$Ratio WHERE Downloaded >= '$Download' AND Downloaded < '$DownloadBarrier' AND can_leech='0' AND Enabled='1'");
		
		$DownloadBarrier = $Download;
	}
	
	$DB->query("UPDATE users_main SET RequiredRatio=0.00 WHERE Downloaded<5*1024*1024*1024");
	
	// Here is where we manage ratio watch
	
	$OffRatioWatch = array();
	$OnRatioWatch = array();
	
	// Take users off ratio watch and enable leeching
	$UserQuery = $DB->query("SELECT m.ID, torrent_pass FROM users_info AS i JOIN users_main AS m ON m.ID=i.UserID
		WHERE m.Uploaded/m.Downloaded >= m.RequiredRatio
		AND i.RatioWatchEnds!='0000-00-00 00:00:00' 
		AND m.can_leech='0'
		AND m.Enabled='1'");
	$OffRatioWatch = $DB->collect('ID');
	if(count($OffRatioWatch)>0) {
		$DB->query("UPDATE users_info AS ui
			JOIN users_main AS um ON um.ID = ui.UserID
			SET ui.RatioWatchEnds='0000-00-00 00:00:00',
			ui.RatioWatchDownload='0',
			um.can_leech='1',
			ui.AdminComment = CONCAT('".$sqltime." - Leeching re-enabled by adequate ratio.\n\n', ui.AdminComment)
			WHERE ui.UserID IN(".implode(",", $OffRatioWatch).")");
	}
	
	foreach($OffRatioWatch as $UserID) {
		$Cache->begin_transaction('user_info_heavy_'.$UserID);
		$Cache->update_row(false, array('RatioWatchEnds'=>'0000-00-00 00:00:00','RatioWatchDownload'=>'0'));
		$Cache->commit_transaction(0);
		send_pm($UserID, 0, db_string("You have been taken off Ratio Watch"), db_string("Congratulations! Feel free to begin downloading again.\n To ensure that you do not get put on ratio watch again, please read the rules located [url=http://".NONSSL_SITE_URL."/rules.php?p=ratio]here[/url].\n"), '');
		echo "Ratio watch off: $UserID\n";
	}
	$DB->set_query_id($UserQuery);
	$Passkeys = $DB->collect('torrent_pass');
	foreach($Passkeys as $Passkey) {
		update_tracker('update_user', array('passkey' => $Passkey, 'can_leech' => '1'));
	}

        // Take users off ratio watch 
        $UserQuery = $DB->query("SELECT m.ID, torrent_pass FROM users_info AS i JOIN users_main AS m ON m.ID=i.UserID
                WHERE m.Uploaded/m.Downloaded >= m.RequiredRatio
                AND i.RatioWatchEnds!='0000-00-00 00:00:00'
                AND m.Enabled='1'");
        $OffRatioWatch = $DB->collect('ID');
        if(count($OffRatioWatch)>0) {
                $DB->query("UPDATE users_info AS ui
                        JOIN users_main AS um ON um.ID = ui.UserID
                        SET ui.RatioWatchEnds='0000-00-00 00:00:00',
                        ui.RatioWatchDownload='0',
                        um.can_leech='1'
                        WHERE ui.UserID IN(".implode(",", $OffRatioWatch).")");
        }

        foreach($OffRatioWatch as $UserID) {
                $Cache->begin_transaction('user_info_heavy_'.$UserID);
                $Cache->update_row(false, array('RatioWatchEnds'=>'0000-00-00 00:00:00','RatioWatchDownload'=>'0'));
                $Cache->commit_transaction(0);
                send_pm($UserID, 0, db_string("You have been taken off Ratio Watch"), db_string("Congratulations! Feel free to begin downloading again.\n To ensure that you do not get put on ratio watch again, please read the rules located [url=http://".NONSSL_SITE_URL."/rules.php?p=ratio]here[/url].\n"), '');
                echo "Ratio watch off: $UserID\n";
        }
        $DB->set_query_id($UserQuery);
        $Passkeys = $DB->collect('torrent_pass');
        foreach($Passkeys as $Passkey) {
                update_tracker('update_user', array('passkey' => $Passkey, 'can_leech' => '1'));
        }

	
	// Put user on ratio watch if he doesn't meet the standards
	sleep(10);
	$DB->query("SELECT m.ID, m.Downloaded FROM users_info AS i JOIN users_main AS m ON m.ID=i.UserID
		WHERE m.Uploaded/m.Downloaded < m.RequiredRatio
		AND i.RatioWatchEnds='0000-00-00 00:00:00'
		AND m.Enabled='1'
		AND m.can_leech='1'");
	$OnRatioWatch = $DB->collect('ID');
	
	if(count($OnRatioWatch)>0) {
		$DB->query("UPDATE users_info AS i JOIN users_main AS m ON m.ID=i.UserID
			SET i.RatioWatchEnds='".time_plus(60*60*24*14)."',
			i.RatioWatchTimes = i.RatioWatchTimes+1,
			i.RatioWatchDownload = m.Downloaded
			WHERE m.ID IN(".implode(",", $OnRatioWatch).")");
	}
	
	foreach($OnRatioWatch as $UserID) {
		$Cache->begin_transaction('user_info_heavy_'.$UserID);
		$Cache->update_row(false, array('RatioWatchEnds'=>time_plus(60*60*24*14),'RatioWatchDownload'=>0));
		$Cache->commit_transaction(0);
		send_pm($UserID, 0, db_string("You have been put on Ratio Watch"), db_string("This happens when your ratio falls below the requirements we have outlined in the rules located [url=http://".NONSSL_SITE_URL."/rules.php?p=ratio]here[/url].\n For information about ratio watch, click the link above."), '');
		echo "Ratio watch on: $UserID\n";
	}
	
	sleep(5);

	//------------- Rescore 0.95 logs of disabled users

	$LogQuery = $DB->query("SELECT DISTINCT t.ID FROM torrents AS t JOIN users_main AS um ON t.UserID = um.ID JOIN torrents_logs_new AS tl ON tl.TorrentID = t.ID WHERE um.Enabled = '2' and t.HasLog = '1' and LogScore = 100 and Log LIKE 'EAC extraction logfile from%'");
	$Details = array();
	$Details[] = "Ripped with EAC v0.95, -1 point [1]";
	$Details = serialize($Details);
	while (list($TorrentID) = $DB->next_record()) {
		$DB->query("UPDATE torrents SET LogScore = 99 WHERE ID = ".$TorrentID);
		$DB->query("UPDATE torrents_logs_new SET Score = 99, Details = '".$Details."' WHERE TorrentID = ".$TorrentID);
	}

	sleep(5);
	
	//------------- Disable downloading ability of users on ratio watch
	
	
	$UserQuery = $DB->query("SELECT ID, torrent_pass FROM users_info AS i JOIN users_main AS m ON m.ID=i.UserID
		WHERE i.RatioWatchEnds!='0000-00-00 00:00:00'
		AND i.RatioWatchEnds<'$sqltime'
		And m.Enabled='1'");
	
	$UserIDs = $DB->collect('ID');
	if(count($UserIDs) > 0) {
		$DB->query("UPDATE users_info AS i JOIN users_main AS m ON m.ID=i.UserID
			SET 
			m.can_leech='0',
			i.AdminComment=CONCAT('$sqltime - Leeching ability disabled by ratio watch system - required ratio: ', m.RequiredRatio,'

'			, i.AdminComment)
			WHERE m.ID IN(".implode(',',$UserIDs).")");
	}
	
	foreach($UserIDs as $UserID) {
		$Cache->begin_transaction('user_info_heavy_'.$UserID);
		$Cache->update_row(false, array('RatioWatchDownload'=>0));
		$Cache->commit_transaction(0);
		send_pm($UserID, 0, db_string("Your downloading rights have been disabled"), db_string("As you did not raise your ratio in time, your downloading rights have been revoked. You will not be able to download any torrents until your ratio is above your new required ratio."), '');
		echo "Ratio watch disabled: $UserID\n";
	}

	$DB->set_query_id($UserQuery);
	$Passkeys = $DB->collect('torrent_pass');
	foreach($Passkeys as $Passkey) {
		update_tracker('update_user', array('passkey' => $Passkey, 'can_leech' => '0'));
	}
	
	//------------- Disable inactive user accounts --------------------------//
	sleep(5);
	// Send email
	$DB->query("SELECT um.Username, um.Email FROM  users_info AS ui JOIN users_main AS um ON um.ID=ui.UserID
		WHERE um.PermissionID IN ('".USER."', '".MEMBER	."')
		AND um.LastAccess<'".time_minus(3600*24*110, true)."'
		AND um.LastAccess>'".time_minus(3600*24*111, true)."'
		AND um.LastAccess!='0000-00-00 00:00:00'
		AND ui.Donor='0'
		AND um.Enabled!='2'");
	while(list($Username, $Email) = $DB->next_record()) {
		$Body = "Hi $Username, \n\nIt has been almost 4 months since you used your account at http://".NONSSL_SITE_URL.". This is an automated email to inform you that your account will be disabled in 10 days if you do not sign in. ";
		send_email($Email, 'Your '.SITE_NAME.' account is about to be disabled', $Body);
	}
	$DB->query("SELECT um.ID FROM  users_info AS ui JOIN users_main AS um ON um.ID=ui.UserID
		WHERE um.PermissionID IN ('".USER."', '".MEMBER	."')
		AND um.LastAccess<'".time_minus(3600*24*30*4)."' 
		AND um.LastAccess!='0000-00-00 00:00:00'
		AND ui.Donor='0'
		AND um.Enabled!='2'");

	if($DB->record_count() > 0) {
		disable_users($DB->collect('ID'), "Disabled for inactivity\n\n", 3);
	}

	//------------- Disable unconfirmed users ------------------------------//
	sleep(10);
	$DB->query("UPDATE users_info AS ui JOIN users_main AS um ON um.ID=ui.UserID
		SET um.Enabled='2',
		ui.BanDate='$sqltime',
		ui.BanReason='3',
		ui.AdminComment=CONCAT('$sqltime - Disabled for inactivity (never logged in)

', ui.AdminComment)
		WHERE um.LastAccess='0000-00-00 00:00:00'
		AND ui.JoinDate<'".time_minus(60*60*24*7)."'
		AND um.Enabled!='2'
		");
	$Cache->decrement('stats_user_count',$DB->affected_rows());
	
	echo "disabled unconfirmed\n";
	
	//------------- Demote users --------------------------------------------//
	sleep(10);
	$DB->query('SELECT um.ID FROM users_main AS um WHERE PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.') AND Uploaded/Downloaded < 0.95 OR PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.') AND Uploaded < 25*1024*1024*1024');
	
	echo "demoted 1\n";
	
	while(list($UserID) = $DB->next_record()) {
		$Cache->begin_transaction('user_info_'.$UserID);
		$Cache->update_row(false, array('PermissionID'=>MEMBER));
		$Cache->commit_transaction(2592000);
	}
	$DB->query('UPDATE users_main SET PermissionID='.MEMBER.' WHERE PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.') AND Uploaded/Downloaded < 0.95 OR PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.') AND Uploaded < 25*1024*1024*1024');
	echo "demoted 2\n";
	
	$DB->query('SELECT um.ID FROM users_main AS um WHERE PermissionID IN('.MEMBER.', '.POWER.', '.ELITE.', '.TORRENT_MASTER.') AND Uploaded/Downloaded < 0.65');
	echo "demoted 3\n";
	while(list($UserID) = $DB->next_record()) {
		$Cache->begin_transaction('user_info_'.$UserID);
		$Cache->update_row(false, array('PermissionID'=>USER));
		$Cache->commit_transaction(2592000);
	}
	$DB->query('UPDATE users_main SET PermissionID='.USER.' WHERE PermissionID IN('.MEMBER.', '.POWER.', '.ELITE.', '.TORRENT_MASTER.') AND Uploaded/Downloaded < 0.65');
	echo "demoted 4\n";

	//------------- Lock old threads ----------------------------------------//
	sleep(10);
	
	
	$DB->query("SELECT ID FROM forums_topics WHERE 
		IsLocked='0'
		AND IsSticky='0'
		AND LastPostTime<'".time_minus(3600*24*28)."'");
	
	$IDs = $DB->collect('ID');
	
	if(count($IDs) > 0) {
		$LockIDs = implode(',', $IDs);
		$DB->query("UPDATE forums_topics SET IsLocked='1' WHERE ID IN($LockIDs)");
		sleep(2);
		$DB->query("DELETE FROM forums_last_read_topics WHERE TopicID IN($LockIDs)");
	
		foreach($IDs as $ID) {
			$Cache->begin_transaction('thread_'.$ID.'_info');
			$Cache->update_row(false, array('IsLocked'=>'1'));
			$Cache->commit_transaction(3600*24*30);
			$Cache->expire_value('thread_'.$TopicID.'_catalogue_0',3600*24*30);
			$Cache->expire_value('thread_'.$TopicID.'_info',3600*24*30);
		}
	}

	//------------- Delete dead torrents ------------------------------------//
	
	sleep(10);
	//remove dead torrents that were never announced to -- XBTT will not delete those with a pid of 0, only those that belong to them (valid pids)
	
	$DB->query("DELETE FROM torrents WHERE flags = 1 AND pid = 0");
	sleep(10);
	


	$i = 0;
	$DB->query("SELECT
		t.ID,
		t.GroupID,
		tg.Name,
		ag.Name,
		t.last_action,
		t.Format,
		t.Encoding,
		t.UserID
		FROM torrents AS t
		JOIN torrents_group AS tg ON tg.ID=t.GroupID
		LEFT JOIN artists_group AS ag ON ag.ArtistID=tg.ArtistID
		WHERE t.flags <> 1
		AND (t.last_action<'".time_minus(3600*24*28)."'
		AND t.last_action!='0000-00-00 00:00:00'
		OR t.Time<'".time_minus(3600*24*2)."'
		AND t.last_action='0000-00-00 00:00:00')");
	$TorrentIDs = $DB->to_array();
	
	$LogEntries = array();
	
	foreach ($TorrentIDs as $TorrentID) {
		list($ID, $GroupID, $Name, $ArtistName, $LastAction, $Format, $Encoding, $UserID) = $TorrentID;
		if($ArtistName) {
			$Name = $ArtistName.' - '.$Name;
		}
		if($Format && $Encoding) {
			$Name.=' ['.$Format.' / '.$Encoding.']';
		}
		delete_torrent($ID, $GroupID);
		$LogEntries[] = "Torrent ".$ID." (".$Name.") was deleted for inactivity (unseeded)";
		
		send_pm($UserID,0,db_string('One of your torrents has been deleted for inactivity'), db_string("The torrent ".$Name." was deleted for being unseeded. Since it didn't break any rules (we hope), you can feel free to re-upload it."));
		
		++$i;
	}
	
	if(count($LogEntries) > 0) {
		$Values = "('".implode("', '".$sqltime."'), ('",$LogEntries)."', '".$sqltime."')";
		$DB->query('INSERT INTO log (Message, Time) VALUES '.$Values);
		echo "\nDeleted $i torrents for inactivity\n";
	}

	$DB->query("SELECT SimilarID FROM artists_similar_scores WHERE Score<=0");
	$SimilarIDs = implode(',',$DB->collect('SimilarID'));
	
	if($SimilarIDs) {	
		$DB->query("DELETE FROM artists_similar WHERE SimilarID IN($SimilarIDs)");
		$DB->query("DELETE FROM artists_similar_scores WHERE SimilarID IN($SimilarIDs)");
		$DB->query("DELETE FROM artists_similar_votes WHERE SimilarID IN($SimilarIDs)");
	}
	
	


	// Daily top 10 history.
	$DB->query("INSERT INTO top10_history (Date, Type) VALUES ('".$sqltime."', 'Daily')");
	$HistoryID = $DB->inserted_id();

	$Top10 = $Cache->get_value('top10tor_day_10');
	if($Top10 === false) {
		$DB->query("SELECT
				t.ID,
				g.ID,
				g.Name,
				g.CategoryID,
				g.TagList,
				t.Format,
				t.Encoding,
				t.Media,
				t.Scene,
				t.HasLog,
				t.HasCue,
				t.LogScore,
				t.RemasterYear,
				g.Year,
				t.RemasterTitle,
				t.Snatched,
				t.Seeders,
				t.Leechers,
				((t.Size * t.Snatched) + (t.Size * 0.5 * t.Leechers)) AS Data
			FROM torrents AS t
				LEFT JOIN torrents_group AS g ON g.ID = t.GroupID
			WHERE t.Seeders>0
				AND t.Time > ('".$sqltime."' - INTERVAL 1 DAY)
			ORDER BY (t.Seeders + t.Leechers) DESC
				LIMIT 10;");

		$Top10 = $DB->to_array();
	}

	$i = 1;
	foreach($Top10 as $Torrent) {
		list($TorrentID,$GroupID,$GroupName,$GroupCategoryID,$TorrentTags,
			$Format,$Encoding,$Media,$Scene,$HasLog,$HasCue,$LogScore,$Year,$GroupYear,
			$RemasterTitle,$Snatched,$Seeders,$Leechers,$Data) = $Torrent;

		$DisplayName='';
		
		$Artists = get_artist($GroupID);
		
		if(!empty($Artists)) {
			$DisplayName = display_artists($Artists, false, true);
		}
		
		$DisplayName.= $GroupName;

		if($GroupCategoryID==1 && $GroupYear>0) {
			$DisplayName.= " [$GroupYear]";
		}

		// append extra info to torrent title
		$ExtraInfo='';
		$AddExtra='';
		if($Format) { $ExtraInfo.=$Format; $AddExtra=' / '; }
		if($Encoding) { $ExtraInfo.=$AddExtra.$Encoding; $AddExtra=' / '; }
		"FLAC / Lossless / Log (100%) / Cue / CD";
		if($HasLog) { $ExtraInfo.=$AddExtra."Log (".$LogScore."%)"; $AddExtra=' / '; }
		if($HasCue) { $ExtraInfo.=$AddExtra."Cue"; $AddExtra=' / '; }
		if($Media) { $ExtraInfo.=$AddExtra.$Media; $AddExtra=' / '; }
		if($Scene) { $ExtraInfo.=$AddExtra.'Scene'; $AddExtra=' / '; }
		if($Year>0) { $ExtraInfo.=$AddExtra.$Year; $AddExtra=' '; }
		if($RemasterTitle) { $ExtraInfo.=$AddExtra.$RemasterTitle; }
		if($ExtraInfo!='') {
			$ExtraInfo = "- [$ExtraInfo]";
		}

		$TitleString = $DisplayName.' '.$ExtraInfo;

		$TagString = str_replace("|", " ", $TorrentTags);

		$DB->query("INSERT INTO top10_history_torrents
			(HistoryID, Rank, TorrentID, TitleString, TagString)
			VALUES
			(".$HistoryID.", ".$i.", ".$TorrentID.", '".db_string($TitleString)."', '".db_string($TagString)."')");
		$i++;
	}

	// Weekly top 10 history.
	// We need to haxxor it to work on a Sunday as we don't have a weekly schedule
	if(date('w') == 0) {
		$DB->query("INSERT INTO top10_history (Date, Type) VALUES ('".$sqltime."', 'Weekly')");
		$HistoryID = $DB->inserted_id();

		$Top10 = $Cache->get_value('top10tor_week_10');
		if($Top10 === false) {
			$DB->query("SELECT
					t.ID,
					g.ID,
					g.Name,
					g.CategoryID,
					g.TagList,
					t.Format,
					t.Encoding,
					t.Media,
					t.Scene,
					t.HasLog,
					t.HasCue,
					t.LogScore,
					t.RemasterYear,
					g.Year,
					t.RemasterTitle,
					t.Snatched,
					t.Seeders,
					t.Leechers,
					((t.Size * t.Snatched) + (t.Size * 0.5 * t.Leechers)) AS Data
				FROM torrents AS t
					LEFT JOIN torrents_group AS g ON g.ID = t.GroupID
				WHERE t.Seeders>0
					AND t.Time > ('".$sqltime."' - INTERVAL 1 WEEK)
				ORDER BY (t.Seeders + t.Leechers) DESC
					LIMIT 10;");

			$Top10 = $DB->to_array();
		}

		$i = 1;
		foreach($Top10 as $Torrent) {
			list($TorrentID,$GroupID,$GroupName,$GroupCategoryID,$TorrentTags,
				$Format,$Encoding,$Media,$Scene,$HasLog,$HasCue,$LogScore,$Year,$GroupYear,
				$RemasterTitle,$Snatched,$Seeders,$Leechers,$Data) = $Torrent;

			$DisplayName='';
			
			$Artists = get_artist($GroupID);
			
			if(!empty($Artists)) {
				$DisplayName = display_artists($Artists, false, true);
			}
			
			$DisplayName.= $GroupName;

			if($GroupCategoryID==1 && $GroupYear>0) {
				$DisplayName.= " [$GroupYear]";
			}

			// append extra info to torrent title
			$ExtraInfo='';
			$AddExtra='';
			if($Format) { $ExtraInfo.=$Format; $AddExtra=' / '; }
			if($Encoding) { $ExtraInfo.=$AddExtra.$Encoding; $AddExtra=' / '; }
			"FLAC / Lossless / Log (100%) / Cue / CD";
			if($HasLog) { $ExtraInfo.=$AddExtra."Log (".$LogScore."%)"; $AddExtra=' / '; }
			if($HasCue) { $ExtraInfo.=$AddExtra."Cue"; $AddExtra=' / '; }
			if($Media) { $ExtraInfo.=$AddExtra.$Media; $AddExtra=' / '; }
			if($Scene) { $ExtraInfo.=$AddExtra.'Scene'; $AddExtra=' / '; }
			if($Year>0) { $ExtraInfo.=$AddExtra.$Year; $AddExtra=' '; }
			if($RemasterTitle) { $ExtraInfo.=$AddExtra.$RemasterTitle; }
			if($ExtraInfo!='') {
				$ExtraInfo = "- [$ExtraInfo]";
			}

			$TitleString = $DisplayName.' '.$ExtraInfo;

			$TagString = str_replace("|", " ", $TorrentTags);

			$DB->query("INSERT INTO top10_history_torrents
				(HistoryID, Rank, TorrentID, TitleString, TagString)
				VALUES
				(".$HistoryID.", ".$i.", ".$TorrentID.", '".db_string($TitleString)."', '".db_string($TagString)."')");
			$i++;
		}


	}
}
/*************************************************************************\
//--------------Run twice per month -------------------------------------//

These functions are twice per month, on the 8th and the 22nd.

\*************************************************************************/

if($BiWeek != next_biweek() || $_GET['runbiweek']) {
	echo "Ran bi-weekly functions\n";

	//------------- Cycle auth keys -----------------------------------------//

	$DB->query("UPDATE users_info
	SET AuthKey =
		MD5(
			CONCAT(
				AuthKey, RAND(), '".db_string(make_secret())."',
				SHA1(
					CONCAT(
						RAND(), RAND(), '".db_string(make_secret())."'
					)
				)
			)
		);"
	);

	//------------- Give out invites! ---------------------------------------//

	/*
	Every month, on the 8th, each Power User gets one invite (max of 4).
	Every month, on the 8th and the 22nd, each Elite User gets one invite (max of 4).
	Every month, on the 8th and the 22nd, each TorrentMaster gets two invites (max of 8).

	Then, every month, on the 8th and the 22nd, we give out bonus invites like this:

	Every Power User, Elite User or TorrentMaster whose total invitee ratio is above 0.75 and total invitee upload is over 2 gigs gets one invite.
	Every Power User, Elite User or TorrentMaster whose total invitee ratio is above 2.0 and total invitee upload is over 10 gigs gets one invite.
	Every Power User, Elite User or TorrentMaster whose total invitee ratio is above 3.0 and total invitee upload is over 20 gigs gets one invite.


	This cascades, so if you qualify for the last bonus group, you also qualify for the first two and will receive three bonus invites.
	So a TorrentMaster who fits in the last bonus category gets 5 invites every month on the 8th and the 22nd, whereas a power user who fits in the first category gets two invites on the 8th and one on the 22nd. A power user whose invitees suck only gets one invite per month.

	There is a hard maximum of 10 invites for all classes, that cannot be exceeded by bonus invites.

	*/

	// Power users
	if($BiWeek == 8){

		$DB->query("SELECT ID FROM users_main AS um JOIN users_info AS ui on ui.UserID=um.ID WHERE PermissionID=".POWER." AND um.Enabled='1' AND ui.DisableInvites = '0' AND um.Invites<4");
		$UserIDs = $DB->collect('ID');
		if (count($UserIDs) > 0) {
			foreach($UserIDs as $UserID) {
					$Cache->begin_transaction('user_info_heavy_'.$UserID);
					$Cache->update_row(false, array('Invites' => '+1'));
					$Cache->commit_transaction(0);
			}
			$DB->query("UPDATE users_main SET Invites=Invites+1 WHERE ID IN(".implode(',',$UserIDs).")");
		}

	}

	// Elite users
	$DB->query("SELECT ID FROM users_main AS um JOIN users_info AS ui on ui.UserID=um.ID WHERE PermissionID=".ELITE." AND um.Enabled='1' AND ui.DisableInvites = '0' AND um.Invites<4");
	$UserIDs = $DB->collect('ID');
	if (count($UserIDs) > 0) {
		foreach($UserIDs as $UserID) {
				$Cache->begin_transaction('user_info_heavy_'.$UserID);
				$Cache->update_row(false, array('Invites' => '+1'));
				$Cache->commit_transaction(0);
		}
		$DB->query("UPDATE users_main SET Invites=Invites+1 WHERE ID IN (".implode(',',$UserIDs).")");
	}

	$BonusReqs = array(
		array(0.75, 2*1024*1024*1024),
		array(2.0, 10*1024*1024*1024),
		array(3.0, 20*1024*1024*1024));
	
	// Since MySQL doesn't like subselecting from the target table during an update, we must create a temporary table.

	$DB->query("CREATE TEMPORARY TABLE u
		SELECT SUM(Uploaded) AS Upload,SUM(Downloaded) AS Download,Inviter
		FROM users_main AS um JOIN users_info AS ui ON ui.UserID=um.ID
		GROUP BY Inviter");
	
	foreach ($BonusReqs as $BonusReq) {
		list($Ratio, $Upload) = $BonusReq;
		$DB->query("SELECT ID FROM users_main AS um JOIN users_info AS ui on ui.UserID=um.ID JOIN u ON u.Inviter = um.ID WHERE u.Upload>$Upload AND u.Upload/u.Download>$Ratio AND um.PermissionID IN (".POWER.", ".ELITE.") AND um.Enabled = '1' AND ui.DisableInvites = '0' AND um.Invites<10");
		$UserIDs = $DB->collect('ID');
		if (count($UserIDs) > 0) {
			foreach($UserIDs as $UserID) {
					$Cache->begin_transaction('user_info_heavy_'.$UserID);
					$Cache->update_row(false, array('Invites' => '+1'));
					$Cache->commit_transaction(0);
			}
			$DB->query("UPDATE users_main SET Invites=Invites+1 WHERE ID IN (".implode(',',$UserIDs).")");
		}
	}
	
	if($BiWeek == 8) {
		$DB->query("TRUNCATE TABLE top_snatchers;");
		$DB->query("INSERT INTO top_snatchers (UserID) SELECT uid FROM xbt_snatched GROUP BY uid ORDER BY COUNT(uid) DESC LIMIT 100;");
	}
}


echo "-------------------------\n\n";
if (check_perms('admin_schedule')) {	
	echo '<pre>';
	show_footer();
}
?>
