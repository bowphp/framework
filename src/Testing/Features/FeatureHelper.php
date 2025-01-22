<?php

declare(strict_types=1);

namespace Bow\Testing\Features;

use Faker\Factory;
use Faker\Generator;

trait FeatureHelper
{
    /**
     * Get fake instance
     *
     * @see https://github.com/fzaninotto/Faker for all documentation
     * @return Generator
     */
    public function faker(): Generator
    {
        static $faker;

        if (is_null($faker)) {
            $faker = Factory::create();
        }

        return $faker;
    }
}
