<?php
/**
 * Created by PhpStorm.
 * User: lyabs
 * Date: 17/08/2020
 * Time: 13:26
 */

//on adding post, send notification
add_action( 'save_post', 'lywoomo_send_notification' );
function lywoomo_send_notification($postId) {
	$response = array();

	if ($postId > 0) {
		$post = get_post($postId);
		//send notification only for post type and for newly post created
		if ($post->post_type == 'post' && $post->post_date == $post->post_modified && $post->post_status == 'publish') {
			$heading = substr($post->post_title, 0, 100);
			$content = substr(strip_tags($post->post_content), 0, 250);
			$thumbnail = wp_get_attachment_url(
				get_post_thumbnail_id($post->ID)
			);

			$headings = array(
				'en' => $heading,
			);

			$contents = array(
				'en' => $content,
			);

			$data['post_id'] = "$postId";

			$response = wp_remote_post(
				'https://onesignal.com/api/v1/notifications', array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type' => 'application/json; charset=utf-8',
						'Authorization' => 'Basic ' . 'NGU1NzljM2MtOTU5NS00MGVjLWFkNTMtZDg1ZTBlNGMwMjhl'
					),
					'body'    => json_encode(array(
						'app_id'            => 'b847ba3e-6c07-47a3-a14e-232382a6580a',
						'included_segments' => array('All'),
						'data'              => $data,
						'headings'          => $headings,
						'contents'          => $contents,
						'big_picture'       => $thumbnail,
						'ttl'               => 14 * 86400
					)),
				)
			);
		}

	}
	return $response;
}

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
		$posts[] = lywoomo_init_post($post);
	}

	return array('data' => $posts);
}

add_action('rest_api_init', function(){
	register_rest_route( 'lywoomo/v1', '/post/', array(
		'methods' => 'POST',
		'callback' => 'lywoomo_get_post',
	));
});

function lywoomo_get_post() {
	$post = null;

	$apikey = get_option('lywoomo_api_key');
	if (isset($_POST['api_key']) && $_POST['api_key'] == $apikey) {
		$post = lywoomo_init_post(get_post((int)$_GET['id']), true);
	}

	return array('data' => $post);
}

//get post from url
add_action('rest_api_init', function(){
	register_rest_route( 'lywoomo/v1', '/post/url', array(
		'methods' => 'POST',
		'callback' => 'lywoomo_get_url_post',
	));
});

function lywoomo_get_url_post() {
	$post = null;

	$apikey = get_option('lywoomo_api_key');
	if (isset($_POST['api_key']) && $_POST['api_key'] == $apikey) {
		$result = url_to_postid($_POST['url']);
		if ($result > 0) {
			$post = lywoomo_init_post(get_post((int)$result), false);
		}
	}

	return array('data' => $post);
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
 					AND post_type = 'post'
 					ORDER BY post_date_gmt DESC
 					LIMIT %d , %d",
				"%$query%",
				$start,
				$totalItems
			), OBJECT
		);

		foreach ($results as $post) {
			$posts[] = lywoomo_init_post($post);
		}
	}
	return array('data' => $posts);
}

function lywoomo_init_post($post, $all_content=false) {

	$item['ID'] = (int)$post->ID;
	$item['post_date_gmt'] = $post->post_date_gmt;
	$item['post_date'] = $post->post_date;
	$item['post_date_timestamp'] = strtotime($post->post_date_gmt);
	$item['post_content'] = ($all_content)? $post->post_content:
		substr(strip_tags($post->post_content), 0, 100);
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

	return $item;
}