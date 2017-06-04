<?php

/*
Plugin Name: WPU Disabler
Description: Disable WordPress features
Version: 0.2.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUDisabler {
    public function __construct() {
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
    }

    public function plugins_loaded() {

        load_plugin_textdomain('wpudisabler', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        if (apply_filters('wpudisabler__disable_author_page', false)) {
            $this->disable_author_page();
        }
        if (apply_filters('wpudisabler__disable_feeds', false)) {
            $this->disable_feeds();
        }
        if (apply_filters('wpudisabler__disable_plugin_deactivation', false)) {
            $this->disable_plugin_deactivation();
        }
        if (apply_filters('wpudisabler__disable_wp_api', false)) {
            $this->disable_wp_api();
        }
    }

    /* ----------------------------------------------------------
      Disable author page
    ---------------------------------------------------------- */

    public function disable_author_page() {
        add_filter('template_redirect', array(&$this, 'author_page'), 50);
        add_filter('author_link', array(&$this, 'get_the_author_url'), 50, 1);
        add_filter('get_the_author_url', array(&$this, 'get_the_author_url'), 50, 1);
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

        // WP-API version 1.x
        add_filter('json_enabled', '__return_false');
        add_filter('json_jsonp_enabled', '__return_false');

        // WP-API version 2.x
        add_filter('rest_enabled', '__return_false');
        add_filter('rest_jsonp_enabled', '__return_false');
    }

}

$WPUDisabler = new WPUDisabler();
