<?php
/*
Plugin Name: Admin Menu Tree Page View
Plugin URI: http://eskapism.se/code-playground/admin-menu-tree-page-view/
Description: Adds a tree of all your pages or custom posts. Use drag & drop to reorder your pages, and edit, view, add, and search your pages.
Version: 0.3
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
add_action('wp_ajax_admin_menu_tree_page_view_add_page', 'admin_menu_tree_page_view_add_page');

function admin_menu_tree_page_view_admin_init() {
	define( "admin_menu_tree_page_view_URL", WP_PLUGIN_URL . '/admin-menu-tree-page-view/' );
	define( "admin_menu_tree_page_view_VERSION", "0.3" );
	wp_enqueue_style("admin_menu_tree_page_view_styles", admin_menu_tree_page_view_URL . "styles.css", false, admin_menu_tree_page_view_VERSION);
	wp_enqueue_script("jquery.highlight", admin_menu_tree_page_view_URL . "jquery.highlight.js", array("jquery"));
}

function admin_menu_tree_page_view_admin_head() {
	?>
	<script type="text/javascript">
		jQuery(function($) {

			setTimeout(function() {
				jQuery("#toplevel_page_admin-menu-tree-page-tree_main").addClass("wp-menu-open");
			}, 100);
			

			// show menu when menu icon is clicked
			jQuery(".admin-menu-tree-page-view-edit").click(function() {
				
				var $this = $(this);
								
				// check if this tree has a menu div defined
				var wpsubmenu = $(this).closest("div.wp-submenu");
				if (wpsubmenu.length == 1) {
					
					var div_popup = wpsubmenu.find(".admin-menu-tree-page-view-popup");
					var do_show = true;
					if (div_popup.length == 0) {
						// no menu div yet, create it
						var html = "";
						html += "<div class='admin-menu-tree-page-view-popup'><span class='admin-menu-tree-page-view-popup-arrow'></span><span class='admin-menu-tree-page-view-popup-page'></span>";
						html += "<ul>";
						html += "<li class='admin-menu-tree-page-view-popup-edit'><a href=''>Edit</a></li>";
						html += "<li class='admin-menu-tree-page-view-popup-view'><a href=''>View</a></li>";
						html += "<li class='admin-menu-tree-page-view-popup-add-here'><a href=''>Add new page here</a></li>";
						html += "<li class='admin-menu-tree-page-view-popup-add-inside'><a href=''>Add new page inside</a></li>";
						html += "</ul></div>";
						var div_popup = $(html).appendTo(wpsubmenu);
						div_popup.show(); // must do this..
						div_popup.hide(); // ..or fade does not work first time
					} else {
						if (div_popup.is(":visible")) {
							//do_show = false;
						}
					}
					
					var a = $this.closest("a");
					var link_text = a.text();
					if (div_popup.find(".admin-menu-tree-page-view-popup-page").text() == link_text) {
						do_show = false;
					}
					div_popup.find(".admin-menu-tree-page-view-popup-page").text( link_text );
					var offset = $this.offset();
					offset.top = (offset.top-3);
					offset.left = (offset.left-3);

					// store post_id
					var post_id = a.attr("href").match(/post=([\w]+)/);
					post_id = post_id[1];
					div_popup.data("admin-menu-tree-page-view-current-post-id", post_id);

					// setup edit and view links
					var edit_link = "post.php?post="+post_id+"&action=edit";
					div_popup.find(".admin-menu-tree-page-view-popup-edit a").attr("href", edit_link);
					
					// view link, this is probably not such a safe way to this this. but let's try! :)
					var view_link = "../?p=" + post_id;
					div_popup.find(".admin-menu-tree-page-view-popup-view a").attr("href", view_link);
					
					if (do_show) {
						//console.log("show");
						div_popup.fadeIn("fast");
					} else {
						// same popup, so close it
						//console.log("hide");
						div_popup.fadeOut("fast");
						div_popup.find(".admin-menu-tree-page-view-popup-page").text("");
					}
					
					div_popup.offset( offset ); // must be last or position gets wrong somehow
					
				}
				
				return false;
			});
			
			// hide menu
			$(".admin-menu-tree-page-view-popup-arrow").live("click", function() {
				$(this).closest(".admin-menu-tree-page-view-popup").fadeOut("fast");
				return false;
			});
			
			// add page
			$(".admin-menu-tree-page-view-popup-add-here, .admin-menu-tree-page-view-popup-add-inside").live("click", function() {
				var div_popup = $(this).closest(".admin-menu-tree-page-view-popup");
				var post_id = div_popup.data("admin-menu-tree-page-view-current-post-id");
				
				var type = "after";
				if ($(this).hasClass("admin-menu-tree-page-view-popup-add-inside")) {
					type = "inside";
				}
				
				var page_title = prompt("Enter name of new page", "Untitled");
				if (page_title) {
					
					var data = {
						"action": 'admin_menu_tree_page_view_add_page',
						"pageID": post_id,
						"type": type,
						"page_title": page_title,
						"post_type": "page"
					};
					jQuery.post(ajaxurl, data, function(response) {
						//alert(response);
						if (response != "0") {
							document.location = response;
						}
					});
				
				} else {
					return false;
				}
				
			});
			
			// search/filter pages
			$(".admin-menu-tree-page-filter input").keyup(function(e) {
				var ul = $(this).closest(".admin-menu-tree-page-tree");
				ul.find("li").hide();
				ul.find(".admin-menu-tree-page-tree_headline,.admin-menu-tree-page-filter").show();
				var s = $(this).val();
				var selector = "li:AminMenuTreePageContains('"+s+"')";
				var hits = ul.find(selector);
				if (hits.length > 0 || s != "") {
					ul.find(".admin-menu-tree-page-filter-reset").fadeIn("fast");
					ul.unhighlight();
				}
				if (s == "") {
					ul.find(".admin-menu-tree-page-filter-reset").fadeOut("fast");
				}
				ul.highlight(s);
				hits.show();
				
			});

			// clear/reset filter and show all pages again
			$(".admin-menu-tree-page-filter-reset").click(function() {
				var $t = $(this);
				var ul = $t.closest(".admin-menu-tree-page-tree");
				ul.find("li").fadeIn("fast");
				$t.fadeOut("fast");
				$t.closest(".admin-menu-tree-page-filter").find("input").val("").focus();
				ul.unhighlight();
			});
			
			// label = hide in and focus input
			$(".admin-menu-tree-page-filter label").click(function() {
				var $t = $(this);
				$t.hide();
				$t.closest(".admin-menu-tree-page-filter").find("input").focus();
			});

		});
		// http://stackoverflow.com/questions/187537/is-there-a-case-insensitive-jquery-contains-selector
		jQuery.expr[':'].AminMenuTreePageContains = function(a,i,m){
		     return (a.textContent || a.innerText || "").toLowerCase().indexOf(m[3].toLowerCase())>=0;
		};
		
	</script>
	
	<?php

}

function admin_menu_tree_page_view_get_pages($args) {


	#$pages = get_pages($args);

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
	foreach ($pages as $one_page) {
		$edit_link = get_edit_post_link($one_page->ID);
		$title = get_the_title($one_page->ID);
		$class = "";
		if (isset($_GET["action"]) && $_GET["action"] == "edit" && isset($_GET["post"]) && $_GET["post"] == $one_page->ID) {
			$class = "current";
		}
		$status_span = "";
		if ($one_page->post_password) {
			$status_span .= "<span class='admin-menu-tree-page-view-protected'></span>";
		}
		if ($one_page->post_status != "publish") {
			$status_span .= "<span class='admin-menu-tree-page-view-status admin-menu-tree-page-view-status-{$one_page->post_status}'>{$one_page->post_status}</span>";
		}

		$output .= "<li class='$class'>";
		$output .= "<a href='$edit_link'>$status_span";
		$output .= $title;

		$output .= "<span class='admin-menu-tree-page-view-edit'></span>";

		$output .= "</a>";

		// now fetch child articles
		#print_r($one_page);
		$args_childs = $args;
		$args_childs["parent"] = $one_page->ID;
		$args_childs["post_parent"] = $one_page->ID;
		$args_childs["child_of"] = $one_page->ID;
		#echo "<pre>";print_r($args_childs);
		$output .= admin_menu_tree_page_view_get_pages($args_childs);
		
		$output .= "</li>";
	}
	
	// if this is a child listing, add ul
	if (isset($args["child_of"]) && $args["child_of"]) {
		$output = "<ul class='admin-menu-tree-page-tree_childs'>$output</ul>";
	}
	
	return $output;
}

function admin_menu_tree_page_view_admin_menu() {

	// add main menu
	#add_menu_page( "title", "Simple Menu Pages", "edit_pages", "admin-menu-tree-page-tree_main", "bonnyFunction", null, 5);

	// end link that is written automatically by WP, and begin ul
	$output = "
		</a>
		<ul class='admin-menu-tree-page-tree'>
		<li class='admin-menu-tree-page-tree_headline'>Pages</li>
		<li class='admin-menu-tree-page-filter'>
			<label>Search/Filter pages</label>
			<input type='text' class='' />
			<div class='admin-menu-tree-page-filter-reset' title='Reset filter and show all pages'></div>
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
	add_submenu_page("edit.php?post_type=page", "Tree View", $output, "edit_pages", "admin-menu-tree-page-tree", "admin_menu_tree_page_page");

}

function admin_menu_tree_page_page() {
	?>
	
	<h2>Simple Admin Menu Tree</h2>
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
	*/
	$type = $_POST["type"];
	$pageID = (int) $_POST["pageID"];
	#$pageID = str_replace("cms-tpv-", "", $pageID);
	$page_title = trim($_POST["page_title"]);
	$post_type = $_POST["post_type"];
	$wpml_lang = $_POST["wpml_lang"];
	if (!$page_title) { $page_title = __("New page", 'cms-tree-page-view'); }

	$ref_post = get_post($pageID);
	
	if ("after" == $type) {

		/*
			add page under/below ref_post
		*/

		// update menu_order of all pages below our page
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+2 WHERE post_parent = %d AND menu_order >= %d AND id <> %d ", $ref_post->post_parent, $ref_post->menu_order, $ref_post->ID ) );		
		
		// create a new page and then goto it
		$post_new = array();
		$post_new["menu_order"] = $ref_post->menu_order+1;
		$post_new["post_parent"] = $ref_post->post_parent;
		$post_new["post_type"] = "page";
		$post_new["post_status"] = "draft";
		$post_new["post_title"] = $page_title;
		$post_new["post_content"] = "";
		$post_new["post_type"] = $post_type;
		$newPostID = wp_insert_post($post_new);

	} else if ( "inside" == $type ) {

		/*
			add page inside ref_post
		*/

		// update menu_order, so our new post is the only one with order 0
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = menu_order+1 WHERE post_parent = %d", $ref_post->ID) );		

		$post_new = array();
		$post_new["menu_order"] = 0;
		$post_new["post_parent"] = $ref_post->ID;
		$post_new["post_type"] = "page";
		$post_new["post_status"] = "draft";
		$post_new["post_title"] = $page_title;
		$post_new["post_content"] = "";
		$post_new["post_type"] = $post_type;
		$newPostID = wp_insert_post($post_new);

	}
	if ($newPostID) {
		// return editlink for the newly created page
		$editLink = get_edit_post_link($newPostID, '');
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
