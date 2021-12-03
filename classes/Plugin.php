<?php
namespace RFS_ACF_SYNC_NOTICE;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'Plugin' ) ) {

  class Plugin {
    private $acf_active = false;

    private $acf_post_type = 'acf-field-group';

    private $is_acf_page = false;

    private $acf_page_url = '';

    private $sync_page_url = '';

    private $group_has_sync = false;

    private $sync_data = array();

    private $files = array();

    private $mode = 'auto';

    private $auto_sync_mode = 'admin';

    private $do_auto_sync = false;

    private $post_type = '';

    private $post_id = false;

    public function __construct() {
      $this->acf_active = $this->is_acf_active();

      add_action( 'admin_notices', array( $this, 'acf_plugin_required_notice' ) );

      if ( !$this->acf_active ) {
          return;
      }

      add_action( 'init', array( $this, 'check_for_updates' ) );

      add_action( 'admin_init', array( $this, 'setup' ) );

      add_action( 'plugins_loaded', array( $this, 'i18n' ) );

      add_action( 'admin_notices', array( $this, 'acf_sync_notice' ) );

      add_action( 'admin_enqueue_scripts', array( $this, 'load_plugin_scripts' ) );

      add_action( 'wp_ajax_rfs_acf_auto_sync', array( $this, 'ajax_auto_sync' ) );
    }

    /**
     * This function will display notice if ACF PRO plugin is not activated on the site
     * @param n/a
     * @return n/a
     */
    public function acf_plugin_required_notice() {
      if ( $this->acf_active === false ) {
        $plugin = plugin_basename( RFS_ACF_SYNC_NOTICE_FILE );

        printf(
          '<div class="error">
            <p>%s</p>
          </div>',
          __( 'ACF JSON Sync Notice plugin requires active Advanced Custom Fields PRO plugin to work.', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN )
        );

        if ( is_plugin_active( $plugin ) ) {
          deactivate_plugins( plugin_basename( RFS_ACF_SYNC_NOTICE_FILE ) );
        }
      } else {
        $this->acf_active = true;
      }
    }

    /**
     * This function will check for plugin updates
     * @param n/a
     * @return n/a
     */
    public function check_for_updates() {
      if ( !is_admin() || wp_doing_cron() ) {
        return;
      }

      $github_username    = get_option( 'rfs-acf-sync-notice-settings-username' );
      $github_repository  = get_option( 'rfs-acf-sync-notice-settings-repository' );
      $github_acces_token = get_option( 'rfs-acf-sync-notice-settings-token' );

      if ( $github_username && $github_repository && $github_acces_token ) {
        include_once( RFS_ACF_SYNC_NOTICE_DIR . '/classes/Updater.php' );

        $updater = new \RFS_ACF_SYNC_NOTICE\Updater( RFS_ACF_SYNC_NOTICE_FILE );
        $updater->set_username( $github_username );
        $updater->set_repository( $github_repository );
        $updater->authorize( $github_acces_token );
        $updater->initialize();
      }
    }

    /**
     * This function will setup plugin vars
     * @param n/a
     * @return n/a
     */
    public function setup() {
      if ( wp_doing_ajax() || wp_doing_cron() ) {
        return;
      }

      global $pagenow;

      if ( $pagenow == 'edit.php' || $pagenow == 'post.php' ) {
        if ( isset( $_GET[ 'post_type' ] ) ) {
          $this->post_type = sanitize_text_field( $_GET[ 'post_type' ] );
        } else if ( isset( $_GET[ 'post' ] ) ) {
          $this->post_id   = absint( $_GET[ 'post' ] );
          $this->post_type = get_post_type( $this->post_id );
        }
      }

      $this->is_acf_page = $this->post_type == $this->acf_post_type;

      $this->auto_sync_mode = get_option( 'rfs-acf-sync-notice-settings-auto-mode', 'admin' );
      $this->mode           = get_option( 'rfs-acf-sync-notice-settings-mode', 'auto' );

      if ( !$this->is_acf_page ) {
        // Make plugin load in whole admin area
        $this->is_acf_page = $this->mode == 'auto' && $this->auto_sync_mode == 'admin';
      } else {
        // make plugin work in acf mode to show acf notice after sync has completed
        $this->auto_sync_mode = 'acf';
      }

      if ( $this->is_acf_page ) {
        $this->sync_page_url = add_query_arg( array(
          'post_type' => 'acf-field-group',
          'post_status' => 'sync'
        ), admin_url( 'edit.php' ) );

        $this->acf_page_url = add_query_arg( array(
          'post_type' => 'acf-field-group'
        ), admin_url( 'edit.php' ) );

        // Review local json field groups.
        if ( $this->files = acf_get_local_json_files() ) {
          add_filter( 'acf/load_field_groups', array( $this, 'acf_apply_get_local_field_groups' ), 20, 1 );

          // Get all groups in a single cached query to check if sync is available.
          $all_field_groups = acf_get_field_groups();

          foreach ( $all_field_groups as $field_group ) {
            // Extract vars.
            $local    = acf_maybe_get( $field_group, 'local' );
            $modified = acf_maybe_get( $field_group, 'modified' );
            $private  = acf_maybe_get( $field_group, 'private' );

            // Ignore if is private.
            if ( $private ) {
              continue;

              // Ignore not local "json".
            } elseif ( $local !== 'json' ) {
              continue;

              // Append to sync if not yet in database.
            } elseif ( !$field_group[ 'ID' ] ) {
              $this->sync_data[ $field_group[ 'key' ] ] = $field_group;

              // Append to sync if "json" modified time is newer than database.
            } elseif ( $modified && $modified > get_post_modified_time( 'U', true, $field_group[ 'ID' ] ) ) {
              $this->sync_data[ $field_group[ 'key' ] ] = $field_group;
            }
          }

          if ( !empty( $this->sync_data ) ) {
            if ( $this->mode == 'notice' ) {
              if ( $this->post_id ) {
                $this->group_has_sync = array_search( $this->post_id, array_column( $this->sync_data, 'ID' ) ) !== false;
              }
            } else {
              $this->do_auto_sync = true;
            }
          }
        }
      }
    }

    /**
     * This function will load plugin's translated strings
     * @param n/a
     * @return n/a
     */
    public function i18n() {
      // Load user's custom translations from wp-content/languages/ folder
      load_textdomain( RFS_ACF_SYNC_NOTICE_TEXTDOMAIN, sprintf(
        '%s/%s-%s.mo',
        WP_LANG_DIR,
        RFS_ACF_SYNC_NOTICE_SLUG,
        get_locale()
      ) );

      // Load plugin's available translations
      load_plugin_textdomain( RFS_ACF_SYNC_NOTICE_TEXTDOMAIN, false, sprintf(
        '%s/languages/',
        RFS_ACF_SYNC_NOTICE_SLUG
      ) );
    }

    /**
     * This function will load plugin js and css scripts
     * @param n/a
     * @return n/a
     */
    public function load_plugin_scripts() {
      if ( $this->is_acf_page ) {
        wp_enqueue_style( RFS_ACF_SYNC_NOTICE_SLUG, RFS_ACF_SYNC_NOTICE_URL . '/dist/styles/plugin.css', array(), '1.0.0' );
        wp_enqueue_script( RFS_ACF_SYNC_NOTICE_SLUG, RFS_ACF_SYNC_NOTICE_URL . '/dist/scripts/plugin.bundle.js', array(), '1.0.0', true );

        wp_localize_script( RFS_ACF_SYNC_NOTICE_SLUG, 'RFS_ACF_SYNC_NOTICE', array(
          'ajaxurl' => admin_url( 'admin-ajax.php' ),
          'nonce' => wp_create_nonce( RFS_ACF_SYNC_NOTICE_SLUG . '-nonce' ),
          'acfPageUrl' => esc_url( $this->acf_page_url ),
          'groupHasSync' => $this->group_has_sync,
          'syncData' => $this->sync_data,
          'files' => $this->files,
          'mode' => esc_attr( $this->mode ),
          'autoSyncMode' => esc_attr( $this->auto_sync_mode ),
          'doAutoSync' => $this->do_auto_sync,
          'postType' => esc_attr( $this->post_type ),
          'autoSync' => array(
            'syncing' => __( 'Synchronizing ACF field groups...', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN ),
            'synced' => __( 'Synchronizing completed.', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN ),
          ),
          'modal' => array(
            'heading' => __( 'ACF Sync Available', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN ),
            'text' => __( 'The field group you are editing has not been synced with your local acf json file. Making any changes will overwrite the local json file and may result in losing data.', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN ),
            'buttons' => array(
              'sync' => array(
                'url' => $this->sync_page_url,
                'label' => __( 'View files to sync', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN )
              ),
              'ignore' => array(
                'url' => '#',
                'label' => __( 'Ignore', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN )
              )
            )
          )
        ) );
      }
    }

    /**
     * This function will synchronize field groups with local acf json files
     * @param n/a
     * @return json
     */
    public function ajax_auto_sync() {
      $security = check_ajax_referer( RFS_ACF_SYNC_NOTICE_SLUG . '-nonce', 'security', false );

      if ( !$security ) {
        wp_send_json( array(
          'error' => 'nonce'
        ) );
      }

      $sync_data      = (array) json_decode( stripslashes( $_POST[ 'sync_data' ] ) ) ?: array();
      $files          = (array) json_decode( stripslashes( $_POST[ 'files' ] ) ) ?: array();
      $acf_page_url   = sanitize_text_field( $_POST[ 'url' ] );
      $auto_sync_mode = sanitize_text_field( $_POST[ 'auto_sync_mode' ] );

      $ids          = array();
      $redirect_url = 'none';

      if ( $files && $sync_data ) {
        // Disabled "Local JSON" controller to prevent the .json file timestamp from being modified during import.
        acf_update_setting( 'json', false );

        foreach ( $sync_data as $key => $group ) {
          $field_group               = (array) $group;
          $local_field_group         = (array) json_decode( file_get_contents( $files[ $key ] ), true );
          $local_field_group[ 'ID' ] = $field_group[ 'ID' ];
          $result                    = acf_import_field_group( $local_field_group );

          $ids[] = $result[ 'ID' ];
        }

        // Re-enable json
        acf_update_setting( 'json', true );
      }

      if ( $ids && $auto_sync_mode == 'acf' ) {
        $redirect_url = add_query_arg( array(
          'acfsynccomplete' => implode( ',', $ids )
        ), $acf_page_url );
      }

      wp_send_json( array(
        'ok' => true,
        'redirectUrl' => $redirect_url,
        'p' => $_POST
      ) );
    }

    /**
     * This function will display notice on acf field groups pages
     * @param n/a
     * @return html
     */
    public function acf_sync_notice() {
      if ( $this->is_acf_page && $this->mode == 'notice' ) {
        if ( !empty( $this->sync_data ) ) {
          printf(
            '<div class="error">
              <p>%s%s</p>
            </div>',
            __( 'You have unsynced ACF fields.', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN ),
            sprintf(
              __( ' %1$sView fields available for sync%2$s', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN ),
              '<a href="' . esc_url( $this->sync_page_url ) . '">',
              '</a>'
            )
          );
        }
      }
    }

    /**
     * acf_apply_get_local_field_groups
     *
     * Appends local field groups to the provided array.
     *
     * @date    23/1/19
     * @param array $field_groups An array of field groups.
     * @return  array
     * @since   5.7.10
     *
     */
    public function acf_apply_get_local_field_groups( $groups = array() ) {
      // Get local groups
      $local = acf_get_store( 'local-groups' )->get();

      if ( $local ) {
        // Generate map of "index" => "key" data.
        $map = wp_list_pluck( $groups, 'key' );

        // Loop over groups and update/append local.
        foreach ( $local as $group ) {
          // Get group allowing cache and filters to run.
          // $group = acf_get_field_group( $group['key'] );

          // Update.
          $i = array_search( $group[ 'key' ], $map );
          if ( $i !== false ) {
            unset( $group[ 'ID' ] );
            $groups[ $i ] = array_merge( $groups[ $i ], $group );

            // Append
          } else {
            $groups[] = acf_get_field_group( $group[ 'key' ] );
          }
        }

        // Sort list via menu_order and title.
        $groups = wp_list_sort(
          $groups,
          array(
            'menu_order' => 'ASC',
            'title' => 'ASC'
          )
        );
      }

      // Return groups.
      return $groups;
    }

    private function is_acf_active() {
      return in_array( 'advanced-custom-fields-pro/acf.php', $this->get_active_plugins() );
    }

    private function get_active_plugins() {
      return apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
    }
  }

}
?>
