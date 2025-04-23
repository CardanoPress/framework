<?php

/**
 * @package ThemePlate
 * @since   0.1.0
 */

namespace CardanoPress\Foundation;

use CardanoPress\Dependencies\ThemePlate\Enqueue\CustomData;
use CardanoPress\Dependencies\ThemePlate\Enqueue\Dynamic;
use CardanoPress\Dependencies\ThemePlate\Vite;
use CardanoPress\Interfaces\HookInterface;
use CardanoPress\Interfaces\ManifestInterface;
use CardanoPress\SharedBase;

abstract class AbstractManifest extends SharedBase implements ManifestInterface, HookInterface
{
    protected string $path;
    protected string $version;
    protected CustomData $data;
    protected Dynamic $dynamic;
    protected ?Vite $vite = null;

    public const HANDLE_PREFIX = '';

    public function __construct(string $path, string $version)
    {
        $this->path = trailingslashit($path);
        $this->version = $version;
        $this->data = new CustomData();
        $this->dynamic = new Dynamic();

        if (file_exists(plugin_dir_path($this->path) . Vite::CONFIG)) {
            $this->vite = new Vite(plugin_dir_path($this->path), plugin_dir_url($this->path));
        }

        $this->initialize();
    }

    /** @return array<string, string> */
    protected function readAssetsManifest(string $manifest): array
    {
        if (! file_exists($manifest)) {
            return [];
        }

        $contents = file_get_contents($manifest);

        if (! $contents) {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (! $decoded) {
            return [];
        }

        return $decoded;
    }

    public function setupHooks(): void
    {
        add_action('wp_enqueue_scripts', [$this->data, 'action'], PHP_INT_MAX);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (null === $this->vite) {
            $this->webpackAssets();
        } else {
            $this->viteAssets();
        }
    }

    protected function webpackAssets(): void
    {
        $manifest = $this->path . 'manifest.json';
        $base = plugin_dir_url($manifest);

        foreach ($this->readAssetsManifest($manifest) as $file => $asset) {
            $parts = explode('.', $file);

            if (1 === count($parts) || ! in_array($parts[1], ['js', 'css'])) {
                continue;
            }

            $type = 'js' === $parts[1] ? 'script' : 'style';
            $arg = 'js' === $parts[1] ? true : 'all';
            $func = 'wp_register_' . $type;
            $deps = [];

            if ('script' === $type && 'script' !== $parts[0]) {
                $deps[] = static::HANDLE_PREFIX . 'script';
            }

            $func(static::HANDLE_PREFIX . $parts[0], $base . $asset, $deps, $this->version, $arg);
        }

        $this->dynamic->action();
    }

    protected function viteAssets(): void
    {
        $manifest = plugin_dir_path($this->path) . Vite::CONFIG;
        $manifest = $this->readAssetsManifest($manifest);

        $this->vite->prefix(self::HANDLE_PREFIX);

        foreach ($manifest['entryNames'] ?? [] as $entry => $file) {
            $parts = explode('.', $file);

            if (1 === count($parts) || ! in_array($parts[1], ['ts', 'css'])) {
                continue;
            }

            $type = 'ts' === $parts[1] ? 'script' : 'style';
            $func = 'wp_dequeue_' . $type;
            $handle = $this->vite->$type(
                $entry,
                ('script' === $type && 'script' !== $entry) ? [static::HANDLE_PREFIX . 'script'] : [],
                'ts' === $parts[1] ? ['in_footer' => true] : ['media' => 'all']
            );

            $func($handle);
        }

        $this->dynamic->action();
        $this->vite->action();
    }

    public function enqueueScript(string $handle): void
    {
        $this->dynamic->script($handle);
    }

    public function enqueueStyle(string $handle): void
    {
        $this->dynamic->style($handle);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
