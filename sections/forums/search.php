<?
//TODO: Clean up this fucking mess
/*
Forums search result page
*/


list($Page,$Limit) = page_limit(POSTS_PER_PAGE);

// Searching for posts by a specific user

// What are we looking for? Let's make sure it isn't dangerous.


if((isset($_GET['type']) && $_GET['type'] == 'body')) {
	$Type = 'body';
} else {
	$Type='title';

}

// What are we looking for? Let's make sure it isn't dangerous.
if(isset($_GET['search'])) {
	$Search = trim($_GET['search']);
} else {
	$Search = '';
}

// Searching for posts by a specific user
if(!empty($_GET['user'])) {
	$User = $_GET['user'];
	$DB->query("SELECT ID FROM users_main WHERE Username='".db_string($User)."'");
	list($AuthorID) = $DB->next_record();
} else {
	$User = '';
}

// Are we looking in individual forums?
if(isset($_GET['forums']) && is_array($_GET['forums'])) {
	$ForumArray = array();
	foreach($_GET['forums'] as $Forum) {
		if(is_number($Forum)) {
			$ForumArray[]=$Forum;
		}
	}
	if(count($ForumArray)>0) {
		$SearchForums = implode(', ',$ForumArray);
	}
}

// Let's hope we got some results - start printing out the content.
show_header('Forums'.' > '.'Search');
show_message();
?>
<div class="thin">
	<h2><a href="forums.php">Forums</a> &gt; Search</h2>
	<form action="" method="get">
		<input type="hidden" name="action" value="search" />
		<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
			<tr>
				<td><strong>Search for:</strong></td>
				<td>
					<input type="text" name="search" size="70" value="<?=display_str($Search)?>" />
				</td>
			</tr>
			<tr>
				<td><strong>Search in:</strong></td>
				<td>
					<input type="radio" name="type" id="type_title" value="title" <? if($Type == 'title') { echo 'checked="checked" '; }?>/> 
					<label for="type_title">Titles</label>
					<input type="radio" name="type" id="type_body" value="body" <? if($Type == 'body') { echo 'checked="checked" '; }?>/> 
					<label for="type_body">Post bodies</label>
				</td>
			</tr>
			<tr>
				<td><strong>Forums:</strong></td>
				<td>
		<table class="cat_list">
	
							
<?// List of forums
$Open = false;
$LastCategoryID = -1;
$Columns = 0;

foreach($Forums as $Forum) {
	if ($Forum['MinClassRead'] > $LoggedUser['Class']) {
		continue;
	}
	
	$Columns++;
	
	if ($Forum['CategoryID'] != $LastCategoryID) {
		$LastCategoryID = $Forum['CategoryID'];
		if($Open) {
			if ((5-($Columns%5)) > 0) { ?>
				<td colspan="<?=(5-($Columns%5))?>"></td>
<? 			
			}

?>
			</tr>
<?		
		}
		$Columns = 0;
		$Open = true;
?>
			<tr>
				<td colspan="5"><strong><?=$ForumCats[$Forum['CategoryID']]?></strong></td>
			</tr>
			<tr>
<?	} elseif ($Columns%5  == 0) { ?>
			</tr>
			<tr>
<?	} ?>
				<td>
					<input type="checkbox" name="forums[]" value="<?=$Forum['ID']?>" id="forum_<?=$Forum['ID']?>"<? if(isset($_GET['forums']) && in_array($Forum['ID'], $_GET['forums'])) { echo ' checked="checked"';} ?> />
					<label for="forum_<?=$Forum['ID']?>"><?=$Forum['Name']?></label>
				</td>
<? } 
if ((5-($Columns%5)) > 0) { ?>
				<td colspan="<?=(5-($Columns%5))?>"></td>
<? } ?>
			</tr>
		</table>
					</td>
				</tr>
				<tr>
					<td><strong>Username:</strong></td>
					<td>
						<input type="text" name="user" size="70" value="<?=display_str($User)?>" />
					</td>
				</tr>
				<tr>
					<td colspan="2" class="center">
						<input type="submit" value="Search" />
					</td>
				</tr>
			</table>	
		</form>
	<div class="linkbox">
<?

// Break search string down into individual words
$Words = explode(' ',  db_string($Search));

if($Type == 'body') {

	$sql = "SELECT SQL_CALC_FOUND_ROWS
		t.ID,
		t.Title,
		t.ForumID,
		f.Name,
		p.AddedTime,
		p.ID,
		CEIL((SELECT COUNT(ID) 
			FROM forums_posts 
			WHERE forums_posts.TopicID = p.TopicID 
			AND forums_posts.ID <= p.ID)) 
			AS Post
		FROM forums_posts AS p
		JOIN forums_topics AS t ON t.ID=p.TopicID
		JOIN forums AS f ON f.ID=t.ForumID
		WHERE 
		f.MinClassRead<='$LoggedUser[Class]' AND ";

	//In tests, this is significantly faster than LOCATE
	$sql .= "p.Body LIKE '%";
	$sql .= implode("%' AND p.Body LIKE '%", $Words);
	$sql .= "%' ";

	//$sql .= "LOCATE('";
	//$sql .= implode("', p.Body) AND LOCATE('", $Words);
	//$sql .= "', p.Body) ";

	if(isset($SearchForums)) {
		$sql.=" AND f.ID IN ($SearchForums)";
	}
	if(isset($AuthorID)) {
		$sql.=" AND p.AuthorID='$AuthorID' ";
	}
	
	$sql .= "ORDER BY p.AddedTime DESC LIMIT $Limit";
	
} else {
	$sql = "SELECT SQL_CALC_FOUND_ROWS 
		t.ID,
		t.Title,
		t.ForumID,
		f.Name,
		t.LastPostTime,
		'',
		''
		FROM forums_topics AS t 
		JOIN forums AS f ON f.ID=t.ForumID
		WHERE 
		f.MinClassRead<='$LoggedUser[Class]' AND ";
	$sql .= "t.Title LIKE '%";
	$sql .= implode("%' AND t.Title LIKE '%", $Words);
	$sql .= "%' ";
	if(isset($SearchForums)) {
		$sql.=" AND f.ID IN ($SearchForums)";
	}
	if(isset($AuthorID)) {
		$sql.=" AND t.AuthorID='$AuthorID' ";
	}
	$sql .= "ORDER BY t.LastPostTime DESC LIMIT $Limit";
}

// Perform the query
$Records = $DB->query($sql);
$DB->query('SELECT FOUND_ROWS()');
list($Results) = $DB->next_record();
$DB->set_query_id($Records);

$Pages=get_pages($Page,$Results,POSTS_PER_PAGE,9);
echo $Pages;
?>
	</div>
	<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
	<tr class="colhead">
		<td>Forum</td>
		<td>Topic</td>
		<td>Time</td>
	</tr>
<? if($DB->record_count() == 0) { ?>
		<tr><td colspan="3">Nothing found!</td></tr>
<? }

$Row = 'a'; // For the pretty colours
while(list($ID, $Title, $ForumID, $ForumName, $LastTime, $PostID, $Post) = $DB->next_record()) {
	$Row = ($Row == 'a') ? 'b' : 'a';
	// Print results
?>
		<tr class="row<?=$Row?>">
			<td>
				<a href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>"><?=$ForumName?></a>
			</td>
			<td>
				<a href="forums.php?action=viewthread&amp;threadid=<?=$ID?>"><?=cut_string($Title, 80) ?></a>
<? if($Type == 'body') { ?>
				<span style="float: right;" class="last_read" title="Jump to post"><a href="forums.php?action=viewthread&amp;threadid=<?=$ID?><? if(!empty($PostID) && !empty($Post)) { echo '&amp;post='.$Post.'#post'.$PostID; } ?>"></a></span>
<? } ?>
			</td>
			<td>
				<?=time_diff($LastTime)?>
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
<? show_footer(); ?>
