<?php
/**
 * Plugin Name: nexTab CPT Media Importer
 * Description: Automatischer Import von Medien-Dateien als Posts in Custom Post Types. Unterstützt Admin-Interface, WP-CLI und REST API.
 * Version: 1.0.0
 * Author: nexTab
 * Author URI: https://nextab.de
 */

if (!defined('ABSPATH')) {
	exit;
}

class NXT_CPT_Media_Importer {
	
	private static $instance = null;
	
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		add_action('admin_menu', [$this, 'add_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		add_action('wp_ajax_nxt_import_media_batch', [$this, 'ajax_import_batch']);
		add_action('rest_api_init', [$this, 'register_rest_routes']);
		
		if (defined('WP_CLI') && WP_CLI) {
			$this->register_cli_commands();
		}
	}
	
	public function add_admin_menu() {
		add_management_page(
			'CPT Media Importer',
			'CPT Media Importer',
			'manage_options',
			'nxt-cpt-media-importer',
			[$this, 'render_admin_page']
		);
	}
	
	public function enqueue_admin_assets($hook) {
		if ('tools_page_nxt-cpt-media-importer' !== $hook) {
			return;
		}
		
		wp_enqueue_style(
			'nxt-cpt-importer-admin',
			$this->get_plugin_url() . 'assets/admin-style.css',
			[],
			'1.0.0'
		);
		
		wp_enqueue_script(
			'nxt-cpt-importer-admin',
			$this->get_plugin_url() . 'assets/admin-script.js',
			[],
			'1.0.0',
			true
		);
		
		wp_localize_script('nxt-cpt-importer-admin', 'nxtImporter', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('nxt_importer_nonce'),
			'strings' => [
				'uploading' => __('Uploading files...', 'nxt'),
				'processing' => __('Processing...', 'nxt'),
				'complete' => __('Import complete!', 'nxt'),
				'error' => __('An error occurred', 'nxt'),
			]
		]);
	}
	
	public function render_admin_page() {
		$post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
		include plugin_dir_path(__FILE__) . 'templates/admin-page.php';
	}
	
	public function ajax_import_batch() {
		check_ajax_referer('nxt_importer_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Insufficient permissions']);
		}
		
		$post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
		$files = $_FILES['files'] ?? [];
		
		if (empty($files['name'])) {
			wp_send_json_error(['message' => 'No files uploaded']);
		}
		
		$results = $this->process_uploads($files, $post_type);
		
		wp_send_json_success([
			'results' => $results,
			'total' => count($results),
			'successful' => count(array_filter($results, function($r) { return $r['success']; })),
		]);
	}
	
	public function register_rest_routes() {
		register_rest_route('nxt/v1', '/import-media', [
			'methods' => 'POST',
			'callback' => [$this, 'rest_import_media'],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			}
		]);
	}
	
	public function rest_import_media($request) {
		$post_type = $request->get_param('post_type') ?? 'post';
		$files = $request->get_file_params();
		
		if (empty($files)) {
			return new WP_Error('no_files', 'No files provided', ['status' => 400]);
		}
		
		$results = $this->process_uploads($files['files'], $post_type);
		
		return rest_ensure_response([
			'success' => true,
			'results' => $results
		]);
	}
	
	private function process_uploads($files, $post_type) {
		$results = [];
		
		if (!is_array($files['name'])) {
			$files = [
				'name' => [$files['name']],
				'type' => [$files['type']],
				'tmp_name' => [$files['tmp_name']],
				'error' => [$files['error']],
				'size' => [$files['size']]
			];
		}
		
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		
		foreach ($files['name'] as $key => $name) {
			if ($files['error'][$key] !== UPLOAD_ERR_OK) {
				$results[] = [
					'success' => false,
					'filename' => $name,
					'message' => 'Upload error: ' . $files['error'][$key]
				];
				continue;
			}
			
			$file_array = [
				'name' => $files['name'][$key],
				'type' => $files['type'][$key],
				'tmp_name' => $files['tmp_name'][$key],
				'error' => $files['error'][$key],
				'size' => $files['size'][$key]
			];
			
			$result = $this->import_single_file($file_array, $post_type);
			$results[] = $result;
		}
		
		return $results;
	}
	
	public function import_single_file($file, $post_type) {
		$filename = $file['name'];
		$alt_text = $this->generate_alt_text($filename);
		$post_title = $this->generate_post_title($filename);
		
		$existing_post = get_page_by_title($post_title, OBJECT, $post_type);
		if ($existing_post) {
			return [
				'success' => false,
				'filename' => $filename,
				'message' => 'Post already exists: ' . $post_title,
				'post_id' => $existing_post->ID
			];
		}
		
		$attachment_id = media_handle_sideload($file, 0);
		
		if (is_wp_error($attachment_id)) {
			return [
				'success' => false,
				'filename' => $filename,
				'message' => $attachment_id->get_error_message()
			];
		}
		
		update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
		
		$post_id = wp_insert_post([
			'post_title' => $post_title,
			'post_status' => 'publish',
			'post_type' => $post_type,
		]);
		
		if (is_wp_error($post_id)) {
			wp_delete_attachment($attachment_id, true);
			return [
				'success' => false,
				'filename' => $filename,
				'message' => $post_id->get_error_message()
			];
		}
		
		set_post_thumbnail($post_id, $attachment_id);
		
		return [
			'success' => true,
			'filename' => $filename,
			'post_id' => $post_id,
			'attachment_id' => $attachment_id,
			'post_title' => $post_title,
			'alt_text' => $alt_text
		];
	}
	
	public function import_from_directory($directory, $post_type, $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']) {
		if (!is_dir($directory)) {
			return new WP_Error('invalid_directory', 'Directory does not exist: ' . $directory);
		}
		
		$files = glob($directory . '/*');
		$results = [];
		
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		
		foreach ($files as $file_path) {
			if (!is_file($file_path)) {
				continue;
			}
			
			$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
			if (!in_array($extension, $allowed_extensions)) {
				continue;
			}
			
			$file_array = [
				'name' => basename($file_path),
				'tmp_name' => $file_path,
				'type' => mime_content_type($file_path),
				'size' => filesize($file_path),
				'error' => 0
			];
			
			$result = $this->import_single_file($file_array, $post_type);
			$results[] = $result;
		}
		
		return $results;
	}
	
	private function generate_alt_text($filename) {
		$name = pathinfo($filename, PATHINFO_FILENAME);
		$name = preg_replace('/[-_]/', ' ', $name);
		$name = preg_replace('/\blogo\b/i', '', $name);
		$name = preg_replace('/\s+/', ' ', $name);
		$name = trim($name);
		$name = ucwords(strtolower($name));
		return $name;
	}
	
	private function generate_post_title($filename) {
		return $this->generate_alt_text($filename);
	}
	
	private function get_plugin_url() {
		return plugin_dir_url(__FILE__);
	}
	
	public function register_cli_commands() {
		WP_CLI::add_command('nxt import-media', [$this, 'cli_import_media']);
	}
	
	public function cli_import_media($args, $assoc_args) {
		$directory = $assoc_args['directory'] ?? '';
		$post_type = $assoc_args['post-type'] ?? 'post';
		
		if (empty($directory)) {
			WP_CLI::error('Please specify a directory with --directory=/path/to/folder');
			return;
		}
		
		if (!is_dir($directory)) {
			WP_CLI::error('Directory does not exist: ' . $directory);
			return;
		}
		
		WP_CLI::line('Starting import from: ' . $directory);
		WP_CLI::line('Target post type: ' . $post_type);
		WP_CLI::line('');
		
		$results = $this->import_from_directory($directory, $post_type);
		
		if (is_wp_error($results)) {
			WP_CLI::error($results->get_error_message());
			return;
		}
		
		$successful = 0;
		$failed = 0;
		
		foreach ($results as $result) {
			if ($result['success']) {
				WP_CLI::success('✓ ' . $result['filename'] . ' → Post ID: ' . $result['post_id']);
				$successful++;
			} else {
				WP_CLI::warning('✗ ' . $result['filename'] . ' → ' . $result['message']);
				$failed++;
			}
		}
		
		WP_CLI::line('');
		WP_CLI::line('Import complete!');
		WP_CLI::line('Successful: ' . $successful);
		WP_CLI::line('Failed: ' . $failed);
		WP_CLI::line('Total: ' . count($results));
	}
}

NXT_CPT_Media_Importer::get_instance();

