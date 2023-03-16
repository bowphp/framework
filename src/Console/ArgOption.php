<?php

namespace Bow\Console;

use Bow\Support\Collection;

class ArgOption
{
    /**
     * The args collection
     *
     * @var Collection
     */
    private $options;

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
     * Format the options
     *
     * @return void
     */
    private function formatParameters()
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
                $this->options['target'] = $param;
            } elseif (preg_match('/^--[a-z-]+$/', $param)) {
                $this->options['options'][$param] = true;
            } elseif (count($part = explode('=', $param)) == 2) {
                $this->options['options'][$part[0]] = $part[1];
            } elseif (count($part = explode('=', $param)) > 2) {
                $tmp = $part[0];
                $this->options['options'][$tmp] = implode("=", array_slice($part, 1));
            } else {
                $this->options['trash'][] = $param;
            }
        }

        if (isset($this->options['options'])) {
            $this->options['options'] = new Collection($this->options['options']);
        }
    }

    /**
     * Retrieves a parameter
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed|Collection|null
     */
    public function getParameter($key, $default = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Retrieves the options of the command
     *
     * @param  string $key
     * @param  string $default
     *
     * @return Collection|mixed|null
     */
    public function options($key = null, $default = null)
    {
        $option = $this->getParameter('options', new Collection());

        if ($key == null) {
            return $option;
        }

        return $option->get($key, $default);
    }

    /**
     * Initialize main command
     *
     * @param string $param
     *
     * @return void
     */
    private function initCommand($param)
    {
        if (!preg_match('/^[a-z]+:[a-z]+$/', $param)) {
            $this->options['command'] = $param;

            return;
        }

        $part = explode(':', $param);

        $this->options['command'] = $part[0];

        $this->options['action'] = $part[1];
    }

    /**
     * Read ligne
     *
     * @param  string $message
     * @return bool
     */
    private function readline($message)
    {
        echo Color::green("$message y/N >>> ");

        $input = strtolower(trim(readline()));

        if (is_null($input) || strlen($input) == 0) {
            $input = 'n';
        }

        if (!in_array($input, ['y', 'n'])) {
            echo Color::red('Invalid choice') . "\n";

            return $this->readline($message);
        }

        if (strtolower($input) == "y") {
            return true;
        }

        return false;
    }
}
