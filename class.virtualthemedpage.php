<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

///*
// * Virtual Themed Page class
// *
// * This class implements virtual pages for a plugin.
// *
// * It is designed to be included then called for each part of the plugin
// * that wants virtual pages.
// *
// * It supports multiple virtual pages and content generation functions.
// * The content functions are only called if a page matches.
// *
// * The class uses the theme templates and as far as I know is unique in that.
// * It also uses child theme templates ahead of main theme templates.
// *
// * Example code follows class.
// *
// * August 2013 Brian Coogan
// *
// */
//
//
//// There are several virtual page classes, we want to avoid a clash!
////
////
//class Virtual_Themed_Pages_BC
//{
//    public $title = '';
//    public $body = '';
//    private $vpages = array();  // the main array of virtual pages
//    private $mypath = '';
//    public $blankcomments = "blank-comments.php";
//
//
//    function __construct($plugin_path = null, $blankcomments = null)
//    {
//		if (empty($plugin_path))
//			$plugin_path = dirname(__FILE__);
//		$this->mypath = $plugin_path;
//
//		if (! empty($blankcomments))
//			$this->blankcomments = $blankcomments;
//
//		// Virtual pages are checked in the 'parse_request' filter.
//		// This action starts everything off if we are a virtual page
//		add_action('parse_request', array($this, 'vtp_parse_request'));
//    }
//
//    function add($virtual_regexp, $contentfunction)
//    {
//		$this->vpages[$virtual_regexp] = $contentfunction;
//    }
//
//
//    // Check page requests for Virtual pages
//    // If we have one, call the appropriate content generation function
//    //
//    function vtp_parse_request(&$wp)
//    {
//	$p = $_SERVER['REQUEST_URI'];
//	$matched = 0;
//	foreach ($this->vpages as $regexp => $func)
//	{
//	    if (preg_match($regexp, $p))
//	    {
//		$matched = 1;
//		break;
//	    }
//	}
//	// Do nothing if not matched
//	if (!$matched)
//	    return;
//	// setup hooks and filters to generate virtual movie page
//	add_action('template_redirect', array($this, 'template_redir'));
//	add_filter('the_posts', array($this, 'vtp_createdummypost'));
//	// we also force comments removal; a comments box at the footer of
//	// a page is rather meaningless.
//	// This requires the blank_comments.php file be provided
//	add_filter('comments_template', array($this, 'disable_comments'), 11);
//	$this->template = $this->subtemplate = null;
//	$this->title = null;
//	unset($this->body);
//	call_user_func_array($func, array(&$this, $p));
//	if (isset($this->redirect))
//	{
//		wp_redirect($this->redirect);
//		exit;
//	}
//	if (! isset($this->body)) //assert
//	    wp_die("Virtual Themed Pages: must save ->body [VTP07]");
//	return $wp;
//    }
//    // Setup a dummy post/page
//    // From the WP view, a post == a page
//    //
//    function vtp_createdummypost($posts)
//    {
//	// have to create a dummy post as otherwise many templates
//	// don't call the_content filter
//	global $wp, $wp_query;
//	//create a fake post intance
//	$p = new stdClass;
//	// fill $p with everything a page in the database would have
//	$p->ID = -1;
//	$p->post_author = 1;
//	$p->post_date = current_time('mysql');
//	$p->post_date_gmt =  current_time('mysql', $gmt = 1);
//	$p->post_content = $this->body;
//	$p->post_title = $this->title;
//	$p->post_excerpt = '';
//	$p->post_status = 'publish';
//	$p->ping_status = 'closed';
//	$p->post_password = '';
//	$p->post_name = 'page'; // slug
//	$p->to_ping = '';
//	$p->pinged = '';
//	$p->modified = $p->post_date;
//	$p->modified_gmt = $p->post_date_gmt;
//	$p->post_content_filtered = '';
//	$p->post_parent = 0;
//	$p->guid = get_home_url('/' . $p->post_name); // use url instead?
//	$p->menu_order = 0;
//	$p->post_type = 'page';
//	$p->post_mime_type = '';
//	$p->comment_status = 'closed';
//	$p->comment_count = 0;
//	$p->filter = 'raw';
//	$p->ancestors = array(); // 3.6
//	// reset wp_query properties to simulate a found page
//	$wp_query->is_page = TRUE;
//	$wp_query->is_singular = TRUE;
//	$wp_query->is_home = FALSE;
//	$wp_query->is_archive = FALSE;
//	$wp_query->is_category = FALSE;
//	unset($wp_query->query['error']);
//	$wp->query = array();
//	$wp_query->query_vars['error'] = '';
//	$wp_query->is_404 = FALSE;
//	$wp_query->current_post = $p->ID;
//	$wp_query->found_posts = 1;
//	$wp_query->post_count = 1;
//	$wp_query->comment_count = 0;
//	// -1 for current_comment displays comment if not logged in!
//	$wp_query->current_comment = null;
//	$wp_query->is_singular = 1;
//	$wp_query->post = $p;
//	$wp_query->posts = array($p);
//	$wp_query->queried_object = $p;
//	$wp_query->queried_object_id = $p->ID;
//	$wp_query->current_post = $p->ID;
//	$wp_query->post_count = 1;
//	return array($p);
//    }
//
//
//    // Virtual Movie page - tell wordpress we are using the given
//    // template if it exists; otherwise we fall back to page.php.
//    //
//    // This func gets called before any output to browser
//    // and exits at completion.
//    //
//    function template_redir()
//    {
//	//    $this->body   -- body of the virtual page
//	//    $this->title  -- title of the virtual page
//	//    $this->template  -- optional theme-provided template eg: 'page'
//	//    $this->subtemplate -- optional subtemplate (eg movie)
//	//
//
//	if (! empty($this->template) && ! empty($this->subtemplate))
//	{
//	    // looks for in child first, then master:
//	    //    template-subtemplate.php, template.php
//	    get_template_part($this->template, $this->subtemplate);
//	}
//	elseif (! empty($this->template))
//	{
//	    // looks for in child, then master:
//	    //    template.php
//	    get_template_part($this->template);
//	}
//	elseif (! empty($this->subtemplate))
//	{
//	    // looks for in child, then master:
//	    //    template.php
//	    get_template_part($this->subtemplate);
//	}
//	else
//	{
//	    get_template_part('page');
//	}
//
//	// It would be possible to add a filter for the 'the_content' filter
//	// to detect that the body had been correctly output, and then to
//	// die if not -- this would help a lot with error diagnosis.
//
//        exit;
//    }
//
//
//    // Some templates always include comments regardless, sigh.
//    // This replaces the path of the original comments template with a
//    // empty template file which returns nothing, thus eliminating
//    // comments reliably.
//    function disable_comments($file)
//    {
//	if (file_exists($this->blankcomments))
//	   return($this->mypath.'/'.$blankcomments);
//	return($file);
//    }
//
//
//} // class
///*
//// Example code - you'd use something very like this in a plugin
////
//if (0)
//{
//    // require 'BC_Virtual_Themed_pages.php';
//    // this code segment requires the WordPress environment
//    $vp =  new Virtual_Themed_Pages_BC();
//    $vp->add('#/mypattern/unique#i', 'mytest_contentfunc');
//
//    // Example of content generating function
//    // Must set $this->body even if empty string
//    function mytest_contentfunc($v, $url)
//    {
//	// extract an id from the URL
//	$id = 'none';
//	if (preg_match('#unique/(\d+)#', $url, $m))
//	    $id = $m[1];
//	// could wp_die() if id not extracted successfully...
//
//	$v->title = "My Virtual Page Title";
//	$v->body = "Some body content for my virtual page test - id $id\n";
//	$v->template = 'page'; // optional
//	$v->subtemplate = 'billing'; // optional
//    }
//}
//*/
//
//
//
//// end