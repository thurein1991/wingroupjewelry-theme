<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="wgj-page">

  <?php if (is_user_logged_in()): ?>
  <nav class="wgj-topbar">
    <div class="wgj-top-logo">
      <div class="wgj-diamond sm"></div>
      <div class="wgj-top-logo-text">
        WINGROUPJEWELRY LLC
        <small>Client Portal</small>
      </div>
    </div>
    <div class="wgj-top-right">
      <?php
        $current = wp_get_current_user();
        $fname   = $current->first_name ?: $current->display_name;
        $is_admin = current_user_can('manage_options');
      ?>
      <span class="wgj-top-user">Welcome, <strong><?php echo esc_html(explode(' ', $fname)[0]); ?></strong></span>
      <?php if ($is_admin): ?>
        <span class="wgj-badge-admin">Admin</span>
      <?php endif; ?>
      <a href="<?php echo wp_logout_url(home_url('/')); ?>" class="wgj-btn-logout">Sign Out</a>
    </div>
  </nav>
  <?php endif; ?>
