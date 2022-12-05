<?php

declare(strict_types=1);

namespace Bow\Testing\Features;

trait FeatureHelper
{
    /**
     * Get fake instance
     *
     * @see https://github.com/fzaninotto/Faker for all documentation
     * @return \Faker\Generator
     */
    public function faker(): \Faker\Generator
    {
        static $faker;

        if (is_null($faker)) {
            $faker = \Faker\Factory::create();
        }

        return $faker;
    }
}
