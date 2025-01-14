<?php

declare(strict_types=1);

namespace Bow\Testing\Features;

trait SeedingHelper
{
    /**
     * Seed alias
     *
     * @param string $seeder
     * @param array $data
     * @return int
     */
    public function seed(string $seeder, array $data = []): int
    {
        return db_seed($seeder, $data);
    }
}
