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

if ( $_SERVER['HTTP_HOST'] == "sporks5000.com" ) {
	define('TABLE_PREFIX', 'cp_');
} else {
	define('TABLE_PREFIX', 'cp_');
}

// Determine the page URI
$rewrite = false;
$page = '';
if ( ! empty( $_GET['page'] ) ) {
	$page = $_GET['page'];
	$rewrite = true;
} elseif ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . '.+/', $_SERVER['REQUEST_URI'] ) ) {
	// ##### if the request is for "index.php", I should find a way to redirect it to "/"
	$page = preg_replace( '/' . preg_quote( BASE_URI, '/' ) . '/', '', $_SERVER['REQUEST_URI'] );
} elseif ( preg_match( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', $_SERVER['HTTP_REFERER'] ) ) {
	$page = preg_replace( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', '', $_SERVER['HTTP_REFERER'] );
	$rewrite = true;
} else {
	$page = '1';
}

// There are a number of URL styles that we want to be able to work, but if possible, we want to redirect to a specific URL style.
if ( $rewrite ) {
	// ##### If ever I'm expecting other query variables, I will need to capture them before running this section.
	$prot = 'http';
	if ( isset( $_SERVER['HTTPS'] ) ) {
		$prot .= 's';
	}
	$url = $prot . '://' . $_SERVER['HTTP_HOST'] . BASE_URI . $page;
	header("Location: " . $url );
	exit();
}

$source_url = PROTOCOL . '://' . REFERER_BASE . $page;

// Initialize the mysql connection
$mysql_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ( $mysqli->connect_errno ) {
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	exit;
}

// Using the URI, get the page number
$results = $mysql_connection->query("SELECT URI,Page from " . TABLE_PREFIX . "name where URI='" . $page . "'");
if ( ! $results->num_rows ) {
	// #####
	echo "No such page. I have to figure out something better to do for this...";
	exit;
}
$row = $results->fetch_assoc();
$page = $row['Page'];

// Using the page number, find out what content we need to pull
$results = $mysql_connection->query("SELECT Content from " . TABLE_PREFIX . "content where Page<='" . $page . "'");
if ( ! $results->num_rows ) {
	// #####
	echo "No such page content. I have to figure out something better to do for this...";
	exit;
}
$row = $results->fetch_assoc();
$content = $row['Content'];

// parse the page style from the content json
$content_list = json_decode($content);
$style = $content_list[0];

// pull the style
$results = $mysql_connection->query("SELECT SingleStyle, Start, End, Middle from " . TABLE_PREFIX . "styles where Type = 'full' AND Name = '" . $style . "' AND Page<='" . $page . "' ORDER BY Page DESC LIMIT 1");
if ( ! $results->num_rows ) {
	// #####
	echo "No such full page style. I have to figure out something better to do for this...";
	exit;
}
$full_style = $results->fetch_assoc();

// start creating the rendered page
$rendered_page = $full_style['Start'];

// for each section in the list of sections
$first = true;
$count = 0;
foreach ( $content_list as &$section_list ) {
	$count++;
	if ( $first === true ) {
		//we can ignore this one
		$first = false;
	} else {
		//get the data about the section style
		$section_style = $section_list[0];
		$results = $mysql_connection->query("SELECT Start, End, Middle, LastLink from " . TABLE_PREFIX . "styles where Type = 'section' AND Name = '" . $section_style . "' AND Page<='" . $page . "' ORDER BY Page DESC LIMIT 1");
		if ( ! $results->num_rows ) {
			// #####
			echo "No such section style. I have to figure out something better to do for this...";
			exit;
		}
		$section_style = $results->fetch_assoc();
		$rendered_page .= $section_style['Start'];
		$first2 = true;
		// go through each of the items in the content list
		foreach ( $section_list as &$item ) {
			if ( $first2 === true ) {
				$first2 = false;
			} else {
				$results = $mysql_connection->query("SELECT Page, DispName, Image, Description from " . TABLE_PREFIX . "items where Name='" . $item . "' AND Page<='" . $page . "' ORDER BY Page DESC LIMIT 2");
				$last = -1;
				$results2 = $results->fetch_all(MYSQLI_BOTH);
				if ( $results->num_rows == 2 ) {
					$last = $results2[1]['Page'];
				} elseif ( ! $results->num_rows ) {
					// #####
					echo "No such item. I have to figure out something better to do for this...";
					exit;
				}
				$dispname = $results2[0]['DispName'];
				$image = $results2[0]['Image'];
				$description = $results2[0]['Description'];

				// parse through the middle part of the section style
				$section_middle = json_decode($section_style['Middle']);
				foreach ( $section_middle as &$middle_part ) {
					if ( $middle_part == "DISP_NAME" ) {
						$rendered_page .= $dispname;
					} elseif ( $middle_part == "IMAGE" ) {
						$rendered_page .= $image;
					} elseif ( $middle_part == "CONTENT" ) {
						$item_content = json_decode($description);
						foreach ( $item_content as &$content_part ) {
							if ( $content_part[0] == "html" ) {
								$rendered_page .= $content_part[1];
							} elseif ( $content_part[0] == "link" ) {
								$rendered_page .= '<a href="' . $content_part[1] . '">' . $content_part[2] . '</a>';
								// ##### This needs to be done less quickly as well
							} elseif ( $content_part[0] == "expand" ) {
								// ##### More work here too
								$rendered_page .= '<div class="expand_head">' . $content_part[1] . '<div class="expand">';
								foreach ( $content_part[2] as &$content_part_part ) {
									if ( $content_part_part[0] == "html" ) {
										$rendered_page .= $content_part[1];
									} elseif ( $content_part_part[0] == "link" ) {
										$rendered_page .= '<a href="' . $content_part_part[1] . '">' . $content_part_part[2] . '</a>';
										// ##### for this one, I should probably just have a function that does both of these link parts.
									}
								}
								$rendered_page .= '</div></div>';
							}
						}
					} elseif ( $middle_part == "LAST_LINK" ) {
						if ( $last > -1 ) {
							$section_last_link = json_decode($section_style['LastLink']);
							foreach ( $section_last_link as &$last_link_part ) {
								if ( $last_link_part == "LINK" ) {
									$rendered_page .= "previous.html";
									// ##### this will involve parsing and building it out and stuff... I'll get to that later
								} else {
									$rendered_page .= $last_link_part;
								}
							}
						}
					} else {
						$rendered_page .= $middle_part;
					}
				}
			}
		}
		$rendered_page .= $section_style['End'];
		if ( $count != sizeof($content_list) ) {
			$rendered_page .= $full_style['Middle'];
		}
	}
}
$rendered_page .= $full_style['End'];
$mysql_connection->close();

// output the final page
echo $rendered_page;
?>
