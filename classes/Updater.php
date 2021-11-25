<?php
namespace RFS_ACF_SYNC_NOTICE;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'Updater' ) ) {

  class Updater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $username;
    private $repository;
    private $authorize_token;
    private $github_response;
    private $plugin_tested_up_to = '5.8.2';

    public function __construct( $file ) {
      $this->file = $file;

      add_action('admin_init', array( $this, 'set_plugin_properties' ) );

      return $this;
    }

    public function set_plugin_properties() {
      $this->plugin = get_plugin_data($this->file);
      $this->basename = plugin_basename($this->file);
      $this->active = is_plugin_active($this->basename);
    }

    public function set_username( $username ) {
      $this->username = $username;
    }

    public function set_repository( $repository ) {
      $this->repository = $repository;
    }

    public function authorize( $token ) {
      $this->authorize_token = $token;
    }

    private function get_repository_info() {
      if ( is_null( $this->github_response ) ) {
        $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases', $this->username, $this->repository);

        // Switch to HTTP Basic Authentication for GitHub API v3
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => $request_uri,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "Authorization: token " . $this->authorize_token,
            "User-Agent: ". $this->repository
          )
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        if ( is_array( $response ) ) {
          $response = current($response);
        }

        // if ( $this->authorize_token && $response && isset( $response['zipball_url'] ) ) {
        //   $response['zipball_url'] = add_query_arg('access_token', $this->authorize_token, $response['zipball_url']);
        // }

        $this->github_response = $response;
      }
    }

    public function initialize() {
      add_filter('pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ), 10, 1);
      add_filter('plugins_api', array( $this, 'plugin_popup' ), 10, 3);

      add_filter('upgrader_pre_download', array( $this, 'before_download' ) );
      add_filter('upgrader_install_package_result', array( $this, 'after_install' ), 10, 2);
    }

    public function modify_transient( $transient ) {
      if ( property_exists( $transient, 'checked' ) ) {
        if ( $checked = $transient->checked ) {
          $this->get_repository_info();

          $out_of_date = false;

          if ( $this->github_response ) {
            $repo_version = preg_replace('/^v/', '', $this->github_response['tag_name'] );
            $plugin_version = preg_replace('/^v/', '', $checked[$this->basename] );

            $out_of_date = version_compare( $repo_version, $plugin_version, 'gt' );
          }

          if ( $out_of_date ) {
            $new_files = $this->github_response['zipball_url'];
            $slug = current(explode('/', $this->basename));

            $plugin = array(
              'url' => $this->plugin['PluginURI'],
              'slug' => $slug,
              'package' => $new_files,
              'new_version' => $this->github_response['tag_name'],
              'tested' => $this->plugin_tested_up_to
            );

            $transient->response[$this->basename] = (object) $plugin;
          }
        }
      }

      return $transient;
    }

    public function plugin_popup( $result, $action, $args ) {
      if ( $action !== 'plugin_information' ) {
        return false;
      }

      if ( !empty($args->slug) ) {
        if ( $args->slug == current(explode('/' , $this->basename)) ) {
          $this->get_repository_info();

          $plugin = array(
            'name' => $this->plugin['Name'],
            'slug' => $this->basename,
            'requires' => $this->plugin['RequiresWP'],
            'requires_php' => $this->plugin['RequiresPHP'],
            'tested' => $this->plugin_tested_up_to,
            'version' => $this->github_response['tag_name'],
            'author' => $this->plugin['AuthorName'],
            'author_profile' => $this->plugin['AuthorURI'],
            'last_updated' => $this->github_response['published_at'],
            'homepage' => $this->plugin['PluginURI'],
            'short_description' => $this->plugin['Description'],
            'sections' => array(
              'description' => $this->plugin['Description'],
              'changelog' => $this->github_response['body']
            ),
            'download_link' => $this->github_response['zipball_url']
          );

          return (object) $plugin;
        }
      }

      return $result;
    }

    public function before_download() {
      add_filter( 'http_request_args', array( $this, 'download_package_headers' ), 15, 2 );
      return false; // upgrader_pre_download filter default return value.
    }

    /**
     * This function will add authentication header for download packages
     * @param $args (array) HTTP GET REQUEST args
     * @param $url (string)
     * @return 
    */
    public function download_package_headers( $args, $url ) {
      $args['headers']['Authorization'] = 'token ' . $this->authorize_token;

      // Remove authentication headers from release assets
      remove_filter( 'http_request_args', array( $this, 'download_package_headers' ) );

      return $args;
    }

    public function after_install($result, $hook_extra) {
      global $wp_filesystem;

      $plugin_file = RFS_ACF_SYNC_NOTICE_FILE;
      $basename = plugin_basename($plugin_file);
      $is_active = is_plugin_active($basename);

      $install_directory = plugin_dir_path($plugin_file);
      $wp_filesystem->move($result['destination'], $install_directory);
      $result['destination'] = $install_directory;

      if ( $is_active ) {
        activate_plugin($basename);
      }

      return $result;
    }
  }

}
?>