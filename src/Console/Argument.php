<?php

declare(strict_types=1);

namespace Bow\Console;

use Bow\Support\Collection;

class Argument
{
    /**
     * The args collection
     *
     * @var array
     */
    private array $parameters = [];

    /**
     * The invalid parameter
     *
     * @var array
     */
    private array $trash = [];

    /**
     * The command first argument
     * php bow add:controller [target]
     *
     * @var ?string
     */
    private ?string $target = null;

    /**
     * The command first argument
     * php bow [command]:action
     *
     * @var ?string
     */
    private ?string $command = null;

    /**
     * The command first argument
     * php bow command:[action]
     *
     * @var ?string
     */
    private ?string $action = null;

    /**
     * Argument Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->formatParameters();
    }

    /**
     * Format the parameters
     *
     * @return void
     */
    private function formatParameters(): void
    {
        foreach ($GLOBALS['argv'] as $key => $param) {
            if ($key == 0) {
                continue;
            }

            if ($key == 1) {
                $this->initCommand($param);
                continue;
            }

            if ($key == 2 && preg_match('/^[a-z0-9_\/-]+$/i', $param)) {
                $this->target = $param;
                continue;
            }

            if (preg_match('/^-{2}[a-z]+[a-z-]+$/', $param)) {
                $this->parameters[$param] = true;
                continue;
            }

            $param_part = explode('=', $param);

            if (count($param_part) == 2) {
                if (preg_match('/^-{2}[a-z]+[a-z-]+$/', $param_part[0])) {
                    $this->parameters[$param_part[0]] = $param_part[1];
                    continue;
                }
            }

            $this->trash[] = $param;
        }
    }

    /**
     * Retrieves a parameter
     *
     * @param  string $key
     * @param  mixed  $default
     * @return bool|string|null
     */
    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Get the collection of parameter
     *
     * @return Collection
     */
    public function getParameters(): Collection
    {
        return new Collection($this->parameters);
    }

    /**
     * Retrieves the target value
     *
     * @return ?string
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * Retrieves the command value
     *
     * @return ?string
     */
    public function getCommand(): ?string
    {
        return $this->command;
    }

    /**
     * Retrieves the command action
     *
     * @return ?string
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Get the trash content
     *
     * @return array
     */
    public function getTrash(): array
    {
        return $this->trash;
    }

    /**
     * Check if bad parameter have been input
     *
     * @return bool
     */
    public function hasTrash(): bool
    {
        return count($this->trash) > 0;
    }

    /**
     * Initialize main command
     *
     * @param string $param
     * @return void
     */
    private function initCommand(string $param): void
    {
        if (!preg_match('/^[a-z-]+[a-z]+:[a-z-]+[a-z]+$/', $param)) {
            $this->command = $param;
            $this->action = null;
        } else {
            [$this->command, $this->action] = explode(':', $param);
        }
    }

    /**
     * Read line
     *
     * @param  string $message
     * @return bool
     */
    public function readline(string $message): bool
    {
        echo Color::green("$message y/N >>> ");

        $input = strtolower(trim(readline()));

        if (strlen($input) == 0) {
            $input = 'n';
        }

        if (!in_array($input, ['y', 'n'])) {
            echo Color::red('Invalid choice') . "\n";

            return $this->readline($message);
        }

        return strtolower($input) == "y";
    }
}
