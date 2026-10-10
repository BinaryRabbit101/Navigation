<?php

use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('cards are sorted into the mini-pc and live tabs by their url', function () {
    Website::create(['title' => 'Tailnet', 'url' => 'https://minipc.jackal-hippocampus.ts.net:444', 'sort_order' => 1]);
    Website::create(['title' => 'LAN', 'url' => 'http://192.168.0.164:96/dead-quiet/index.html', 'sort_order' => 2]);
    Website::create(['title' => 'Public', 'url' => 'https://kitchen.thecorgisquad.com', 'sort_order' => 3]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('sites', 3)
            ->where('sites.0.location', 'minipc')
            ->where('sites.1.location', 'minipc')
            ->where('sites.2.location', 'live'));
});
