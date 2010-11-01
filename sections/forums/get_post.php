<?
//TODO: make this use the cache version of the thread, save the db query
/*********************************************************************\
//--------------Get Post--------------------------------------------//

This gets the raw BBCode of a post. It's used for editing and 
quoting posts. 

It gets called if $_GET['action'] == 'get_post'. It requires 
$_GET['post'], which is the ID of the post.

\*********************************************************************/

// Quick SQL injection check
if(!$_GET['post'] || !is_number($_GET['post'])){
	error(0);
}

// Variables for database input
$PostID = $_GET['post'];

// Mainly 
$DB->query("SELECT
		p.Body
		FROM forums_posts as p
		JOIN forums_topics as t on p.TopicID = t.ID
		JOIN forums as f ON t.ForumID=f.ID 
		WHERE 
		p.ID='$PostID' AND 
		f.MinClassRead<='$LoggedUser[Class]'");
list($Body) = $DB->next_record(MYSQLI_NUM);

// This gets sent to the browser, which echoes it wherever 

echo trim($Body);

?>