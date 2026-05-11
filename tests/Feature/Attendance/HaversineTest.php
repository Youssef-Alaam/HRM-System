<?php

use App\Support\Haversine;

test('distance between identical points is zero', function () {
    expect(Haversine::distance(30.0444, 31.2357, 30.0444, 31.2357))->toBe(0.0);
});

test('distance between Cairo Downtown and Maadi is ~10 km', function () {
    // Cairo Downtown (~Tahrir):  30.0444, 31.2357
    // Maadi (~Road 9):           29.9602, 31.2569
    $d = Haversine::distance(30.0444, 31.2357, 29.9602, 31.2569);

    // ~9.5 km
    expect($d)->toBeGreaterThan(9_000);
    expect($d)->toBeLessThan(10_500);
});

test('distance between Cairo and Alexandria is ~180 km', function () {
    // Cairo:      30.0444, 31.2357
    // Alexandria: 31.2001, 29.9187
    $d = Haversine::distance(30.0444, 31.2357, 31.2001, 29.9187);

    expect($d)->toBeGreaterThan(175_000);
    expect($d)->toBeLessThan(185_000);
});

test('1 metre east at the equator is approximately 1 metre', function () {
    // 1° at equator ≈ 111,320 metres, so 0.00001° ≈ 1.113 m
    $d = Haversine::distance(0, 0, 0, 0.00001);
    expect($d)->toBeGreaterThan(1.0);
    expect($d)->toBeLessThan(1.5);
});

test('Haversine is symmetric (a→b == b→a)', function () {
    $a = Haversine::distance(30.0, 31.0, 30.1, 31.1);
    $b = Haversine::distance(30.1, 31.1, 30.0, 31.0);

    expect(abs($a - $b))->toBeLessThan(0.001); // floating-point tolerance
});
