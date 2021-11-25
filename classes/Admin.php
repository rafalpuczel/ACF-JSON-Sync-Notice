<?php
namespace RFS_ACF_SYNC_NOTICE;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'Admin' ) ) {

  class Admin {
    private $settings_page_capability = 'manage_options';
    private $settings_page_slug = '';

    public function __construct() {
      $this->settings_page_slug = RFS_ACF_SYNC_NOTICE_SLUG.'-settings';

      add_action('admin_menu', array( $this, 'add_settings_page' ), 99 );
      add_action('admin_init', array( $this, 'create_settings_sections') );
      add_action('admin_init', array( $this, 'create_settings_fields') );
    }

    /**
     * This function will create plugin setting page in admin area
     * @param n/a
     * @return n/a
    */
    public function add_settings_page() {
      add_submenu_page( 
        'edit.php?post_type=acf-field-group', 
        __('ACF JSON Sync Notice', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN), 
        __('ACF JSON Sync Notice', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN), 
        $this->settings_page_capability, 
        $this->settings_page_slug, 
        array( $this, 'settings_page' )
      );
    }

    /**
     * This function will display settings page html
     * @param n/a
     * @return html
    */
    public function settings_page() {
      ?>
      <div class="wrap">
        <h2>ACF JSON Sync Notice</h2>

        <form method="post" action="options.php">
        <?php
          settings_fields($this->settings_page_slug);
          do_settings_sections($this->settings_page_slug);
          submit_button();
        ?>
      </form>
      </div>
      <?php
    }

    /**
     * This function will create plugin settings sections
     * @param n/a
     * @return n/a
    */
    public function create_settings_sections() {
      add_settings_section( $this->settings_page_slug.'-mode', __('Plugin Settings', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN), array( $this, 'mode_section_callback'), $this->settings_page_slug );
      add_settings_section( $this->settings_page_slug.'-repo', __('Updates Settings', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN), array( $this, 'repo_section_callback'), $this->settings_page_slug );
    }

    public function mode_section_callback() {}

    /**
     * This function will display settings section html
     * @param n/a
     * @return html
    */
    public function repo_section_callback() {
      printf(
        '<p>%s</p>',
        __('Enter GitHub connection data in order to allow plugin updates.', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN)
      );
    }

    /**
     * This function will create plugin settings fields
     * @param n/a
     * @return n/a
    */
    public function create_settings_fields() {
      $fields = array(
        'mode'  => array(
          'section' => 'mode',
          'label' => __('Plugin Mode', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
          'type' => 'select',
          'options' => array(
            'auto' => __('Auto Sync', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
            'notice' => __('Display Notice', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN)
          ),
          'default' => 'auto'
        ),
        'auto-mode'  => array(
          'section' => 'mode',
          'label' => __('Auto Sync Mode', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
          'description' => __('Run sync when accessing Admin area or Custom Fields section only.', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
          'type' => 'select',
          'options' => array(
            'admin' => __('Whole Admin Area', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
            'acf' => __('ACF Section', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN)
          ),
          'default' => 'admin',
          'condition' => array(
            'field_id' => 'mode',
            'value' => 'auto',
            'default' => 'auto'
          )
        ),
        'username'  => array(
          'section' => 'repo',
          'label' => __('GitHub Username', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
          'type' => 'text'
        ),
        'repository'  => array(
          'section' => 'repo',
          'label' => __('GitHub Repository Name', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
          'type' => 'text'
        ),
        'token'  => array(
          'section' => 'repo',
          'label' => __('GitHub Access Token', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN),
          'type' => 'password'
        )
      );

      foreach( $fields as $field_id => $data ) {
        if ( !isset( $data['default'] ) ) {
          $data['default'] = '';
        }

        if ( !isset( $data['condition'] ) ) {
          $data['condition'] = array();
        }

        if ( !isset( $data['description'] ) ) {
          $data['description'] = '';
        }

        $field_id = $this->settings_page_slug.'-'.$field_id;
        $data['section']  = $this->settings_page_slug.'-'.$data['section'];

        $display_field = !$data['condition'];

        if ( $data['condition'] ) {
          $value = get_option($this->settings_page_slug.'-'.$data['condition']['field_id'], $data['condition']['default']);

          $display_field = $value == $data['condition']['value'];
        }
        
        if ( $display_field ) {
          add_settings_field( $field_id, __($data['label'], RFS_ACF_SYNC_NOTICE_TEXTDOMAIN), array( $this, 'settings_fields_callback'), $this->settings_page_slug, $data['section'], array('id' => $field_id, 'data' => $data) );
          register_setting( $this->settings_page_slug, $field_id );
        }
      }
    }

    /**
     * This function will display settings fields html
     * @param n/a
     * @return html
    */
    public function settings_fields_callback( $args ) {
      if ( $args && isset( $args['id'] ) ) {
        $field_id   = $args['id'];
        $data       = $args['data'];
        $field_type = isset( $data['type'] ) ? $data['type'] : 'text';
        $default    = $data['default'];
  
        switch( $field_type ) {
          case 'text':
          case 'password':
            $field_html = sprintf(
              '<input name="%1$s" id="%1$s" type="%2$s" class="regular-text" value="%3$s" />',
              esc_attr( $field_id ),
              esc_attr( $field_type ),
              get_option( $field_id, '' )
            );
            break;
          case 'select':
            
            $field_html = '';

            if ( !empty( $data['options'] ) ) {
              $field_html .= sprintf(
                '<select name="%1$s" id="%1$s">',
                esc_attr( $field_id )
              );

              if ( !$default ) {
                $default = array_key_first( $data['options'] );
              }

              $i=1; foreach ( $data['options'] as $option_value => $option_label ) {
                $selected = selected( get_option( $field_id, $default ), $option_value, false );
                $field_html .= sprintf(
                  '<option %1$s value="%2$s">%3$s</option>',
                  $selected,
                  esc_attr( $option_value ),
                  esc_html( $option_label )
                );
              $i++; }

              $field_html .= '</select>';
            }
            break;
          default:
            $field_html = '';
            break;
        }

        if ( $data['description'] ) {
          $field_html .= sprintf('<p class="description">%s</p>', esc_html( $data['description'] ));
        }
  
        echo $field_html;
      }
    }
  }

}
?>