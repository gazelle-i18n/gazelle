<?
if (!extension_loaded('date')) {
	error('Date Extension not loaded.');
}

function time_ago($TimeStamp) {
	if(!is_number($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
		if($TimeStamp == '0000-00-00 00:00:00') { return false; }
		$TimeStamp = strtotime($TimeStamp);
	}
	if($TimeStamp == 0) { return false; }
	return time()-$TimeStamp;
}

function time_diff($TimeStamp,$Levels=2,$Span=true) {	
	if(!is_number($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
		if($TimeStamp == '0000-00-00 00:00:00') { return 'Never'; }
		$TimeStamp = strtotime($TimeStamp);
	}
	if($TimeStamp == 0) { return 'Never'; }
	$Time = time()-$TimeStamp;
	
	//If the time is negative, then we know that it expires in the future
	if($Time < 0) {
		$Time = -$Time;
		$HideAgo = true;
	}

	$Years=floor($Time/31556926); // seconds in a year
	$Remain = $Time - $Years*31556926;

	$Months = floor($Remain/2629744); // seconds in a month
	$Remain = $Remain - $Months*2629744;

	$Weeks = floor($Remain/604800); // seconds in a week
	$Remain = $Remain - $Weeks*604800;

	$Days = floor($Remain/86400); // seconds in a day
	$Remain = $Remain - $Days*86400;

	$Hours=floor($Remain/3600);
	$Remain = $Remain - $Hours*3600;

	$Minutes=floor($Remain/60);
	$Remain = $Remain - $Minutes*60;

	$Seconds=$Remain;

	$Return = '';

	if ($Years>0 && $Levels>0) {
		$Return.=$Years.' year';
		if ($Years>1) { $Return.='s'; }
		$Levels--;
	}

	if ($Months>0 && $Levels>0) {
		if ($Return!='') { $Return.=', '; }
		$Return.=$Months.' month';
		if ($Months>1) { $Return.='s'; }
		$Levels--;
	}

	if ($Weeks>0 && $Levels>0) {
		if ($Return!="") { $Return.=', '; }
		$Return.=$Weeks.' week';
		if ($Weeks>1) { $Return.='s'; }
		$Levels--;
	}

	if ($Days>0 && $Levels>0) {
		if ($Return!='') { $Return.=', '; }
		$Return.=$Days.' day';
		if ($Days>1) { $Return.='s'; }
		$Levels--;
	}

	if ($Hours>0 && $Levels>0) {
		if ($Return!='') { $Return.=', '; }
		$Return.=$Hours.' hour';
		if ($Hours>1) { $Return.='s'; }
		$Levels--;
	}

	if ($Minutes>0 && $Levels>0) {
		if ($Return!='') { $Return.=' and '; }
		$Return.=$Minutes.' min';
		if ($Minutes>1) { $Return.='s'; }
		$Levels--;
	}
	
	if($Return == '') {
		$Return = 'Just now';
	} elseif (!isset($HideAgo)) {
		$Return.=' ago';
	}
	
	if ($Span) {
		return '<span title="'.date('M d Y, H:i', $TimeStamp).'">'.$Return.'</span>';
	} else {
		return $Return;
	}
}

/* SQL utility functions */

function time_plus($Offset) {
	return date('Y-m-d H:i:s', time()+$Offset);
}

function time_minus($Offset, $Fuzzy = false) {
	if($Fuzzy) {
		return date('Y-m-d 00:00:00', time()-$Offset);
	} else {
		return date('Y-m-d H:i:s', time()-$Offset);
	}
}

function sqltime() {
	return date('Y-m-d H:i:s');
}

function validDate($DateString) {
	$DateTime = explode(" ", $DateString);
	if(count($DateTime) != 2) return false;
	list($Date, $Time) = $DateTime;
	$SplitTime = explode(":", $Time);
	if(count($SplitTime) != 3) return false;
	list($H, $M, $S) = $SplitTime;
	if(!(is_number($H) && $H < 24 && $H >= 0)) return false;
	if(!(is_number($M) && $M < 60 && $M >= 0)) return false;
	if(!(is_number($S) && $S < 60 && $S >= 0)) return false;
	$SplitDate = explode("-", $Date);
	if(count($SplitDate) != 3) return false;
	list($Y, $M, $D) = $SplitDate;
	return checkDate($M, $D, $Y);
}
?>
