<?php

declare(strict_types=1);

namespace Bow\Testing\Features;

trait SeedingHelper
{
    /**
     * Enable auto seeding
     *
     * @var bool
     */
    protected $seeding = false;

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
