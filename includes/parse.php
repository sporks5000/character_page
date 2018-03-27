<?php

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
// This is by far the ugliest and most incomprehensible portion of the project. On the whole everything here makes sense, it's just that there are a lot of things that need to be taken into consideration. I am unaware of a prettier way to accomplish this, so I'm gong to leave it as is. I'm sorry.
	static $v_head = ''; //text for the head of the html
	static $v_body = ''; //text for the body of the html
	static $v_error = ''; //any lines that don't make sense will be output here
	static $v_iframe = ''; //text for the part of the html surrounding the iframe
	static $v_description = ''; //text for item descriptions

	static $v_main_type = ''; //When this function is first called, what is the value of $v_type
	static $a_section_content = array();
	static $v_section_name = '';
	static $v_current_item = '';
	static $v_disp_name = '';
	static $v_disp_image = '';
	static $v_last_section = '';
	static $v_last_item = '';
	static $a_item_data = array();
	static $a_set = array();
	static $a_constant = array();
	static $a_document_list = array();
	static $v_previous_page = '';
	static $v_next_page = '';
	static $v_single_style = '';
	static $v_title = '';
	static $b_head = false;
	static $b_description = false;
	static $b_iframe = false;
	static $b_is_prev = false;
	static $b_is_next = false;
	static $c_sections = -1;
	static $c_item_contents = 0;
	global $o_mysql_connection;
	global $o_content;
	global $v_page;
	global $v_source_url;
	global $v_main_page;
	global $v_relative_path;
	global $v_current;
	global $v_item; //for single item views, what is the name fo the item
	global $v_style;
	global $v_show_parse_level;
	global $v_next_int_page;
	global $v_prev_int_page;

	if ( $v_main_type == "" ) {
		$v_main_type = $v_type;
	}

	if ( ! $v_last_section && $v_type == "full" ) {
		$v_last_section = ( count( $o_content ) - 1 );
		$v_current = $v_page;
	} elseif ( $v_type == "single" && ! $v_description ) {
		$v_single_style = $v_style;
		$v_current_item = $v_item;
		list( $b_is_prev, $v_previous_page, $b_is_next, $v_next_page, $a_item_text ) = fn_request_item( $v_current_item, $v_page );
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
			$v_error .= "PARSED: |" . $v_line . "|\n";
		}

		$c_lines++;
		if ( preg_match( '/^\s*>>>\s+([^\s]+)(\s+(([^\s]+).*))?\s*$/', $v_line, $a_match ) ) {
			if ( $v_show_parse_level == 1 ) {
				$v_error .= "PARSED: |" . $v_line . "|\n";
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
					$out .= $v_section_name;
				} elseif ( $a_match[4] == "SOURCE_URL" ) {
					$out .= $v_source_url;
				} elseif ( $a_match[4] == "MAIN_EXT_URL" ) {
					$out .= PROTOCOL . '://' . REFERER_MAIN;
				} elseif ( $a_match[4] == "MAIN_URL" ) {
					$out .= $v_main_page;
				} elseif ( $a_match[4] == "SECT_NUM" ) {
					$out .= $c_sections;
				} elseif ( $a_match[4] == "ITEM_NUM" ) {
					$out .= ( $c_item_contents - 3 );
				} elseif ( $a_match[4] == "NEXT_INT_PAGE" ) {
					$out .= $v_next_int_page;
				} elseif ( $a_match[4] == "PREV_INT_PAGE" ) {
					$out .= $v_prev_int_page;
				} elseif ( $a_match[4] == "CONSTANT" ) {
					$variable = preg_replace( '/^\s*>>>\s+var\s+CONSTANT\s+([^\s]+).*$/', '$1', $a_match[0] );
					$out .= $a_constant[$variable];
				} else {
					$v_error .= "Error (variable): " . $v_line . "\n";
				}
				unset( $out );
			} elseif ( $a_match[1] == "content" ) {
				if ( $b_iframe ) {
					$v_iframe .= addslashes( '<iframe id="cp_single" src=""></iframe>' );
				} elseif ( $v_main_type == "document" ) {
					fn_parse_descriptions( $a_document_list, 'document' );
				} elseif ( $v_type == "full" ) {
					$o_results = $o_mysql_connection->query("
						SELECT Description from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "styles
						WHERE Type = 'section'
						AND Name = '" . $o_mysql_connection->real_escape_string($a_section_content[1]) . "'
						AND Page<='" . $o_mysql_connection->real_escape_string($v_page) . "'
						ORDER BY Page DESC
						LIMIT 1
					");
					if ( $o_results->num_rows == 0 ) {
						// #####
						echo "No such section style. I have to figure out something better to do for this...";
						exit;
					}
					$a_section_style = $o_results->fetch_assoc();
					$v_section_style_text = $a_section_style['Description'];
					$a_section_style_list = preg_split( "/(\r)?\n/", $v_section_style_text );
					fn_parse_descriptions( $a_section_style_list, 'section' );
				} elseif ( $v_type == "section" || $v_type == "single" ) {
					$v_body .= $v_description;
				} else {
					$v_error .= "Error (content out of place in " . $v_type . "): " . $v_line . "\n";
				}
			} elseif ( $a_match[1] == "block" ) {
				$o_results = $o_mysql_connection->query("
					SELECT Description from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "styles
					WHERE Type = 'block'
					AND Name = '" . $o_mysql_connection->real_escape_string($a_match[4]) . "'
					AND Page<='" . $o_mysql_connection->real_escape_string($v_page) . "'
					ORDER BY Page DESC
					LIMIT 1
				");
				if ( $o_results->num_rows == 0 ) {
					// #####
					echo "No such block style \"" . $a_match[4] . "\". I have to figure out something better to do for this...";
					exit;
				}
				$a_block_style = $o_results->fetch_assoc();
				$v_block_style_text = $a_block_style['Description'];
				$a_block_style_list = preg_split( "/(\r)?\n/", $v_block_style_text );
				fn_parse_descriptions( $a_block_style_list, 'block' );
			} elseif ( $a_match[1] == "ilink" && $v_main_type != "document" ) {
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
				if ( $v_main_type != "single" ) {
					$link = $v_relative_path . 'single/' . $a_match[4] . '?current=' . $v_current . '&page=' . $v_current . '&style=' . $v_single_style;
					$out .= '<a class="cp_link" href="#" src="' . $link . '" onclick="fn_open_link(this);return false;">' . $link_text . "</a>\n";
				} else {
					$link = $v_relative_path . 'single/' . $a_match[4] . '?current=' . $v_current . '&page=' . $v_page . '&style=' . $v_single_style;
					$out .= '<a class="cp_link" href="' . $link . '">' . $link_text . "</a>\n";
				}
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
			} elseif ( $a_match[1] == "plink" && $v_main_type != "document" ) {
				if ( $b_head ) {
					$out =& $v_head;
				} elseif ( $b_description ) {
					$out =& $v_description;
				} elseif ( $b_iframe ) {
					$out =& $v_iframe;
				} else {
					$out =& $v_body;
				}
				$link = $v_relative_path . 'single/' . $v_current_item . '?current=' . $v_current . '&page=' . $v_previous_page . '&style=' . $v_single_style;
				if ( $v_main_type != "single" ) {
					$out .= '<a class="cp_link" href="#" src="' . $link . '" onclick="fn_open_link(this);return false;">' . $a_match[3] . "</a>\n";
				} else {
					$out .= '<a class="cp_link" href="' . $link . '">' . $a_match[3] . "</a>\n";
				}
				unset( $out );
			} elseif ( $a_match[1] == "repeat" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				$c_repeats = 0;
				if ( $v_main_type == "document" ) {
					fn_parse_descriptions( $a_out[$c_repeats], $v_type );
				} elseif ( $v_type == "full" ) {
					foreach ( $o_content as &$_section_content ) {
						$c_sections++;
						$a_section_content = $_section_content;
						$v_section_name = $a_section_content[0];
						fn_parse_descriptions( $a_out[$c_repeats], $v_type );
						$c_repeats++;
						if ( $c_repeats >= count( $a_out ) ) {
							$c_repeats = 0;
						}
					}
				} elseif ( $v_type == "section" ) {
					$c_item_contents = 0;
					$v_last_item = ( count( $o_content[$c_sections] ) - 3 );
					foreach ( $o_content[$c_sections] as &$_current_item ) {
						if ( $c_item_contents < 2 ) {
							$c_item_contents++;
							continue;
						}
						$c_item_contents++;
						$v_current_item = $_current_item;
						list( $b_is_prev, $v_previous_page, $b_is_next, $v_next_page, $a_item_text ) = fn_request_item( $v_current_item, $v_page );
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
			} elseif ( $a_match[1] == "nlink" && $v_main_type != "document" ) {
				if ( $b_head ) {
					$out =& $v_head;
				} elseif ( $b_description ) {
					$out =& $v_description;
				} elseif ( $b_iframe ) {
					$out =& $v_iframe;
				} else {
					$out =& $v_body;
				}
				$link = $v_relative_path . 'single/' . $v_current_item . '?current=' . $v_current . '&page=' . $v_next_page . '&style=' . $v_single_style;
				if ( $v_main_type != "single" ) {
					$out .= '<a class="cp_link" href="#" src="' . $link . '" onclick="fn_open_link(this);return false;">' . $a_match[3] . "</a>\n";
				} else {
					$out .= '<a class="cp_link" href="' . $link . '">' . $a_match[3] . "</a>\n";
				}
				unset( $out );
			} elseif ( $a_match[1] == "comment" ) {
				// no nothing
			} elseif ( $a_match[1] == "not_first" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_main_type == "document" ) {
				} elseif ( $v_type == "full" && $c_sections != 0 ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( ( $c_item_contents - 3 ) != 0 ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "not_last" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_main_type == "document" ) {
				} elseif ( $v_type == "full" && $c_sections != $v_last_section ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( ( $c_item_contents - 3 ) != $v_last_item ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_first" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_main_type == "document" ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( $v_type == "full" && $c_sections == 0 ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( ( $c_item_contents - 3 ) == 0 ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_last" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "full" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_main_type == "document" ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( $v_type == "full" && $c_sections == $v_last_section ) {
					fn_parse_descriptions( $a_out, $v_type );
				} elseif ( ( $c_item_contents - 3 ) == $v_last_item ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_prev" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "single" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $b_is_prev ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_next" && $a_match[4] == "start" && ( $v_type == "single" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $b_is_next ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "no_prev" && $a_match[4] == "start" && ( $v_type == "section" || $v_type == "single" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( ! $b_is_prev ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "no_next" && $a_match[4] == "start" && ( $v_type == "single" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( ! $b_is_next ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_int_prev" && $a_match[4] == "start" && ( $v_type == "full" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_main_type == "document" ) {
				} elseif ( $v_prev_int_page ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "is_int_next" && $a_match[4] == "start" && ( $v_type == "full" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_main_type == "document" ) {
				} elseif ( $v_next_int_page ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "no_int_prev" && $a_match[4] == "start" && ( $v_type == "full" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_main_type == "document" ) {
				} elseif ( ! $v_prev_int_page ) {
					fn_parse_descriptions( $a_out, $v_type );
				}
			} elseif ( $a_match[1] == "no_int_next" && $a_match[4] == "start" && ( $v_type == "full" || $v_type == "block" ) ) {
				list( $a_out, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, $a_match[1] );
				if ( $v_main_type == "document" ) {
				} elseif ( ! $v_next_int_page ) {
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
				array_push( $a_item_data, $a_match[3] );
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
				$link = $v_relative_path . 'single/' . $v_current_item . '?current=' . $v_current . '&page=' . $v_next_page . '&style=' . $v_single_style;
				$out .= $link;
				unset( $out );
			} elseif ( $a_match[1] == "style" && $v_type == "document" ) {
				$a_arguments = preg_split( "/\s+/", $a_match[3] );
				list( $a_document_list, $c_lines ) = fn_extract_lines( $a_content_list, $c_lines, "_____" );
				$v_page = $a_arguments[1];
				$o_results = $o_mysql_connection->query("
					SELECT Description from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "styles
					WHERE Type = 'full'
					AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					AND Page<='" . $o_mysql_connection->real_escape_string($v_page) . "'
					ORDER BY Page DESC
					LIMIT 1
				");
				if ( $o_results->num_rows == 0 ) {
					// #####
					echo "No such full page style. I have to figure out something better to do for this...";
					exit;
				}
				$a_full_style = $o_results->fetch_assoc();
				$v_full_style_text = $a_full_style['Description'];
				$a_full_style_list = preg_split( "/(\r)?\n/", $v_full_style_text );
				fn_parse_descriptions( $a_full_style_list, "full" );
			} elseif ( ! preg_match( '/^\s*>>>\s+[^\s]+\s+end\s*$/', $v_line ) ) {
				$v_error .= "Error (" . $v_type . "): " . $v_line . "\n";
			}
		} elseif ( preg_match( '/^\s*>>>>>\s+/', $v_line ) ) {
			// do nothing.
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

function fn_request_item( $v_current_item, $v_page ) {
	global $o_mysql_connection;
	$o_results = $o_mysql_connection->query("
		SELECT Page, Description, Next, Previous from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
		WHERE Name = '" . $o_mysql_connection->real_escape_string($v_current_item) . "'
		AND Page<='" . $o_mysql_connection->real_escape_string($v_page) . "'
		ORDER BY Page DESC
		LIMIT 1
	");
	if ( $o_results->num_rows == 0 ) {
		// #####
		echo "No such item. I have to figure out something better to do for this...";
		exit;
	}
	$a_item_data = $o_results->fetch_assoc();
	$v_item_text = $a_item_data['Description'];
	$b_is_next = false;
	$b_is_prev = false;
	$v_next_page = '';
	$v_previous_page = '';
	if ( ! is_null( $a_item_data['Next'] ) ) {
		$b_is_next = true;
		$v_next_page = $a_item_data['Next'];
	}
	if ( ! is_null( $a_item_data['Previous'] ) ) {
		$b_is_prev = true;
		$v_previous_page = $a_item_data['Previous'];
	}
	$a_item_text = preg_split( "/(\r)?\n/", $v_item_text );
	return array( $b_is_prev, $v_previous_page, $b_is_next, $v_next_page, $a_item_text );
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
			array_push( $a_out[$c_block], $v_line );
		} else {
			array_push( $a_out, $v_line );
		}
		if ( $b_break ) {
			break;
		}
	}
	return array( $a_out, $c_lines );
}
