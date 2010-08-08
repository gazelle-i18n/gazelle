<?
/*
 * Self explanatory
 */
?>
<div class="linkbox thin">
	<a href="reportsv2.php">Views</a>
	| <a href="reportsv2.php?action=new">New</a>
	| <a href="reportsv2.php?view=unauto">New (Un-auto)</a>
	| <a href="reportsv2.php?view=staff&amp;id=<?=$LoggedUser['ID']?>">My In-Progress</a>
	| <a href="reportsv2.php?view=resolver&amp;id=<?=$LoggedUser['ID']?>">My Resolved</a>
 	| <a href="reportsv2.php?view=resolved">Old</a>
 	| <a href="reportsv2.php?action=search">Search</a>
</div>
