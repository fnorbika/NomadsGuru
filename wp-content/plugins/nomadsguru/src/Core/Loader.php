<?php

namespace NomadsGuru\Core;

use NomadsGuru\Admin\AdminMenu;
use NomadsGuru\Admin\PublishingSettings;
use NomadsGuru\Admin\QueueManager;
use NomadsGuru\Frontend\Shortcode;
use NomadsGuru\Blocks\DealsBlock;
use NomadsGuru\REST\DealsController;
use NomadsGuru\REST\SourcesController;
use NomadsGuru\REST\AffiliatesController;
use NomadsGuru\REST\ConfigController;
use NomadsGuru\REST\StatsController;

class Loader {

	/**
	 * @var Loader|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Loader
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin
	 */
	public function init() {
		// Load Text Domain
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Initialize Components
		$this->init_admin();
		$this->init_public();
		$this->init_rest_api();
		$this->init_blocks();
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'nomadsguru', false, dirname( plugin_basename( NOMADSGURU_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Initialize Admin Components
	 */
	private function init_admin() {
		if ( is_admin() ) {
			$admin_menu = new AdminMenu();
			$admin_menu->init();

			$publishing_settings = new PublishingSettings();
			$publishing_settings->init();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}
	}

	/**
	 * Enqueue Admin Assets
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'nomadsguru' ) === false ) {
			return;
		}
		wp_enqueue_style( 'nomadsguru-admin', NOMADSGURU_PLUGIN_URL . 'assets/css/admin.css', array(), NOMADSGURU_VERSION );
		wp_enqueue_script( 'nomadsguru-admin', NOMADSGURU_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), NOMADSGURU_VERSION, true );
		
		wp_localize_script( 'nomadsguru-admin', 'nomadsguruParams', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nomadsguru_admin_nonce' ),
		) );
	}

	/**
	 * Initialize Public Components
	 */
	private function init_public() {
		$shortcode = new Shortcode();
		$shortcode->init();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue Frontend Assets
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'nomadsguru-frontend', NOMADSGURU_PLUGIN_URL . 'assets/css/frontend.css', array(), NOMADSGURU_VERSION );
	}

	/**
	 * Initialize Blocks
	 */
	private function init_blocks() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register Gutenberg Blocks
	 */
	public function register_blocks() {
		$deals_block = new DealsBlock();
		$deals_block->register();

		$filter_block = new \NomadsGuru\Blocks\DealFilterBlock();
		$filter_block->register();
	}

	/**
	 * Initialize REST API
	 */
	private function init_rest_api() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST routes
	 */
	public function register_rest_routes() {
		$deals_controller = new DealsController();
		$deals_controller->register_routes();

		$sources_controller = new SourcesController();
		$sources_controller->register_routes();

		$affiliates_controller = new AffiliatesController();
		$affiliates_controller->register_routes();

		$config_controller = new ConfigController();
		$config_controller->register_routes();

		$stats_controller = new StatsController();
		$stats_controller->register_routes();
	}

	/**
	 * Activation Hook
	 */
	public static function activate() {
		// Create Database Tables
		Database::activate();

		// Flush Rewrite Rules
		flush_rewrite_rules();
	}

	/**
	 * Deactivation Hook
	 */
	public static function deactivate() {
		// Flush Rewrite Rules
		flush_rewrite_rules();
	}
}
