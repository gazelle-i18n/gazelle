function show_peers (TorrentID, Page) {
	if(Page>0) {
		ajax.get('torrents.php?action=peerlist&page='+Page+'&torrentid=' + TorrentID,function(response){
			$('#peers_' + TorrentID).show().raw().innerHTML=response;
		});
	} else {
		if ($('#peers_' + TorrentID).raw().innerHTML === '') {
			$('#peers_' + TorrentID).show().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=peerlist&torrentid=' + TorrentID,function(response){
				$('#peers_' + TorrentID).show().raw().innerHTML=response;
			});
		} else {
			$('#peers_' + TorrentID).toggle();
		}
	}
	$('#snatches_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#files_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_snatches (TorrentID, Page){
	if(Page>0) {
		ajax.get('torrents.php?action=snatchlist&page='+Page+'&torrentid=' + TorrentID,function(response){
			$('#snatches_' + TorrentID).show().raw().innerHTML=response;
		});
	} else {
		if ($('#snatches_' + TorrentID).raw().innerHTML === '') {
			$('#snatches_' + TorrentID).show().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=snatchlist&torrentid=' + TorrentID,function(response){
				$('#snatches_' + TorrentID).show().raw().innerHTML=response;
			});
		} else {
			$('#snatches_' + TorrentID).toggle();
		}
	}
	$('#peers_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#files_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_downloads (TorrentID, Page){
	if(Page>0) {
		ajax.get('torrents.php?action=downloadlist&page='+Page+'&torrentid=' + TorrentID,function(response){
			$('#downloads_' + TorrentID).show().raw().innerHTML=response;
		});
	} else {
		if ($('#downloads_' + TorrentID).raw().innerHTML === '') {
			$('#downloads_' + TorrentID).show().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=downloadlist&torrentid=' + TorrentID,function(response){
				$('#downloads_' + TorrentID).raw().innerHTML=response;
			});
		} else {
			$('#downloads_' + TorrentID).toggle();
		}
	}
	$('#peers_' + TorrentID).hide();
	$('#snatches_' + TorrentID).hide();
	$('#files_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_files(TorrentID){
	$('#files_' + TorrentID).toggle();
	$('#peers_' + TorrentID).hide();
	$('#snatches_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_reported(TorrentID){
	$('#files_' + TorrentID).hide();
	$('#peers_' + TorrentID).hide();
	$('#snatches_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#reported_' + TorrentID).toggle();
}

function add_tag(tag) {
	if ($('#tags').raw().value == "") {
		$('#tags').raw().value = tag;
	} else {
		$('#tags').raw().value = $('#tags').raw().value + ", " + tag;
	}
}

function toggle_group(groupid, link, event) {
	var clickedRow = link;
	while (clickedRow.nodeName != 'TR') {
		clickedRow = clickedRow.parentNode;
	}
	var group_rows = clickedRow.parentNode.children;
	//var showing = has_class(nextElementSibling(clickedRow), 'hidden'); // nextElementSibling(clickedRow) is a .edition
	var showing = $(clickedRow).nextElementSibling().has_class('hidden');
	var allGroups = event.ctrlKey;
	for (var i = 0; i < group_rows.length; i++) {
		var row = $(group_rows[i]);
		if (row.has_class('colhead_dark')) { continue; }
		if (row.has_class('colhead')) { continue; }
		var relevantRow = row.has_class('group') ? row.nextElementSibling() : row;
		if (allGroups || relevantRow.has_class('groupid_' + groupid)) {
			if (row.has_class('group')) {
				$('a.show_torrents_link', row).raw().title = (showing) ? 'Collapse this group' : 'Expand this group';
			} else {
				if (showing) {
					// show the row depending on whether the edition it's in is collapsed or not
					if (row.has_class('edition')) {
						row.show();
						showRow = ($('a', row.raw()).raw().innerHTML != '+');
					} else {
						if (showRow) {
							row.show();
						} else {
							row.hide();
						}
					}
				} else {
					row.hide();
				}
			}
		}
	}
	if (event.preventDefault) { event.preventDefault(); } else { event.returnValue = false; }
}

function toggle_edition(groupid, editionid, lnk, event) {
	var clickedRow = lnk;
	while (clickedRow.nodeName != 'TR') {
		clickedRow = clickedRow.parentNode;
	}
	//var showing = has_class(nextElementSibling(clickedRow), 'hidden');
	var showing = $(clickedRow).nextElementSibling().has_class('hidden');
	var allEditions = event.ctrlKey;
	var group_rows = $('tr.groupid_' + groupid);
	for (var i = 0; i < group_rows.results(); i++) {
		var row = $(group_rows.raw(i));
		if (row.has_class('edition') && (allEditions || row == clickedRow)) {
			$('a', row.raw()).raw().innerHTML = (showing) ? '&minus;' : '+';
			$('a', row.raw()).raw().title = (showing) ? 'Collapse this edition' : 'Expand this edition';
			continue;
		}
		if (allEditions || row.has_class('edition_' + editionid)) {
			if (showing && !row.has_class('torrentdetails')) {
				row.show();
			} else {
				row.hide();
			}
		}
	}
	if (event.preventDefault) { event.preventDefault(); } else { event.returnValue = false; }
}

// Bookmarks
function Bookmark(groupid,newname) {
        var lnk = $('#bookmarklink'+groupid).raw();
        lnk.setAttribute('newname', lnk.innerHTML);
        ajax.get("bookmarks.php?action=add&auth=" + authkey + "&groupid=" + groupid, function() {
                lnk.onclick = function() { unbookmark(groupid,this.getAttribute('newname')); return false; };
                lnk.innerHTML = newname;
        });
}

function unbookmark(groupid,newname) {
        if(window.location.pathname.indexOf('bookmarks.php') != -1) {
                ajax.get("bookmarks.php?action=remove&auth=" + authkey + "&groupid=" + groupid,function() {
                        $('#group_' + groupid).remove();
                        $('.groupid_' + groupid).remove();
                });
        } else {
                var lnk = $('#bookmarklink'+groupid).raw();
                lnk.setAttribute('newname', lnk.innerHTML);
                ajax.get("bookmarks.php?action=remove&auth=" + authkey + "&groupid=" + groupid, function() {
                        lnk.onclick = function() { Bookmark(groupid,this.getAttribute('newname')); return false; };
                        lnk.innerHTML = newname;
                });
        }
}

// For /sections/torrents/browse.php (not browse2.php)
function Bitrate() {
	$('#other_bitrate').raw().value = '';
	if ($('#bitrate').raw().options[$('#bitrate').raw().selectedIndex].value == 'Other') {
		$('#other_bitrate_span').show();
	} else {
		$('#other_bitrate_span').hide();
	}
}

var ArtistFieldCount = 1;

function AddArtistField() {
	if (ArtistFieldCount >= 100) { return; }
	var x = $('#AddArtists').raw();
	x.appendChild(document.createElement("br"));
	var ArtistField = document.createElement("input");
	ArtistField.type = "text";
	ArtistField.name = "aliasname[]";
	ArtistField.size = "20";
	x.appendChild(ArtistField);
	x.appendChild(document.createTextNode(' '));
	var Importance = document.createElement("select");
	Importance.name = "importance[]";
	Importance.innerHTML = '<option value="1">Main</option><option value="2">Guest</option><option value="3">Remixer</option>';
	x.appendChild(Importance);
	ArtistFieldCount++;	
}

function ToggleEditionRows() {
	$('#edition_title').toggle();
	$('#edition_label').toggle();
	$('#edition_catalogue').toggle();
}


function ToggleGroup(groupid) {
	var show = $('#showimg_' + groupid).has_class('show_torrents')
	if(show) {
		$('.groupid_' + groupid).show();
		$('#showimg_' + groupid).remove_class('show_torrents').add_class('hide_torrents');
	} else {
		$('.groupid_' + groupid).hide();
		$('#showimg_' + groupid).remove_class('hide_torrents').add_class('show_torrents');
	}
}
