<?php
/*
Plugin Name: Admin Menu Tree Page View
Plugin URI: http://eskapism.se/code-playground/admin-menu-tree-page-view/
Description: Get a tree view of all your pages directly in the admin menu. Search, edit, view and add pages - all with just one click away!
Version: 1.3
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

add_action("admin_head", "admin_menu_tree_page_view_admin_head");
add_action('admin_menu', 'admin_menu_tree_page_view_admin_menu');
add_action("admin_init", "admin_menu_tree_page_view_admin_init");
add_action('wp_ajax_admin_menu_tree_page_view_add_page', 'admin_menu_tree_page_view_add_page');

function admin_menu_tree_page_view_admin_init() {

	define( "admin_menu_tree_page_view_VERSION", "1.3" );
	define( "admin_menu_tree_page_view_URL", WP_PLUGIN_URL . '/admin-menu-tree-page-view/' );
	define( "admin_menu_tree_page_view_DIR", WP_PLUGIN_DIR . '/admin-menu-tree-page-view/' );

	wp_enqueue_style("admin_menu_tree_page_view_styles", admin_menu_tree_page_view_URL . "css/styles.css", false, admin_menu_tree_page_view_VERSION);
	wp_enqueue_script("jquery.highlight", admin_menu_tree_page_view_URL . "js/jquery.highlight.js", array("jquery"));
	wp_enqueue_script("jquery-cookie", admin_menu_tree_page_view_URL . "js/jquery.biscuit.js", array("jquery")); // renamed from cookie to fix problems with mod_security
	wp_enqueue_script("jquery.ui.nestedSortable", admin_menu_tree_page_view_URL . "js/jquery.ui.nestedSortable.js", array("jquery", "jquery-ui-sortable"));
	wp_enqueue_script("jquery.client", admin_menu_tree_page_view_URL . "js/jquery.client.js", array("jquery"));
	wp_enqueue_script("jquery-ui-sortable");
	wp_enqueue_script("admin_menu_tree_page_view", admin_menu_tree_page_view_URL . "js/scripts.js", array("jquery"));

	$oLocale = array(
		"Edit" => __("Edit", 'admin-menu-tree-page-view'),
		"View" => __("View", 'admin-menu-tree-page-view'),
		"Add_new_page_here" => __("Add new page after", 'admin-menu-tree-page-view'),
		"Add_new_page_inside" => __("Add new page inside", 'admin-menu-tree-page-view'),
		"Untitled" => __("Untitled", 'admin-menu-tree-page-view'),
	);
	wp_localize_script( "admin_menu_tree_page_view", 'amtpv_l10n', $oLocale);
}

function admin_menu_tree_page_view_admin_head() {

}

function admin_menu_tree_page_view_get_pages($args) {

	$defaults = array(
    	"post_type" => "page",
		"parent" => "0",
		"post_parent" => "0",
		"numberposts" => "-1",
		"orderby" => "menu_order",
		"order" => "ASC",
		"post_status" => "any"
	);
	$args = wp_parse_args( $args, $defaults );

	$pages = get_posts($args);
	$output = "";
	$str_child_output = "";
	foreach ($pages as $one_page) {
		$edit_link = get_edit_post_link($one_page->ID);
		$title = get_the_title($one_page->ID);
		
		// add num of children to the title
		$post_children = get_children(array(
			"post_parent" => $one_page->ID,
			"post_type" => "page"
		));
		$post_children_count = sizeof($post_children);
		// var_dump($post_children_count);
		if ($post_children_count>0) {
			$title .= " <span class='child-count'>($post_children_count)</span>";
		}
		
		$class = "";
		if (isset($_GET["action"]) && $_GET["action"] == "edit" && isset($_GET["post"]) && $_GET["post"] == $one_page->ID) {
			$class = "current";
		}
		$status_span = "";
		if ($one_page->post_password) {
			$status_span .= "<span class='admin-menu-tree-page-view-protected'></span>";
		}
		if ($one_page->post_status != "publish") {
			$status_span .= "<span class='admin-menu-tree-page-view-status admin-menu-tree-page-view-status-{$one_page->post_status}'>".__(ucfirst($one_page->post_status))."</span>";
		}

		// add css if we have childs
		$args_childs = $args;
		$args_childs["parent"] = $one_page->ID;
		$args_childs["post_parent"] = $one_page->ID;
		$args_childs["child_of"] = $one_page->ID;
		$str_child_output = admin_menu_tree_page_view_get_pages($args_childs);
		if ($post_children_count>0) {
			$class .= " admin-menu-tree-page-view-has-childs";
		}
		
		// determine if ul should be opened or closed
		$isOpened = FALSE;
		
		// check cookie first
		$cookie_opened = isset($_COOKIE["admin-menu-tree-page-view-open-posts"]) ? $_COOKIE["admin-menu-tree-page-view-open-posts"] : ""; // 2,95,n
		$cookie_opened = explode(",", $cookie_opened);

		// if we are editing a post, we should see it in the tree, right?
		if ( isset($_GET["action"]) && "edit" == $_GET["action"] && isset($_GET["post"])) {
			// if post with id get[post] is a parent of the current post, show it
			if ($_GET["post"] != $one_page->ID) {
				$post_to_check_parents_for = $_GET["post"];
				// seems to be a problem with get_post_ancestors (yes, it's in the trac too)
				wp_cache_delete($post_to_check_parents_for, 'posts');
				$one_page_parents = get_post_ancestors($post_to_check_parents_for);
				if (in_array($one_page->ID, $one_page_parents)) {
					$isOpened = TRUE;
				}
			}
		}

		if (in_array($one_page->ID, $cookie_opened) || $isOpened && $post_children_count>0) {
			$class .= " admin-menu-tree-page-view-opened";
		} elseif ($post_children_count>0) {
			$class .= " admin-menu-tree-page-view-closed";
		}		
		
		$class .= " nestedSortable";
		
		$output .= "<li class='$class'>";
		// first div used for nestedSortable
		$output .= "<div>";
		// div used to make hover work and to put edit-popup outside the <a>
		$output .= "<div class='amtpv-linkwrap' data-post-id='".$one_page->ID."'>";
		$output .= "<a href='$edit_link' data-post-id='".$one_page->ID."'>$status_span";
		$output .= $title;

		// add the view link, hidden, used in popup
		$permalink = get_permalink($one_page->ID);
		$output .= "<span class='admin-menu-tree-page-view-view-link'>$permalink</span>";
		$output .= "<span class='admin-menu-tree-page-view-edit'></span>";

		$output .= "</a>";
		
		// popup edit div
		$output .= "
			<div class='amtpv-editpopup'>
				<div class='amtpv-editpopup-editview'>
					<div class='amtpv-editpopup-edit' data-link='".$edit_link."'>".__("Edit", 'admin-menu-tree-page-view')."</div>
					 | 
					<div class='amtpv-editpopup-view' data-link='".$permalink."'>".__("View", 'admin-menu-tree-page-view')."</div>
				</div>
				<div class='amtpv-editpopup-add'>".__("Add new page", 'admin-menu-tree-page-view')."<br />
					<div class='amtpv-editpopup-add-after'>".__("After", 'admin-menu-tree-page-view')."</div>
					 | 
					<div class='amtpv-editpopup-add-inside'>".__("Inside", 'admin-menu-tree-page-view')."</div>
				</div>
				<div class='amtpv-editpopup-postid'>".__("Post ID:", 'admin-menu-tree-page-view')." " . $one_page->ID."</div>
			</div>
		";

		// close div used to make hover work and to put edit-popup outside the <a>
		$output .= "</div>";

		// close div for nestedSortable
		$output .= "</div>";

		// add child articles
		$output .= $str_child_output;
		
		$output .= "</li>";
	}
	
	// if this is a child listing, add ul
	if (isset($args["child_of"]) && $args["child_of"] && $output != "") {
		$output = "<ul class='admin-menu-tree-page-tree_childs'>$output</ul>";
	}
	
	return $output;
}

function admin_menu_tree_page_view_admin_menu() {

	load_plugin_textdomain('admin-menu-tree-page-view', false, "/admin-menu-tree-page-view/languages");

	// add main menu
	#add_menu_page( "title", "Simple Menu Pages", "edit_pages", "admin-menu-tree-page-tree_main", "bonnyFunction", null, 5);

	// end link that is written automatically by WP, and begin ul
	$output = "
		</a>
		<ul class='admin-menu-tree-page-tree'>
			<li class='admin-menu-tree-page-tree_headline'>" . __("Pages", 'admin-menu-tree-page-view') . "</li>
			<li class='admin-menu-tree-page-filter'>
				<label>".__("Search", 'admin-menu-tree-page-view')."</label>
				<input type='text' class='' />
				<div class='admin-menu-tree-page-filter-reset' title='".__("Reset search and show all pages", 'admin-menu-tree-page-view')."'></div>
				<div class='admin-menu-tree-page-filter-nohits'>".__("No pages found", 'admin-menu-tree-page-view')."</div>
			</li>
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
	add_submenu_page("edit.php?post_type=page", "Admin Menu Tree Page View", $output, "edit_pages", "admin-menu-tree-page-tree", "admin_menu_tree_page_page");

}

function admin_menu_tree_page_page() {
	?>
	
	<h2>Admin Menu Tree Page View</h2>
	<p>Nothing to see here. Move along! :)</p>
	
	<?php
}



/**
 * Code from plugin CMS Tree Page View
 * http://wordpress.org/extend/plugins/cms-tree-page-view/
 * Used with permission! :)
 */
function admin_menu_tree_page_view_add_page() {

	global $wpdb;

	/*
	(
	[action] => cms_tpv_add_page 
	[pageID] => cms-tpv-1318
	type
	)
	action	admin_menu_tree_page_view_add_page
	pageID	448
	page_titles[]	pending inside
	post_status	pending
	post_type	page
	type	inside
	*/
	$type = $_POST["type"];
	$pageID = (int) $_POST["pageID"];
	$post_type = $_POST["post_type"];
	$wpml_lang = isset($_POST["wpml_lang"]) ? $_POST["wpml_lang"] : "";
	$page_titles = (array) $_POST["page_titles"];
	$ref_post = get_post($pageID);
	$post_status = $_POST["post_status"];
	if (!$post_status) { $post_status = "draft"; }

	$post_id_to_return = NULL;

	if ("after" == $type) {

		/*
			add page under/below ref_post
		*/

		if (!function_exists("admin_menu_tree_page_view_add_page_after")) {
		function admin_menu_tree_page_view_add_page_after($ref_post_id, $page_title, $post_type, $post_status = "draft") {
			
			global $wpdb;
			
			$ref_post = get_post($ref_post_id);
			// update menu_order of all pages below our page
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+2 WHERE post_parent = %d AND menu_order >= %d AND id <> %d ", $ref_post->post_parent, $ref_post->menu_order, $ref_post->ID ) );
			
			// create a new page and then goto it
			$post_new = array();
			$post_new["menu_order"] = $ref_post->menu_order+1;
			$post_new["post_parent"] = $ref_post->post_parent;
			$post_new["post_type"] = "page";
			$post_new["post_status"] = $post_status;
			$post_new["post_title"] = $page_title;
			$post_new["post_content"] = "";
			$post_new["post_type"] = $post_type;
			$newPostID = wp_insert_post($post_new);
			return $newPostID;
		}
		}
		
		$ref_post_id = $ref_post->ID;
		$loopNum = 0;
		foreach ($page_titles as $one_page_title) {
			$newPostID = admin_menu_tree_page_view_add_page_after($ref_post_id, $one_page_title, $post_type, $post_status);
			$new_post = get_post($newPostID);
			$ref_post_id = $new_post->ID;
			if ($loopNum == 0) {
				$post_id_to_return = $newPostID;
			}
			$loopNum++;
		}
		

	} else if ( "inside" == $type ) {

		/*
			add page inside ref_post
		*/
		if (!function_exists("admin_menu_tree_page_view_add_page_inside")) {
		function admin_menu_tree_page_view_add_page_inside($ref_post_id, $page_title, $post_type, $post_status = "draft") {

			global $wpdb;
			
			$ref_post = get_post($ref_post_id);

			// update menu_order, so our new post is the only one with order 0
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE post_parent = %d", $ref_post->ID) );		
	
			$post_new = array();
			$post_new["menu_order"] = 0;
			$post_new["post_parent"] = $ref_post->ID;
			$post_new["post_type"] = "page";
			$post_new["post_status"] = $post_status;
			$post_new["post_title"] = $page_title;
			$post_new["post_content"] = "";
			$post_new["post_type"] = $post_type;
			$newPostID = wp_insert_post($post_new);
			return $newPostID;
		
		}
		}
		
		// add reversed
		$ref_post_id = $ref_post->ID;
		$page_titles = array_reverse($page_titles);
		$loopNum = 0;
		foreach ($page_titles as $one_page_title) {
			$newPostID = admin_menu_tree_page_view_add_page_inside($ref_post_id, $one_page_title, $post_type, $post_status);
			$new_post = get_post($newPostID);
			// $ref_post_id = $new_post->ID;
			if ($loopNum == 0) {
				$post_id_to_return = $newPostID;
			}
			$loopNum++;
		}
		$post_id_to_return = $newPostID;

	}
	
	if ($post_id_to_return) {
		// return editlink for the newly created page
		$editLink = get_edit_post_link($post_id_to_return, '');
		if ($wpml_lang) {
			$editLink = add_query_arg("lang", $wpml_lang, $editLink);
		}
		echo $editLink;
	} else {
		// fail, tell js
		echo "0";
	}
	#print_r($post_new);
	exit;
}
