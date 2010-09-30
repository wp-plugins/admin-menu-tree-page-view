<?php
/*
Plugin Name: Admin Menu Tree Page View
Plugin URI: http://eskapism.se/code-playground/admin-menu-tree-page-view/
Description: Get a tree view of all your pages directly in the admin menu. All pages is available just one click away!
Version: 0.1
Author: Pär Thernström
Author URI: http://eskapism.se/
License: GPL2
*/

/*  Copyright 2010  Pär Thernström (email: par.thernstrom@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Admin Menu Tree Page View
admin-menu-tree-page-view
*/
add_action('admin_menu', 'admin_menu_tree_page_view_admin_menu');
add_action("admin_head", "admin_menu_tree_page_view_admin_head");
add_action("admin_init", "admin_menu_tree_page_view_admin_init");


function admin_menu_tree_page_view_admin_init() {
	define( "admin_menu_tree_page_view_URL", WP_PLUGIN_URL . '/admin-menu-tree-page-view/' );
	define( "admin_menu_tree_page_view_VERSION", "0.1" );
	wp_enqueue_style( "admin_menu_tree_page_view_styles", admin_menu_tree_page_view_URL . "styles.css", false, admin_menu_tree_page_view_VERSION );
}

function admin_menu_tree_page_view_admin_head() {
	?>
	<script type="text/javascript">
		jQuery(function() {
			setTimeout(function() {
				jQuery("#toplevel_page_bonny_tree_main").addClass("wp-menu-open");
			}, 100);
			
		});
	</script>
	
	<?php

}

function admin_menu_tree_page_view_get_pages($args) {
	$pages = get_pages($args);
	foreach ($pages as $one_page) {
		$edit_link = get_edit_post_link($one_page->ID);
		$title = get_the_title($one_page->ID);
		$class = "";
		if (isset($_GET["action"]) && $_GET["action"] == "edit" && isset($_GET["post"]) && $_GET["post"] == $one_page->ID) {
			$class = "current";
		}
		$output .= "<li class='$class'>";
		$output .= "<a href='$edit_link'>";
		$output .= $title;
		$output .= "</a>";
		
		// now fetch child articles
		#print_r($one_page);
		$args_childs = $args;
		$args_childs["parent"] = $one_page->ID;
		$args_childs["child_of"] = $one_page->ID;
		#echo "<pre>";print_r($args_childs);
		$output .= admin_menu_tree_page_view_get_pages($args_childs);
		
		$output .= "</li>";
	}
	
	// if this is a child listing, add ul
	if ($args["child_of"]) {
		$output = "<ul class='bonny_tree_childs'>$output</ul>";
	}
	
	return $output;
}

function admin_menu_tree_page_view_admin_menu() {

	// add main menu
	#add_menu_page( "title", "Simple Menu Pages", "edit_pages", "bonny_tree_main", "bonnyFunction", null, 5);

	// end link that is written automatically by WP, and begin ul
	$output = "
		</a>
		<ul class='bonny_tree'>
		<li class='bonny_tree_headline'>Pages</li>
		";

	// get root items
	$args = array(
		"echo" => 0,
		"sort_order" => "ASC",
		"sort_column" => "menu_order",
		"parent" => 0
	);

	$output .= admin_menu_tree_page_view_get_pages($args);
	
	// end our ul and add the a-tag that wp automatically will close
	$output .= "
		</ul>
		<a href='#'>
	";

	// add subitems to main menu
	add_submenu_page("edit.php?post_type=page", "Tree View", $output, "edit_pages", "bonny_tree", "bonnyFunction");

}

function bonnyFunction() {
	?>
	
	<h2>Simple Admin Menu Tree</h2>
	<p>Nothing to see here. Move along! :)</p>
	
	<?php
}


