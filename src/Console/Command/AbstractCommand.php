<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\Setting;
use Bow\Console\Argument;
use Bow\Console\Traits\ConsoleTrait;

abstract class AbstractCommand
{
    use ConsoleTrait;

     /**
     * Store dirname
     *
     * @var Setting
     */
    protected Setting $setting;

    /**
     * The application namespace
     *
     * @var array
     */
    protected array $namespaces;

     /**
     * The Arg Option instance
     *
     * @var Argument
     */
    protected Argument $arg;

    /**
     * AbstractCommand constructor
     *
     * @param Setting $setting
     * @param Argument $arg
     * @return void
     */
    public function __construct(Setting $setting, Argument $arg)
    {
        $this->setting = $setting;
        $this->arg = $arg;
        $this->namespaces = $setting->getNamespaces();
    }
}
