<?php

declare ( strict_types = 1 );
/**
 * Freemius integration.
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace com\wp_fail2ban\addons\Blocklist\Freemius;

// @codeCoverageIgnoreStart
defined( 'ABSPATH' ) or exit;
if ( !function_exists( __NAMESPACE__ . '\\wpf2b_addon_blocklist_fs' ) ) {
    /**
     * Create a helper function for easy SDK access.
     *
     * @since   1.0.0
     *
     * @return  \Freemius|null
     */
    function wpf2b_addon_blocklist_fs() : ?\Freemius
    {
        global  $wpf2b_addon_blocklist_fs ;
        
        if ( !isset( $wpf2b_addon_blocklist_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_6750_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_6750_MULTISITE', true );
            }
            // Include Freemius SDK.
            
            if ( file_exists( dirname( __DIR__ ) . '/wp-fail2ban/vendor/freemius/wordpress-sdk/start.php' ) ) {
                // Try to load SDK from parent plugin folder.
                require_once dirname( __DIR__ ) . '/wp-fail2ban/vendor/freemius/wordpress-sdk/start.php';
            } elseif ( file_exists( dirname( __DIR__ ) . '/wp-fail2ban-premium/vendor/freemius/wordpress-sdk/start.php' ) ) {
                // Try to load SDK from premium parent plugin folder.
                require_once dirname( __DIR__ ) . '/wp-fail2ban-premium/vendor/freemius/wordpress-sdk/start.php';
            } else {
                return null;
            }
            
            $wpf2b_addon_blocklist_fs = fs_dynamic_init( array(
                'id'               => '6750',
                'slug'             => 'wpf2b-addon-blocklist',
                'premium_slug'     => 'wp-fail2ban-addon-blocklist',
                'type'             => 'plugin',
                'public_key'       => 'pk_02a7118d5d6b8dddbf72b18fb0f19',
                'is_premium'       => false,
                'has_paid_plans'   => true,
                'trial'            => array(
                'days'               => 14,
                'is_require_payment' => false,
            ),
                'parent'           => array(
                'id'         => '3072',
                'slug'       => 'wp-fail2ban',
                'public_key' => 'pk_146d2c2a5bee3b157e43501ef8682',
                'name'       => 'WP fail2ban',
            ),
                'menu'             => array(
                'slug'    => 'wp-fail2ban_addon_blocklist',
                'support' => false,
                'parent'  => array(
                'slug' => 'wp-fail2ban-menu',
            ),
            ),
                'enable_anonymous' => false,
                'is_live'          => true,
            ) );
        }
        
        return $wpf2b_addon_blocklist_fs;
    }

}
/**
 * Freemius boilerplate
 *
 * @since   1.0.0
 *
 * @return  bool
 */
function wpf2b_addon_blocklist_fs_is_parent_active_and_loaded() : bool
{
    // Check if the parent's init SDK method exists.
    return function_exists( 'org\\lecklider\\charles\\wordpress\\wp_fail2ban\\wf_fs' );
}

/**
 * Freemius boilerplate
 *
 * @since   1.0.0
 *
 * @return  bool
 */
function wpf2b_addon_blocklist_fs_is_parent_active() : bool
{
    $active_plugins = get_option( 'active_plugins', [] );
    
    if ( is_multisite() ) {
        $network_active_plugins = get_site_option( 'active_sitewide_plugins', [] );
        $active_plugins = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
    }
    
    foreach ( $active_plugins as $basename ) {
        if ( 0 === strpos( $basename, 'wp-fail2ban/' ) || 0 === strpos( $basename, 'wp-fail2ban-premium/' ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Freemius init
 *
 * @since   1.0.0
 *
 * @return  void
 */
function wpf2b_addon_blocklist_fs_init() : void
{
    
    if ( wpf2b_addon_blocklist_fs_is_parent_active_and_loaded() ) {
        global  $wf_fs ;
        // Set the permissions on the parent
        $wf_fs->add_filter( 'permission_list', function ( $permissions ) {
            global  $wf_fs ;
            $permissions['wpf2b-blocklist'] = [
                'icon-class' => 'dashicons dashicons-share',
                'label'      => $wf_fs->get_text_inline( 'Share WP fail2ban Events', 'wpf2b-blocklist' ),
                'desc'       => $wf_fs->get_text_inline( 'Sharing events is what makes the Blocklist work', 'permissions-wpf2b-blocklist' ),
                'priority'   => 16,
            ];
            return $permissions;
        } );
        // Init Freemius.
        
        if ( null === ($fs = wpf2b_addon_blocklist_fs()) ) {
            // parent lives somewhere weird
            // TODO: nice error message
        } else {
            $fs->add_filter( 'default_currency', function () {
                return 'gbp';
            } );
            // Set custom icon
            $fs->add_filter( 'plugin_icon', function () {
                return __DIR__ . '/assets/icon.png';
            } );
            $fs->add_filter( 'show_delegation_option', '__return_false' );
            $fs->add_filter( 'enable_per_site_activation', '__return_false' );
            $fs->add_filter( 'redirect_on_activation', function ( $true ) {
                assert( $true );
                fs_redirect( network_admin_url( 'admin.php?page=wp-fail2ban_addon_blocklist' ) );
            } );
            // Signal that the add-on's SDK was initiated.
            do_action( 'wpf2b_addon_blocklist_fs_loaded' );
            require_once __DIR__ . '/functions.php';
            require_once __DIR__ . '/init.php';
            if ( is_admin() ) {
                require_once __DIR__ . '/admin/admin.php';
            }
            /**
             * @since 2.1.0 Add our tests to Site Health
             */
            add_filter( 'site_status_tests', WP_FAIL2BAN_ADDON_BLOCKLIST_NS . '\\SiteHealth::get_tests' );
            // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing
            
            if ( is_multisite() && !is_main_site() ) {
                add_action( 'rest_api_init', WP_FAIL2BAN_ADDON_BLOCKLIST_NS . '\\rest_api_init_teapot' );
            } elseif ( $fs->can_use_premium_code() || $fs->is_registered() && $fs->is_tracking_allowed() ) {
                add_action( 'plugins_loaded', WP_FAIL2BAN_ADDON_BLOCKLIST_NS . '\\plugins_loaded' );
                add_action( 'init', WP_FAIL2BAN_ADDON_BLOCKLIST_NS . '\\init' );
            } else {
                // noop
            }
            
            // phpcs:enable
        }
    
    } else {
        // Parent is inactive, add your error handling here.
    }

}


if ( wpf2b_addon_blocklist_fs_is_parent_active_and_loaded() ) {
    // If parent already included, init add-on.
    wpf2b_addon_blocklist_fs_init();
} elseif ( wpf2b_addon_blocklist_fs_is_parent_active() ) {
    // Init add-on only after the parent is loaded.
    add_action( 'wf_fs_loaded', __NAMESPACE__ . '\\wpf2b_addon_blocklist_fs_init' );
} else {
    // Even though the parent is not activated, execute add-on for activation / uninstall hooks.
    wpf2b_addon_blocklist_fs_init();
}
