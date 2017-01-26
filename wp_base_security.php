<?php
/**
 * Plugin Name: Wp Base Security
 *
 * Plugin URI:  #
 * Description: Plugin per la sicurezza base di Wordpress - (inserisce in autometico delle regole nel file .htaccess)
 *
 * Version:     1.0.0
 * Author:      Alfredo Piccione <alfio.piccione@gmail.com>
 * Text Domain: pic
 * Domain Path: /languages
 * License      GPL 2
 *
 *    This program is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU General Public License
 *    as published by the Free Software Foundation; either version 2
 *    of the License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program; if not, write to the Free Software
 *    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('_wp_base_security_activate')) {
	/**
	 * Activation hook
	 *
	 * @since 1.0.0
	 */
	function _wp_base_security_activate() {

		do_action( '_wp_base_security_activate_hook' );

	}
	register_activation_hook( __FILE__, '_wp_base_security_activate' );
}

if (!function_exists('_wp_base_security_deactivate')) {
	/**
	 * Deactivation hook
	 *
	 * @since 1.0.0
	 */
	function _wp_base_security_deactivate() {

		do_action( '_wp_base_security_deactivate_hook' );
	}
	register_deactivation_hook( __FILE__, '_wp_base_security_deactivate' );
}

/* ---------------------------------------------------------------------------
 * Security
 * --------------------------------------------------------------------------- */

// Remove Generator
add_filter('the_generator', '__return_null');

if (!function_exists('_wp_base_security_remove_version')) {
	/**
	 * Remove version
	 *
	 * @since 1.0.0
	 *
	 * @param $src
	 * @return string
	 */
	function _wp_base_security_remove_version($src)
	{
		$src = remove_query_arg('ver', $src);
		return $src;
	}

	add_filter('style_loader_src', '_wp_base_security_remove_version');
	add_filter('script_loader_src', '_wp_base_security_remove_version');
}

if (!function_exists('wp_base_security_hide_login_message')) {
	/**
	 * Hide login message
	 *
	 * @since 1.0.0
	 *
	 * @param $message
	 * @return string|void
	 */
	function _wp_base_security_hide_login_message($message)
	{
		return __('username o password errati', 'pic');
	}

	add_filter('login_errors', '_wp_base_security_hide_login_message');
}

if (!function_exists('_wp_base_security_wp_login_filter')) {
	/**
	 * Hide wp-login
	 *
	 * @since 1.0.0
	 *
	 * @param $url
	 * @param $path
	 * @param $orig_scheme
	 * @return mixed
	 */
	function _wp_base_security_wp_login_filter($url, $path, $orig_scheme)
	{
		return str_replace('wp-login.php', 'administrator', $url);
	}

	add_filter('site_url', '_wp_base_security_wp_login_filter', 10, 3);
}

if (!function_exists('_wp_base_security_login_redirect')) {
	/**
	 * Login redirect
	 *
	 * @since 1.0.0
	 */
	function _wp_base_security_login_redirect()
	{
		if (strpos($_SERVER['REQUEST_URI'], 'administrator') === false && !is_admin()) {
			wp_redirect(site_url());
			exit();
		}
	}

	add_action('login_init', '_wp_base_security_login_redirect');
}

/* ---------------------------------------------------------------------------
 * Style
 * --------------------------------------------------------------------------- */

if (!function_exists('_wp_base_security_load_admin_style')) {
	/**
	 * Add style in admin
	 *
	 * @since 1.0.0
	 *
	 * @param $hook
	 */
	function _wp_base_security_load_admin_style($hook)
	{
		if ($hook != 'plugins.php') {
			return;
		}
		wp_enqueue_style('custom_wp_admin_css', plugins_url('wp_base_security-style.css', __FILE__));
	}

	add_action('admin_enqueue_scripts', '_wp_base_security_load_admin_style');


}

/* ---------------------------------------------------------------------------
 * .htacces rules
 * --------------------------------------------------------------------------- */

if (!function_exists('_wp_base_security_add_htaccess')) {
	/**
	 * Inserts an array of strings into a file (.htaccess ), placing it between
	 * BEGIN and END markers. Replaces existing marked info. Retains surrounding
	 * data. Creates file if none exists.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	function _wp_base_security_add_htaccess()
	{
		require_once( ABSPATH . '/wp-admin/includes/misc.php' );

		$rules = array();
		$rules[] = 'Options -Indexes';
		$rules[] = '<files wp-config.php>';
		$rules[] = 'order allow,deny';
		$rules[] = 'deny from all';
		$rules[] = '</files>';
		$rules[] = '';
		$rules[] = '<IfModule mod_rewrite.c>';
		$rules[] = 'RewriteEngine On';
		$rules[] = 'RewriteBase /';
		$rules[] = 'RewriteRule ^wp-admin/includes/ - [F,L]';
		$rules[] = 'RewriteRule !^wp-includes/ - [S=3]';
		$rules[] = 'RewriteRule ^wp-includes/[^/]+\.php$ - [F,L]';
		$rules[] = 'RewriteRule ^wp-includes/js/tinymce/langs/.+\.php - [F,L]';
		$rules[] = 'RewriteRule ^wp-includes/theme-compat/ - [F,L]';
		$rules[] = 'RewriteRule ^administrator$ wp-login.php';
		$rules[] = '</IfModule>';

		$htaccess_file = ABSPATH.'.htaccess';
		return insert_with_markers($htaccess_file, 'WP BASE SECURITY', (array) $rules);
	}
	add_action('_wp_base_security_activate_hook','_wp_base_security_add_htaccess');
}

if (!function_exists('_wp_base_security_rewrite_rules')) {
	/**
	 * Rewrite rules
	 *
	 * @since 1.0.0
	 */
	function _wp_base_security_rewrite_rules() {

		require_once( ABSPATH . '/wp-admin/includes/misc.php' );

		$rules = array();
		$htaccess_file = ABSPATH.'.htaccess';
		$result = extract_from_markers( $htaccess_file, 'WP BASE SECURITY' );

		if (sizeof($result) > 0) {
			return insert_with_markers($htaccess_file, 'WP BASE SECURITY', (array) $rules);
			flush_rewrite_rules();
		}

	}
	add_action('_wp_base_security_deactivate_hook','_wp_base_security_rewrite_rules');
}
