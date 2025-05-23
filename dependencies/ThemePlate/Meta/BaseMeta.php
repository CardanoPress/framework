<?php

/**
 * Setup meta boxes
 *
 * @package CardanoPress\Dependencies\ThemePlate
 * @since 0.1.0
 */

namespace CardanoPress\Dependencies\ThemePlate\Meta;

use CardanoPress\Dependencies\ThemePlate\Core\Config;
use CardanoPress\Dependencies\ThemePlate\Core\Fields;
use CardanoPress\Dependencies\ThemePlate\Core\Form;
use CardanoPress\Dependencies\ThemePlate\Core\Handler;
use CardanoPress\Dependencies\ThemePlate\Core\Helper\BoxHelper;
use CardanoPress\Dependencies\ThemePlate\Core\Helper\FieldsHelper;

abstract class BaseMeta extends Form {

	protected int $current_id = 0;


	protected function register(): void {

		if ( did_action( 'init' ) ) {
			$this->register_meta();
		} else {
			add_action( 'init', array( $this, 'register_meta' ) ); // @codeCoverageIgnore
		}

	}


	protected function get_handler(): Handler {

		return new MetaHandler( $this->config['object_type'] );

	}


	protected function fields_group_key(): string {

		return 'themeplate';

	}


	/** @return array{action: string, name: string} */
	protected function get_nonce_data( int $object_id ): array {

		$form_id = $this->config['form_id'];
		$action  = 'save_' . $this->fields_group_key() . '_' . $form_id;
		$name    = $this->fields_group_key() . '_' . $form_id . '_' . $object_id;

		return compact( 'action', 'name' );

	}


	protected function maybe_nonce_fields( string $current_id ): void {

		$data = $this->get_nonce_data( (int) $current_id );

		wp_nonce_field( $data['action'], $data['name'] );

	}


	protected function can_save( int $object_id ): bool {

		if ( ! $this->fields instanceof Fields || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return false;
		}

		$data = $this->get_nonce_data( $object_id );

		return isset( $_POST[ $data['name'] ] ) && wp_verify_nonce( $_POST[ $data['name'] ], $data['action'] );

	}


	protected function save( int $object_id ): void {

		if ( ! $this->fields instanceof Fields ) {
			return;
		}

		$config = $this->config;

		foreach ( $this->fields->get_collection() as $field ) {
			$key = $field->data_key( $this->config['data_prefix'] );

			if ( ! isset( $_POST[ $this->fields_group_key() ][ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				continue;
			}

			$stored  = get_metadata( $config['object_type'], $object_id, $key, ! $field->get_config( 'repeatable' ) );
			$updated = $_POST[ $this->fields_group_key() ][ $key ]; // phpcs:ignore WordPress.Security.NonceVerification

			if ( is_array( $updated ) ) {
				$updated = BoxHelper::prepare_save( $updated );
				$updated = array_filter( $updated );
			}

			if ( $field->get_config( 'repeatable' ) ) {
				delete_metadata( $config['object_type'], $object_id, $key );

				foreach ( $updated as $i => $value ) {
					if ( 'i-x' === $i ) {
						continue;
					}

					add_metadata( $config['object_type'], $object_id, $key, $value );
				}
			} else {
				if ( ! $stored && ! $updated ) {
					continue;
				}

				if ( $stored === $updated ) {
					continue;
				}

				if ( $updated ) {
					update_metadata( $config['object_type'], $object_id, $key, $updated, $stored );
				} else {
					delete_metadata( $config['object_type'], $object_id, $key, $stored );
				}
			}
		}

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


	public function register_meta(): void {

		if ( ! $this->fields instanceof Fields ) {
			return;
		}

		$prefix = $this->config['data_prefix'];
		$types  = property_exists( $this, 'locations' ) ? $this->locations : array( '' );

		add_filter( "default_{$this->config['object_type']}_metadata", array( MetaHelpers::class, 'default' ), 10, 5 );

		foreach ( $this->fields->get_collection() as $field ) {
			$args = FieldsHelper::get_schema( $field );

			unset( $args['default'] );

			$args['show_in_rest'] = array( 'schema' => $args );

			$args['single'] = ! $field->get_config( 'repeatable' );

			foreach ( $types as $type ) {
				$args['object_subtype'] = $type;

				add_filter( "themeplate_{$this->config['object_type']}_meta_{$field->data_key( $prefix )}_schema", array( $this, 'build_schema' ) );

				register_meta( $this->config['object_type'], $field->data_key( $prefix ), $args );
			}
		}

	}

}
