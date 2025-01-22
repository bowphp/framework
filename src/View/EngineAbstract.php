<?php

declare(strict_types=1);

namespace Bow\View;

use Bow\View\Exception\ViewException;

abstract class EngineAbstract
{
    /**
     * The helper lists
     *
     * @var array
     */
    protected const HELPERS = [
        'secure' => 'secure',
        'route' => 'route',
        'bow_hash' => 'bow_hash',
        'config' => 'config',
        'faker' => 'faker',
        'env' => 'env',
        'app_mode' => 'app_mode',
        'app_lang' => 'app_lang',
        'flash' => 'flash',
        'cache' => 'cache',
        'encrypt' => 'encrypt',
        'decrypt' => 'decrypt',
        'collect' => 'collect',
        'url' => 'url',
        'input' => 'input',
        'response' => 'response',
        'request' => 'request',
        'sanitize' => 'sanitize',
        'slugify' => 'slugify',
        'str_slug' => 'str_slug',
        'session' => 'session',
        'csrf_token' => 'csrf_token',
        'csrf_field' => 'csrf_field',
        'trans' => 'trans',
        't' => 't',
        '__' => '__',
        'escape' => 'e',
        'old' => 'old',
        "public_path" => "public_path",
        "frontend_path" => "frontend_path",
        "storage_path" => "storage_path",
        "client_locale" => "client_locale",
        "auth" => "auth",
    ];

    /**
     * The template engine name
     *
     * @var string
     */
    protected string $name;

    /**
     * The configuration loader
     *
     * @var array
     */
    protected array $config;

    /**
     * Make template rendering
     *
     * @param string $filename
     * @param array $data
     *
     * @return string
     */
    abstract public function render(string $filename, array $data = []): string;

    /**
     * Get the using engine
     *
     * @return mixed
     */
    abstract public function getEngine(): mixed;

    /**
     * Get the engine name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if the define file exists
     *
     * @param string $filename
     * @return bool
     */
    public function fileExists(string $filename): bool
    {
        $normalized_filename = $this->normalizeFilename($filename);

        return file_exists($this->config['path'] . '/' . $normalized_filename);
    }

    /**
     * Check the parsed file
     *
     * @param string $filename
     * @param bool $extended
     * @return string
     * @throws ViewException
     */
    protected function checkParseFile(string $filename, bool $extended = true): string
    {
        $normalized_filename = $this->normalizeFilename($filename);

        // Check if file exists
        if ($this->config['path'] !== null && !file_exists($this->config['path'] . '/' . $normalized_filename)) {
            throw new ViewException(
                sprintf(
                    'The view [%s] does not exists. %s/%s',
                    $normalized_filename,
                    $this->config['path'],
                    $filename
                )
            );
        }

        return $extended ? $normalized_filename : $filename;
    }

    /**
     * Normalize the file
     *
     * @param string $filename
     * @return string
     */
    private function normalizeFilename(string $filename): string
    {
        return preg_replace('/@|\./', '/', $filename) . '.' . trim($this->config['extension'], '.');
    }
}
