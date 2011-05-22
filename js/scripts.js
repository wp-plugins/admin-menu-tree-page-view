
jQuery(function($) {

	setTimeout(function() {
		jQuery("#toplevel_page_admin-menu-tree-page-tree_main").addClass("wp-menu-open");
	}, 100);

	// show menu when menu icon is clicked
	jQuery("span.admin-menu-tree-page-view-edit").click(function() {
		
		var $this = $(this);
						
		// check if this tree has a menu div defined
		var wpsubmenu = $(this).closest("div.wp-submenu");
		if (wpsubmenu.length == 1) {
			
			var div_popup = wpsubmenu.find("div.admin-menu-tree-page-view-popup");
			var do_show = true;
			if (div_popup.length == 0) {
				// no menu div yet, create it
				var html = "";
				html += "<div class='admin-menu-tree-page-view-popup'><span class='admin-menu-tree-page-view-popup-arrow'></span><span class='admin-menu-tree-page-view-popup-page'></span>";
				html += "<ul>";
				html += "<li class='admin-menu-tree-page-view-popup-edit'><a href=''>"+amtpv_l10n.Edit+"</a></li>";
				html += "<li class='admin-menu-tree-page-view-popup-view'><a href=''>"+amtpv_l10n.View+"</a></li>";
				html += "<li class='admin-menu-tree-page-view-popup-add-here'><a href=''>"+amtpv_l10n.Add_new_page_here+"</a></li>";
				html += "<li class='admin-menu-tree-page-view-popup-add-inside'><a href=''>"+amtpv_l10n.Add_new_page_inside+"</a></li>";
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
			if (div_popup.find("div.admin-menu-tree-page-view-popup-page").text() == link_text) {
				do_show = false;
			}
			div_popup.find("div.admin-menu-tree-page-view-popup-page").text( link_text );
			var offset = $this.offset();
			offset.top = (offset.top-3);
			offset.left = (offset.left-3);

			// store post_id
			var post_id = a.attr("href").match(/post=([\w]+)/);
			post_id = post_id[1];
			div_popup.data("admin-menu-tree-page-view-current-post-id", post_id);

			// setup edit and view links
			var edit_link = "post.php?post="+post_id+"&action=edit";
			div_popup.find("div.admin-menu-tree-page-view-popup-edit a").attr("href", edit_link);
			
			// view link, this is probably not such a safe way to this this. but let's try! :)
			var view_link = $this.closest("li").find(".admin-menu-tree-page-view-view-link").text();
			div_popup.find("div.admin-menu-tree-page-view-popup-view a").attr("href", view_link);
			
			if (do_show) {
				div_popup.fadeIn("fast");
			} else {
				// same popup, so close it
				div_popup.fadeOut("fast");
				div_popup.find("div.admin-menu-tree-page-view-popup-page").text("");
			}
			
			div_popup.offset( offset ); // must be last or position gets wrong somehow
			
		}
		
		return false;
	});
	
	// hide menu
	$("span.admin-menu-tree-page-view-popup-arrow").live("click", function() {
		$(this).closest("div.admin-menu-tree-page-view-popup").fadeOut("fast");
		return false;
	});
	
	// add page
	$(".admin-menu-tree-page-view-popup-add-here, .admin-menu-tree-page-view-popup-add-inside").live("click", function() {
		var div_popup = $(this).closest("div.admin-menu-tree-page-view-popup");
		var post_id = div_popup.data("admin-menu-tree-page-view-current-post-id");
		
		var type = "after";
		if ($(this).hasClass("admin-menu-tree-page-view-popup-add-inside")) {
			type = "inside";
		}
		
		var page_title = prompt("Enter name of new page", amtpv_l10n.Untitled);
		if (page_title) {
			
			var data = {
				"action": 'admin_menu_tree_page_view_add_page',
				"pageID": post_id,
				"type": type,
				"page_title": page_title,
				"post_type": "page"
			};
			jQuery.post(ajaxurl, data, function(response) {
				if (response != "0") {
					document.location = response;
				}
			});
			return false;
		
		} else {
			return false;
		}
		
	});
	
	// search/filter pages
	$("li.admin-menu-tree-page-filter input").keyup(function(e) {
		var ul = $(this).closest("ul.admin-menu-tree-page-tree");
		ul.find("li").hide();
		ul.find("li.admin-menu-tree-page-tree_headline,li.admin-menu-tree-page-filter").show();
		var s = $(this).val();
		var selector = "li:AminMenuTreePageContains('"+s+"')";
		var hits = ul.find(selector);
		if (hits.length > 0 || s != "") {
			ul.find("div.admin-menu-tree-page-filter-reset").fadeIn("fast");
			ul.unhighlight();
		}
		if (s == "") {
			ul.find("div.admin-menu-tree-page-filter-reset").fadeOut("fast");
		}
		ul.highlight(s);
		hits.show();
		
		// hits can be childs of hidden li:s, so we must show the parents of the hits too
		hits.each(function(i, elm) {
			var parent = elm.parentNode;
			console.log(parent);
			if (parent) {
				parent = $(parent);
				// ul -> div -> ul
				parent.parent().parent().addClass("admin-menu-tree-page-view-opened").removeClass("admin-menu-tree-page-view-closed");
				//console.log(parent.parent());
				parent.show();
			}
		});
		
		// if no hits: tell the user so we have less confusion. confusion is bad.
		var nohits_div = ul.find("div.admin-menu-tree-page-filter-nohits");
		if (hits.length == 0) {
			nohits_div.show();
		} else {
			nohits_div.hide();
		}
		
	});

	// clear/reset filter and show all pages again
	$("div.admin-menu-tree-page-filter-reset").click(function() {
		var $t = $(this);
		var ul = $t.closest("ul.admin-menu-tree-page-tree");
		ul.find("li").fadeIn("fast");
		$t.fadeOut("fast");
		$t.closest("li.admin-menu-tree-page-filter").find("input").val("").focus();
		ul.unhighlight();
		ul.find("div.admin-menu-tree-page-filter-nohits").hide();
	});
	
	// label = hide in and focus input
	$("li.admin-menu-tree-page-filter label, li.admin-menu-tree-page-filter input").click(function() {
		var $t = $(this);
		$t.closest("li.admin-menu-tree-page-filter").find("label").hide();
		$t.closest("li.admin-menu-tree-page-filter").find("input").focus();
	});

	var trees = jQuery("ul.admin-menu-tree-page-tree");
	
	// add links to expand/collapse
	trees.find("li.admin-menu-tree-page-view-has-childs > div").after("<div class='admin-menu-tree-page-expand' title='Show/Hide child pages' />");
	trees.find("div.admin-menu-tree-page-expand").live("click", function(e) {
		
		e.preventDefault();
		var $t = $(this);
		var $li = $t.closest("li");
		var $a = $li.find("a:first");
		var $ul = $li.find("ul:first");
		
		var isOpen = false;
		if ($ul.is(":visible")) {
			$ul.slideUp(function() {
				$li.addClass("admin-menu-tree-page-view-closed").removeClass("admin-menu-tree-page-view-opened");
			});
			
		} else {
			$ul.slideDown(function() {
				$li.addClass("admin-menu-tree-page-view-opened").removeClass("admin-menu-tree-page-view-closed");
			});
			isOpen = true;
		}

		var post_id = $a.attr("href").match(/\?post=([\d]+)/)[1];
		var array_pos = $.inArray(post_id, admin_menu_tree_page_view_opened_posts);
		if (array_pos > -1) {
			// did exist in cookie
			admin_menu_tree_page_view_opened_posts = admin_menu_tree_page_view_opened_posts.splice(array_pos+1, 1);
		}
		// array now has not our post_id. so add it if visible/open
		if (isOpen) {
			admin_menu_tree_page_view_opened_posts.push(post_id);
		}

		admin_menu_tree_page_view_save_opened_posts();

	});



	// mouse over to show edit-box
	//$("ul.admin-menu-tree-page-tree li a:first-child").live("mouseenter mouseleave", function(e) {
	$("ul.admin-menu-tree-page-tree li div.amtpv-linkwrap:first-child").live("mouseenter mouseleave", function(e) {

		var t = $(this);
		var li = t.closest("li");
		
		var popupdiv = li.find("span.amtpv-editpopup:first");
		
		if (e.type == "mouseenter" || e.type == "mouseover") {
			var ul = t.closest("ul.admin-menu-tree-page-tree");
			ul.find("span.amtpv-editpopup").removeClass("amtpv-editpopup-hover");
			ul.find("div.amtpv-linkwrap").removeClass("amtpv-linkwrap-hover");
			popupdiv.addClass("amtpv-editpopup-hover");
			li.find("div.amtpv-linkwrap:first").addClass("amtpv-linkwrap-hover");
		} else if (e.type == "mouseleave") {
			// don't hide if related target is the shadow of the menu, aka #adminmenushadow
			var do_hide = true;
			if (e.relatedTarget && e.relatedTarget.id == "adminmenushadow") {
				do_hide = false;
			}
			if (do_hide) {
				popupdiv.removeClass("amtpv-editpopup-hover");
			}
			
		}
	});
	
	// don't allow clicks directly on .amtpv-editpopup. it's kinda confusing
	$("span.amtpv-editpopup").live("click", function(e) {
		//e.preventDefault();
	});
	
	// edit/view links
	$("span.amtpv-editpopup-edit, span.amtpv-editpopup-view").live("click",function(e) {
		e.preventDefault();
		var t = $(this);
		var link = t.data("link");
		var new_win = false;
		
		if ( ($.client.os == "Mac" && (e.metaKey || e.shiftKey)) || ($.client.os != "Mac" && e.ctrlKey) ) {
			new_win = true;
		}		
		if (new_win) {
			window.open(link);
		} else {
			document.location = link;
		}
		
	});
	
	// add links
	$("span.amtpv-editpopup-add-after, span.amtpv-editpopup-add-inside").live("click", function(e) {

		var t = $(this);
		var post_id = t.closest("a").data("post-id");
		var popup = t.closest("span.amtpv-editpopup");
		var editpopup_add = popup.find("span.amtpv-editpopup-add");
		var editpopup_editview = popup.find("span.amtpv-editpopup-editview");
		
		//editpopup_add.hide();
		//editpopup_editview.hide();
		popup.find("> span").hide();

		var type = "after";
		if (t.hasClass("amtpv-editpopup-add-inside")) {
			type = "inside";
		}
		
		// remove possibly previous added add-stuff
		popup.find("span.amtpv-editpopup-addpages").remove();
		
		var add_pages = $("<span />")
			.addClass("amtpv-editpopup-addpages")
			.insertAfter(editpopup_add)
			;
		add_pages.append( "<span class='amtpv-editpopup-addpages-headline'>Add new page(s)</span>" );
		add_pages.append( $("<input type='hidden' class='amtpv-editpopup-addpages-type' value='"+type+"' />") );
		//add_pages.append( $("<span class='amtpv-editpopup-addpages-status'><label>Status</label><select><option value='draft'>Draft</option><option value='publish'>Publish</option></select></span>"));
		add_pages.append( $("<label class='amtpv-editpopup-addpages-label'>Name</label>") );
		add_pages.append( $("<input class='amtpv-editpopup-addpages-name' type='text' value=''/>") );
		add_pages.append( $("<span class='amtpv-editpopup-addpages-addpage'>+ page</span>"));
		add_pages.append( $("<span class='amtpv-editpopup-addpages-publish-checkbox-wrap'><input id='amtpv-editpopup-addpages-publish-checkbox' type='checkbox' value='1'><label for='amtpv-editpopup-addpages-publish-checkbox'>Publish added pages</label></span>") );
		add_pages.append( $("<span class='amtpv-editpopup-addpages-submit'><input type='button' value='Add page(s)' /> or <span class='amtpv-editpopup-addpages-cancel'>cancel</span></span>"));
		add_pages.find(".amtpv-editpopup-addpages-name").focus();
		
		return;
		
	});


	$(".amtpv-editpopup").live("click", function(e) {
		//e.preventDefault();
		//e.stopPropagation();
	});
	
	$("input.amtpv-editpopup-addpages-publish-checkbox input").live("click", function(e) {
		//e.preventDefault();
		//e.stopPropagation();
		//var t = $(this);
		//this.checked = !this.checked;
		//console.log(t.prop("checked"));
		//t.prop("checked", true);
		//t.attr("checked", true);
		//console.log(t.prop("checked"));
	});

	// add new page-link
	$("span.amtpv-editpopup-addpages-addpage").live("click", function() {
		var t = $(this);
		var newelm = $("<input class='amtpv-editpopup-addpages-name' type='text' value=''/>");
		t.before( newelm );
		newelm.focus();
	});
	
	// cancel-link
	$("span.amtpv-editpopup-addpages-cancel").live("click", function() {
		var t = $(this);
		var popup = t.closest("span.amtpv-editpopup");
		popup.find("span.amtpv-editpopup-addpages").hide().remove();
		popup.find("> span").show();
	});
	
	// woho, add da pages!
	$("span.amtpv-editpopup-addpages-submit input").live("click", function() {
		// fetch all .amtpv-editpopup-addpages-name for this popup
		//console.log("add pages");
		var t = $(this);
		var div_popup = t.closest("div.admin-menu-tree-page-view-popup");
		var post_id = t.closest("div.amtpv-linkwrap").data("post-id");
		var popup = t.closest("span.amtpv-editpopup");
		//var post_id = div_popup.data("admin-menu-tree-page-view-current-post-id");

		var names = popup.find(".amtpv-editpopup-addpages-name");

		var arr_names = [];
		names.each(function(i, elm) {
			var name = $.trim($(elm).val());
			if (name) {
				arr_names.push( $(elm).val() );
			}
		});
		
		// we must at least have one name
		// @todo: make this a bit better looking
		if (arr_names.length == 0) {
			alert("Please enter a name for the new page");
			return false;
		}
		
		// detect after or inside
		var type = popup.find(".amtpv-editpopup-addpages-type").val();
		
		var data = {
			"action": 'admin_menu_tree_page_view_add_page',
			"pageID": post_id,
			"type": type,
			"page_titles": arr_names,
			"post_type": "page"
		};
		// console.log("data", data);

		jQuery.post(ajaxurl, data, function(response) {
			if (response != "0") {
				var new_win = false;
				//if ( ($.client.os == "Mac" && (e.metaKey || e.shiftKey)) || ($.client.os != "Mac" && e.ctrlKey) ) {
				//	new_win = true;
				//}
				return;
				if (new_win) {
					window.open(response);
				} else {
					document.location = response;
				}

			}
		});

	});

});

function admin_menu_tree_page_view_save_opened_posts() {
	jQuery.cookie('admin-menu-tree-page-view-open-posts', admin_menu_tree_page_view_opened_posts.join(","));
}

// array with all post ids that are open
var admin_menu_tree_page_view_opened_posts = jQuery.cookie('admin-menu-tree-page-view-open-posts') || "";
admin_menu_tree_page_view_opened_posts = admin_menu_tree_page_view_opened_posts.split(",");
if (admin_menu_tree_page_view_opened_posts[0] == "") {
//	admin_menu_tree_page_view_opened_posts = [];
}

// http://stackoverflow.com/questions/187537/is-there-a-case-insensitive-jquery-contains-selector
jQuery.expr[':'].AminMenuTreePageContains = function(a,i,m){
     return (a.textContent || a.innerText || "").toLowerCase().indexOf(m[3].toLowerCase())>=0;
};
