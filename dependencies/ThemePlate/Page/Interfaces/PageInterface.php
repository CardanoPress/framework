<?php

/**
 * Setup options page
 *
 * @package CardanoPress\Dependencies\ThemePlate
 * @since 0.1.0
 */

namespace CardanoPress\Dependencies\ThemePlate\Page\Interfaces;

interface PageInterface {

	public function capability( string $capability ): static;

	public function title( string $title ): static;

	public function slug( string $slug ): static;

	public function position( int $position ): static;

	public function menu(): void;

}
