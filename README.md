# WPU Disabler
Disable WordPress features

[![PHP workflow](https://github.com/WordPressUtilities/wpudisabler/actions/workflows/php.yml/badge.svg 'PHP workflow')](https://github.com/WordPressUtilities/wpudisabler/actions)

Filter list :
---

```php

/* Prevent plugin deactivation */
add_filter('wpudisabler__disable_plugin_deactivation', '__return_true')

/* Disable WordPress API */
add_filter('wpudisabler__disable_wp_api', '__return_true')

/* Disable WordPress API for non logged-in users*/
add_filter('wpudisabler__disable_wp_api__logged_in', '__return_true')

/* Disable oembed metas */
add_filter('wpudisabler__disable_wp_oembed', '__return_true')

/* Disable the author page  */
add_filter('wpudisabler__disable_author_page', '__return_true');

/* Disable feeds */
add_filter('wpudisabler__disable_feeds', '__return_true');

```
