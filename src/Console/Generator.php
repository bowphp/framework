<?php

declare(strict_types=1);

namespace Bow\Console;

use Bow\Console\Traits\ConsoleTrait;

class Generator
{
    use ConsoleTrait;

    /**
     * The base directory where that are going to generate
     *
     * @var string
     */
    private string $base_directory;

    /**
     * The generate name
     *
     * @var string
     */
    private string $name;

    /**
     * GeneratorCommand constructor
     *
     * @param string $base_directory
     * @param string $name
     */
    public function __construct(string $base_directory, string $name)
    {
        $this->base_directory = $base_directory;
        $this->name = $name;
    }

    /**
     * Check if filename is valid
     *
     * @param string|null $filename
     */
    public function filenameIsValid(?string $filename): void
    {
        if (is_null($filename)) {
            echo Color::red('The file name is invalid.');

            exit(1);
        }
    }

    /**
     * Check if controller exists
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        $this->filenameIsValid($this->name);

        return file_exists($this->getPath()) || is_dir($this->base_directory . "/" . $this->name);
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->base_directory . "/" . $this->name . ".php";
    }

    /**
     * Check if controller exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        $this->filenameIsValid($this->name);

        return file_exists($this->getPath());
    }

    /**
     * Write file
     *
     * @param string $type
     * @param array $data
     * @return bool
     */
    public function write(string $type, array $data = []): bool
    {
        $dirname = dirname($this->name);

        if (!is_dir($this->base_directory)) {
            @mkdir($this->base_directory, 0777, true);
        }

        if ($dirname != '.') {
            @mkdir($this->base_directory . '/' . trim($dirname, '/'), 0777, true);

            $namespace = '\\' . str_replace('/', '\\', ucfirst(trim($dirname, '/')));
        } else {
            $namespace = '';
        }

        // Transform class to match the PSR-2 standard
        $classname = ucfirst(
            \Bow\Support\Str::camel(basename($this->name))
        );

        // Create the stub parsed content
        $template = $this->makeStubContent($type, array_merge([
            'namespace' => $namespace,
            'className' => $classname
        ], $data));

        return (bool) file_put_contents($this->getPath(), $template);
    }

    /**
     * Stub render
     *
     * @param string $type
     * @param array $data
     * @return string
     */
    public function makeStubContent(string $type, array $data = []): string
    {
        $content = file_get_contents(__DIR__ . '/stubs/' . $type . '.stub');

        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', (string) $value, $content);
        }

        return $content;
    }

    /**
     * Set writing filename
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the base directory
     *
     * @param string $base_directory
     */
    public function setBaseDirectory(string $base_directory): void
    {
        $this->base_directory = $base_directory;
    }
}
