<?php

$v_db_alt_user = '';
$v_db_alt_password = '';
$v_out = '';

$v_rootdir = dirname( __FILE__ );
$v_incdir = $v_rootdir . '/includes';

require( $v_incdir . '/session.php' );

$v_prot = 'http';
if ( isset( $_SERVER['HTTPS'] ) ) {
	$v_prot .= 's';
} elseif ( FORCE_HTTPS ) {
	http_response_code(301);
	header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	exit();
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['user'] ) && isset( $_POST['pass'] ) ) {
	// use the username and password from the POST data
	$_SESSION['user'] = $_POST['user'];
	$_SESSION['pass'] = $_POST['pass'];
}

if ( isset( $_SESSION['user'] ) && isset( $_SESSION['pass'] ) ) {
	$v_db_alt_user = $_SESSION['user'];
	$v_db_alt_password = $_SESSION['pass'];

	require( $v_incdir . '/connect.php' );
	require( $v_incdir . '/edit_functions.php' );
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['type'] == "logout" ) {
	fn_log_out();
}

?>

<html>
<head>
<style>
	a {text-decoration-line:none;}
	a:link {color:mediumblue;}
	a:visited {color:mediumblue;}
	h3 {margin-top:5px;margin-bottom:0px;}
	input[type=button], input[type=submit] {margin-top:5px;margin-bottom:5px;}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script>
	<?php
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			echo '$(document).ready(function() {' . "\n" . '$("#type_select").val("' . $_POST['type'] . '")' . "\n" . '});' . "\n";
		}
		echo file_get_contents( $v_incdir . '/edit.js' );
	?>
</script>
</head>
<body>

<form action="edit.php" method="post">
<?php
if ( ! isset( $_SESSION['user'] ) ) {
	echo 'Username: <input type="text" name="user" id="cp_user"><br />';
}
if ( ! isset( $_SESSION['pass'] ) ) {
	echo 'Password: <input type="password" name="pass" id="cp_pass"><br />';
}
?>
	Object Type or Action: <select name="type" id="type_select">
		<option value="select">Select</option>
		<option value="name">Names</option>
		<option value="content">Content</option>
		<option value="item">Items</option>
		<option value="style">Styles</option>
		<option value="category">Categories</option>
		<option value="document">Documents</option>
		<option value="export">Export All</option>
		<option value="logout">Log Out</option>
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
	$v_out2 .= '<div><div class="cp_object"><li>[<a href="new" onclick="fn_edit_help(this);return false;">HELP' . "</a>]</li></div></div>\n";
	$v_out2 .= '<div><div class="cp_object"><li>[<a href="new" onclick="fn_edit_object(this);return false;">CREATE NEW' . "</a>]</li></div></div>\n";

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

		$v_out = 'Site Data:<br><textarea id="cp_full_content" name="text" cols="60" rows="20">' . $v_out . '</textarea><br />' . "\n";

	}

	$v_out .= "<br /><br /><br /><br /><br />";
	echo $v_out;
}
?>


</body>
</html>

<?php
if ( isset( $o_mysql_connection ) ) {
	fn_close();
} else {
	exit;
}
?>
