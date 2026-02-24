<?php
/**
 * Template Name: Client Portal
 *
 * Assign this template to a Page titled "Client Portal"
 * with slug "client-portal" in WP Admin → Pages.
 */
defined('ABSPATH') || exit;

get_header();

$current  = wp_get_current_user();
$is_admin = current_user_can('manage_options');
$stages   = wgj_stages();
?>

<main class="wgj-main">

<?php if ($is_admin): ?>
  <?php /* ══════════════════ ADMIN VIEW ══════════════════ */ ?>

  <?php
    $all_projects = wgj_get_projects();
    $clients      = get_users(['role' => 'wgj_client', 'orderby' => 'display_name']);

    $collected = 0; $outstanding = 0; $in_prog = 0;
    foreach ($all_projects as $p) {
        $dp = get_post_meta($p->ID, '_wgj_deposit_paid', true);
        $fp = get_post_meta($p->ID, '_wgj_final_paid', true);
        $tc = (float) get_post_meta($p->ID, '_wgj_total_cost', true);
        $da = (float) get_post_meta($p->ID, '_wgj_deposit_amt', true);
        $st = (int)   get_post_meta($p->ID, '_wgj_stage', true);
        if ($dp) $collected += $da;
        if ($fp) $collected += ($tc - $da);
        else {
            if (!$dp) $outstanding += $da;
            $outstanding += ($tc - ($dp ? $da : 0));
        }
        if ($st < 4) $in_prog++;
    }
  ?>

  <div class="wgj-page-header">
    <h2>Admin <em>Dashboard</em></h2>
    <p>WINGROUPJEWELRY LLC — Manage all client projects and payment records.</p>
  </div>

  <div class="wgj-kpi-row">
    <div class="wgj-kpi" style="--kc:var(--wgj-gold)">
      <div class="wgj-kpi-label">Clients</div>
      <div class="wgj-kpi-value"><?php echo count($clients); ?></div>
    </div>
    <div class="wgj-kpi" style="--kc:var(--wgj-blue)">
      <div class="wgj-kpi-label">Active Projects</div>
      <div class="wgj-kpi-value"><?php echo $in_prog; ?></div>
      <div class="wgj-kpi-sub">of <?php echo count($all_projects); ?> total</div>
    </div>
    <div class="wgj-kpi" style="--kc:var(--wgj-green)">
      <div class="wgj-kpi-label">Revenue Collected</div>
      <div class="wgj-kpi-value">$<?php echo number_format($collected, 0); ?></div>
    </div>
    <div class="wgj-kpi" style="--kc:var(--wgj-orange)">
      <div class="wgj-kpi-label">Outstanding</div>
      <div class="wgj-kpi-value">$<?php echo number_format($outstanding, 0); ?></div>
    </div>
  </div>

  <!-- ADD PROJECT -->
  <div class="wgj-admin-zone">
    <div class="wgj-sec-title" style="color:var(--wgj-gold)">Add New Project</div>
    <div class="wgj-form-grid">
      <div class="wgj-field">
        <label>Client</label>
        <select id="nClient">
          <?php foreach ($clients as $c): ?>
            <option value="<?php echo $c->ID; ?>"><?php echo esc_html($c->display_name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="wgj-field"><label>Project Name</label><input type="text" id="nName" placeholder="e.g. Custom Engagement Ring"></div>
      <div class="wgj-field wgj-span2"><label>Description</label><input type="text" id="nDesc" placeholder="Metal, stone, style, dimensions…"></div>
      <div class="wgj-field"><label>Total Cost ($)</label><input type="number" id="nTotal" placeholder="0.00"></div>
      <div class="wgj-field"><label>Deposit Amount ($)</label><input type="number" id="nDeposit" placeholder="0.00"></div>
      <div class="wgj-field"><label>Est. Completion</label><input type="date" id="nDate"></div>
      <div class="wgj-field">
        <label>Initial Stage</label>
        <select id="nStage">
          <?php foreach ($stages as $i => $s): ?>
            <option value="<?php echo $i; ?>"><?php echo $s; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="wgj-field"><label>Notes (optional)</label><input type="text" id="nNotes" placeholder="Note visible to client"></div>
    </div>
    <button class="wgj-btn-add" onclick="wgjAddProject()">+ Add Project</button>
  </div>

  <!-- ALL PROJECTS -->
  <div class="wgj-sec-title">All Projects</div>

  <?php if (empty($all_projects)): ?>
    <div class="wgj-empty"><div class="wgj-empty-icon">📋</div><p>No projects yet. Add one above.</p></div>
  <?php else: foreach ($all_projects as $p):
    $client_id = get_post_meta($p->ID, '_wgj_client_id', true);
    $client    = $client_id ? get_userdata($client_id) : null;
    $stage     = (int)   get_post_meta($p->ID, '_wgj_stage', true);
    $tc        = (float) get_post_meta($p->ID, '_wgj_total_cost', true);
    $da        = (float) get_post_meta($p->ID, '_wgj_deposit_amt', true);
    $dp        = get_post_meta($p->ID, '_wgj_deposit_paid', true);
    $fp        = get_post_meta($p->ID, '_wgj_final_paid', true);
    $dd        = get_post_meta($p->ID, '_wgj_deposit_date', true);
    $fd2       = get_post_meta($p->ID, '_wgj_final_date', true);
    $bal       = $fp ? 0 : ($tc - ($dp ? $da : 0));
  ?>
  <div class="wgj-admin-proj-card" id="apc-<?php echo $p->ID; ?>">
    <div>
      <div class="wgj-admin-client-tag">Client: <?php echo $client ? esc_html($client->display_name) : 'Unknown'; ?></div>
      <div class="wgj-proj-title"><?php echo esc_html($p->post_title); ?></div>
      <div class="wgj-proj-desc"><?php echo esc_html(get_post_meta($p->ID, '_wgj_description', true)); ?></div>

      <?php wgj_stage_tracker($stage, $stages); ?>

      <div style="max-width:240px;margin-top:.75rem">
        <div class="wgj-field">
          <label>Update Stage</label>
          <select onchange="wgjUpdate(<?php echo $p->ID; ?>,'_wgj_stage',this.value)">
            <?php foreach ($stages as $i => $s): ?>
              <option value="<?php echo $i; ?>" <?php selected($stage, $i); ?>><?php echo $s; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="wgj-pay-block" style="max-width:400px">
        <div class="wgj-pay-line"><span class="wgj-pay-lbl">Project Total</span><span class="wgj-pay-val">$<?php echo number_format($tc, 0); ?></span></div>
        <div class="wgj-pay-line">
          <span class="wgj-pay-lbl">Deposit ($<?php echo number_format($da, 0); ?>)</span>
          <label class="wgj-toggle-row">
            <input type="checkbox" <?php checked($dp, '1'); ?> onchange="wgjUpdate(<?php echo $p->ID; ?>,'_wgj_deposit_paid',this.checked?1:0)">
            <span style="color:<?php echo $dp ? 'var(--wgj-green)' : 'var(--wgj-muted)'; ?>">
              <?php echo $dp ? 'Paid — ' . wgj_fd($dd) : 'Mark as Paid'; ?>
            </span>
          </label>
        </div>
        <div class="wgj-pay-line">
          <span class="wgj-pay-lbl">Final Payment</span>
          <label class="wgj-toggle-row">
            <input type="checkbox" <?php checked($fp, '1'); ?> onchange="wgjUpdate(<?php echo $p->ID; ?>,'_wgj_final_paid',this.checked?1:0)">
            <span style="color:<?php echo $fp ? 'var(--wgj-green)' : 'var(--wgj-muted)'; ?>">
              <?php echo $fp ? 'Paid in Full — ' . wgj_fd($fd2) : 'Mark as Fully Paid'; ?>
            </span>
          </label>
        </div>
        <div class="wgj-pay-line wgj-subtotal">
          <span class="wgj-pay-lbl">Remaining Balance</span>
          <span class="wgj-pay-val">$<?php echo number_format($bal, 0); ?></span>
        </div>
      </div>
    </div>

    <div class="wgj-proj-side">
      <?php wgj_stage_badge($stage); ?>
      <?php wgj_pay_badge_obj((object)['deposit_paid'=>$dp,'final_paid'=>$fp]); ?>
      <button class="wgj-btn-del" onclick="wgjDelete(<?php echo $p->ID; ?>)">Delete</button>
    </div>
  </div>
  <?php endforeach; endif; ?>

  <!-- CLIENTS TABLE -->
  <div class="wgj-divider"></div>
  <div class="wgj-sec-title">Registered Clients</div>
  <div style="overflow-x:auto">
  <table class="wgj-tbl">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Projects</th><th>Total Value</th><th>Collected</th><th>Outstanding</th></tr></thead>
    <tbody>
    <?php foreach ($clients as $c):
      $cp  = wgj_get_projects($c->ID);
      $tv  = 0; $col = 0;
      foreach ($cp as $p) {
          $tc2 = (float) get_post_meta($p->ID, '_wgj_total_cost', true);
          $da2 = (float) get_post_meta($p->ID, '_wgj_deposit_amt', true);
          $dp2 = get_post_meta($p->ID, '_wgj_deposit_paid', true);
          $fp2 = get_post_meta($p->ID, '_wgj_final_paid', true);
          $tv += $tc2;
          if ($dp2) $col += $da2;
          if ($fp2) $col += ($tc2 - $da2);
      }
      $out = $tv - $col;
      $phone = get_user_meta($c->ID, 'wgj_phone', true);
    ?>
    <tr>
      <td style="color:var(--wgj-gold-light)"><?php echo esc_html($c->display_name); ?></td>
      <td><?php echo esc_html($c->user_email); ?></td>
      <td><?php echo esc_html($phone ?: '—'); ?></td>
      <td><?php echo count($cp); ?></td>
      <td>$<?php echo number_format($tv, 0); ?></td>
      <td style="color:var(--wgj-green)">$<?php echo number_format($col, 0); ?></td>
      <td style="color:<?php echo $out > 0 ? 'var(--wgj-orange)' : 'var(--wgj-muted)'; ?>">$<?php echo number_format($out, 0); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

<?php else: ?>
  <?php /* ══════════════════ CLIENT VIEW ══════════════════ */ ?>

  <?php
    $my_projects = wgj_get_projects($current->ID);
    $total_paid  = 0; $bal_due = 0;
    $in_prog = 0; $done = 0;
    foreach ($my_projects as $p) {
        $tc = (float) get_post_meta($p->ID, '_wgj_total_cost', true);
        $da = (float) get_post_meta($p->ID, '_wgj_deposit_amt', true);
        $dp = get_post_meta($p->ID, '_wgj_deposit_paid', true);
        $fp = get_post_meta($p->ID, '_wgj_final_paid', true);
        $st = (int)   get_post_meta($p->ID, '_wgj_stage', true);
        if ($dp) $total_paid += $da;
        if ($fp) $total_paid += ($tc - $da);
        if (!$fp) { if (!$dp) $bal_due += $da; $bal_due += ($tc - ($dp?$da:0)); }
        $st < 4 ? $in_prog++ : $done++;
    }
  ?>

  <div class="wgj-page-header">
    <h2>My <em>Projects</em></h2>
    <p>Track your custom jewelry from first consultation to final delivery.</p>
  </div>

  <div class="wgj-kpi-row">
    <div class="wgj-kpi" style="--kc:var(--wgj-gold)">
      <div class="wgj-kpi-label">My Orders</div>
      <div class="wgj-kpi-value"><?php echo count($my_projects); ?></div>
      <div class="wgj-kpi-sub"><?php echo $in_prog; ?> in progress · <?php echo $done; ?> complete</div>
    </div>
    <div class="wgj-kpi" style="--kc:var(--wgj-green)">
      <div class="wgj-kpi-label">Total Paid</div>
      <div class="wgj-kpi-value">$<?php echo number_format($total_paid, 0); ?></div>
    </div>
    <div class="wgj-kpi" style="--kc:var(--wgj-orange)">
      <div class="wgj-kpi-label">Balance Due</div>
      <div class="wgj-kpi-value">$<?php echo number_format($bal_due, 0); ?></div>
    </div>
  </div>

  <div class="wgj-sec-title">Your Custom Pieces</div>

  <?php if (empty($my_projects)): ?>
    <div class="wgj-empty"><div class="wgj-empty-icon">💍</div><p>No projects yet — your jeweler will add them here.</p></div>
  <?php else: foreach ($my_projects as $p):
    $stage = (int)   get_post_meta($p->ID, '_wgj_stage', true);
    $tc    = (float) get_post_meta($p->ID, '_wgj_total_cost', true);
    $da    = (float) get_post_meta($p->ID, '_wgj_deposit_amt', true);
    $dp    = get_post_meta($p->ID, '_wgj_deposit_paid', true);
    $fp    = get_post_meta($p->ID, '_wgj_final_paid', true);
    $dd    = get_post_meta($p->ID, '_wgj_deposit_date', true);
    $fd2   = get_post_meta($p->ID, '_wgj_final_date', true);
    $ec    = get_post_meta($p->ID, '_wgj_est_complete', true);
    $dc    = get_post_meta($p->ID, '_wgj_date_created', true);
    $notes = get_post_meta($p->ID, '_wgj_notes', true);
    $rem   = $tc - ($dp ? $da : 0);
  ?>
  <div class="wgj-proj-wrap">
  <div class="wgj-proj-card">
    <div>
      <div class="wgj-proj-order">Order #WGJ<?php echo str_pad($p->ID, 4, '0', STR_PAD_LEFT); ?></div>
      <div class="wgj-proj-title"><?php echo esc_html($p->post_title); ?></div>
      <div class="wgj-proj-desc"><?php echo esc_html(get_post_meta($p->ID, '_wgj_description', true)); ?></div>

      <?php wgj_stage_tracker($stage, $stages); ?>

      <div class="wgj-proj-meta">
        <div><div class="wgj-mi-label">Date Started</div><div class="wgj-mi-value"><?php echo wgj_fd($dc); ?></div></div>
        <div><div class="wgj-mi-label">Est. Completion</div><div class="wgj-mi-value"><?php echo wgj_fd($ec); ?></div></div>
        <?php if ($notes): ?>
          <div><div class="wgj-mi-label">Jeweler's Note</div><div class="wgj-mi-value" style="color:var(--wgj-muted);font-style:italic;max-width:360px"><?php echo esc_html($notes); ?></div></div>
        <?php endif; ?>
      </div>

      <div class="wgj-pay-block">
        <div class="wgj-pay-line"><span class="wgj-pay-lbl">Project Total</span><span class="wgj-pay-val">$<?php echo number_format($tc, 0); ?></span></div>
        <div class="wgj-pay-line"><span class="wgj-pay-lbl">Deposit Required</span><span class="wgj-pay-val">$<?php echo number_format($da, 0); ?></span></div>
        <div class="wgj-pay-line">
          <span class="wgj-pay-lbl">Deposit Status</span>
          <span style="color:<?php echo $dp ? 'var(--wgj-green)' : 'var(--wgj-red)'; ?>;font-size:.82rem">
            <?php echo $dp ? '✓ Paid on ' . wgj_fd($dd) : '⚠ Not Yet Paid'; ?>
          </span>
        </div>
        <div class="wgj-pay-line wgj-subtotal">
          <span class="wgj-pay-lbl">Remaining Balance</span>
          <span class="wgj-pay-val">$<?php echo number_format($fp ? 0 : $rem, 0); ?></span>
        </div>
        <?php if ($fp): ?>
          <div class="wgj-paid-full">✓ Paid in Full — <?php echo wgj_fd($fd2); ?></div>
        <?php elseif ($stage >= 4): ?>
          <div class="wgj-due-banner">Balance of $<?php echo number_format($rem, 0); ?> due upon pickup</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="wgj-proj-side">
      <?php wgj_stage_badge($stage); ?>
      <?php wgj_pay_badge_obj((object)['deposit_paid'=>$dp,'final_paid'=>$fp]); ?>
    </div>
  </div>
  </div>
  <?php endforeach; endif; ?>

<?php endif; ?>
</main>

<?php get_footer(); ?>

<?php
// ── Template helper functions ──────────────────────────────────

function wgj_stage_tracker($stage, $stages) {
    echo '<div class="wgj-tracker"><div class="wgj-track-bar">';
    foreach ($stages as $i => $s) {
        $cls = $i < $stage ? 'done' : ($i === $stage ? 'active' : '');
        echo "<div class='wgj-track-seg $cls'></div>";
    }
    echo '</div><div class="wgj-track-labels">';
    foreach ($stages as $i => $s) {
        $cls = $i < $stage ? 'done' : ($i === $stage ? 'active' : '');
        echo "<div class='wgj-track-lbl $cls'>" . esc_html($s) . "</div>";
    }
    echo '</div></div>';
}

function wgj_stage_badge($stage) {
    $labels = ['In Consultation','In Design','Being Crafted','Quality Check','Ready'];
    $cls    = ['b-blue','b-orange','b-orange','b-gold','b-green'];
    $lbl    = $labels[$stage] ?? '—';
    $c      = $cls[$stage]    ?? 'b-gold';
    echo "<span class='wgj-badge wgj-{$c}'>{$lbl}</span>";
}

function wgj_pay_badge_obj($p) {
    if ($p->final_paid)        echo "<span class='wgj-badge wgj-b-green'>Paid in Full</span>";
    elseif (!$p->deposit_paid) echo "<span class='wgj-badge wgj-b-red'>Deposit Due</span>";
    else                       echo "<span class='wgj-badge wgj-b-orange'>Balance Pending</span>";
}
