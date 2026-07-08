<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiderRegistrationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rider_registration_store_persists_profile_and_guarantors(): void
    {
        $this->markTestIncomplete('Requires a seeded user and full auth context for the registration payload.');
    }
}
