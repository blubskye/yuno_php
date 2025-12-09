<?php
/*
    Yuno Gasai. A Discord.JS based bot, with multiple features.
    Copyright (C) 2018 Maeeen <maeeennn@gmail.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see https://www.gnu.org/licenses/.
*/

namespace Yuno\Lib;

/**
 * Configuration manager - loads and manages JSON configuration files
 */
class ConfigManager
{
    private static ?ConfigManager $instance = null;

    private function __construct()
    {
        // Singleton
    }

    public static function init(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Read config file synchronously
     */
    public function readConfigSync(string $file): Config
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("Config file {$file} doesn't exist.");
        }

        $content = file_get_contents($file);

        if ($content === false) {
            throw new \RuntimeException("Failed to read config file {$file}.");
        }

        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Config file {$file} isn't valid JSON: " . json_last_error_msg());
        }

        return new Config($json, $file);
    }

    /**
     * Alias for readConfigSync
     */
    public function readConfig(string $file): Config
    {
        return $this->readConfigSync($file);
    }
}

/**
 * Configuration object with defaults support
 */
class Config
{
    private array $data;
    private ?array $defaults = null;
    private ?string $file;

    public function __construct(array $data, ?string $file = null)
    {
        $this->data = $data;
        $this->file = $file;
    }

    /**
     * Set default values
     */
    public function defaults(array $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * Get a configuration value
     */
    public function get(string $key): mixed
    {
        // First check if key exists in data
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        // Fall back to defaults
        if ($this->defaults !== null && array_key_exists($key, $this->defaults)) {
            return $this->defaults[$key];
        }

        return null;
    }

    /**
     * Get value without considering defaults
     */
    public function getWithoutDefault(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Get default value
     */
    public function getDefault(string $key): mixed
    {
        return $this->defaults[$key] ?? null;
    }

    /**
     * Set a configuration value
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data) ||
               ($this->defaults !== null && array_key_exists($key, $this->defaults));
    }

    /**
     * Get all data as array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Save configuration to file
     */
    public function save(?string $file = null): void
    {
        $file = $file ?? $this->file;

        if ($file === null) {
            return;
        }

        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \RuntimeException("Failed to encode config to JSON.");
        }

        $result = file_put_contents($file, $json);

        if ($result === false) {
            throw new \RuntimeException("Failed to save config to {$file}.");
        }
    }

    /**
     * Get the file path
     */
    public function getFile(): ?string
    {
        return $this->file;
    }
}
