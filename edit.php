<?php

$v_db_alt_user = '';
$v_db_alt_password = '';
$v_out = '';

$v_rootdir = dirname( __FILE__ );
$v_incdir = $v_rootdir . '/includes';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	// use the username and password from the POST data
	$v_db_alt_user = $_POST['user'];
	$v_db_alt_password = $_POST['pass'];

	require( $v_incdir . '/config.php' );
	require( $v_incdir . '/connect.php' );
	require( $v_incdir . '/edit_functions.php' );
}

?>

<html>
<head>
<style>
	a {text-decoration-line:none;}
	a:link {color:mediumblue;}
	a:visited {color:mediumblue;}
	h3 {margin-top:5px;margin-bottom:0px;}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script>
	<?php
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			echo '$(document).ready(function() {' . "\n" . '$("#type_select").val("' . $_POST['type'] . '")' . "\n" . '});' . "\n";
		}
	?>
	function fn_edit_object(clicked, action) {
	// when a link is clicked, open it in the background and then remove the old content
		var link = $(clicked);
		var type = link.attr('href');
		var values = link.attr('cp_data');
		var username = $('#cp_user').val();
		var password = $('#cp_pass').val();
		var url;
		var data = new FormData();
		data.append("user", username);
		data.append("pass", password);
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
					object.parent().after('<div><div class="cp_object"><li><a href="new" onclick="fn_edit_object(this);return false;">CREATE NEW' + "</a></li></div></div>\n");
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
		var username = $('#cp_user').val();
		var password = $('#cp_pass').val();
		var data = new FormData();
		data.append("user", username);
		data.append("pass", password);
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
</script>
</head>
<body>

<form action="edit.php" method="post">
	Username: <input type="text" name="user" id="cp_user"<?php
if ( $v_db_alt_user ) {
	echo " value=\"" . $v_db_alt_user . "\"";
}
?>>
	<br />
	Password: <input type="password" name="pass" id="cp_pass"<?php
if ( $v_db_alt_user ) {
	echo " value=\"" . $v_db_alt_password . "\"";
}
?>>
	<br />
	Object Type: <select name="type" id="type_select">
		<option value="select">Select</option>
		<option value="name">Names</option>
		<option value="content">Contents</option>
		<option value="item">Items</option>
		<option value="style">Styles</option>
		<option value="category">Categories</option>
		<option value="document">Documents</option>
		<option value="export">Export All</option>
	</select>
	<br />
	<input type="submit" value="Submit">
</form>

<?php

function fn_make_row( $v_type, $a_row ) {
	return '<div><div class="cp_object">' . fn_make_links( $v_type, $a_row ) . "</div></div>\n";
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	$v_type = $_POST['type'];
	$v_out2 = '<div><div class="cp_object"><li><a href="new" onclick="fn_edit_object(this);return false;">CREATE NEW' . "</a></li></div></div>\n";

#========================================================================#
#== Pull the names and key details of all items in a specific category ==#
#========================================================================#

	if ( $v_type == "name" ) {
		$v_out .= "<h2>Names:</h2><ul>\n" . $v_out2;
		$v_query = "
			SELECT URI, ID from " . $v_table_prefix . "names 
			ORDER BY ID ASC
		";
		$o_results = fn_query_check( "\"names\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= fn_make_row( $v_type, $a_row );
		}
		$v_out .= '</ul>';
	} elseif ( $v_type == "content" ) {
		$v_out .= "<h2>Contents:</h2><ul>\n" . $v_out2;
		$v_query = "
			SELECT ID, Content, Type, Name from " . $v_table_prefix . "contents
			ORDER BY ID ASC
		";
		$o_results = fn_query_check( "\"contents\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= fn_make_row( $v_type, $a_row );
		}
	} elseif ( $v_type == "item" ) {
		$v_out .= "<h2>Items:</h2><ul>\n" . $v_out2;
		$v_query = "
			SELECT Name, ID, Description from " . $v_table_prefix . "items
			ORDER BY ID ASC
		";
			$o_results = fn_query_check( "\"items\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= fn_make_row( $v_type, $a_row );
		}
	} elseif ( $v_type == "style" ) {
		$v_out .= "<h2>Styles:</h2><ul>\n" . $v_out2;
		$v_query = "
			SELECT Type, Name, ID, Description from " . $v_table_prefix . "styles
			ORDER BY ID ASC
		";
		$o_results = fn_query_check( "\"styles\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= fn_make_row( $v_type, $a_row );
		}
	} elseif ( $v_type == "category" ) {
		$v_out .= "<h2>Categories:</h2><ul>\n" . $v_out2;
		$v_query = "
			SELECT Name, Category, Start, End from " . $v_table_prefix . "categories
			ORDER BY Category ASC
		";
		$o_results = fn_query_check( "\"categories\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= fn_make_row( $v_type, $a_row );
		}
	} elseif ( $v_type == "document" ) {
		$v_out .= "<h2>Documents:</h2><ul>\n" . $v_out2;
		$v_query = "
			SELECT URI, Description from " . $v_table_prefix . "documents
			ORDER BY URI ASC
		";
		$o_results = fn_query_check( "\"documents\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= fn_make_row( $v_type, $a_row );
		}

#===========================================#
#== Just output everything to a text area ==#
#===========================================#

	} elseif ( $v_type == "export" ) {
		$v_query = "
			SELECT URI, ID from " . $v_table_prefix . "names 
			ORDER BY ID ASC
		";
		$o_results = fn_query_check( "\"names\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare name " . $a_row['URI'] . " " . fn_minimize_ID($a_row['ID']) . "\n";
		}
		$v_out .= "\n\n\n";

		$v_query = "
			SELECT Name, Category, Start, End from " . $v_table_prefix . "categories
			ORDER BY Category ASC
		";
		$o_results = fn_query_check( "\"categories\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare category " . $a_row['Name'] . " " . $a_row['Category'] . " " . fn_category_id($a_row['Start'], $a_row['End']) . "\n";
		}
		$v_out .= "\n\n\n";

		$v_query = "
			SELECT ID, Content, Type, Name from " . $v_table_prefix . "contents
			ORDER BY ID ASC
		";
		$o_results = fn_query_check( "\"contents\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			if ( $a_row['Type'] == "block" || $a_row['Type'] == "list" ) {
				$v_out .= ">>>>> declare content " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . " " . $a_row['Name'] . "\n" . $a_row['Content'] . "\n\n";
			} else {
				$v_out .= ">>>>> declare content " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "\n" . $a_row['Content'] . "\n\n";
			}
		}
		$v_out .= "\n\n\n";

		$v_query = "
			SELECT Name, ID, Description from " . $v_table_prefix . "items
			ORDER BY ID ASC
		";
			$o_results = fn_query_check( "\"items\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare item " . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . "\n" . $a_row['Description'] . "\n\n";
		}
		$v_out .= "\n\n\n";

		$v_query = "
			SELECT Type, Name, ID, Description from " . $v_table_prefix . "styles
			ORDER BY ID ASC
		";
			$o_results = fn_query_check( "\"styles\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare style " . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "\n" . $a_row['Description'] . "\n\n";
		}
		$v_out .= "\n\n\n";

		$v_query = "
			SELECT URI, Description from " . $v_table_prefix . "documents
			ORDER BY URI ASC
		";
			$o_results = fn_query_check( "\"documents\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare document " . $a_row['URI'] . "\n" . $a_row['Description'] . "\n\n";
		}

		while ( substr( $v_out, -1 ) == "\n" ) {
			$v_out = substr( $v_out, 0, -1 );
		}

		$v_out =  'Site Data:<br><textarea name="text" cols="60" rows="20">' . $v_out . '</textarea><br />' . "\n";
	}

	$v_out .= "<br /><br /><br /><br /><br />";
	echo $v_out;
}
?>


</body>
</html>

<?php
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	fn_close();
} else {
	exit;
}
?>
