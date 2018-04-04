<?php

function fn_parse_content ( $a_content_list, $v_first, $v_type ) {
	// expland all blocks and acquire all items
	static $a_items = array(); // an array to put a list items in
	static $a_section_styles = array(); // an array to put a list of section styles in
	static $a_content_list_expanded = array(); // an array to put the expanded list of content lines in
	static $a_blocks = array(); // If a block is calle dmore thna once, this way we won't have to query for it more than once
	static $v_error = "<!--\n"; // a place to put error content
	global $v_ID; // the ID of the current page
	global $o_mysql_connection; // the mysql connection
	global $v_item_category; // the category of the items that we're viewing
	global $v_show_parse_level; // whether or not we're outputtin gthe parse information.
	global $v_table_prefix;
	foreach ( $a_content_list as &$v_line ) {
		if ( preg_match( '/^\s*>>>\s+(items|block|s_style)\s+(.*)$/', $v_line, $a_match ) ) {
			$a_arguments = preg_split( "/\s+/", $a_match[2] );
			if ( $a_match[1] == "items" && $v_type == "page" ) {
				$a_content_list_expanded[] =  $v_line;
				foreach ( $a_arguments as $v_item ) {
					if ( $v_item != "" ) {
						$a_items[] =  $v_item;
					}
				}
			} elseif ( $a_match[1] == "s_style" ) {
				$a_content_list_expanded[] = $v_line;
				$a_section_styles[] = $a_arguments[0];
			} elseif ( $a_match[1] == "block" ) {
				$a_list = array();
				if ( isset( $a_blocks[$a_arguments[0]] ) ) {
					$a_list = $a_blocks[$a_arguments[0]];
				} else {
					$v_query = "
						SELECT Content from " . $v_table_prefix . "contents
						WHERE ID<='" . $o_mysql_connection->real_escape_string($v_ID) . "'
						AND Type = 'block'
						AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
						ORDER BY ID DESC
						LIMIT 1
					";
					$o_results = fn_query_check( "contents->ID: " . $v_ID . ", block, " . $a_arguments[0], $v_query, true );
					$a_row = $o_results->fetch_assoc();
					$v_content = $a_row['Content'];
					$a_list = preg_split( "/(\r)?\n/", $v_content );
					$a_blocks[$a_arguments[0]] = $a_list;
				}
				fn_parse_content ( $a_list, false, $v_type );
			}
		} elseif ( preg_match( '/^\s*>>>\s+(sections?|type)\s+(.*)$/', $v_line, $a_match ) && $v_type == "list" ) {
			// ignore these for list type
		} else {
			$a_content_list_expanded[] = $v_line;
		}
	}
	if ( ! $v_first ) {
		return;
	}

	if ( $v_type == "page" ) {
		// create a string of all of the items
		$v_in_string = "'"; // we'll concatenate the list of items into a string to use in the mysql query
		foreach ( $a_items as $v_item ) {
			$v_in_string .= $o_mysql_connection->real_escape_string($v_item) . "','";
		}
		$v_in_string = substr( $v_in_string, 0, -2 );

		// query for all of the items
		$v_query = "
			SELECT a.Name, a.ID, a.Description, a.Previous FROM " . $v_table_prefix . "items a
			INNER JOIN (
				SELECT Name, MAX( ID ) ID
				FROM " . $v_table_prefix . "items
				WHERE ID<='" . $o_mysql_connection->real_escape_string($v_ID) . "'
				GROUP BY Name
			) b ON a.Name = b.Name AND a.ID = b.ID
			WHERE a.Name IN ( " . $v_in_string . " )
			ORDER BY Name ASC
		";
		$o_results = fn_query_check( "items: (" . $v_in_string . "), " . $v_ID, $v_query, false );
	} elseif ( $v_type == "list" ) {
		// query for item content
		$v_query = "
			SELECT a.Name, a.ID, a.Description, a.Previous FROM " . $v_table_prefix . "items a
			INNER JOIN " . $v_table_prefix . "categories ON a.Name = " . $v_table_prefix . "categories.Name
			INNER JOIN (
				SELECT Name, MAX( ID ) ID
				FROM " . $v_table_prefix . "items
				WHERE ID<='" . $o_mysql_connection->real_escape_string($v_ID) . "'
				GROUP BY Name
			) b ON a.Name = b.Name AND a.ID = b.ID
			WHERE " . $v_table_prefix . "categories.Category = '" . $o_mysql_connection->real_escape_string($v_item_category) . "'
			AND " . $v_table_prefix . "categories.Start <= '" . $o_mysql_connection->real_escape_string($v_ID) . "'
			AND " . $v_table_prefix . "categories.End >= '" . $o_mysql_connection->real_escape_string($v_ID) . "'
			ORDER BY Name ASC
		";
		$o_results = fn_query_check( "items->category: " . $v_item_category . ", " . $v_ID, $v_query, false );
	}

	// Put them all into an object
	$o_items = array(); // I need an object to keep all of the item data in
	$v_items = '';
	while ( $a_row = $o_results->fetch_assoc() ) {
		$o_items[$a_row['Name']] = array();
		$o_items[$a_row['Name']]['name'] = $a_row['Name'];
		$o_items[$a_row['Name']]['ID'] = $a_row['ID'];
		$o_items[$a_row['Name']]['previous'] = $a_row['Previous'];
		$o_items[$a_row['Name']]['description'] = preg_split( "/(\r)?\n/", $a_row['Description'] );
		if ( $v_type == "list" ) {
			$v_items .= $a_row['Name'] . " ";
		}
	}
	if ( $v_type == "page" ) {
		foreach ( $a_items as $v_item ) {
			if ( ! isset( $o_items[$v_item] ) ) {
				if ( ! headers_sent() ) {
					http_response_code(500);
				}
				echo "No such item \"" . $v_item . "\" for this ID.";
				error_log("Character Page at " . $_SERVER['REQUEST_URI'] . " - No such item \"" . $v_item . "\" for this ID.", 0);
				fn_close();
			}
		}
	}

	// create a string of all of the section styles
	$v_in_string = "'"; // we'll concatenate the list of items into a string to use in the mysql query
	foreach ( $a_section_styles as $v_section_style ) {
		$v_in_string .= $o_mysql_connection->real_escape_string($v_section_style) . "','";
	}
	$v_in_string = substr( $v_in_string, 0, -2 );

	// query for all of the styles
	$v_query = "
		SELECT a.Name, a.ID, a.Description FROM " . $v_table_prefix . "styles a
		INNER JOIN (
			SELECT Name, MAX( ID ) ID
			FROM " . $v_table_prefix . "styles
			WHERE ID<='" . $o_mysql_connection->real_escape_string($v_ID) . "'
			GROUP BY Name
		) b ON a.Name = b.Name AND a.ID = b.ID
		WHERE a.Name IN ( " . $v_in_string . " )
		ORDER BY Name ASC
	";
	$o_results = fn_query_check( "styles: (" . $v_in_string . "), " . $v_ID, $v_query, false );

	// Put them all into an object
	$o_section_styles = array(); // I need an object to keep all of the item data in
	while ( $a_row = $o_results->fetch_assoc() ) {
		$o_section_styles[$a_row['Name']] = array();
		$o_section_styles[$a_row['Name']]['name'] = $a_row['Name'];
		$o_section_styles[$a_row['Name']]['ID'] = $a_row['ID'];
		$o_section_styles[$a_row['Name']]['description'] = preg_split( "/(\r)?\n/", $a_row['Description'] );
	}
	foreach ( $a_section_styles as $v_section_style ) {
		if ( ! isset( $o_section_styles[$v_section_style] ) ) {
			if ( ! headers_sent() ) {
				http_response_code(500);
			}
			echo "No such style \"" . $v_section_style . "\" for this ID.";
			error_log("Character Page at " . $_SERVER['REQUEST_URI'] . " - No such style \"" . $v_section_style . "\" for this ID.", 0);
			fn_close();
		}
	}

	if ( $v_type == "list" ) {
		$a_temp_array = array(
			">>> sections " . $v_item_category,
			">>> section " . $v_item_category,
			">>> category " . $v_item_category,
			">>> items " . $v_items
		);
		$a_content_list_expanded = array_merge( $a_temp_array, $a_content_list_expanded );
	}

	// parse the content block
	$o_content = array(); // I need an object to keep all of the content in.
	$v_cur_section = ''; // This will store what the current section is
	$c_lines = 0;
	while ( $c_lines < count( $a_content_list_expanded ) ) {
		$v_line = $a_content_list_expanded[$c_lines];

		if ( $v_show_parse_level == 2 ) {
			$v_error .= "PARSED: |" . $v_line . "|\n";
		}

		$c_lines++;
		if ( preg_match( '/^\s*>>>\s+([^\s]+)(\s+(([^\s]+).*))?\s*$/', $v_line, $a_match ) ) {
			if ( $v_show_parse_level == 1 ) {
				$v_error .= "PARSED: |" . $v_line . "|\n";
			}
			if ( $a_match[1] == "style" ) {
				$o_content['style'] = $a_match[4];
			} elseif ( $a_match[1] == "sections" ) {
				$a_arguments = preg_split( "/\s+/", $a_match[3] );
				foreach ( $a_arguments as $v_section ) {
					if ( $v_section != "" ) {
						$o_content['order'][] =  $v_section;
					}
				}
			} elseif ( $a_match[1] == "section" ) {
				$v_cur_section = $a_match[4];
			} elseif ( $a_match[1] == "category" ) {
				$o_content['sections'][$v_cur_section]['category'] = $a_match[4];
			} elseif ( $a_match[1] == "s_name" ) {
				$o_content['sections'][$v_cur_section]['name'] = $a_match[3];
			} elseif ( $a_match[1] == "s_style" ) {
				$o_content['sections'][$v_cur_section]['style'] = $o_section_styles[$a_match[4]]['description'];
			} elseif ( $a_match[1] == "items" ) {
				$a_arguments = preg_split( "/\s+/", $a_match[3] );
				foreach ( $a_arguments as $v_item ) {
					if ( $v_item != "" ) {
						$o_content['sections'][$v_cur_section]['order'][] =  $v_item;
						$o_content['sections'][$v_cur_section]['items'][$v_item]['ID'] = $o_items[$v_item]['ID'];
						$o_content['sections'][$v_cur_section]['items'][$v_item]['previous'] = $o_items[$v_item]['previous'];
						$o_content['sections'][$v_cur_section]['items'][$v_item]['description'] = $o_items[$v_item]['description'];
					}
				}
			} elseif ( $a_match[1] == "header" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list_expanded, $c_lines, $a_match[1] );
				$o_content['sections'][$v_cur_section]['header'] = $a_out;
			} elseif ( $a_match[1] == "footer" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list_expanded, $c_lines, $a_match[1] );
				$o_content['sections'][$v_cur_section]['footer'] = $a_out;
			} elseif ( ! preg_match( '/^\s*>>>\s+[^\s]+\s+end\s*$/', $v_line ) ) {
				$v_error .= "Error: " . $v_line . "\n";
			}
		} else {
			$v_error .= "Error: " . $v_line . "\n";
		}
	}

	// check the content object to make sure that the number of sections and items line up
	if ( count( $o_content['order'] ) != count( $o_content['sections'] ) ) {
		echo "Number of sections declared does not match number of sections present.";
		fn_close();
	}
	foreach ( $o_content['sections'] as $_section )	{
		if ( count( $_section['order'] ) != count( $_section['items'] ) ) {
			echo "Number of itmes declared does not match number of items present in section " . $_section['name'];
			fn_close();
		}
	}
	

	$v_error .= "-->\n";
	return array( $o_content, $v_error );
}

function fn_parse_descriptions( $a_content_list, $v_type ) {
// This is by far the ugliest and most incomprehensible portion of the project. On the whole everything here makes sense, it's just that there are a lot of things that need to be taken into consideration. I am unaware of a prettier way to accomplish this, so I'm gong to leave it as is. I'm sorry.
	static $v_head = ''; //text for the head of the html
	static $v_body = ''; //text for the body of the html
	static $v_error = ''; //any lines that don't make sense will be output here
	static $v_iframe = ''; //text for the part of the html surrounding the iframe
	static $v_description = ''; //text for item descriptions

	static $v_primary_type = ''; //When this function is first called, what is the value of $v_type
	static $v_section_id = ''; // the internal name of the section that we're working with currently.
	static $v_current_item = ''; // the name of the current item that we are working with
	static $v_disp_name = ''; // the display name set within the item object
	static $v_disp_image = ''; // the image url set within the item object
	static $v_max_section_num = ''; // the array position of the last section (counted starting with zero)
	static $v_max_item_num = ''; //the array position of the last item within this section (counted starting with zero)
	static $a_item_data = array(); // This is where other item variables are stored
	static $a_set = array(); // This allows us to set varaibles using the "set" command keyword
	static $a_constant = array(); // an array where the end user can set a value ONCE and it will never change
	static $a_document_list = array(); // the content for a document. This needs to be handled differently, thus it has its own variable.
	static $a_style_blocks = array(); // an array of style blocks that have already been queried for so that they will not have to be queried for
	static $v_previous_ID = ''; // what is the number of the previous ID for this item
	static $v_next_ID = ''; // what is the number of the next ID for this item (only in single view)
	static $v_single_style = ''; // When parsing a full page view, what is the requested single style?
	static $b_head = false; // whether or not we're currently writing to the head
	static $b_description = false; // whether or not we're currently writing to an item description
	static $b_iframe = false; // whether or not we're currently writing to the iframe
	static $b_is_prev = false; // whether or not there is a previous version of this item
	static $b_is_next = false; // whether or not there is a next version of this item (that's available)
	static $c_sections = -1; // a count of which section we're currently on
	static $c_items = 0; // a count of which item we're currently on within that section

	global $o_mysql_connection; // the object that contains the mysql connection
	global $o_content; // the object that all content and item data is stored in
	global $v_ID; // The ID number that's being requested
	global $v_source_url; //the url for the source page
	global $v_source_uri; // the uri portion for the source page
	global $v_main_page;
	global $v_relative_path;
	global $v_current; // for single item views, the ID number that is considered "current" (not necessarily the ID number being requested)
	global $v_item; // for single item views, what is the name of the item
	global $v_style;
	global $v_show_parse_level;
	global $v_next_int_uri;
	global $v_prev_int_uri;
	global $v_table_prefix;

	if ( $v_primary_type == "" ) {
		$v_primary_type = $v_type;
	}

	if ( ! $v_max_section_num && $v_type == "full" ) {
		$v_max_section_num = ( count( $o_content['order'] ) - 1 );
		$v_current = $v_ID;
	} elseif ( $v_type == "single" && ! $v_description ) {
		$v_single_style = $v_style;
		$v_current_item = $v_item;
		list( $b_is_prev, $v_previous_ID, $b_is_next, $v_next_ID, $a_item_text ) = fn_request_item( $v_current_item, $v_ID, $v_current );
		$v_description = '';
		$v_disp_name = '';
		$v_disp_image = '';
		$a_item_data = array();
		fn_parse_descriptions( $a_item_text, 'item' );
	}

	$c_lines = 0;
	while ( $c_lines < count( $a_content_list ) ) {
		$v_line = $a_content_list[$c_lines];

		if ( $v_show_parse_level == 2 ) {
			$v_error .= "PARSED (" . $v_type . "): |" . $v_line . "|\n";
		}

		$c_lines++;
		if ( preg_match( '/^\s*>>>\s+([^\s]+)(\s+(([^\s]+).*))?\s*$/', $v_line, $a_match ) ) {
			if ( $v_show_parse_level == 1 ) {
				$v_error .= "PARSED (" . $v_type . "): |" . $v_line . "|\n";
			}
			if ( $a_match[1] == "head" ) {
				if ( $a_match[4] == "start" ) {
					$b_head = true;
				} else {
					$b_head = false;
				}
			} elseif ( $a_match[1] == "var" ) {
				if ( $b_head ) {
					$out =& $v_head;
				} elseif ( $b_description ) {
					$out =& $v_description;
				} elseif ( $b_iframe ) {
					$out =& $v_iframe;
				} else {
					$out =& $v_body;
				}
				$out = substr( $out, 0, -1 );
				if ( $a_match[4] == "ITEM_NAME" ) {
					$out .= $v_current_item;
				} elseif ( $a_match[4] == "DISP_NAME" ) {
					$out .= $v_disp_name;
				} elseif ( $a_match[4] == "IMAGE" ) {
					$out .= $v_relative_path . $v_disp_image;
				} elseif ( $a_match[4] == "ITEM_DATA" ) {
					$variable = preg_replace( '/^\s*>>>\s+var\s+ITEM_DATA\s+([^\s]+).*$/', '$1', $a_match[0] );
					$out .= $a_item_data[$variable - 1];
				} elseif ( $a_match[4] == "SET" ) {
					$variable = preg_replace( '/^\s*>>>\s+var\s+SET\s+([^\s]+).*$/', '$1', $a_match[0] );
					$out .= $a_set[$variable];
				} elseif ( $a_match[4] == "SECTION_NAME" ) {
					$out .= $o_content['sections'][$v_section_id]['name'];
				} elseif ( $a_match[4] == "SOURCE_URL" ) {
					$out .= $v_source_url;
				} elseif ( $a_match[4] == "MAIN_EXT_URL" ) {
					$out .= PROTOCOL . '://' . REFERER_MAIN;
				} elseif ( $a_match[4] == "MAIN_URL" ) {
					$out .= $v_main_page;
				} elseif ( $a_match[4] == "SECT_NUM" ) {
					$out .= $c_sections;
				} elseif ( $a_match[4] == "ITEM_NUM" ) {
					$out .= $c_items;
				} elseif ( $a_match[4] == "NEXT_PAGE" ) {
					$out .= $v_next_int_uri;
				} elseif ( $a_match[4] == "PREV_PAGE" ) {
					$out .= $v_prev_int_uri;
				} elseif ( $a_match[4] == "CONSTANT" ) {
					$variable = preg_replace( '/^\s*>>>\s+var\s+CONSTANT\s+([^\s]+).*$/', '$1', $a_match[0] );
					$out .= $a_constant[$variable];
				} elseif ( $a_match[4] == "LIST_URL" ) {
					$variable = preg_replace( '/^\s*>>>\s+var\s+CONSTANT\s+([^\s]+).*$/', '$1', $a_match[0] );
					if ( $variable != $a_match[0] ) {
						$out .= BASE_URI . LIST_DIR . $variable . "/" . $v_source_uri;
					} else {
						$out .= BASE_URI . LIST_DIR . $o_content['sections'][$v_section_id]['category'] . "/" . $v_source_uri;
					}
				} elseif ( $a_match[4] == "PAGE_URL" ) {
					$out .= BASE_URI . PAGE_DIR . $v_source_uri;
				} elseif ( $a_match[4] == "SOURCE_URI" ) {
					$variable = preg_replace( '/^\s*>>>\s+var\s+SOURCE_URI\s+([^\s]+).*$/', '$1', $a_match[0] );
					$out .= PROTOCOL . "://" . REFERER_BASE . $variable;
				} else {
					$v_error .= "Error (variable): " . $v_line . "\n";
				}
				unset( $out );
			} elseif ( $a_match[1] == "content" ) {
				if ( $b_iframe ) {
					$v_iframe .= addslashes( '<iframe id="cp_single" src=""></iframe>' );
				} elseif ( $v_primary_type == "document" ) {
					fn_parse_descriptions( $a_document_list, 'document' );
				} elseif ( $v_type == "full" ) {
					$a_section_style_list = $o_content['sections'][$v_section_id]['style'];
					fn_parse_descriptions( $a_section_style_list, 'section' );
				} elseif ( $v_type == "section" || $v_type == "single" ) {
					$v_body .= $v_description;
				} else {
					$v_error .= "Error (content out of place in " . $v_type . "): " . $v_line . "\n";
				}
			} elseif ( $a_match[1] == "block" ) {
				if ( isset( $a_style_blocks[$a_match[4]] ) ) {
					$a_block_style_list = $a_style_blocks[$a_match[4]];
				} else {
					$v_query = "
						SELECT Description from " . $v_table_prefix . "styles
						WHERE Type = 'block'
						AND Name = '" . $o_mysql_connection->real_escape_string($a_match[4]) . "'
						AND ID<='" . $o_mysql_connection->real_escape_string($v_ID) . "'
						ORDER BY ID DESC
						LIMIT 1
					";
					$o_results = fn_query_check( "styles->Name: " . $a_match[4] . ", block, " . $v_ID, $v_query, true );
					$a_block_style = $o_results->fetch_assoc();
					$v_block_style_text = $a_block_style['Description'];
					$a_block_style_list = preg_split( "/(\r)?\n/", $v_block_style_text );
					$a_style_blocks[$a_match[4]] = $a_block_style_list;
				}
				fn_parse_descriptions( $a_block_style_list, $v_type );
			} elseif ( $a_match[1] == "ilink" && $v_primary_type != "document" ) {
				if ( $b_head ) {
					$out =& $v_head;
				} elseif ( $b_description ) {
					$out =& $v_description;
				} elseif ( $b_iframe ) {
					$out =& $v_iframe;
				} else {
					$out =& $v_body;
				}
				$link_text = preg_replace( '/^' . preg_quote( $a_match[4] ) . '\s+/', '', $a_match[3] );
				if ( $v_primary_type != "single" ) {
					$link = $v_relative_path . 'single/' . $a_match[4] . '?current=' . $v_current . '&id=' . $v_current . '&style=' . $v_single_style;
				} else {
					$link = $v_relative_path . 'single/' . $a_match[4] . '?current=' . $v_current . '&id=' . $v_ID . '&style=' . $v_single_style;
				}
				$out .= '<a class="cp_link" href="' . $link . '" onclick="fn_open_link(this);return false;">' . $link_text . "</a>\n";
			} elseif ( $a_match[1] == "no_new_line" ) {
				if ( $b_head ) {
					$out =& $v_head;
				} elseif ( $b_description ) {
					$out =& $v_description;
				} elseif ( $b_iframe ) {
					$out =& $v_iframe;
				} else {
					$out =& $v_body;
				}
				if ( substr( $out, -1 ) == "\n" ) {
					$out = substr( $out, 0, -1 );
				}
			} elseif ( $a_match[1] == "plink" && $v_primary_type != "document" ) {
				if ( $b_head ) {
					$out =& $v_head;
				} elseif ( $b_description ) {
					$out =& $v_description;
				} elseif ( $b_iframe ) {
					$out =& $v_iframe;
				} else {
					$out =& $v_body;
				}
				$link = $v_relative_path . 'single/' . $v_current_item . '?current=' . $v_current . '&id=' . $v_previous_ID . '&style=' . $v_single_style;
				$out .= '<a class="cp_link" href="' . $link . '" onclick="fn_open_link(this);return false;">' . $a_match[3] . "</a>\n";
				unset( $out );
			} elseif ( $a_match[1] == "repeat" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				$c_repeats = 0;
				if ( $v_primary_type == "document" ) {
					fn_parse_descriptions( $a_out[$c_repeats], $v_type );
				} elseif ( $v_type == "full" ) {
					foreach ( $o_content['order'] as $_section_name ) {
						$c_sections++;
						$v_section_id = $_section_name;
						fn_parse_descriptions( $a_out[$c_repeats], $v_type );
						$c_repeats++;
						if ( $c_repeats >= count( $a_out ) ) {
							$c_repeats = 0;
						}
					}
				} elseif ( $v_type == "section" ) {
					$c_items = -1;
					$v_max_item_num = ( count( $o_content['sections'][$v_section_id]['order'] ) - 1);
					foreach ( $o_content['sections'][$v_section_id]['order'] as $_current_item ) {
						$c_items++;
						$v_current_item = $_current_item;
						$b_is_prev = false;
						if ( ! is_null( $o_content['sections'][$v_section_id]['items'][$v_current_item]['previous'] ) ) {
							$b_is_prev = true;
							$v_previous_ID = $o_content['sections'][$v_section_id]['items'][$v_current_item]['previous'];
						}
						$a_item_text = $o_content['sections'][$v_section_id]['items'][$v_current_item]['description'];
						$v_description = '';
						$v_disp_name = '';
						$v_disp_image = '';
						$a_item_data = array();
						fn_parse_descriptions( $a_item_text, 'item' );
						fn_parse_descriptions( $a_out[$c_repeats], $v_type );
						$c_repeats++;
						if ( $c_repeats >= count( $a_out ) ) {
							$c_repeats = 0;
						}
					}
				} else {
					$v_error .= "Error (repeat out of place in " . $v_type . "): " . $v_line . "\n";
				}
			} elseif ( $a_match[1] == "set" && preg_match( '/^[0-9]+$/', $a_match[4] ) ) {
				$variable = preg_replace( '/^\s*>>>\s+set\s+' . preg_quote( $a_match[4], "/" ) . '\s+/', '', $a_match[0] );
				$a_set[$a_match[4]] = $variable;
			} elseif ( $a_match[1] == "iframe" ) {
				if ( $a_match[4] == "start" ) {
					$b_iframe = true;
				} else {
					$b_iframe = false;
				}
			} elseif ( $a_match[1] == "section_head" && isset( $v_section_id ) ) {
				fn_parse_descriptions( $o_content['sections'][$v_section_id]['header'], 'section' );
			} elseif ( $a_match[1] == "section_foot" ) {
				fn_parse_descriptions( $o_content['sections'][$v_section_id]['footer'], 'section' );
			} elseif ( $a_match[1] == "nlink" && $v_primary_type != "document" ) {
				if ( $b_head ) {
					$out =& $v_head;
				} elseif ( $b_description ) {
					$out =& $v_description;
				} elseif ( $b_iframe ) {
					$out =& $v_iframe;
				} else {
					$out =& $v_body;
				}
				$link = $v_relative_path . 'single/' . $v_current_item . '?current=' . $v_current . '&id=' . $v_next_ID . '&style=' . $v_single_style;
				$out .= '<a class="cp_link" href="' . $link . '" onclick="fn_open_link(this);return false;">' . $a_match[3] . "</a>\n";
				unset( $out );
			} elseif ( $a_match[1] == "comment" ) {
				if ( $a_match[4] == "start" ) {
					// Just skip past these lines
					list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				}
			} elseif ( $a_match[1] == "not_first" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_primary_type == "document" ) {
				} elseif ( $v_type == "full" && $c_sections != 0 ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( $c_items != 0 ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "not_last" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_primary_type == "document" ) {
				} elseif ( $v_type == "full" && $c_sections != $v_max_section_num ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( $c_items != $v_max_item_num ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_first" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_primary_type == "document" ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( $v_type == "full" && $c_sections == 0 ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( $c_items == 0 ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_last" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_primary_type == "document" ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( $v_type == "full" && $c_sections == $v_max_section_num ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( $c_items == $v_max_item_num ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_prev" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "single" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $b_is_prev ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_next" && $a_match[4] == "start" && $v_type == "single" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $b_is_next ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "no_prev" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "single" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( ! $b_is_prev ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "no_next" && $a_match[4] == "start" && $v_type == "single" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( ! $b_is_next ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_int_prev" && $a_match[4] == "start" && $v_type == "full" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_primary_type == "document" ) {
				} elseif ( $v_prev_int_uri ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_int_next" && $a_match[4] == "start" && $v_type == "full" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_primary_type == "document" ) {
				} elseif ( $v_next_int_uri ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "no_int_prev" && $a_match[4] == "start" && $v_type == "full" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_primary_type == "document" ) {
				} elseif ( ! $v_prev_int_uri ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "no_int_next" && $a_match[4] == "start" && $v_type == "full" ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_primary_type == "document" ) {
				} elseif ( ! $v_next_int_uri ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
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
				$a_item_data[] = $a_match[3];
			} elseif ( $a_match[1] == "single" && $v_type = "full" ) {
				$v_single_style = $a_match[3];
			} elseif ( $a_match[1] == "constant" && preg_match( '/^[0-9]+$/', $a_match[4] ) ) {
				$variable = preg_replace( '/^\s*>>>\s+constant\s+' . preg_quote( $a_match[4], "/" ) . '\s+/', '', $a_match[0] );
				if ( $a_constant[$a_match[4]] == "" ) {
					$a_constant[$a_match[4]] = $variable;
				}
			} elseif ( $a_match[1] == "link" ) {
				$v_target_item = $a_match[4];
				if ( $b_head ) {
					$out =& $v_head;
				} elseif ( $b_description ) {
					$out =& $v_description;
				} elseif ( $b_iframe ) {
					$out =& $v_iframe;
				} else {
					$out =& $v_body;
				}
				$out = substr( $out, 0, -1 );
				$link = $v_relative_path . 'single/' . $v_current_item . '?current=' . $v_current . '&id=' . $v_next_ID . '&style=' . $v_single_style;
				$out .= $link;
				unset( $out );
			} elseif ( $a_match[1] == "style" && $v_type == "document" ) {
				$a_arguments = preg_split( "/\s+/", $a_match[3] );
				list( $a_document_list, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, "_____" );
				$v_ID = $a_arguments[1];
				$v_query = "
					SELECT Description from " . $v_table_prefix . "styles
					WHERE Type = 'full'
					AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					AND ID<='" . $o_mysql_connection->real_escape_string($v_ID) . "'
					ORDER BY ID DESC
					LIMIT 1
				";
				$o_results = fn_query_check( "styles->Name: " . $a_arguments[0] . ", full, " . $v_ID, $v_query, true );
				$a_full_style = $o_results->fetch_assoc();
				$v_full_style_text = $a_full_style['Description'];
				$a_full_style_list = preg_split( "/(\r)?\n/", $v_full_style_text );
				fn_parse_descriptions( $a_full_style_list, "full" );
			} elseif ( ! preg_match( '/^\s*>>>\s+[^\s]+\s+end\s*$/', $v_line ) ) {
				$v_error .= "Error (" . $v_type . "): " . $v_line . "\n";
			}
		} elseif ( preg_match( '/^\s*>>>>>\s+/', $v_line ) ) {
			// do nothing; this should never happen
		} else {
			if ( $b_head ) {
				$v_head .= $v_line . "\n";
			} elseif ( $b_description ) {
				$v_description .= $v_line . "\n";
			} elseif ( $b_iframe ) {
				$v_iframe .= addslashes( $v_line );
			} else {
				$v_body .= $v_line . "\n";
			}
		}
	}
	return array( $v_body, $v_error, $v_head, $v_iframe );
}

function fn_request_item( $v_current_item, $v_ID, $v_current ) {
	global $o_mysql_connection;
	global $v_table_prefix;
	$v_query = "
		SELECT ID, Description, Next, Previous from " . $v_table_prefix . "items
		WHERE Name = '" . $o_mysql_connection->real_escape_string($v_current_item) . "'
		AND ID<='" . $o_mysql_connection->real_escape_string($v_ID) . "'
		ORDER BY ID DESC
		LIMIT 1
	";
	$o_results = fn_query_check( "items->Name: " . $v_current_item . ", " . $v_ID, $v_query, true );
	$a_item_data = $o_results->fetch_assoc();
	$v_item_text = $a_item_data['Description'];
	$b_is_next = false;
	$b_is_prev = false;
	$v_next_ID = '';
	$v_previous_ID = '';
	if ( ! is_null( $a_item_data['Next'] ) && $a_item_data['Next'] <= $v_current ) {
		$b_is_next = true;
		$v_next_ID = $a_item_data['Next'];
	}
	if ( ! is_null( $a_item_data['Previous'] ) ) {
		$b_is_prev = true;
		$v_previous_ID = $a_item_data['Previous'];
	}
	$a_item_text = preg_split( "/(\r)?\n/", $v_item_text );
	return array( $b_is_prev, $v_previous_ID, $b_is_next, $v_next_ID, $a_item_text );
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
		$b_break = false;
		if ( preg_match( '/^\s*>>>(\s+' . preg_quote( $v_type, "/" ) . '\s+end|>>)/', $v_line ) ) {
			$b_break = true;
		} elseif ( $v_type == "repeat" && preg_match( '/^\s*>>>\s+repeat\s+start\s/', $v_line ) ) {
			$c_block++;
		} elseif ( preg_match( '/^\s*>>>\s+' . preg_quote( $v_type, "/" ) . '\s+start\s/', $v_line ) ) {
			continue;
		}
		if ( $v_type == "repeat" ) {
			$a_out[$c_block][] = $v_line;
		} else {
			$a_out[] = $v_line;
		}
		if ( $b_break ) {
			break;
		}
	}
	return array( $a_out, $c_lines );
}
