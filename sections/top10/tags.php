<?
// error out on invalid requests (before caching)
if(isset($_GET['details'])) {
	if(in_array($_GET['details'],array('ut','ur','v'))) {
		$Details = $_GET['details'];
	} else {
		error(404);
	}
} else {
	$Details = 'all';
}

show_header('Top 10 Tags');
?>
<div class="thin">
	<h2> Top 10 Tags </h2>
	<div class="linkbox">
		<a href="top10.php?type=torrents">[Torrents]</a>
		<a href="top10.php?type=users">[Users]</a>
		<a href="top10.php?type=tags"><strong>[Tags]</strong></a>
	</div>

<?

// defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, array(10,100,250)) ? $Limit : 10;

if ($Details=='all' || $Details=='ut') {
	if (!$TopUsedTags = $Cache->get_value('topusedtag_'.$Limit)) {
		$DB->query("SELECT
			t.ID,
			t.Name,
			COUNT(tt.GroupID) AS Uses,
			SUM(tt.PositiveVotes-1) AS PosVotes,
			SUM(tt.NegativeVotes-1) AS NegVotes
			FROM tags AS t
			JOIN torrents_tags AS tt ON tt.TagID=t.ID
			GROUP BY tt.TagID
			ORDER BY Uses DESC
			LIMIT $Limit");
		$TopUsedTags = $DB->to_array();
		$Cache->cache_value('topusedtag_'.$Limit,$TopUsedTags,3600*12);
	}

	generate_tag_table('Most Used Torrent Tags', 'ut', $TopUsedTags, $Limit);
}

if ($Details=='all' || $Details=='ur') {
	if (!$TopRequestTags = $Cache->get_value('toprequesttag_'.$Limit)) {
		$DB->query("SELECT
			t.ID,
			t.Name,
			COUNT(r.RequestID) AS Uses,
			'',''
			FROM tags AS t
			JOIN requests_tags AS r ON r.TagID=t.ID
			GROUP BY r.TagID
			ORDER BY Uses DESC
			LIMIT $Limit");
		$TopRequestTags = $DB->to_array();
		$Cache->cache_value('toprequesttag_'.$Limit,$TopRequestTags,3600*12);
	}

	generate_tag_table('Most Used Request Tags', 'ur', $TopRequestTags, $Limit, false);
}

if ($Details=='all' || $Details=='v') {
	if (!$TopVotedTags = $Cache->get_value('topvotedtag_'.$Limit)) {
		$DB->query("SELECT
			t.ID,
			t.Name,
			COUNT(tt.GroupID) AS Uses,
			SUM(tt.PositiveVotes-1) AS PosVotes,
			SUM(tt.NegativeVotes-1) AS NegVotes
			FROM tags AS t
			JOIN torrents_tags AS tt ON tt.TagID=t.ID
			GROUP BY tt.TagID
			ORDER BY PosVotes DESC
			LIMIT $Limit");
		$TopVotedTags = $DB->to_array();
		$Cache->cache_value('topvotedtag_'.$Limit,$TopVotedTags,3600*12);
	}

	generate_tag_table('Most Highly Voted Tags', 'v', $TopVotedTags, $Limit);
}

echo '</div>';
show_footer();
exit;

// generate a table based on data from most recent query to $DB
function generate_tag_table($Caption, $Tag, $Details, $Limit, $ShowVotes=true) {
?>
	<h3>Top <?=$Limit.' '.$Caption?>
		<small>
			- [<a href="top10.php?type=tags&amp;limit=100&amp;details=<?=$Tag?>">Top 100</a>]
			- [<a href="top10.php?type=tags&amp;limit=250&amp;details=<?=$Tag?>">Top 250</a>]
		</small>
	</h3>
	<table class="border">
	<tr class="colhead">
		<td class="center">Rank</td>
		<td>Tag</td>
		<td style="text-align:right">Uses</td>
<?	if($ShowVotes) {	?>
		<td style="text-align:right">Pos. Votes</td>
		<td style="text-align:right">Neg. Votes</td>
<?	}	?>
	</tr>
<?
	// in the unlikely event that query finds 0 rows...
	if(empty($Details)) {
		echo '
		<tr class="rowb">
			<td colspan="9" class="center">
				Found no tags matching the criteria
			</td>
		</tr>
		</table><br />';
		return;
	}
	$Rank = 0;
	foreach($Details as $Detail) {
		$Rank++;
		$Highlight = ($Rank%2 ? 'a' : 'b');

		// print row
?>
	<tr class="row<?=$Highlight?>">
		<td class="center"><?=$Rank?></td>
		<td><a href="torrents.php?taglist=<?=$Detail['Name']?>"><?=$Detail['Name']?></a></td>
		<td style="text-align:right"><?=$Detail['Uses']?></td>
<?		if($ShowVotes) { ?>
		<td style="text-align:right"><?=$Detail['PosVotes']?></td>
		<td style="text-align:right"><?=$Detail['NegVotes']?></td>
<?		} ?>
	</tr>
<?
	}
	echo '</table><br />';
}
?>
