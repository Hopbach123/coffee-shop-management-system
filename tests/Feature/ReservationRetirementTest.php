<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReservationRetirementTest extends TestCase
{
    public function test_reservation_entry_points_are_not_exposed_in_any_workspace(): void
    {
        $this->get('/reservation')->assertNotFound();
        $this->post('/reservation')->assertNotFound();
        $this->get('/reservation/confirmation')->assertNotFound();
        $this->get('/admin/reservations')->assertNotFound();
        $this->get('/staff/reservations')->assertNotFound();
    }
}
