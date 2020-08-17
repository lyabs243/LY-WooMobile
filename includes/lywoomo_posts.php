<?php
/**
 * Created by PhpStorm.
 * User: lyabs
 * Date: 17/08/2020
 * Time: 13:26
 */

add_action('rest_api_init', function(){
	register_rest_route( 'lywoomo/v1', '/posts/', array(
		'methods' => 'POST',
		'callback' => 'lywoomo_get_posts',
	));
});

function lywoomo_get_posts() {
	$posts = array();
	$allposts = array();

	$apikey = get_option('lywoomo_api_key');
	if (isset($_POST['api_key']) && $_POST['api_key'] == $apikey) {
		$args = array('orderby' => 'post_date_gmt');
		if (isset($_GET['category']) && ((int)$_GET['category'] > 0)) {
			$args['category'] = (int)$_GET['category'];
		}
		if (isset($_GET['page']) && ((int)$_GET['page'] > 0)) {
			$args['paged'] = (int)$_GET['page'];
		}
		if (isset($_GET['numberposts']) && ((int)$_GET['numberposts'] > 0)) {
			$args['numberposts'] = (int)$_GET['numberposts'];
		}
		$allposts = get_posts($args);
	}

	//init of all posts
	foreach ($allposts as $post) {
		$item['ID'] = $post->ID;
		$item['post_date_gmt'] = $post->post_date_gmt;
		$item['post_content'] = $post->post_content;
		$item['post_title'] = $post->post_title;
		$item['permalink'] = get_permalink($post->ID);
		$item['thumbnail'] = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );

		$categories = array();
		$categoriesIds = wp_get_post_categories($post->ID);
		foreach ($categoriesIds as $id) {
			$category = get_category($id);
			$categories[] = array(
				'name' => $category->name,
				'slug' => $category->slug,
				'description' => $category->description,
				'cat_ID' => $category->cat_ID,
			);
		}
		$item['categories'] = $categories;

		$posts[] = $item;
	}

	return $posts;
}

add_action('rest_api_init', function(){
	register_rest_route( 'lywoomo/v1', '/posts/search', array(
		'methods' => 'POST',
		'callback' => 'lywoomo_search_posts',
	));
});

function lywoomo_search_posts() {
	global $wpdb;
	$posts = array();

	$totalItems = 5;
	$page = 1;

	$apikey = get_option('lywoomo_api_key');
	if (isset($_POST['api_key']) && $_POST['api_key'] == $apikey && isset($_POST['query']) && !empty($_POST['query'])) {
		$query = strip_tags(strtolower($_POST['query']));

		if (isset($_GET['total_items'])) {
			$totalItems = (int)$_GET['total_items'];
		}
		if (isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		}

		$start = ($page - 1) * $totalItems;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}posts
 					WHERE LOWER(post_content) LIKE %s
 					ORDER BY post_date_gmt DESC
 					LIMIT %d , %d",
				"%$query%",
				$start,
				$totalItems
			), OBJECT
		);

		foreach ($results as $post) {
			$item['ID'] = $post->ID;
			$item['post_date_gmt'] = $post->post_date_gmt;
			$item['post_content'] = $post->post_content;
			$item['post_title'] = $post->post_title;
			$item['permalink'] = get_permalink($post->ID);
			$item['thumbnail'] = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );

			$categories = array();
			$categoriesIds = wp_get_post_categories($post->ID);
			foreach ($categoriesIds as $id) {
				$category = get_category($id);
				$categories[] = array(
					'name' => $category->name,
					'slug' => $category->slug,
					'description' => $category->description,
					'cat_ID' => $category->cat_ID,
				);
			}
			$item['categories'] = $categories;

			$posts[] = $item;
		}
	}
	return $posts;
}