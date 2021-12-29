<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Faker\Factory as Faker;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected $faker;

    public function setUp() : void
    {
        parent::setUp();

        $this->faker = Faker::create();
    }

    public function tearDown() : void
    {
        parent::tearDown();
    }
}
