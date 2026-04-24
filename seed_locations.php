<?php
/**
 * Attend Ease - Seed Demo Locations
 * 
 * Run once to populate demo check-in locations.
 * 
 * @package AttendEase
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Demo: University of Santo Tomas (Manila, Philippines)
$demoLocations = [
    [
        'name' => 'Main Campus - Central Building',
        'latitude' => 14.6103,
        'longitude' => 120.9892,
        'radius' => 50,
        'description' => 'Main building, 2nd Floor Lecture Hall'
    ],
    [
        'name' => 'Engineering Building - Lab 3',
        'latitude' => 14.6095,
        'longitude' => 120.9905,
        'radius' => 25,
        'description' => 'Computer Engineering Lab, Room 301'
    ],
    [
        'name' => 'Library - Study Room B',
        'latitude' => 14.6110,
        'longitude' => 120.9885,
        'radius' => 30,
        'description' => '2nd Floor Collaborative Study Space'
    ]
];

$inserted = 0;
foreach ($demoLocations as $loc) {
    // Check if already exists
    $existing = dbRow("SELECT id FROM locations WHERE name = ?", "s", [$loc['name']]);
    if ($existing) continue;
    
    $result = dbInsert(
        "INSERT INTO locations (name, latitude, longitude, radius_meters, description, created_by) VALUES (?, ?, ?, ?, ?, ?)",
        "sddisi",
        [$loc['name'], $loc['latitude'], $loc['longitude'], $loc['radius'], $loc['description'], 1]
    );
    if ($result) $inserted++;
}

header('Content-Type: text/plain');
echo "Attend Ease - Location Seeder\n";
echo "=============================\n\n";
echo "Inserted: $inserted new locations\n";
echo "Total locations: " . dbValue("SELECT COUNT(*) FROM locations") . "\n\n";

$all = dbQuery("SELECT * FROM locations ORDER BY name");
foreach ($all as $loc) {
    echo "- {$loc['name']}\n";
    echo "  Coords: {$loc['latitude']}, {$loc['longitude']} ({$loc['radius_meters']}m radius)\n";
    echo "  {$loc['description']}\n\n";
}
