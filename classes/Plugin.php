<?php
namespace RFS_ACF_SYNC_NOTICE;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'Plugin' ) ) {

  class Plugin {

    private $acf_active = false;
    private $post_type = '';
    private $post_id = false;
    private $acf_post_type = 'acf-field-group';
    private $group_has_sync = false;
    private $sync = array();
    private $sync_page_url = '';

    public function __construct() {
      $this->acf_active = class_exists('ACF');

      add_action( 'admin_notices', array( $this, 'check_for_acf' ) );

      if ( !$this->acf_active ) {
        return;
      }

      add_action( 'admin_init',  array( $this, 'setup' ) );

      add_action( 'plugins_loaded', array( $this, 'i18n' ) );

      add_action( 'admin_notices',  array( $this, 'acf_sync_notice' ) );

      add_action( 'admin_enqueue_scripts', array( $this, 'load_plugin_scripts' ) );
    }

    /**
     * This function will check if ACF PRO plugin is activated on the site
     * @param n/a
     * @return n/a
    */
    public function check_for_acf() {
      if ( !$this->acf_active ) {
        printf( 
          '<div class="error">
            <p>%s</p>
          </div>',
          __( 'ACF Sync Notice plugin requires active Advanced Custom Fields PRO plugin to work.', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN ),
        );
      } else {
        $this->acf_active = true;
      }
    }

    /**
     * This function will setup plugin vars
     * @param n/a
     * @return n/a
    */
    public function setup() {
      global $pagenow;

      if ( $pagenow == 'edit.php' || $pagenow == 'post.php' ) {
        if ( isset( $_GET['post_type'] ) ) {
          $this->post_type = sanitize_text_field( $_GET['post_type'] );
        } else if ( isset( $_GET['post'] ) ) {
          $this->post_id = absint( $_GET['post'] );
          $this->post_type = get_post_type( $this->post_id );
        }
      }

      if ( $this->post_type == $this->acf_post_type ) {
        $this->sync_page_url = add_query_arg( array(
          'post_type' => 'acf-field-group',
          'post_status' => 'sync'
        ), admin_url('edit.php') );

        // Review local json field groups.
        if ( acf_get_local_json_files() ) {
          add_filter( 'acf/load_field_groups',  array( $this, 'acf_apply_get_local_field_groups' ), 20, 1 );

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
            } elseif ( ! $field_group['ID'] ) {
              $this->sync[ $field_group['key'] ] = $field_group;
    
              // Append to sync if "json" modified time is newer than database.
            } elseif ( $modified && $modified > get_post_modified_time( 'U', true, $field_group['ID'] ) ) {
              $this->sync[ $field_group['key'] ] = $field_group;
            }
          }
        }

        if ( !empty( $this->sync ) && $this->post_id ) {
          $this->group_has_sync = array_search( $this->post_id, array_column( $this->sync, 'ID' ) ) !== false;
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
      if ( $this->post_type == $this->acf_post_type ) {
        wp_enqueue_style( RFS_ACF_SYNC_NOTICE_SLUG, RFS_ACF_SYNC_NOTICE_URL.'/dist/styles/plugin.css', array(), '1.0.0' );
        wp_enqueue_script( RFS_ACF_SYNC_NOTICE_SLUG, RFS_ACF_SYNC_NOTICE_URL.'/dist/scripts/plugin.bundle.js', array(), '1.0.0', true );

        wp_localize_script( RFS_ACF_SYNC_NOTICE_SLUG, 'RFS_ACF_SYNC_NOTICE', array(
          'groupHasSync' => $this->group_has_sync,
          'postType' => esc_attr( $this->post_type ),
          'modal' => array(
            'heading' => __('ACF Sync Available', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
            'text' => __('The field group you are editing has not been synced with your local acf json file. Making any changes will overwrite the local json file and may result in losing data.', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
            'buttons' => array(
              'sync' => array(
                'url' => $this->sync_page_url,
                'label' => __('View files to sync', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN)
              ),
              'ignore' => array(
                'url' => '#',
                'label' => __('Ignore', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN)
              )
            )
          )
        ) );
      }
    }

    /**
     * This function will display notice on acf field groups pages
     * @param n/a
     * @return html
    */
    public function acf_sync_notice() {
      if ( $this->post_type == $this->acf_post_type ) {
        if ( !empty( $this->sync ) ) {
          printf( 
            '<div class="error">
              <p>%s%s</p>
            </div>',
            __( 'You have unsynced ACF fields.', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN ),
            sprintf(
              __( ' %1$sView fields available for sync%2$s', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN ),
              '<a href="' . esc_url( $this->sync_page_url ) . '">',
              '</a>',
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
     * @since   5.7.10
     *
     * @param   array $field_groups An array of field groups.
     * @return  array
     */
    public function acf_apply_get_local_field_groups( $groups = array() ) {
      // Get local groups
      $local = acf_get_store('local-groups')->get();

      if ( $local ) {
        // Generate map of "index" => "key" data.
        $map = wp_list_pluck( $groups, 'key' );
  
        // Loop over groups and update/append local.
        foreach ( $local as $group ) {
          // Get group allowing cache and filters to run.
          // $group = acf_get_field_group( $group['key'] );
  
          // Update.
          $i = array_search( $group['key'], $map );
          if ( $i !== false ) {
            unset( $group['ID'] );
            $groups[ $i ] = array_merge( $groups[ $i ], $group );
  
            // Append
          } else {
            $groups[] = acf_get_field_group( $group['key'] );
          }
        }
  
        // Sort list via menu_order and title.
        $groups = wp_list_sort(
          $groups,
          array(
            'menu_order' => 'ASC',
            'title'      => 'ASC',
          )
        );
      }
  
      // Return groups.
      return $groups;
    }
  }

}
?>