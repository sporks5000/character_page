<?php

// Define the database connectivity variables
define('DB_NAME', 'sporks50_char');
define('DB_USER', 'sporks50_char');
define('DB_PASSWORD', 'bBpA?7h%s#w~XC');
define('DB_HOST', 'localhost');
// assuming that the source is linking to this page, what's the base URL for those requests?
define('REFERER_BASE','www.all-night-laundry.com/post/');
// for building links back to the site, it's useful to know what protocol is being used:
define('PROTOCOL','http');
// What's the base URI for this page? This will usually be "/"
define('BASE_URI','/aln/');
// What table prefix will the database use for table names
define('TABLE_PREFIX', 'cp_');
// the posts can be in a subdirectory of this
define('POST_DIR', 'posts/');

if ( $_SERVER['HTTP_HOST'] == "example.com" ) {
	// With this, we can have multiple domains pointed at the same docroot but showing different content
} elseif ( $_SERVER['HTTP_HOST'] == "example2.com" ) {
	// This can be replicated as many times as needed for as much content as you want to cover
} else {
	// Any of the above define statements can be moved out of the above section and replicated into all of the sections here with different values as needed
	// However, if we're defining different prefixes for each one, then the database connectivity data can remain the same.
}
