<?php
/**
 * WINGROUPJEWELRY Theme — functions.php
 */
defined('ABSPATH') || exit;

// ── Load includes ──────────────────────────────────────────────
require_once get_template_directory() . '/inc/cpt.php';
require_once get_template_directory() . '/inc/roles.php';
require_once get_template_directory() . '/inc/ajax.php';

// ── Theme setup ────────────────────────────────────────────────
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form','comment-form','gallery','caption']);
});

// ── Enqueue assets ─────────────────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    // Google Fonts
    wp_enqueue_style(
        'wgj-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400&family=Raleway:wght@200;300;400;500;600&display=swap',
        [], null
    );

    // Main portal CSS
    wp_enqueue_style(
        'wgj-portal',
        get_template_directory_uri() . '/assets/css/portal.css',
        ['wgj-fonts'],
        '1.0.0'
    );

    // Portal JS
    wp_enqueue_script(
        'wgj-portal',
        get_template_directory_uri() . '/assets/js/portal.js',
        [],
        '1.0.0',
        true  // load in footer
    );

    // Pass AJAX url + nonce to JS
    wp_localize_script('wgj-portal', 'WGJ', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('wgj_nonce'),
        'homeUrl' => home_url('/'),
    ]);
});

// ── Redirect: logged-in users away from wp-login/register ──────
add_action('init', function () {
    // After login, send clients to portal page, admins to wp-admin
    add_filter('login_redirect', function ($redirect, $request, $user) {
        if (isset($user->roles) && in_array('wgj_client', $user->roles)) {
            $portal = get_page_by_path('client-portal');
            return $portal ? get_permalink($portal->ID) : home_url('/');
        }
        return admin_url();
    }, 10, 3);
});

// ── Redirect: non-logged-in users away from portal page ────────
add_action('template_redirect', function () {
    if (is_page('client-portal') && !is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
});

// ── Helper: get projects for a user ────────────────────────────
function wgj_get_projects($user_id = null) {
    $args = [
        'post_type'      => 'wgj_project',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];
    if ($user_id) {
        $args['meta_query'] = [['key' => '_wgj_client_id', 'value' => $user_id]];
    }
    return get_posts($args);
}

// ── Helper: format date ─────────────────────────────────────────
function wgj_fd($date_str) {
    if (!$date_str) return '—';
    $d = DateTime::createFromFormat('Y-m-d', $date_str);
    return $d ? $d->format('M j, Y') : $date_str;
}

// ── Helper: stage labels ────────────────────────────────────────
function wgj_stages() {
    return ['Consultation', 'Design', 'Crafting', 'Quality Check', 'Ready'];
}
