<?php

namespace Bow\Console;

use Bow\Support\Collection;

class ArgOption
{
    /**
     * The args collection
     *
     * @var array
     */
    private array $parameters;

    /**
     * The invalid parameter
     *
     * @var array
     */
    private array $trash;

    /**
     * The command first argument
     * php bow add:constroller [target]
     *
     * @var string
     */
    private ?string $target = null;

    /**
     * The command first argument
     * php bow [command]:action
     *
     * @var string
     */
    private ?string $command = null;

    /**
     * The command first argument
     * php bow command:[action]
     *
     * @var string
     */
    private ?string $action = null;

    /**
     * ArgOption Constructor
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

            $param_part = explode('=', $param);
            if (preg_match('/^--[a-z-]+$/', $param)) {
                $this->parameters[$param] = true;
                continue;
            }

            if (count($param_part) == 2) {
                $this->parameters[$param_part[0]] = $param_part[1];
                continue;
            }

            if (count($param_part) > 2) {
                $tmp = $param_part[0];
                $this->parameters[$tmp] = implode("=", array_slice($param_part, 1));
                continue;
            }

            $this->trash[] = $param;
        }
    }

    /**
     * Retrieves a parameter
     *
     * @param  string $key
     * @param  mixed  $default
     * @return ?string
     */
    public function getParameter(string $key, mixed $default = null): ?string
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
     * @return string
     */
    public function getTarget(): ?string
    {
        return $this->target ?? null;
    }

    /**
     * Retrieves the command value
     *
     * @return string
     */
    public function getCommand(): ?string
    {
        return $this->command;
    }

    /**
     * Retrieves the command action
     *
     * @return string
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Initialize main command
     *
     * @param string $param
     * @return void
     */
    private function initCommand(string $param): void
    {
        if (!preg_match('/^[a-z]+:[a-z]+$/', $param)) {
            $this->command = $param;
            $this->action = null;
        } else {
            [$this->command, $this->action] = explode(':', $param);
        }
    }

    /**
     * Read ligne
     *
     * @param  string $message
     * @return bool
     */
    public function readline(string $message): bool
    {
        echo Color::green("$message y/N >>> ");

        $input = strtolower(trim(readline()));

        if (is_null($input) || strlen($input) == 0) {
            $input = 'n';
        }

        if (!in_array($input, ['y', 'n'])) {
            echo Color::red('Invalid choice')."\n";

            return $this->readline($message);
        }

        return strtolower($input) == "y";
    }
}
