<?php

namespace Bow\Console\Command;

use Bow\Console\Setting;
use Bow\Console\ArgOption;
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
     * @var ArgOption
     */
    protected ArgOption $arg;

    /**
     * AbstractCommand constructor
     *
     * @param Setting $setting
     * @param ArgOption $arg
     * @return void
     */
    public function __construct(Setting $setting, ArgOption $arg)
    {
        $this->setting = $setting;
        $this->arg = $arg;
        $this->namespaces = $setting->getNamespaces();
    }
}
