<?php

if ( ! class_exists( 'DB_Reset_Admin' ) ) :

  class DB_Reset_Admin {

    private $code;
    private $nonce;
    private $notice_error;
    private $notice_success;
    private $request;
    private $resetter;
    private $user;
    private $version;
    private $wp_tables;

    public function __construct( $version ) {
      $this->resetter = new DB_Resetter();
      $this->version = $version;

      $this->set_request( $_REQUEST );
      $this->set_view_variables();
    }

    private function set_request( array $request ) {
      $this->request = $request;
    }

    private function set_view_variables() {
      $this->set_code();
      $this->set_nonce();
      $this->set_user();
      $this->set_wp_tables();
    }

    private function set_code() {
      $this->code = $this->generate_code();
    }

    private function set_nonce() {
      $this->nonce = strtolower( str_replace( '_', '-', __CLASS__ ) );
    }

    private function set_user() {
      $this->user = $this->resetter->get_user();
    }

    private function set_wp_tables() {
      $this->wp_tables = $this->resetter->get_wp_tables();
    }

    private function generate_code( $length = 5 ) {
      return substr( md5( time() ), 1, $length );
    }

    public function run() {
      add_action( 'admin_init', array( $this, 'reset' ) );
      add_action( 'admin_menu', array( $this, 'add_tools_menu' ) );
    }

    public function reset() {
      if ( $this->form_is_safe_to_submit() ) {
        try {
          $this->resetter->reset(
            $this->request[ 'db-reset-tables' ],
            $this->request[ 'db-reset-reactivate-theme-data' ]
          );

          $this->notice_success = __( 'The selected tables were reset', 'wp-reset' );
        } catch ( Exception $e ) {
          $this->notice_error = $e->getMessage();
        }
      }
    }

    private function form_is_safe_to_submit() {
      return isset( $this->request['db-reset-code-confirm'] ) &&
             $this->assert_correct_code() &&
             check_admin_referer( $this->nonce );
    }

    private function assert_correct_code() {
      if ( $this->request['db-reset-code'] !==
           $this->request['db-reset-code-confirm'] ) {
        $this->notice_error = __( 'You entered the wrong security code', 'wp-reset' );
        return false;
      }

      return true;
    }

    public function add_tools_menu() {
      $plugin_page = add_management_page(
        __( 'Database Reset', 'wp-reset' ),
        __( 'Database Reset', 'wp-reset' ),
        'update_core',
        'database-reset',
        array( $this, 'render' )
      );

      add_action( 'load-' . $plugin_page, array( $this, 'load_assets' ) );
    }

    public function render() {
      require_once( DB_RESET_PATH . '/views/index.php' );
    }

    public function load_assets() {
      $this->load_stylesheets();
      $this->load_javascript();
    }

    private function load_stylesheets() {
      wp_enqueue_style(
        'bsmselect',
        plugins_url( 'assets/css/bsmselect.css', __FILE__ ),
        array(),
        $this->version
      );

      wp_enqueue_style(
        'database-reset',
        plugins_url( 'assets/css/database-reset.css', __FILE__ ),
        array('bsmselect'),
        $this->version
      );
    }

    private function load_javascript() {
      wp_enqueue_script(
        'bsmselect',
        plugins_url( 'assets/js/bsmselect.js', __FILE__ ),
        array( 'jquery' ),
        $this->version,
        true
      );

      wp_enqueue_script(
        'bsmselect-compatibility',
        plugins_url( 'assets/js/bsmselect.compatibility.js', __FILE__ ),
        array( 'bsmselect' ),
        $this->version,
        true
      );

      wp_enqueue_script(
        'database-reset',
        plugins_url( 'assets/js/database-reset.js', __FILE__ ),
        array( 'bsmselect', 'bsmselect-compatibility' ),
        $this->version,
        true
      );

      wp_localize_script(
        'database-reset',
        'dbReset',
        $this->load_javascript_vars()
      );
    }

    private function load_javascript_vars() {
      return array(
        'confirmAlert' => __( 'Are you sure you want to continue?', 'wp-reset' ),
        'selectTable' => __( 'Select Tables', 'wp-reset' )
      );
    }

  }

endif;
