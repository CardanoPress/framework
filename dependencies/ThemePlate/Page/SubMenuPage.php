<?php

/**
 * Setup options page
 *
 * @package CardanoPress\Dependencies\ThemePlate
 * @since 0.1.0
 */

namespace CardanoPress\Dependencies\ThemePlate\Page;

use CardanoPress\Dependencies\ThemePlate\Page\Interfaces\SubMenuPageInterface;

class SubMenuPage extends BasePage implements SubMenuPageInterface {

	/** @param array<string, mixed>|array{} $config */
	public function __construct( string $title, string $parent_slug = '', array $config = array() ) {

		if ( '' !== $parent_slug ) {
			_deprecated_argument( __METHOD__, '2.1.0', 'Use the new ' . esc_html( self::class . '::parent()' ) . ' instead.' );
		}

		if ( array() !== $config ) {
			_deprecated_argument( __METHOD__, '2.5.0', 'Use the new ' . esc_html( self::class . '::config()' ) . ' instead.' );

			if ( isset( $config['capability'] ) ) {
				_deprecated_argument( __METHOD__, '2.5.0', 'Use the new ' . esc_html( self::class . '::capability()' ) . ' instead.' );
			}

			if ( isset( $config['menu_title'] ) ) {
				_deprecated_argument( __METHOD__, '2.5.0', 'Use the new ' . esc_html( self::class . '::title()' ) . ' instead.' );
			}

			if ( isset( $config['menu_slug'] ) ) {
				_deprecated_argument( __METHOD__, '2.5.0', 'Use the new ' . esc_html( self::class . '::slug()' ) . ' instead.' );
			}

			if ( isset( $config['position'] ) ) {
				_deprecated_argument( __METHOD__, '2.5.0', 'Use the new ' . esc_html( self::class . '::position()' ) . ' instead.' );
			}
		}

		$config['parent_slug'] = $parent_slug;

		$this->initialize( $title, $config );

	}


	public function parent( string $slug ): self {

		$this->config['parent_slug'] = $slug;

		return $this;

	}


	public function menu(): void {

		$config = $this->config;

		$this->hookname = (string) add_submenu_page(
			// Parent Slug
			$config['parent_slug'],
			// Page Title
			$this->title,
			// Menu Title
			$config['menu_title'],
			// Capability
			$config['capability'],
			// Menu Slug
			$config['menu_slug'],
			// Content Function
			array( $this, 'create' ),
			// Menu Order
			$config['position']
		);

		add_action( 'load-' . $this->hookname, array( $this, 'load' ) );

	}

}
