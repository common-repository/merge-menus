<?php
declare( strict_types=1 );

namespace WatchTheDot\Plugins;

/**
 * Plugin Name:       Merge Menus
 * Plugin URI:        https://support.watchthedot.com/our-plugins/merge-menus
 * Description:       Merge WordPress menus! Import 1 menu into another to save time when copying a menu over to a new menu.
 * Version:           1.1.3
 * Requires PHP:      7.4
 *
 * Requires at least: 5.8
 * Tested up to:      6.4
 *
 * Author:            Watch The Dot
 * Author URI:        https://www.watchthedot.com
 *
 * Text-Domain:       merge-menus
 * Domain Path:       /languages
 *
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

class MergeMenus {
	/**
	 * The name of the plugin displayed in the admin panel
	 */
	const NAME = 'Merge Menus';

	/**
	 * The version number used for certain errors when raised
	 */
	const VERSION = '1.1.3';

	/**
	 * The namespace for the plugins settings
	 */
	const TOKEN = 'merge-menus';

	/**
	 * The ONLY instance of the plugin.
	 * Accessable via ::instance().
	 * Ensures that the hooks are only added once
	 */
	private static ?self $instance;

	/**
	 * The FULL filepath to this file
	 */
	private string $file;

	/**
	 * The FULL directory path to this folder
	 */
	private string $dir;

	/**
	 * The directory where the plugin's assets are stored
	 */
	private string $assets_dir;

	/**
	 * The URL to the plugin's assets.
	 * Used when enqueuing styles and scripts
	 */
	private string $assets_url;

	private function __construct() {
		$this->file       = __FILE__;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		add_action( 'admin_init', [ $this, 'action_admin_init' ] );
		add_filter( 'plugin_row_meta', [ $this, 'filter_plugin_row_meta' ], 10, 2 );

		add_action( 'wp_ajax_merge_menu_get_items', [ $this, 'ajax_merge_menu_get_items' ] );
	}

	public function action_admin_init() {
		add_action( 'admin_head-nav-menus.php', [ $this, 'action_admin_head_nav_menus' ] );
	}

	public function filter_plugin_row_meta( array $plugin_meta, $plugin_file ): array {
		if ( $plugin_file !== plugin_basename( __FILE__ ) ) {
			return $plugin_meta;
		}

		return array_merge(
			array_slice( $plugin_meta, 0, 2 ),
			[
				sprintf(
					"<a href='https://support.watchthedot.com/our-plugins/merge-menus/#documentation' target='_blank'>%s</a>",
					__( 'Documentation', 'merge-menus' )
				),
			],
			array_slice( $plugin_meta, 2 )
		);
	}

	public function action_admin_head_nav_menus() {
		add_meta_box(
			'merge_menus_nav_link',
			__( 'Merge Menus', 'merge-menus' ),
			[ $this, 'meta_box_merge_menus_nav_link' ],
			'nav-menus',
			'side',
			'low'
		);
	}

	public function meta_box_merge_menus_nav_link() {
		$nav_menus = wp_get_nav_menus();
		?>
		<div id="merge-menus" class="posttypediv">
			<div>
				<select id="merge-menu" style="width: 100%" <?php disabled( ! count( $nav_menus ) ); ?>>
					<option value="null" disabled selected>Select</option>
					<?php foreach ( $nav_menus as $menu ) : ?>
						<option value="<?php echo esc_attr( (string) $menu->term_id ); ?>">
							<?php echo esc_html( $menu->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<p class="button-controls">
				<span class="add-to-menu">
					<input
						type="button"
						class="button-secondary right"
						value="<?php esc_attr_e( 'Merge Menu', 'merge-menus' ); ?>"
						id="submit-merge-menus"
						value="<?php esc_attr_e( 'Add to menu', 'merge-menus' ); ?>"
					>
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<script>
			jQuery(document).ready(($) => {
				$('#submit-merge-menus').on('click', function () {
					const $this = $(this);
					const $selectedVal = parseInt( $('#merge-menu').val() );

					if ($selectedVal === NaN) return;

					$this.siblings('.spinner').css('visibility', 'visible');

					const resetSpinner = () => $this.siblings('.spinner').css('visibility', 'hidden');

					$.ajax({
						url: "<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>",
						method: "post",
						data: {
							action: "merge_menu_get_items",
							menu_id: $selectedVal
						}
					}).done((data) => {
						const arr = JSON.parse(data);
						if (!Array.isArray(arr)) {
							window.wpNavMenu.addItemToMenu(arr, window.wpNavMenu.addMenuItemToBottom, resetSpinner);
						} else {
							resetSpinner();
						}
					}).fail(resetSpinner);
				});
			});
		</script>
		<?php
	}

	public function ajax_merge_menu_get_items(): void {
		if ( ! current_theme_supports( 'menus' ) && ! current_theme_supports( 'widgets' ) ) {
			die( '[]' );
		}

		// Permissions check.
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			die( '[]' );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$request_method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );
		if ( $request_method !== 'POST' || ! isset( $_POST['menu_id'] ) ) {
			die( '[]' );
		}

		$menu_id = sanitize_text_field( wp_unslash( $_POST['menu_id'] ) );
		if ( ! is_numeric( $menu_id ) ) {
			die( '[]' );
		}
		$menu_id = intval( $menu_id );
		// phpcs:enable

		$found_menu = array_values( array_filter( wp_get_nav_menus(), static fn ( $menu ) => $menu->term_id === $menu_id ) );
		if ( empty( $found_menu ) ) {
			die( '[]' );
		}
		$menu = $found_menu[0];

		$items = wp_get_nav_menu_items( $menu->term_id, [ 'post_status' => 'any' ] );
		if ( empty( $items ) ) {
			die( '[]' );
		}

		$json = [];
		$i    = 0;
		foreach ( $items as $item ) {
			$json[ --$i ] = [
				'menu-item-db-id'            => $item->db_id,
				'menu-item-menu-item-parent' => $item->menu_item_parent,
				'menu-item-object-id'        => $item->object_id,
				'menu-item-object'           => $item->object,
				'menu-item-type'             => $item->type,
				'menu-item-type-label'       => $item->type_label,
				'menu-item-url'              => $item->url,
				'menu-item-title'            => $item->title,
				'menu-item-target'           => $item->target,
				'menu-item-attr-title'       => $item->attr_title,
				'menu-item-description'      => $item->description,
				'menu-item-classes'          => implode( ' ', $item->classes ),
				'menu-item-xfn'              => $item->xfn,
			];
		}

		echo json_encode( $json );
		die();
	}

	/* === GETTERS AND INSTANCES === */

	public function get_filename(): string {
		return $this->file;
	}

	public function get_directory(): string {
		return $this->dir;
	}

	public function get_assets_dir(): string {
		return $this->assets_dir;
	}

	public function get_assets_url(): string {
		return $this->assets_url;
	}

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( 'Cloning of ' . self::class . ' is forbidden' ), esc_attr( self::VERSION ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( 'Unserializing instances of ' . self::class . ' is forbidden' ), esc_attr( self::VERSION ) );
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action(
		'plugins_loaded',
		static function () {
			MergeMenus::instance();
		}
	);
}
