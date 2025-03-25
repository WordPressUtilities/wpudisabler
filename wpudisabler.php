<?php
defined('ABSPATH') || die;

/*
Plugin Name: WPU Disabler
Description: Disable WordPress features
Plugin URI: https://github.com/wordPressUtilities/wpudisabler
Update URI: https://github.com/wordPressUtilities/wpudisabler
Version: 0.7.1
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wpudisabler
Domain Path: /lang
Requires at least: 6.2
Requires PHP: 8.0
Network: Optional
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUDisabler {
    private $plugin_version = '0.7.1';
    private $plugin_description;
    private $settings_update;
    private $disable_wp_api_user_level;
    public function __construct() {
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
        add_action('init', array(&$this, 'load_translation'));
    }

    # TRANSLATION
    public function load_translation() {
        $lang_dir = dirname(plugin_basename(__FILE__)) . '/lang/';
        if (strpos(__DIR__, 'mu-plugins') !== false) {
            load_muplugin_textdomain('wpudisabler', $lang_dir);
        } else {
            load_plugin_textdomain('wpudisabler', false, $lang_dir);
        }
        $this->plugin_description = __('Disable WordPress features', 'wpudisabler');
    }

    public function plugins_loaded() {

        /* Base UPDATE */
        require_once __DIR__ . '/inc/WPUBaseUpdate/WPUBaseUpdate.php';
        $this->settings_update = new \wpudisabler\WPUBaseUpdate(
            'WordPressUtilities',
            'wpudisabler',
            $this->plugin_version);

        /* Filters */
        if (apply_filters('wpudisabler__disable_author_page', false)) {
            $this->disable_author_page();
        }
        if (apply_filters('wpudisabler__disable_feeds', false)) {
            $this->disable_feeds();
        }
        if (apply_filters('wpudisabler__disable_plugin_deactivation', false)) {
            $this->disable_plugin_deactivation();
        }

        $this->disable_wp_api_user_level = apply_filters('wpudisabler__disable_wp_api_user_level', 'remove_users');
        if (apply_filters('wpudisabler__disable_wp_api', false)) {
            $this->disable_wp_api();
        }
        if (apply_filters('wpudisabler__disable_wp_api__logged_in', false)) {
            $this->disable_wp_api__logged_in();
        }
        if (apply_filters('wpudisabler__disable_wp_oembed', false)) {
            $this->disable_wp_oembed();
        }
    }

    /* ----------------------------------------------------------
      Disable author page
    ---------------------------------------------------------- */

    public function disable_author_page() {
        add_filter('template_redirect', array(&$this, 'author_page'), 50);
        add_filter('author_link', array(&$this, 'get_the_author_url'), 50, 1);
        add_filter('get_the_author_url', array(&$this, 'get_the_author_url'), 50, 1);
        add_filter('wp_sitemaps_enabled', array(&$this, 'disable_users_sitemap__wp_sitemaps_enabled'), 10, 1);
        add_filter('wp_sitemaps_add_provider', array(&$this, 'disable_users_sitemap__wp_sitemaps_add_provider'), 10, 2);

    }

    public function author_page($template) {
        if (is_author()) {
            wp_redirect(site_url());
            die;
        }
        return $template;
    }

    public function get_the_author_url($url) {
        return home_url();
    }

    public function disable_users_sitemap__wp_sitemaps_enabled($is_enabled) {
        global $wp_query;
        if (is_object($wp_query) && isset($wp_query->query_vars['sitemap']) && $wp_query->query_vars['sitemap'] == 'users') {
            return false;
        }
        return $is_enabled;
    }

    public function disable_users_sitemap__wp_sitemaps_add_provider($provider, $name) {
        if ($name == 'users') {
            return false;
        }
        return $provider;
    }

    /* ----------------------------------------------------------
      Disable feeds
    ---------------------------------------------------------- */

    public function disable_feeds() {
        add_action('do_feed', array(&$this, 'disable_feed'), 1);
        add_action('do_feed_rdf', array(&$this, 'disable_feed'), 1);
        add_action('do_feed_rss', array(&$this, 'disable_feed'), 1);
        add_action('do_feed_rss2', array(&$this, 'disable_feed'), 1);
        add_action('do_feed_atom', array(&$this, 'disable_feed'), 1);
        add_action('do_feed_rss2_comments', array(&$this, 'disable_feed'), 1);
        add_action('do_feed_atom_comments', array(&$this, 'disable_feed'), 1);
        add_action('feed_links_show_posts_feed', '__return_false', 1);
        add_action('feed_links_show_comments_feed', '__return_false', 1);
        remove_action('wp_head', 'feed_links_extra', 3);
    }

    public function disable_feed() {
        wp_die(sprintf(__('No feed available, please visit our <a href="%s">homepage</a>!', 'wpudisabler'), get_bloginfo('url')));
    }

    /* ----------------------------------------------------------
      Disable plugin deactivation
    ---------------------------------------------------------- */

    public function disable_plugin_deactivation() {
        add_filter('plugin_action_links', array(&$this, 'disable_plugin_deactivation__action'));
    }

    public function disable_plugin_deactivation__action($links) {
        if (isset($links['deactivate'])) {
            unset($links['deactivate']);
        }
        if (isset($links['activate'])) {
            unset($links['activate']);
        }
        return $links;
    }

    /* ----------------------------------------------------------
      Disable WP API
    ---------------------------------------------------------- */

    public function disable_wp_api() {

        // XML RPC
        add_filter('xmlrpc_enabled', '__return_false');
        remove_action('wp_head', 'rsd_link');
        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');

        // WP-API version 1.x
        add_filter('json_enabled', '__return_false');
        add_filter('json_jsonp_enabled', '__return_false');

        // WP-API version 2.x
        add_filter('rest_enabled', '__return_false');
        add_filter('rest_jsonp_enabled', '__return_false');
        remove_action('auth_cookie_bad_hash', 'rest_cookie_collect_status');
        remove_action('auth_cookie_bad_username', 'rest_cookie_collect_status');
        remove_action('auth_cookie_expired', 'rest_cookie_collect_status');
        remove_action('auth_cookie_malformed', 'rest_cookie_collect_status');
        remove_action('auth_cookie_valid', 'rest_cookie_collect_status');
        remove_action('init', 'rest_api_init');
        remove_action('parse_request', 'rest_api_loaded');
        remove_action('template_redirect', 'rest_output_link_header', 11);
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        add_filter('rest_authentication_errors', array(&$this, 'rest_authentication_errors'));

    }

    public function rest_authentication_errors($access) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_cannot_access', __('Nope', 'wpudisabler'), array('status' => rest_authorization_required_code()));
        }

        return $access;
    }

    /* Only for logged in users
    -------------------------- */

    public function disable_wp_api__logged_in() {
        if (is_user_logged_in() && current_user_can($this->disable_wp_api_user_level)) {
            return;
        }
        add_action('wp', array(&$this, 'disable_wp_api__logged_in__wp_head'), 999);
        add_filter('rest_authentication_errors', array(&$this, 'disable_wp_api__logged_in__rest_authentication_errors'));
    }

    public function disable_wp_api__logged_in__rest_authentication_errors($result) {
        if (!empty($result)) {
            return $result;
        }

        $allowed_routes = apply_filters('wpudisabler__disable_wp_api__logged_in__allowed_routes', array());
        $current_route = $_SERVER['REQUEST_URI'];
        foreach ($allowed_routes as $allowed_route) {
            if (strpos($current_route, $allowed_route) !== false) {
                return $result;
            }
        }

        if (!is_user_logged_in()) {
            return new WP_Error('rest_not_logged_in', 'You are not currently logged in.', array(
                'status' => 401
            ));
        }
        if (!current_user_can($this->disable_wp_api_user_level)) {
            return new WP_Error('rest_not_admin', 'You are not an administrator.', array(
                'status' => 401
            ));
        }
        return $result;
    }

    public function disable_wp_api__logged_in__wp_head() {
        remove_action('template_redirect', 'rest_output_link_header', 11);
        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
    }

    /* ----------------------------------------------------------
      Disable WP Oembed
    ---------------------------------------------------------- */

    public function disable_wp_oembed() {

        /* Header items */
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        /* REST API route */
        remove_action('rest_api_init', 'wp_oembed_register_route');

    }

}

$WPUDisabler = new WPUDisabler();
