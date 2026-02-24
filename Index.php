<?php
/**
 * Fallback index — redirects to the portal page.
 */
defined('ABSPATH') || exit;

$portal = get_page_by_path('client-portal');
if ($portal) {
    wp_redirect(get_permalink($portal->ID));
} else {
    wp_redirect(wp_login_url());
}
exit;
