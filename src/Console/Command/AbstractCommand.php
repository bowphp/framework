<?php

namespace Bow\Console\Command;

use Bow\Console\ArgOption;
use Bow\Console\ConsoleInformation;
use Bow\Console\Setting;

class AbstractCommand
{
    use ConsoleInformation;

     /**
     * Store dirname
     *
     * @var Setting
     */
    protected $setting;

    /**
     * The application namespace
     *
     * @var array
     */
    protected $namespaces;

     /**
     * The Arg Option instance
     *
     * @var ArgOption
     */
    protected $arg;

    /**
     * AbstractCommand constructor
     *
     * @param Setting $setting
     * @param ArgOption $arg
     *
     * @return void
     */
    public function __construct(Setting $setting, ArgOption $arg)
    {
        $this->setting = $setting;

        $this->arg = $arg;

        $this->namespaces = $setting->getNamespaces();
    }
}
