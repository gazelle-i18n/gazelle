function SetMessage() {
	$('#quickpost').raw().value = document.getElementById('common_answers_body').innerHTML;
	$('#common_answers').hide();
}

function UpdateMessage() {
	var id = document.getElementById('common_answers_select').value;

	ajax.get("?action=get_response&id=" + id, function (data) {
		$('#common_answers_body').raw().innerHTML = data;
		$('#first_common_response').remove()
	});
}

function SaveMessage(id) {
	var ajax_message = 'ajax_message_' + id;
	var ToPost = [];
	
	ToPost['id'] = id;
	ToPost['name'] = document.getElementById('response_name_' + id).value;
	ToPost['message'] = document.getElementById('response_message_' + id).value;

	ajax.post("?action=edit_response", ToPost, function (data) {
			if (data == '1') {
				document.getElementById(ajax_message).textContent = 'Response successfully created.';
			} else if (data == '2') {
				document.getElementById(ajax_message).textContent = 'Response successfully edited.';
			} else {
				document.getElementById(ajax_message).textContent = 'Something went wrong.';
			}
			$('#' + ajax_message).show();
			var t = setTimeout("$('#" + ajax_message + "').hide()", 2000);
		}
	);
}

function DeleteMessage(id) {
	var div = '#response_' + id;
	var ajax_message = 'ajax_message_' + id;

	var ToPost = [];
	ToPost['id'] = id;

	ajax.post("?action=delete_response", ToPost, function (data) {
		$(div).hide();
		if (data == '1') {
			document.getElementById(ajax_message).textContent = 'Response successfully deleted.';
		} else {
			document.getElementById(ajax_message).textContent = 'Something went wrong.';
		}
		$('#'+ajax_message).show();
		var t = setTimeout("$('#" + ajax_message + "').hide()", 2000);
	});
}

function Assign() {
	var ToPost = [];
	ToPost['assign'] = document.getElementById('assign_to').value;
	ToPost['convid'] = document.getElementById('convid').value;

	ajax.post("?action=assign", ToPost, function (data) {
		if (data == '1') {
			document.getElementById('ajax_message').textContent = 'Conversation successfully assigned.';
		} else {
			document.getElementById('ajax_message').textContent = 'Something went wrong.';
		}
		$('#ajax_message').show();
		var t = setTimeout("$('#ajax_message').hide()", 2000);
	});
}