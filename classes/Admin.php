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
        'options-general.php', 
        __('RFS ACF Sync Notice', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN), 
        __('RFS ACF Sync Notice', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN), 
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
        <h2>RFS ACF JSON Sync Notice</h2>

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
      add_settings_section( $this->settings_page_slug.'-repo', __('Repository Connection', RFS_ACF_SYNC_NOTICE_TEXTDOMAIN), array( $this, 'repo_section_callback'), $this->settings_page_slug );
    }

    /**
     * This function will display settings section html
     * @param n/a
     * @return html
    */
    public function repo_section_callback() {}

    /**
     * This function will create plugin settings fields
     * @param n/a
     * @return n/a
    */
    public function create_settings_fields() {
      $fields = array(
        'username'  => array(
          'section' => 'repo',
          'label' => 'GitHub Username',
          'type' => 'text'
        ),
        'repository'  => array(
          'section' => 'repo',
          'label' => 'GitHub Repository Name',
          'type' => 'text'
        ),
        'token'  => array(
          'section' => 'repo',
          'label' => 'GitHub Access Token',
          'type' => 'text'
        )
      );

      foreach( $fields as $field_id => $data ) {
        $field_id = $this->settings_page_slug.'-'.$field_id;
        $data['section']  = $this->settings_page_slug.'-'.$data['section'];

        add_settings_field( $field_id, __($data['label'], RFS_ACF_SYNC_NOTICE_TEXTDOMAIN), array( $this, 'settings_fields_callback'), $this->settings_page_slug, $data['section'], array('id' => $field_id, 'data' => $data) );
        register_setting( $this->settings_page_slug, $field_id );
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
        $default    = isset( $data['default'] ) ? $data['default'] : '';
  
        switch( $field_type ) {
          case 'text':
            $field_html = '<input name="'.$field_id.'" id="'.$field_id.'" type="'.$field_type.'" class="regular-text" value="'.get_option($field_id, '').'" />';
            break;
          default:
            $field_html = '';
            break;
        }
  
        echo $field_html;
      }
    }
  }

}
?>