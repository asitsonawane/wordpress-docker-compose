<?php declare(strict_types=1);
/**
 * Admin - splash
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use function org\lecklider\charles\wordpress\wp_fail2ban\{
    logo_box,
    readme
};

defined('ABSPATH') or exit;

/**
 * Admin page
 *
 * @since   1.0.0
 *
 * @return  void
 */
function splash(): void
{
    $utm = '?utm_source=addon-blocklist&utm_medium=about&utm_campaign='.WP_FAIL2BAN_ADDON_BLOCKLIST_VER;

    $logo_box = [
        'title' => 'WP fail2ban Blocklist',
        'logo'  => plugins_url('assets/icon.png', WP_FAIL2BAN_ADDON_BLOCKLIST_FILE),
        'links' => [
            'Blog'      => "https://addons.wp-fail2ban.com/blog/{$utm}",
            'Guide'     => "https://life-with.wp-fail2ban.com/add-ons/blocklist/{$utm}",
            'Reference' => "https://docs.wp-fail2ban.com/projects/wp-fail2ban-blocklist/{$utm}",
            'Support'   => "https://forums.invis.net/c/wp-fail2ban-blocklist/support/{$utm}"
        ]
    ];
    ?>
<div class="wrap" id="wp-fail2ban">
  <div id="poststuff">
    <div id="post-body" class="metabox-holder columns-2">
      <div id="post-body-content">
        <div class="meta-box-sortables ui-sortable">
          <div class="postbox" id="1-0-0">
            <h2>Version <?=WP_FAIL2BAN_ADDON_BLOCKLIST_VER?></h2>
            <div class="inside">
              <section>
        <?php readme(WP_FAIL2BAN_ADDON_BLOCKLIST_VER_SHORT, WP_FAIL2BAN_ADDON_BLOCKLIST_DIR.'/readme.txt'); ?>
              </section>
            </div>
          </div>
        </div>
      </div>
      <div id="postbox-container-1" class="postbox-container">
        <div class="meta-box-sortables">
          <?php logo_box($logo_box); ?>
          <div class="postbox status">
            <div class="inside">
              <h3>Status</h3>
              <?=get_extra_spash_info()?>
            </div>
          </div>
        </div>
      </div>
    </div>
    &nbsp;
  </div>
</div>
    <?php
}

