<?php
/**
 * GitHub Updater Additions
 *
 * @author    Andy Fragen
 * @license   MIT
 * @link      https://github.com/afragen/github-updater-additions
 * @package   github-updater-additions
 */

namespace Fragen\GitHub_Updater\Additions;

/**
 * Class Settings
 */
class Settings {
	/**
	 * Holds the values for additions settings.
	 *
	 * @deprecated 9.1.0
	 *
	 * @var array $option_remote
	 */
	public static $options_additions;

	/**
	 * Supported types.
	 *
	 * @deprecated 9.1.0
	 *
	 * @var array $addition_types
	 */
	public static $addition_types = [
		'github_plugin',
		'github_theme',
		'bitbucket_plugin',
		'bitbucket_theme',
		'gitlab_plugin',
		'gitlab_theme',
		'gitea_plugin',
		'gitea_theme',
	];


	/**
	 * Settings constructor.
	 */
	public function __construct() {
		$this->load_options();
	}

	/**
	 * Load site options.
	 */
	private function load_options() {
		self::$options_additions = get_site_option( 'github_updater_additions', [] );
	}

	/**
	 * Load needed action/filter hooks.
	 */
	public function load_hooks() {
		add_action(
			'github_updater_update_settings',
			function ( $post_data ) {
				$this->save_settings( $post_data );
			}
		);
		$this->add_settings_tabs();

		add_filter(
			'github_updater_add_admin_page',
			function ( $tab, $action ) {
				$this->add_admin_page( $tab, $action );
			},
			10,
			2
		);
	}

	/**
	 * Save Additions settings.
	 *
	 * @uses 'github_updater_update_settings' action hook
	 * @uses 'github_updater_save_redirect' filter hook
	 *
	 * @param array $post_data $_POST data.
	 */
	public function save_settings( $post_data ) {
		$options   = get_site_option( 'github_updater_additions', [] );
		$duplicate = false;
		if ( isset( $post_data['option_page'] ) &&
			'github_updater_additions' === $post_data['option_page']
		) {
			$new_options = isset( $post_data['github_updater_additions'] )
				? $post_data['github_updater_additions']
				: [];

			$new_options = $this->sanitize( $new_options );

			foreach ( $options as $option ) {
				$duplicate = in_array( $new_options[0]['ID'], $option, true );
				if ( $duplicate ) {
					break;
				}
			}

			if ( ! $duplicate ) {
				$options = array_merge( $options, $new_options );
				update_site_option( 'github_updater_additions', $options );
			}

			add_filter(
				'github_updater_save_redirect',
				function ( $option_page ) {
					return array_merge( $option_page, [ 'github_updater_additions' ] );
				}
			);
		}
	}

	/**
	 * Adds Additions tab to Settings page.
	 */
	public function add_settings_tabs() {
		$install_tabs = [ 'github_updater_additions' => esc_html__( 'Additions', 'github-updater-additions' ) ];
		add_filter(
			'github_updater_add_settings_tabs',
			function ( $tabs ) use ( $install_tabs ) {
				return array_merge( $tabs, $install_tabs );
			},
			20,
			1
		);
	}

	/**
	 * Add Settings page data via action hook.
	 *
	 * @uses 'github_updater_add_admin_page' action hook
	 *
	 * @param string $tab    Tab name.
	 * @param string $action Form action.
	 */
	public function add_admin_page( $tab, $action ) {
		$this->additions_page_init();

		if ( 'github_updater_additions' === $tab ) {
			$action = add_query_arg(
				[
					'page' => 'github-updater',
					'tab'  => $tab,
				],
				$action
			);
			( new Repo_List_Table() )->render_list_table();
			?>
			<form class="settings" method="post" action="<?php esc_attr_e( $action ); ?>">
				<?php
				settings_fields( 'github_updater_additions' );
				do_settings_sections( 'github_updater_additions' );
				submit_button();
				?>
			</form>
			<?php
		}
	}

	/**
	 * Settings for Additions.
	 */
	public function additions_page_init() {
		register_setting(
			'github_updater_additions',
			'github_updater_additions',
			null
		);

		add_settings_section(
			'github_updater_additions',
			esc_html__( 'Additions', 'github-updater' ),
			[ $this, 'print_section_additions' ],
			'github_updater_additions'
		);

		add_settings_field(
			'type',
			esc_html__( 'Repository Type', 'github-updater-additions' ),
			[ $this, 'callback_dropdown' ],
			'github_updater_additions',
			'github_updater_additions',
			[
				'id'      => 'github_updater_additions_type',
				'setting' => 'type',
			]
		);

		add_settings_field(
			'slug',
			esc_html__( 'Repository Slug', 'github-updater-additions' ),
			[ $this, 'callback_field' ],
			'github_updater_additions',
			'github_updater_additions',
			[
				'id'      => 'github_updater_additions_slug',
				'setting' => 'slug',
			]
		);

		add_settings_field(
			'uri',
			esc_html__( 'Repository URI', 'github-updater-additions' ),
			[ $this, 'callback_field' ],
			'github_updater_additions',
			'github_updater_additions',
			[
				'id'      => 'github_updater_additions_uri',
				'setting' => 'uri',
			]
		);
	}

	/**
	 * Sanitize each setting field as needed.
	 *
	 * @param array $input Contains all settings fields as array keys.
	 *
	 * @return array
	 */
	public function sanitize( $input ) {
		$new_input = [];

		foreach ( (array) $input as $key => $value ) {
			$new_input[0][ $key ] = 'uri' === $key ? untrailingslashit( esc_url_raw( trim( $value ) ) ) : sanitize_text_field( $value );
		}
			$new_input[0]['ID'] = md5( $new_input[0]['slug'] );

		return $new_input;
	}

	/**
	 * Print the Remote Management text.
	 */
	public function print_section_additions() {
		echo '<p>';
		esc_html_e( 'If there are git repositories that do not natively support GitHub Updater you can add them here.', 'github-updater-additions' );
		echo '</p>';
	}

	/**
	 * Field callback.
	 *
	 * @param array $args Data passed from add_settings_field().
	 *
	 * @return void
	 */
	public function callback_field( $args ) {
		?>
		<label for="<?php esc_attr_e( $args['id'] ); ?>">
			<input type="text" style="width:50%;" id="<?php esc_attr( $args['id'] ); ?>" name="github_updater_additions[<?php esc_attr_e( $args['setting'] ); ?>]" value="">
			<br>
			<span class="description">
				<?php esc_html_e( 'Ensure proper slug for plugin or theme.', 'github-updater-additions' ); ?>
			</span>
		</label>
		<?php
	}

	/**
	 * Dropdown callback.
	 *
	 * @param arra $args Data passed from add_settings_field().
	 *
	 * @return void
	 */
	public function callback_dropdown( $args ) {
		$options['type'] = [ 'github_plugin' ];
		?>
		<label for="<?php esc_attr_e( $args['id'] ); ?>">
		<select id="<?php esc_attr_e( $args['id'] ); ?>" name="github_updater_additions[<?php esc_attr_e( $args['setting'] ); ?>]">
		<?php
		foreach ( self::$addition_types as $item ) {
			$selected = ( 'github_plugin' === $item ) ? 'selected="selected"' : '';
			echo '<option value="' . esc_attr( $item ) . '" $selected>' . esc_attr( $item ) . '</option>';
		}
		?>
		</select>
		</label>
		<?php
	}

}