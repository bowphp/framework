<?php

namespace Bow\View;

use Bow\Configuration\Loader as ConfigurationLoader;
use Bow\View\Exception\ViewException;

abstract class EngineAbstract
{
    /**
     * The helper lists
     *
     * @var array
     */
    const HELPERS = [
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
     * @param  string $filename
     * @param  array  $data
     *
     * @return string
     */
    abstract public function render(string $filename, array $data = []): string;

    /**
     * Check the parsed file
     *
     * @param  string $filename
     * @param  bool   $extended
     *
     * @return string
     * @throws ViewException
     */
    protected function checkParseFile(string $filename, bool $extended = true): string
    {
        $tmp_filename = preg_replace('/@|\./', '/', $filename) . $this->config['extension'];

        // Vérification de l'existance du fichier
        if ($this->config['path'] !== null && !file_exists($this->config['path'].'/'.$tmp_filename)) {
            throw new ViewException(
                sprintf(
                    'The view [%s] does not exists. %s/%s',
                    $tmp_filename,
                    $this->config['path'],
                    $filename
                ),
                E_ERROR
            );
        }

        if ($extended) {
            $filename = $tmp_filename;
        }

        return $filename;
    }

    /**
     * Get the engine name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
