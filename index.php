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

function fn_parse_content ( $a_content_list ) {
	$v_full_style = '';
	$c_styles = -1;
	$c_items = 0;
	$o_content = array();
	$v_errors = '';
	foreach ( $a_content_list as &$v_line ) {
		if ( preg_match( '/^\s*>>>\s+([^\s]+)(\s+(([^\s]+).*))?\s*$/', $v_line, $a_match ) ) {
			if ( $a_match[1] == "item" ) {
				$o_content[$c_styles][$c_items + 2] = $a_match[4];
				$c_items++;
			} elseif ( $a_match[1] == "s_name" ) {
				$c_styles++;
				$c_items = 0;
				$o_content[$c_styles] = array();
				$o_content[$c_styles][0] = $a_match[3];
			} elseif ( $a_match[1] == "s_style" ) {
				$o_content[$c_styles][1] = $a_match[4];
			} elseif ( $a_match[1] == "style" ) {
				$v_full_style = $a_match[4];
			} else {
				$v_errors .= "Error (content): " . $v_line . "\n";
			}
		} else {
			$v_errors .= "Error (content): " . $v_line . "\n";
		}
	}
	return array( $v_full_style, $o_content, $v_errors );
}

function fn_parse_descriptions( $a_content_list, $v_type ) {
	static $v_head = '';
	static $v_body = '';
	static $v_error = '';
	static $v_description = '';

	static $a_section_content = array();
	static $v_section_name = '';
	static $v_current_item = '';
	static $v_disp_name = '';
	static $v_disp_image = '';
	static $a_item_data = array();
	static $b_head = false;
	static $b_description = false;
	static $b_is_prev = false;
	static $c_sections = -1;
	static $c_item_contents = 0;
	global $o_mysql_connection;
	global $o_content;
	global $v_page;
	$c_lines = 0;
	while ( $c_lines < count( $a_content_list ) ) {
		$v_line = $a_content_list[$c_lines];

//echo "THIS: " . $v_line . "\n";

		$c_lines++;
		if ( preg_match( '/^\s*>>>\s+([^\s]+)(\s+(([^\s]+).*))?\s*$/', $v_line, $a_match ) ) {
			if ( $a_match[1] == "head" ) {
				if ( $a_match[4] == "start" ) {
					$b_head = true;
				} else {
					$b_head = false;
				}
			} elseif ( $a_match[1] == "var" ) {
				if ( $b_head ) {
					$v_head = substr( $v_head, 0, -1 );
					$out =& $v_head;
				} elseif ( $b_description ) {
					$v_description = substr( $v_description, 0, -1 );
					$out =& $v_description;
				} else {
					$v_body = substr( $v_body, 0, -1 );
					$out =& $v_body;
				}
				if ( $a_match[4] == "ITEM_NAME" ) {
					$out .= $v_current_item;
				} elseif ( $a_match[4] == "DISP_NAME" ) {
					$out .= $v_disp_name;
				} elseif ( $a_match[4] == "IMAGE" ) {
					$out .= $v_disp_image;
				} elseif ( $a_match[4] == "ITEM_DATA" ) {
				} elseif ( $a_match[4] == "NEXT_URL" ) {
				} elseif ( $a_match[4] == "PREV_URL" ) {
				} elseif ( $a_match[4] == "SECTION_NAME" ) {
					$out .= $v_section_name;
				} elseif ( $a_match[4] == "SOURCE_URL" ) {
				} elseif ( $a_match[4] == "MAIN_URL" ) {
				} elseif ( $a_match[4] == "SECT_NUM" ) {
					$out .= $c_sections;
				} elseif ( $a_match[4] == "ITEM_NUM" ) {
					$out .= ( $c_item_contents - 2 );
				} else {
					$v_error .= "Error (variable): " . $v_line . "\n";
				}
				unset( $out );
			} elseif ( $a_match[1] == "content" ) {
				if ( $v_type == "full" ) {
					$o_results = $o_mysql_connection->query("SELECT Description from " . TABLE_PREFIX . "styles where Type = 'section' AND Name = '" . $a_section_content[1] . "' AND Page<='" . $v_page . "' ORDER BY Page DESC LIMIT 1");
					if ( ! $o_results->num_rows ) {
						// #####
						echo "No such section style. I have to figure out something better to do for this...";
						exit;
					}
					$a_section_style = $o_results->fetch_assoc();
					$v_section_style_text = $a_section_style['Description'];
					$a_section_style_list = preg_split( "/(\r)?\n/", $v_section_style_text );
					fn_parse_descriptions( $a_section_style_list, 'section' );
				} elseif ( $v_type == "section" ) {
					$v_body .= $v_description;
				}
			} elseif ( $a_match[1] == "ilink" ) {
			} elseif ( $a_match[1] == "plink" ) {
			} elseif ( $a_match[1] == "repeat" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				$c_repeats = 0;
				if ( $v_type == "full" ) {
					foreach ( $o_content as &$_section_content ) {
						$c_sections++;
						$a_section_content = $_section_content;
						$v_section_name = $a_section_content[0];
						fn_parse_descriptions( $a_out[$c_repeats], 'full' );
						$c_repeats++;
						if ( $c_repeats >= count( $a_out ) ) {
							$c_repeats = 0;
						}


					}
				} else {
					$c_item_contents = 0;
					foreach ( $o_content[$c_sections] as &$_current_item ) {
						if ( $c_item_contents < 2 ) {
							$c_item_contents++;
							continue;
						}
						$v_current_item = $_current_item;
						##### I'm going to have to do this query different for single items, because I'll need to know if there's a next.
						$o_results = $o_mysql_connection->query("SELECT Description from " . TABLE_PREFIX . "items where Name = '" . $v_current_item . "' AND Page<='" . $v_page . "' ORDER BY Page DESC LIMIT 2");
						if ( ! $o_results->num_rows ) {
							// #####
							echo "No such item. I have to figure out something better to do for this...";
							exit;
						} elseif ( $o_results->num_rows == "1" ) {
							$b_is_prev = true;
						} else {
							$b_is_prev = false;
						}
						$a_item_data = $o_results->fetch_assoc();
						$v_item_text = $a_item_data['Description'];
						$a_item_text = preg_split( "/(\r)?\n/", $v_item_text );
						$v_description = '';
						$v_disp_name = '';
						$v_disp_image = '';
						$a_item_data = array();
						fn_parse_descriptions( $a_item_text, 'item' );
						fn_parse_descriptions( $a_out[$c_item_contents - 2], "section" );
					}
				}
			} elseif ( $a_match[1] == "iframe" ) {
			} elseif ( $a_match[1] == "nlink" ) {
			} elseif ( $a_match[1] == "comment" ) {
				// no nothing
			} elseif ( $a_match[1] == "not_first" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				#####
			} elseif ( $a_match[1] == "not_last" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				#####
			} elseif ( $a_match[1] == "is_first" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				#####
			} elseif ( $a_match[1] == "is_last" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				#####
			} elseif ( $a_match[1] == "is_prev" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "single" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $b_is_prev ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
				#####
			} elseif ( $a_match[1] == "is_next" && $a_match[4] == "start" && $v_type == "single" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				#####
			} elseif ( $a_match[1] == "no_prev" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "single" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				#####
			} elseif ( $a_match[1] == "no_next" && $a_match[4] == "start" && $v_type == "single" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				#####
			} elseif ( $a_match[1] == "description" && $v_type == "item" ) {
				if ( $a_match[4] == "start" ) {
					$b_description = true;
				} else {
					$b_description = false;
				}
			} elseif ( $a_match[1] == "disp_name" && $v_type == "item" ) {
				$v_disp_name = $a_match[3];
			} elseif ( $a_match[1] == "disp_image" && $v_type == "item" ) {
				$v_disp_image = $a_match[4];
			} elseif ( $a_match[1] == "item_data" && $v_type == "item" ) {
				array_push( $a_item_data, $a_match[3] );
				##### should be done, but I have not tested this yet
			} elseif ( $a_match[1] == "single" && $v_type = "full" ) {
			} elseif ( $a_match[1] == "link" ) {
				$v_target_item = $a_match[4];
				if ( $b_head ) {
					$v_head = substr( $v_head, 0, -1 );
					$out =& $v_head;
				} elseif ( $b_description ) {
					$v_description = substr( $v_description, 0, -1 );
					$out =& $v_description;
				} else {
					$v_body = substr( $v_body, 0, -1 );
					$out =& $v_body;
				}
				$link = "##### generate a real link to " . $v_target_item . " here";
				$out .= $link;
				unset( $out );
			} else {
				$v_error .= "Error (style): " . $v_line . "\n";
			}
		} elseif ( preg_match( '/^\s*>>>>>\s+/', $v_line ) ) {
			// do nothing.
		} else {
			if ( $b_head ) {
				$v_head .= $v_line . "\n";
			} elseif ( $b_description ) {
				$v_description .= $v_line . "\n";
			} else {
				$v_body .= $v_line . "\n";
			}
		}
	}
	return array( $v_body, $v_error, $v_head );
}

function fn_extract_lines( $a_lines, $c_lines, $v_type ) {
	$a_out = array();
	$c_block = 0;
	if ( $v_type == "repeat" ) {
		$a_out[0] = array();
	}
	while ( $c_lines < count( $a_lines ) ) {
		$v_line = $a_lines[$c_lines];
		$c_lines++;
		if ( preg_match( '/^\s*>>>(\s+' . $v_type . '\s+end|>>)/', $v_line ) ) {
			break;
		} elseif ( $v_type == "repeat" && preg_match( '/^\s*>>>\s+repeat\s+start\s/', $v_line ) ) {
			$c_block++;
		} elseif ( preg_match( '/^\s*>>>\s+' . $v_type . '\s+start\s/', $v_line ) ) {
			continue;
		}
		if ( $v_type == "repeat" ) {
			array_push( $a_out[$c_block], $v_line );
		} else {
			array_push( $a_out, $v_line );
		}
	}
	return array( $a_out, $c_lines );
}

// Determine the page URI
$v_rewrite = false;
$v_source_uri = '';
if ( ! empty( $_GET['page'] ) ) {
	$v_source_uri = $_GET['page'];
	$v_rewrite = true;
} elseif ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . '.+/', $_SERVER['REQUEST_URI'] ) ) {
	// ##### if the request is for "index.php", I should find a way to redirect it to "/"
	$v_source_uri = preg_replace( '/' . preg_quote( BASE_URI, '/' ) . '/', '', $_SERVER['REQUEST_URI'] );
} elseif ( preg_match( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', $_SERVER['HTTP_REFERER'] ) ) {
	$v_source_uri = preg_replace( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', '', $_SERVER['HTTP_REFERER'] );
	$v_rewrite = true;
} else {
	// ##### This isn't right.
	$v_source_uri = '1';
}

// There are a number of URL styles that we want to be able to work, but if possible, we want to redirect to a specific URL style.
if ( $v_rewrite ) {
	// ##### If ever I'm expecting other query variables, I will need to capture them before running this section.
	$v_prot = 'http';
	if ( isset( $_SERVER['HTTPS'] ) ) {
		$v_prot .= 's';
	}
	$v_this_url = $v_prot . '://' . $_SERVER['HTTP_HOST'] . BASE_URI . $v_source_uri;
	header("Location: " . $v_this_url );
	exit();
}

$v_source_url = PROTOCOL . '://' . REFERER_BASE . $v_source_uri;

// Initialize the mysql connection
$o_mysql_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ( $o_mysql_connection->connect_errno ) {
	echo "Failed to connect to MySQL: (" . $o_mysql_connection->connect_errno . ") " . $o_mysql_connection->connect_error;
	exit;
}

// Using the URI, get the page number
$o_results = $o_mysql_connection->query("SELECT Page from " . TABLE_PREFIX . "names where URI='" . $v_source_uri . "'");
if ( ! $o_results->num_rows ) {
	// #####
	echo "No such page. I have to figure out something better to do for this...";
	exit;
}
$a_row = $o_results->fetch_assoc();
$v_page = $a_row['Page'];

// Using the page number, find out what content we need to pull
$o_results = $o_mysql_connection->query("SELECT Content from " . TABLE_PREFIX . "contents where Page<='" . $v_page . "'");
if ( ! $o_results->num_rows ) {
	// #####
	echo "No such page content. I have to figure out something better to do for this...";
	exit;
}
$a_row = $o_results->fetch_assoc();
$v_content = $a_row['Content'];

// Create the variables that will store the head, the body, and any errors
$out_head = "<html>\n<head>\n";
// ##### This is where I need to add the fancy bits for the iframe
$out_body = '';
$out_error = "<!--\n";

// split the contents by line and parse them into an object
$a_content_list = preg_split( "/(\r)?\n/", $v_content );
list( $v_full_style, $o_content, $v_errors ) = fn_parse_content ($a_content_list);
$out_error .= $v_errors;

// pull the full style
$o_results = $o_mysql_connection->query("SELECT Description from " . TABLE_PREFIX . "styles where Type = 'full' AND Name = '" . $v_full_style . "' AND Page<='" . $v_page . "' ORDER BY Page DESC LIMIT 1");
if ( ! $o_results->num_rows ) {
	// #####
	echo "No such full page style. I have to figure out something better to do for this...";
	exit;
}
$a_full_style = $o_results->fetch_assoc();
$v_full_style_text = $a_full_style['Description'];
$a_full_style_list = preg_split( "/(\r)?\n/", $v_full_style_text );

list( $v_body, $v_error, $v_head ) = fn_parse_descriptions( $a_full_style_list, 'full' );
$out_body .= $v_body . "</body>\n</html>\n";
$out_error .= $v_error . "-->\n";
$out_head .= $v_head . "</head>\n";


echo $out_head . $out_error . $out_body;

exit;













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
		$results = $o_mysql_connection->query("SELECT Start, End, Middle, LastLink from " . TABLE_PREFIX . "styles where Type = 'section' AND Name = '" . $section_style . "' AND Page<='" . $page . "' ORDER BY Page DESC LIMIT 1");
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
				$results = $o_mysql_connection->query("SELECT Page, DispName, Image, Description from " . TABLE_PREFIX . "items where Name='" . $item . "' AND Page<='" . $page . "' ORDER BY Page DESC LIMIT 2");
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
$o_mysql_connection->close();

// output the final page
echo $rendered_page;
?>
