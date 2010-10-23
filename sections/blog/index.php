<?
enforce_login();

define('ANNOUNCEMENT_FORUM_ID', 19);
show_header('Blog');
require(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

if(check_perms('admin_manage_blog')) {
	if(!empty($_REQUEST['action'])) {
		switch($_REQUEST['action']) {
			case 'deadthread' :
				if(is_number($_GET['id'])){
					$DB->query("UPDATE blog SET ThreadID=NULL WHERE ID=".$_GET['id']);
					$Cache->delete_value('blog');
					$Cache->delete_value('feed_blog');
				}
				header('Location: blog.php');
				break;
			case 'takeeditblog':
				authorize();
				if(is_number($_POST['blogid']) && is_number($_POST['thread'])){
					$DB->query("UPDATE blog SET Title='".db_string($_POST['title'])."', Body='".db_string($_POST['body'])."', ThreadID=".$_POST['thread']." WHERE ID='".db_string($_POST['blogid'])."'");
					$Cache->delete_value('blog');
					$Cache->delete_value('feed_blog');
				}
				header('Location: blog.php');
				break;
			case 'editblog':
				if(is_number($_GET['id'])){
					$BlogID = $_GET['id'];
					$DB->query("SELECT Title, Body, ThreadID FROM blog WHERE ID=$BlogID");
					list($Title, $Body, $ThreadID) = $DB->next_record();
				}
				break;
			case 'deleteblog':
				if(is_number($_GET['id'])){
					authorize();
					$DB->query("DELETE FROM blog WHERE ID='".db_string($_GET['id'])."'");
					$Cache->delete_value('blog');
					$Cache->delete_value('feed_blog');
				}
				header('Location: blog.php');
				break;
		
			case 'takenewblog':
				authorize();
				$Title = db_string($_POST['title']);
				$Body = db_string($_POST['body']);
				$ThreadID = $_POST['thread'];
				if($ThreadID && is_number($ThreadID)) {
					$DB->query("SELECT ForumID FROM forums_topics WHERE ID=".$ThreadID);
					if($DB->record_count() < 1) {
						error("No such thread exists!");
						header('Location: blog.php');
					} 
				} else {
					$ThreadID = create_thread(ANNOUNCEMENT_FORUM_ID, $LoggedUser[ID], $Title, $Body);
					if($ThreadID < 1) {
						error(0);
					}
				}
				
				$DB->query("INSERT INTO blog (UserID, Title, Body, Time, ThreadID) VALUES ('$LoggedUser[ID]', '".db_string($_POST['title'])."', '".db_string($_POST['body'])."', '".sqltime()."', ".$ThreadID.")");
				$Cache->delete_value('blog');
		
				header('Location: blog.php');
				break;
		}
	}
		
	?>
		<div class="box thin">
			<div class="head">
				<?=((empty($_GET['action'])) ? 'Create a blog post' : 'Edit blog post')?>
			</div>
			<form action="blog.php" method="post">
				<div class="pad">
					<input type="hidden" name="action" value="<?=((empty($_GET['action'])) ? 'takenewblog' : 'takeeditblog')?>" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<? if(!empty($_GET['action']) && $_GET['action'] == 'editblog'){?> 
					<input type="hidden" name="blogid" value="<?=$BlogID; ?>" />
	<? }?> 
					<h3>Title</h3>
					<input type="text" name="title" size="95" <? if(!empty($Title)) { echo 'value="'.display_str($Title).'"'; } ?> /><br />
					<h3>Body</h3>
					<textarea name="body" cols="95" rows="15"><? if(!empty($Body)) { echo display_str($Body); } ?></textarea> <br />
					<h3>Thread ID</h3>
					<input type="text" name="thread" size="8"<? if(!empty($ThreadID)) { echo 'value="'.display_str($ThreadID).'"'; } ?> />
					(Leave blank to create thread automatically)
					<br /><br />
					<div class="center">
						<input type="submit" value="<?=((!isset($_GET['action'])) ? 'Create blog post' : 'Edit blog post') ?>" />
					</div>
				</div>
			</form>
		</div>
		<br />
<? 
}
?>
<div class="thin">
<?
if (!$Blog = $Cache->get_value('blog')) {
	$DB->query("SELECT
		b.ID,
		um.Username,
		b.Title,
		b.Body,
		b.Time,
		b.ThreadID
		FROM blog AS b LEFT JOIN users_main AS um ON b.UserID=um.ID
		ORDER BY Time DESC
		LIMIT 20");
	$Blog = $DB->to_array();
	$Cache->cache_value('Blog',$Blog,1209600);
}

foreach ($Blog as $BlogItem) {
	list($BlogID, $Author, $Title, $Body, $BlogTime, $ThreadID) = $BlogItem;
?>
			<div id="blog<?=$BlogID?>" class="box">
				<div class="head">
					<strong><?=$Title?></strong> - posted <?=time_diff($BlogTime);?> by <?=$Author?>
		<? if(check_perms('admin_manage_blog')) { ?> 
					- <a href="blog.php?action=editblog&amp;id=<?=$BlogID?>">[Edit]</a>
					<a href="blog.php?action=deleteblog&amp;id=<?=$BlogID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">[Delete]</a>
		 <? } ?>
				</div>
				<div class="pad">
					<?=$Text->full_format($Body)?>
		<? if($ThreadID) { ?>
					<br /><br />
					<em><a href="forums.php?action=viewthread&threadid=<?=$ThreadID?>">Discuss this post here</a></em>
		<? 		if(check_perms('admin_manage_blog')) { ?> 
					<a href="blog.php?action=deadthread&amp;id=<?=$BlogID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">[Dead]</a>
		<? 		}
			} ?>
				</div>
			</div>
		<br />
<? 
}
?>
</div>
<?
show_footer();
?>
