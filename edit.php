<?php

$v_db_alt_user = '';
$v_db_alt_password = '';
$v_out = '';

$v_rootdir = dirname( __FILE__ );
$v_incdir = $v_rootdir . '/includes';

require( $v_incdir . '/exp_functions.php' );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	// use the username and password from the POST data
	$v_db_alt_user = $_POST['user'];
	$v_db_alt_password = $_POST['pass'];

	require( $v_incdir . '/config.php' );
	require( $v_incdir . '/connect.php' );
}

?>

<html>
<head>
<style>
	a {text-decoration-line:none;}
	a:link {color:mediumblue;}
	a:visited {color:mediumblue;}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script>
	<?php
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			echo '$(document).ready(function() {' . "\n" . '$("#type_select").val("' . $_POST['type'] . '")' . "\n" . '});' . "\n";
		}
	?>
	function fn_edit_object(clicked) {
	// when a link is clicked, open it in the background and then remove the old content
		var link = $(clicked);
		var type = link.attr('href').split( "/" )[0];
		var values = link.attr('href').split( "/" )[1];
		var username = $('#cp_user').val();
		var password = $('#cp_pass').val();
		var data = new FormData();
		data.append("user", username);
		data.append("pass", password);
		data.append("type", type);
		data.append("values", values);
		// make the request to includes/edit_help.php
		var xmlHttp = new XMLHttpRequest();
		xmlHttp.onreadystatechange = function() {
			if (xmlHttp.readyState == 4 && xmlHttp.status == 200){
				var response = xmlHttp.responseText;
				var responseDom = $(response);
				link.parent().css('display','none');
				// other stuff here
				link.parent().parent().append(responseDom);
			}
		};
		xmlHttp.open("POST", "includes/edit_help.php", true);
		xmlHttp.send(data);
	}
	function fn_submit(clicked) {
		if ( ! window.confirm("Are you sure you want to modify this object?") ) {
			return;
		}
		var button = $(clicked);
		var text = button.parent().children('textarea').val();
		var username = $('#cp_user').val();
		var password = $('#cp_pass').val();
		var data = new FormData();
		data.append("user", username);
		data.append("pass", password);
		data.append("text", text);
		var xmlHttp = new XMLHttpRequest();
		xmlHttp.onreadystatechange = function() {
			if (xmlHttp.readyState == 4 && xmlHttp.status == 200){
				var response = xmlHttp.responseText;
				button.parent().remove();
			}
		};
		xmlHttp.open("POST", "import.php", true);
		xmlHttp.send(data);
	}
	function fn_delete(clicked) {
		if ( ! window.confirm("Are you sure you want to DELETE this object?") ) {
			return;
		}
		var button = $(clicked);
		var text = button.parent().children('input[type="hidden"]').val();
		var username = $('#cp_user').val();
		var password = $('#cp_pass').val();
		var data = new FormData();
		data.append("user", username);
		data.append("pass", password);
		data.append("text", text);
		var xmlHttp = new XMLHttpRequest();
		xmlHttp.onreadystatechange = function() {
			if (xmlHttp.readyState == 4 && xmlHttp.status == 200){
				var response = xmlHttp.responseText;
				button.parent().remove();
			}
		};
		xmlHttp.open("POST", "import.php", true);
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
		<option value="names">Names</option>
		<option value="contents">Contents</option>
		<option value="items">Items</option>
		<option value="styles">Styles</option>
		<option value="categories">Categories</option>
		<option value="documents">Documents</option>
	</select>
	<input type="hidden" name="action" value="type_select">
	<br />
	<input type="submit" value="Submit">
</form>
<br />

<?php
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	$v_action = $_POST['action'];
	if ( $v_action == "type_select" ) {
		$v_type = $_POST['type'];
		if ( $v_type == "names" ) {
			$v_out .= "<h2>Names:</h2><ul>";
			$v_query = "
				SELECT URI, ID from " . $v_table_prefix . "names 
				ORDER BY ID ASC
			";
			$o_results = fn_query_check( "\"names\"", $v_query, false );
			while ( $a_row = $o_results->fetch_assoc() ) {
				$v_out .= '<div><li>name <a href="names/' . $a_row['URI'] . '" onclick="fn_edit_object(this);return false;">' . $a_row['URI'] . " " . fn_minimize_ID($a_row['ID']) . "</a></li></div>\n";
			}
			$v_out .= '</ul>';
		} elseif ( $v_type == "contents" ) {
			$v_out .= "<h2>Names:</h2><ul>";
			$v_query = "
				SELECT ID, Content, Type, Name from " . $v_table_prefix . "contents
				ORDER BY ID ASC
			";
			$o_results = fn_query_check( "\"contents\"", $v_query, false );
			while ( $a_row = $o_results->fetch_assoc() ) {
				if ( $a_row['Type'] == "block" || $a_row['Type'] == "list" ) {
					$v_out .= '<div><li>content <a href="contents/' .  fn_minimize_ID($a_row['ID']) . '&' . $a_row['Type'] . '&' . $a_row['Name'] . '" onclick="fn_edit_object(this);return false;">' . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . " " . $a_row['Name'] . "</a></li></div>\n";
				} else {
					$v_out .= '<div><li>content <a href="contents/' .  fn_minimize_ID($a_row['ID']) . '&' . $a_row['Type'] . '&' . $a_row['Name'] . '" onclick="fn_edit_object(this);return false;">' . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "</a></li></div>\n";
				}
			}
		} elseif ( $v_type == "items" ) {
			$v_out .= "<h2>Items:</h2><ul>";
			$v_query = "
				SELECT Name, ID, Description from " . $v_table_prefix . "items
				ORDER BY ID ASC
			";
				$o_results = fn_query_check( "\"items\"", $v_query, false );
			while ( $a_row = $o_results->fetch_assoc() ) {
				$v_out .= '<div><li>item <a href="items/' . $a_row['Name'] . "&" . fn_minimize_ID($a_row['ID']) . '" onclick="fn_edit_object(this);return false;">' . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . "</a></li></div>\n";
			}
		} elseif ( $v_type == "styles" ) {
			$v_out .= "<h2>Styles:</h2><ul>";
			$v_query = "
				SELECT Type, Name, ID, Description from " . $v_table_prefix . "styles
				ORDER BY ID ASC
			";
			$o_results = fn_query_check( "\"styles\"", $v_query, false );
			while ( $a_row = $o_results->fetch_assoc() ) {
				$v_out .= '<div><li>style <a href="styles/' . $a_row['Name'] . "&" . fn_minimize_ID($a_row['ID']) . '" onclick="fn_edit_object(this);return false;">' . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "</a></li></div>\n";
			}
		} elseif ( $v_type == "categories" ) {
			$v_out .= "<h2>Categories:</h2><ul>";
			$v_query = "
				SELECT Name, Category, Start, End from " . $v_table_prefix . "categories
				ORDER BY Category ASC
			";
			$o_results = fn_query_check( "\"categories\"", $v_query, false );
			while ( $a_row = $o_results->fetch_assoc() ) {
				$v_out .= '<div><li>category <a href="categories/' . $a_row['Name'] . "&" . $a_row['Category'] . "&" . fn_minimize_ID($a_row['Start']) . '" onclick="fn_edit_object(this);return false;">' . $a_row['Name'] . " " . $a_row['Category'] . " " . fn_category_id($a_row['Start'], $a_row['End']) . "</a></li></div>\n";
			}
		} elseif ( $v_type == "documents" ) {
			$v_out .= "<h2>Documents:</h2><ul>";
			$v_query = "
				SELECT URI, Description from " . $v_table_prefix . "documents
				ORDER BY URI ASC
			";
			$o_results = fn_query_check( "\"documents\"", $v_query, false );
			while ( $a_row = $o_results->fetch_assoc() ) {
				$v_out .= '<div><li>document <a href="documents/' . $a_row['URI'] . '" onclick="fn_edit_object(this);return false;">' . $a_row['URI'] . "</a></li></div>\n";
			}
		}
	}

	echo $v_out;


/*









	$v_query = "
		SELECT URI, Description from " . $v_table_prefix . "documents
		ORDER BY URI ASC
	";
	$o_results = fn_query_check( "\"documents\"", $v_query, false );
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare document " . $a_row['URI'] . "\n" . $a_row['Description'] . "\n\n";
	}


*/




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
