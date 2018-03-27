<?php


$v_page = "30";
$v_type = "character";

$o_results = $o_mysql_connection->query("
	SELECT a.Name, a.Page, a.Description, a.Previous FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items a
	INNER JOIN " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "types ON a.Name = " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "types.Name
	INNER JOIN (
		SELECT Name, MAX( Page ) Page
		FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
		WHERE Page<='" . $o_mysql_connection->real_escape_string($v_page) . "'
		GROUP BY Name
	) b ON a.Name = b.Name AND a.Page = b.Page
	WHERE " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "types.Type = '" . $o_mysql_connection->real_escape_string($v_type) . "'
	ORDER BY Name ASC
");
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such item type. I have to figure out something better to do for this...";
	exit;
}
while ( $a_row = $o_results->fetch_assoc() ) {
	##### This is the part that loops through each of the items of that specific type
}
