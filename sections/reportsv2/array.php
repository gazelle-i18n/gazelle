<?
/*
 * The $Types array is the backbone of the reports system and is stored here so it can 
 * be included on the pages that need it, but not clog up the pages that don't.
 * Important thing to note about the array:
 * 1. When coding for a non music site, you need to ensure that the top level of the 
 * array lines up with the $Categories array in your config.php.
 * 2. The first sub array contains resolves that are present on every report type 
 * regardless of category.
 * 3. The only part that shouldn't be self explanatory is that for the tracks field in 
 * the report_fields arrays, 0 means not shown, 1 means required, 2 means required but
 * you can't tick the 'All' box.
 * 4. The current report_fields that are set up are tracks, sitelink, link and image. If
 * you wanted to add a new one, you'd need to add a field to the reportsv2 table, elements 
 * to the relevant report_fields arrays here, add the HTML in ajax_report and add security 
 * in takereport. 
 */

$Types = array(
		'master' => array(
			'dupe' => array(
				'priority' => '2',
				'title' => 'Dupe',
				'report_messages' => array(
					'Please specify a link to the original torrent.'
				),
				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => 'Your torrent has been deleted for being a duplicate of another torrent.'
				)
			),
			'banned' => array(
				'priority' => '988',
				'title' => 'Specifically Banned',
				'report_messages' => array(
					'Please specify exactly which entry on the Do Not Upload list this is violating.'
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '4',
					'delete' => '1',
					'pm' => 'The releases on the Do Not Upload list (on the upload page) are currently forbidden from being uploaded from the site. Do not upload them unless your torrent meets a condition specified in the comment.'
				)
			),
			'urgent' => array(
				'priority' => '3',
				'title' => 'Urgent',
				'report_messages' => array(
					'This report type is only for the very urgent reports, usually for personal information being found within a torrent.',
					'Abusing the Urgent reports could result in a warning or worse',
					'As by default this report type gives the staff absolutely no information about the problem, please be as clear as possible in your comments as to the problem'
				),
				'report_fields' => array(
					'sitelink' => '0',
					'track' => '0',
					'link' => '0',
					'image' => '0',
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => ''
				)
			),
			'other' => array(
				'priority' => '999',
				'title' => 'Other',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => ''
				)
			)
		),
		'1' => array( //Music Resolves
			'trump' => array(
				'priority' => '1',
				'title' => 'Trump',
				'report_messages' => array(
					'Please list the specific reason(s) the newer torrent trumps the older one.',
					'Please make sure you are reporting the torrent <strong>which has been trumped</strong> and should be deleted, not the torrent that you think should remain on site.'
				),

				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => 'Your torrent has been deleted as it was trumped by another torrent.'
				)
			),
			'tag_trump' => array (
				'priority' => '703',
				'title' => 'Tag Trump',
				'report_messages' => array(
					'Please list the specific tag(s) the newer torrent trumps the older one.',
					'Please make sure you are reporting the torrent <strong>which has been trumped</strong> and should be deleted, not the torrent that you think should remain on site.'
				),
				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '2.3.12. Properly tag your music files. Certain meta tags (e.g. ID3, Vorbis) are required on all music uploads. Make sure to use the proper format tags for your files (e.g. no ID3 tags for FLAC). ID3v2 tags for files are highly recommended over ID3v1. If you upload an album missing one or more of these tags, then another user may add the tags, re-upload, and report yours for deletion.'
				)
			),
			'folder_trump' => array (
				'priority' => '704',
				'title' => 'Folder Trump',
				'report_messages' => array(
					'Please list the folder name and what is wrong with it',
					'Please make sure you are reporting the torrent <strong>which has been trumped</strong> and should be deleted, not the torrent that you think should remain on site.'
				),
				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => 'Name your directories with meaningful titles, such as "Artist - Album (Year) - Format."  We advise that directory names in your uploads should at least be "Artist - Album (Year) - Format". The minimum acceptable is "Album", although it is preferable to include more information. If the directory name does not include this minimum then another user can rename the directory, re-upload and report yours for deletion. Avoid creating unnecessary nested folders (such as an extra folder for the actual album) inside your properly named directory. Nested folders make it less likely that downloaders leave the torrent unchanged in order to stay seeding.'
				)
			),
			'tracks_missing' => array(
				'priority' => '730',
				'title' => 'Track(s) Missing',
				'report_messages' => array(
					'Please list the track number and title of the missing track',
					'If possible, please provide a link to Amazon.com or another source showing the proper track listing.'
				),
				'report_fields' => array(
					'track' => '2',
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '1',
					'delete' => '1',
					'pm' => 'All music torrents must represent a complete album. If tracks are available separately, but not released as singles, you may not upload them individually.'
				)
			),
			'discs_missing' => array(
				'priority' => '740',
				'title' => 'Disc(s) Missing',
				'report_messages' => array(
					'If possible, please provide a link to Amazon.com or another source showing the proper track listing.'
				),
				'report_fields' => array(
					'track' => '0',
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '1',
					'delete' => '1',
					'pm' => 'All music torrents must represent a complete release. Albums must not be missing discs in the case of a multi-disc release.'
				)
			),
			'bonus_tracks' => array(
				'priority' => '920',
				'title' => 'Bonus Tracks Only',
				'report_messages' => array(
					'If possible, please provide a link to Amazon.com or another source showing the proper track listing.'
				),
				'report_fields' => array(
					'track' => '0',
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '1',
					'delete' => '1',
					'pm' => 'Please note that individual bonus tracks are not allowed to be uploaded without the rest of the album. Bonus tracks are not bonus discs.'
				)
			),						
			'transcode' => array(
				'priority' => '3',
				'title' => 'Transcode',
				'report_messages' => array(
					"Please list the tracks you checked, and the method used to determine the transcode.",
					"If possible, please include at least one screenshot of any spectral analysis done. You may include more than one."
				),
				'report_fields' => array(
					'image' => '0',
					'track' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '2',
					'delete' => '1',
					'pm' => 'No transcodes or re-encodes of lossy releases are acceptable here. For more information about transcodes, please visit this wiki page : http://what.cd/wiki.php?action=article&id=14'
				)
			),
			'low' => array(
				'priority' => '4',
				'title' => 'Low Bitrate',
				'report_messages' => array(
					"Please tell us the actual bitrate, and the software used to check."
				),		  
				'report_fields' => array(
					'track' => '0'
				),	  
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '2',
					'delete' => '1',
					'pm' => 'Music releases must have an average bitrate of at least 192kbps regardless of the format.'
				)	   
			),
			'mutt' => array(
				'priority' => '5',
				'title' => 'Mutt rip',
				'report_messages' => array(
					"Please list at least two (2) tracks which have different bitrates and/or encoders."
				),
				'report_fields' => array(
					'track' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '2',
					'delete' => '1',
					'pm' => 'All music torrents must be encoded with a single encoder using the same settings.'
				)
			),
			'single_track' => array(
				'priority' => '750',
				'title' => 'Unsplit album rip',
				'report_messages' => array(
					"If possible, please provide a link to Amazon.com or another source showing the proper track listing.",
					"This option is for uploads of CDs ripped as a single track when it should be split as on the CD",
					"This option is not to be confused with uploads of a single track, taken from a CD with multiple tracks (Tracks Missing)"
				),
				'report_fields' => array(
					'link' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => 'Albums must not be ripped or uploaded as a single track.'
				)
			),
			'tags_lots' => array(
				'priority' => '700',
				'title' => 'Very bad tags / no tags at all',
				'report_messages' => array(
					"Please specify which tags are missing, and whether they're missing from all tracks.",
					"Ideally, you will replace this torrent with one with fixed tags and report this with the reason 'Trumped'"
				),
				'report_fields' => array(
					'track' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => 'The [url=http://what.cd/rules.php?p=upload#r2.3.12]Uploading Rules[/url] require all uploads to be properly tagged. Your torrent has been marked as eligible for trumping, which is now visible to all interested users, who may trump your torrent at any time.
[b]You can avoid a 1-week warning by fixing this torrent yourself![/b] It\'s easy, and only takes a few minutes: Add or fix the required tags, and upload a new torrent to the site. Then, report (RP) the bad torrent for the reason "Trump", indicate in the report comments that you have fixed the tags, and provide a link (PL) to the new torrent.')
			),
			'folders_bad' => array(
				'priority' => '701',
				'title' => 'Very bad folder names',
				'report_messages' => array(
					"Please specify the issue with the folder names.",
					"Ideally you will replace this torrent with one with fixed folder names and report this with the reason 'Trumped'."
					),
				'report_fields' => array(),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => 'The [url=http://what.cd/rules.php?p=upload#2.3.2]Uploading Rules[/url] require all uploads to have meaningful directory names. Your torrent has been marked as eligible for trumping, which is now visible to all interested users, who may trump your torrent at any time.
[b]You can fix this torrent yourself![/b]It\'s easy, and only takes a few minutes: Add or fix the folder/directory name(s), and upload a new torrent to the site. Then, report (RP) the bad torrent for the reason "Trump", indicate in the report comments that you have fixed the directory name(s), and provide a link (PL) to the new torrent.')
			),
			'wrong_format' => array(
				'priority' => '705',
				'title' => 'Wrong specified format',
				'report_messages' => array(
					"Please specify the correct format."
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => 'Please be careful when specifying the format of your uploads'
				)
			),
			'format' => array(
				'priority' => '790',
				'title' => 'Disallowed Format',
				'report_messages' => array(
					"If applicable, list the relevant tracks"
				),
				'report_fields' => array(
					'track' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => 'The only formats allowed for music are: MP3, FLAC, Ogg Vorbis, AAC, AC3, DTS'
				)
			),
			'bitrate' => array(
				'priority' => '800',
				'title' => 'Inaccurate Bitrate',
				'report_messages' => array(
					"Please tell us the actual bitrate, and the software used to check.",
					"If the correct bitrate would make this torrent a duplicate, please report it as a dupe, and include the mislabeling in 'Comments'.",
					"If the correct bitrate would result in this torrent trumping another, please report it as a trump, and include the mislabeling in 'Comments'."
				),
				'report_fields' => array(
					'track' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => 'Bitrates must accurately reflect encoder presets or average bitrate of the audio files.'
				)
			),
			'source' => array(
				'priority' => '870',
				'title' => 'Radio/TV/FM/WEBRIP',
				'report_messages' => array(
					"Please include as much information as possible to verify the report"
				),
				'report_fields' => array(
					'link' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "Radio, television, web rips and podcasts are not allowed. It does not matter whether it's FM, direct satellite, internet, or even if it's a pre-broadcast tape."
				)
			),
			'discog' => array(
				'priority' => '890',
				'title' => 'Discography',
				'report_messages' => array(
					"Please include as much information as possible to verify the report"
				),
				'report_fields' => array(
					'link' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "Multi-album torrents are not allowed on the site under any circumstances. That means no discographies, Pitchfork compilations, etc."
				)
			),
			'user_discog' => array(
				'priority' => '880',
				'title' => 'User Compilation',
				'report_messages' => array(
					"Please include as much information as possible to verify the report"
				),
				'report_fields' => array(
					'link' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "User-made compilations are not allowed. Compilations must be reasonably official."
				)
			),
			'lineage' => array(
				'priority' => '900',
				'title' => 'No Lineage Info',
				'report_messages' => array(
					"Please list the specific information missing from the torrent (hardware, software, etc.)"
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => "All lossless analog rips must include clear information about source lineage. This information should be displayed in the torrent description. Optionally, the uploader may include the information in a .txt or .log file within the torrent."
				)
			),
			'edited' => array(
				'priority' => '940',
				'title' => 'Edited Log',
				'report_messages' => array(
					"Please explain exactly where you believe the log was edited."
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '4',
					'delete' => '1',
					'pm' => "No log editing is permitted. You may change the path of the files to hide your username or personal info in the home directory (e.g. C:\Documents and Settings\MyRealName\My Documents\) after ripping if need be. No other log editing is permitted."
				)
			),
			'audience' => array(
				'priority' => '760',
				'title' => 'Audience Recording',
				'report_messages' => array(
					"Please include as much information as possible to verify the report"
				),
				'report_fields' => array(
					'link' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "No unofficial audience recordings. Unofficially-mastered audience recordings (AUD) are not allowed here regardless of how rare you think they are."
				)
			),
			'filename' => array(
				'priority' => '770',
				'title' => 'Bad File Names',
				'report_messages' => array(
				),
				'report_fields' => array(
					'track' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "File names must accurately reflect the song titles. You may not have file names like 01track.mp3, 02track.mp3, etc."
				)
			),
			'cassette' => array(
				'priority' => '910',
				'title' => 'Unapproved Cassette',
				'report_messages' => array(
					"If the album was never released other than on cassette, please include a source."
				),
				'report_fields' => array(
					'link' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "Cassette-sourced uploads must be approved by staff first. You must contact a moderator privately for approval before uploading. Unapproved cassette torrents may be reported and deleted if no note exists of prior staff approval."
				)
			),
			'skips' => array(
				'priority' => '745',
				'title' => 'Skips / Encode Errors',
				'report_messages' => array(
					"Please tell us which track(s) we should check.",
					"Telling us where precisely in the song the skip/encode error can be heard will help us deal with your torrent."
				),
				'report_fields' => array(
					'track' => '2'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => "Music not sourced from vinyl must not contain pops, clicks, or skips. They will be deleted for rip/encode errors if reported. Music that is sourced from vinyl must not have excessive problems."
				)
			),
			'rescore' => array(
				'priority' => '747',
				'title' => 'Log Rescore Request',
				'report_messages' => array(
					"It could help us if you say exactly why you believe this log requires rescoring.",
					"For example, if it's a foreign log which needs scoring, or if the log wasn't uploaded at all"
				),
				'report_fields' => array(													  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => ""
				)
			),
			'ogg' => array(
				'priority' => '980',
				'title' => 'Disallowed Ogg Preset',
				'report_messages' => array(
					"Please include as much information as possible to verify the report"
				),
				'report_fields' => array(
					'track' => '0'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "Only -q8.x (~256 (VBR)) is allowed on the site for Ogg Vorbis. Torrents encoded with presets other than -q8.x will be deleted."
				)
			)
		),
		'2' => array( //Applications Rules Broken
			'missing_crack' => array(
				'priority' => '35',
				'title' => 'No Crack/Keygen/Patch',
				'report_messages' => array(
					'Please include as much information as possible to verify the report',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '1',
					'delete' => '1',
					'pm' => 'All applications must come with a crack, keygen, or other method of ensuring that downloaders can install them easily. App torrents with keygens, cracks, or patches that do not work and torrents missing clear installation instructions are deleted if reported. No exceptions.'
				)
			),
			'game' => array(
				'priority' => '40',
				'title' => 'Game',
				'report_messages' => array(
					'Please include as much information as possible to verify the report',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '4',
					'delete' => '1',
					'pm' => 'No games of any kind are allowed: whether PC, Mac, phone or any other platform.'
				)
			),
			'free' => array(
				'priority' => '60',
				'title' => 'Freely Available',
				'report_messages' => array(
					'Please include a link to a source of information or to the freely available app itself.',
				),
				'report_fields' => array(
					'link' => '1'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '1',
					'delete' => '1',
					'pm' => 'App releases must not be freely available tools. Application releases cannot be freely downloaded anywhere from any official source. Nor may you upload open source apps where the source code is available for free.'
				)
			),
			'description' => array(
				'priority' => '55',
				'title' => 'No Description',
				'report_messages' => array(
					'If possible, please provide a link to an accurate description',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '1',
					'delete' => '1',
					'pm' => 'Release descriptions for apps must contain good information about the application. You should either have a small description of the program (either taken from its website or from a NFO) or a link to information–ideally both.'
				)
			),
			'pack' => array(
				'priority' => '49',
				'title' => 'Archived Pack',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '1',
					'delete' => '1',
					'pm' => 'Sound sample packs, template collections, and font collections are allowed if they are official releases, not freely available, and unarchived.'
				)
			),
			'collection' => array(
				'priority' => '51',
				'title' => 'Collection of Cracks',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '1',
					'delete' => '1',
					'pm' => 'Collections of cracks, keygens or serials are not allowed. The crack, keygen or serial for an application must be in a torrent with its corresponding application. It cannot be uploaded separately from the application.'
				)
			),
			'hack' => array(
				'priority' => '50',
				'title' => 'Hacking Tool',
				'report_messages' => array(
					'Please include as much information as possible to verify the report',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0', 
					'warn' => '1',
					'delete' => '1',
					'pm' => 'Torrents containing hacking or cracking tools are not allowed.'
				)
			)
		),
		'3' => array( //Ebook Rules Broken
			'unrelated' => array(
				'priority' => '50',
				'title' => 'Unrelated Ebooks',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => 'Collections of eBooks are allowed only if each title is related to each other in a meaningful way.'
				)
			)
		),
		'4' => array( //Audiobook Rules Broken
			'skips' => array(
				'priority' => '745',
				'title' => 'Skips / Encode Errors',
				'report_messages' => array(
					"Please tell us which track(s) we should check.",
					"Telling us where precisely in the song the skip/encode error can be heard will help us deal with your torrent."
				),
				'report_fields' => array(
					'track' => '2'														  
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => "Music not sourced from vinyl must not contain pops, clicks, or skips. They will be deleted for rip/encode errors if reported. Music that is sourced from vinyl must not have excessive problems."
				)
			)
		),
		'5' => array( //E-Learning vidoes Rules Broken
			'dissallowed' => array(
				'priority' => '50',
				'title' => 'Disallowed Topic',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => 'Tutorials on how to use features of applications, musical instruments or computer hardware are the only allowed topics for eLearning Videos.'
				)
			)
		),
		'6' => array( //Comedy Rules Broken
			'talkshow' => array(
				'priority' => '50',
				'title' => 'Talkshow/Podcast',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => 'No radio talk shows and podcasts are allowed.'
				)
			)
		),
		'7' => array( //Comics Rules Broken
			'titles' => array(
				'priority' => '50',
				'title' => 'Multiple Comic Titles',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '',
					'delete' => '1',
					'pm' => "Collections may not span more than one comic title. You may not collect multiple different comic titles. e.g. 'The Amazing Spider-Man #1 and The Incredible Hulk #1' Exceptions: Titles may contain more than one comic title if either: it's a recognized comic crossover/event or it's a DCP weekly release."
				)
			),
			'volumes' => array(
				'priority' => '51',
				'title' => 'Multiple Volumes',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '',
					'delete' => '1',
					'pm' => "Torrents spanning multiple volumes are too large and must be uploaded as separate volumes."
				)
			)
		)
	);
