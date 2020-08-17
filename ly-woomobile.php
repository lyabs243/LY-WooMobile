<?php
/**
 * Plugin Name: Woo Mobile
 * Description: This plugin creates a REST API which will allow communicatio with a mobile application to transform a website into a mobile application.
 * Version: 1.0
 * Author: Loic Yabili, Lyabs Media
 * License: GPLV3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.fr.html
 *
 * Woo Mobile is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Woo Mobile is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Woo Mobile. If not, see https://www.gnu.org/licenses/gpl-3.0.fr.html.
 */

include_once 'includes/lywoomo_posts.php';
include_once 'includes/lywoomo_categories.php';

/**
 * add api key to database
 */
function lywoomo_add_option_apikey() {
	$apikey = '6abJACvxcu87EZ1ODhkL';
	add_option('lywoomo_api_key', $apikey);
}

/**
 * Register function call on plugin activated
 */
function lywoomo_activate() {
	lywoomo_add_option_apikey();
}
register_activation_hook(__FILE__, 'lywoomo_activate');