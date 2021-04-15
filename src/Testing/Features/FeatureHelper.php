<?php

namespace Bow\Testing\Features;

trait FeatureHelper
{
    /**
     * Get fake instance
     *
     * @see https://github.com/fzaninotto/Faker for all documentation
     * @return \Faker\Generator;
     */
    public function faker()
    {
        static $faker;

        if (is_null($faker)) {
            $faker = \Faker\Factory::create();
        }

        return $faker;
    }

    /**
     * Seed alias
     *
     * @param string $seeder
     * @param array $data
     * @return int
     */
    public function seed($seeder, array $data = [])
    {
        return seed($seeder, $data);
    }
}
