<?

// Note: at the time this file is loaded, check_perms is not defined. Don't
// call check_paranoia in /classes/script_start.php without ensuring check_perms has been defined

// The following are used throughout the site:
// uploaded, ratio, downloaded: stats
// lastseen: approximate time the user last used the site
// uploads: the full list of the user's uploads
// uploads+: just how many torrents the user has uploaded
// snatched, seeding, leeching: the list of the user's snatched torrents, seeding torrents, and leeching torrents respectively
// snatched+, seeding+, leeching+: the length of those lists respectively
// uniquegroups, perfectflacs: the list of the user's uploads satisfying a particular criterion
// uniquegroups+, perfectflacs+: the length of those lists
// If "uploads+" is disallowed, so is "uploads". So if "uploads" is in the array, the user is a little paranoid, "uploads+", very paranoid.

// The following are almost only used in /sections/user/user.php:
// requiredratio
// requestsfilled_count: the number of requests the user has filled
//   requestsfilled_bounty: the bounty thus earned
//   requestsfilled_list: the actual list of requests the user has filled
// requestsvoted_...: similar
// artistsadded: the number of artists the user has added
// torrentcomments: the list of comments the user has added to torrents
//   +
// collages: the list of collages the user has created
//   +
// collagecontribs: the list of collages the user has contributed to
//   +
// invitedcount: the number of users this user has directly invited

/**
 * Return whether currently logged in user can see $Property on a user with $Paranoia, $UserClass and (optionally) $UserID
 * If $Property is an array of properties, returns whether currently logged in user can see *all* $Property ...
 *
 * @param $Property The property to check, or an array of properties.
 * @param $Paranoia The paranoia level to check against.
 * @param $UserClass The user class to check against (Staff can see through paranoia of lower classed staff)
 * @param $UserID Optional. The user ID of the person being viewed
 * @return Boolean representing whether the current user can see through the paranoia setting
 */

function check_paranoia($Property, $Paranoia, $UserClass, $UserID = false) {
	global $LoggedUser;
	if(check_perms('users_override_paranoia', $UserClass)) {
		return true;
	}
	if(!is_array($Paranoia)) {
		$Paranoia = unserialize($Paranoia);
	}
	if(!is_array($Paranoia)) {
		$Paranoia = array();
	}
	if(is_array($Property)) {
		$all = true;
		foreach ($Property as $P) { $all = $all && check_paranoia($P, $Paranoia, $UserClass, $UserID); }
		return $all;
	} else {
		if(($UserID !== false) && ($LoggedUser['ID'] == $UserID)) {
			return true;
		}

		$May = !in_array($Property, $Paranoia) && !in_array($Property . '+', $Paranoia);
		switch ($Property) {
			case 'downloaded':
			case 'ratio':
			case 'uploaded':
			case 'lastseen':
				$May = $May || check_perms('users_mod', $UserClass);
				break;
			case 'snatched': case 'snatched+':
				$May = $May || check_perms('site_view_torrent_snatchlist', $UserClass);
				break;
			case 'uploading': case 'uploading+':
			case 'seeding': case 'seeding+':
			case 'leeching': case 'leeching+':
				$May = $May || check_perms('users_view_seedleech', $UserClass);
				break;
			case 'invitedcount':
				$May = $May || check_perms('users_view_invites', $UserClass);
				break;
		}
		return $May;
	}
}
