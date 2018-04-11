	function fn_edit_object(clicked, action) {
	// when a link is clicked, open it in the background and then remove the old content
		var link = $(clicked);
		var type = link.attr('href');
		var values = link.attr('cp_data');
		var url;
		var data = new FormData();
		if ( ! action ) {
			data.append("type", type);
			data.append("values", values);
			url = "includes/edit_help.php"
		} else if ( action == "delete" ) {
			if ( ! window.confirm("Are you sure you want to DELETE this object?") ) {
				return;
			}
			data.append("text", ">>>>> delete " + type + " " + values);
			url = "includes/import.php"
		}
		// make the request to includes/edit_help.php
		var xmlHttp = new XMLHttpRequest();
		xmlHttp.onreadystatechange = function() {
			if (xmlHttp.readyState == 4 && (xmlHttp.status == 200 || xmlHttp.status == 500)){
				var response = xmlHttp.responseText;
				var responseDom = $(response);
				var object = link.closest('.cp_object');
				object.css('display','none');
				object.parent().append(responseDom);
				if ( type == "new" ) {
					object.parent().after('<div><div class="cp_object"><li>[<a href="new" onclick="fn_edit_object(this);return false;">CREATE NEW' + "</a>]</li></div></div>\n");
				}
			}
		};
		xmlHttp.open("POST", url, true);
		xmlHttp.send(data);
	}
	function fn_delete_object(clicked) {
		fn_edit_object(clicked, "delete");
	}
	function fn_submit(clicked) {
		var button = $(clicked);
		var action = button.val();
		var text;
		if ( action == "Cancel" ) {
			button.parent().parent().children('.cp_object').css('display','block');
			button.parent().remove();
			return;
		} else if ( action = "Submit" ) {
			if ( ! window.confirm("Are you sure you want to modify this object?") ) {
				return;
			}
			var text = button.parent().children('textarea').val();
		}
		var data = new FormData();
		data.append("text", text);
		var xmlHttp = new XMLHttpRequest();
		xmlHttp.onreadystatechange = function() {
			if (xmlHttp.readyState == 4 && (xmlHttp.status == 200 || xmlHttp.status == 500)){
				var response = xmlHttp.responseText;
				var responseDom = $(response);
				button.parent().after(responseDom);
				button.parent().remove();
			}
		};
		xmlHttp.open("POST", "includes/import.php", true);
		xmlHttp.send(data);
	}
	function fn_edit_help(clicked) {
		var link = $(clicked);
		link.parent().html('[<a href="includes/documentation.html#names" target="_blank">Name Help</a>] &nbsp;[<a href="includes/documentation.html#content" target="_blank">Content Help</a>] &nbsp;[<a href="includes/documentation.html#items" target="_blank">Item Help</a>] &nbsp;[<a href="includes/documentation.html#styles" target="_blank">Style Help</a>] &nbsp;[<a href="includes/documentation.html#categories" target="_blank">Category Help</a>] &nbsp;[<a href="includes/documentation.html#documents" target="_blank">Document Help</a>]');
	}
