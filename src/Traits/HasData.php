<?php

/**
 * @package ThemePlate
 * @since   0.1.0
 */

namespace CardanoPress\Traits;

use CardanoPress\Dependencies\ThemePlate\Core\Repository;
use CardanoPress\Dependencies\ThemePlate\Core\Config;

trait HasData
{
    protected ?Repository $data = null;

    protected function setData(Repository $data): void
    {
        $this->data = $data;
    }

    protected function getData(): ?Repository
    {
        return $this->data;
    }

    protected function storeConfig(Config $config): void
    {
        if (null === $this->getData()) {
            return;
        }

        $this->getData()->store($config);
    }

    /** @return mixed */
    protected function retrieveValue(string $key, string $id)
    {
        if (null === $this->getData()) {
            return '';
        }

        return $this->getData()->retrieve($key, $id);
    }
}
