<?php

require( dirname( __FILE__ ) . '/connect.php' );

##### Need to add error checking for all of these
$o_results = $o_mysql_connection->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "names ( ID MEDIUMINT PRIMARY KEY NOT NULL AUTO_INCREMENT, URI TEXT, Page FLOAT(8.2) )";
$o_results = $o_mysql_connection->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "contents ( ID MEDIUMINT PRIMARY KEY NOT NULL AUTO_INCREMENT, Page FLOAT(8.2), Content MEDIUMTEXT )";
$o_results = $o_mysql_connection->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "items ( ID MEDIUMINT PRIMARY KEY NOT NULL AUTO_INCREMENT, Name TEXT, Page FLOAT(8.2), Description LONGTEXT )";
$o_results = $o_mysql_connection->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "styles ( ID MEDIUMINT PRIMARY KEY NOT NULL AUTO_INCREMENT, Type TINYTEXT, Name TINYTEXT, Page FLOAT(8.2), Description LONGTEXT )";
$o_results = $o_mysql_connection->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "types ( ID MEDIUMINT PRIMARY KEY NOT NULL AUTO_INCREMENT, Name TINYTEXT, Type TINYTEXT )";

echo "I need to add error checking here, but this probably did the thing.\n";
