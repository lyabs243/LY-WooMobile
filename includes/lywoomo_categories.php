<?php
/**
 * Created by PhpStorm.
 * User: lyabs
 * Date: 17/08/2020
 * Time: 12:52
 */

//register route to retreive data as REST API
add_action('rest_api_init', function(){
	register_rest_route( 'lywoomo/v1', '/categories/', array(
		'methods' => 'POST',
		'callback' => 'lywoomo_get_categories',
	));
});

function lywoomo_get_categories() {
	$categories = array();
	$apikey = get_option('lywoomo_api_key');
	if (isset($_POST['api_key']) && $_POST['api_key'] == $apikey) {
		$allCategories = get_categories();
		foreach ($allCategories as $category) {
			$categories[] = array(
				'name' => $category->name,
				'slug' => $category->slug,
				'description' => $category->description,
				'cat_ID' => $category->cat_ID,
			);
		}
	}
	return $categories;
}

