<?php

/**
 * Setup options meta boxes
 *
 * @package CardanoPress\Dependencies\ThemePlate
 * @since 0.1.0
 */

namespace CardanoPress\Dependencies\ThemePlate\Settings;

use CardanoPress\Dependencies\ThemePlate\Core\Config;
use CardanoPress\Dependencies\ThemePlate\Core\Fields;
use CardanoPress\Dependencies\ThemePlate\Core\Form;
use CardanoPress\Dependencies\ThemePlate\Core\Handler;
use CardanoPress\Dependencies\ThemePlate\Core\Helper\BoxHelper;
use CardanoPress\Dependencies\ThemePlate\Core\Helper\FieldsHelper;
use CardanoPress\Dependencies\ThemePlate\Core\Helper\FormHelper;

class OptionBox extends Form {

	protected string $option_name = '';

	/** @var string[] */
	protected array $menu_pages = array();


	protected function get_handler(): Handler {

		return new OptionHandler();

	}


	/**
	 * @param array{
	 *     context: string,
	 *     data_prefix: string,
	 * } $config
	 */
	protected function initialize( array &$config ): void {
	}


	protected function fields_group_key(): string {

		return $this->option_name;

	}


	protected function maybe_nonce_fields( string $current_id ): void {

		$this->option_name = $current_id;

	}


	public function create(): void {

		$priority = BoxHelper::get_priority( $this->config );

		foreach ( $this->menu_pages as $menu_page ) {
			$section = $menu_page . '_' . $this->config['context'];

			add_filter( 'sanitize_option_' . $menu_page, array( OptionHelpers::class, 'sanitize' ), 10, 2 );
			add_action( 'themeplate_page_' . $menu_page . '_load', array( FormHelper::class, 'enqueue_assets' ) );
			add_action( 'themeplate_settings_' . $section, array( $this, 'layout_postbox' ), $priority );
			add_filter( 'themeplate_setting_' . $menu_page . '_schema', array( $this, 'build_schema' ), $priority );
		}

		if ( did_action( 'init' ) ) {
			$this->register_setting();
		} else {
			add_action( 'init', array( $this, 'register_setting' ) ); // @codeCoverageIgnore
		}

	}


	public function location( string $page ): self {

		$this->menu_pages[] = $page;

		return $this;

	}


	/** @param array<string, Field|mixed> $collection */
	public function fields( array $collection ): self {

		$this->fields = new Fields( $collection );

		return $this;

	}


	public function get_config(): Config {

		return new Config( $this->config['data_prefix'], $this->fields );

	}


	/**
	 * @param array<mixed> $data
	 * @return array<mixed>
	 */
	public function build_schema( array $data ): array {

		if ( ! $this->fields instanceof Fields ) {
			return $data;
		}

		$schema = FieldsHelper::build_schema( $this->fields, $this->config['data_prefix'] );

		return array_merge(
			$data,
			$schema,
		);

	}


	public function register_setting(): void {

		foreach ( $this->menu_pages as $menu_page ) {
			$option = OptionHelpers::schema_default( $menu_page );
			$args   = array(
				'default'      => $option['default'],
				'type'         => 'object',
				'show_in_rest' => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => $option['schema'],
					),
				),
			);

			register_setting( $menu_page, $menu_page, $args );
		}

	}

}
