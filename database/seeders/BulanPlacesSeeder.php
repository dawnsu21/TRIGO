<?php

namespace Database\Seeders;

use App\Models\Place;
use Illuminate\Database\Seeder;

class BulanPlacesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $places = [
            // Landmarks
            [
                'name' => 'Bulan Public Market',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6714,
                'longitude' => 123.8750,
                'category' => Place::CATEGORY_LANDMARK,
                'is_active' => true,
            ],
            [
                'name' => 'Bulan Port',
                'address' => 'Port Area, Bulan, Sorsogon',
                'latitude' => 12.6700,
                'longitude' => 123.8800,
                'category' => Place::CATEGORY_LANDMARK,
                'is_active' => true,
            ],
            [
                'name' => 'Bulan Town Plaza',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6720,
                'longitude' => 123.8740,
                'category' => Place::CATEGORY_LANDMARK,
                'is_active' => true,
            ],
            [
                'name' => 'Bulan Municipal Hall',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6730,
                'longitude' => 123.8730,
                'category' => Place::CATEGORY_GOVERNMENT,
                'is_active' => true,
            ],

            // Schools
            [
                'name' => 'Bulan National High School',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6740,
                'longitude' => 123.8720,
                'category' => Place::CATEGORY_SCHOOL,
                'is_active' => true,
            ],
            [
                'name' => 'Bulan Central School',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6750,
                'longitude' => 123.8710,
                'category' => Place::CATEGORY_SCHOOL,
                'is_active' => true,
            ],

            // Government Offices
            [
                'name' => 'Bulan Police Station',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6760,
                'longitude' => 123.8700,
                'category' => Place::CATEGORY_GOVERNMENT,
                'is_active' => true,
            ],
            [
                'name' => 'Bulan Rural Health Unit',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6770,
                'longitude' => 123.8690,
                'category' => Place::CATEGORY_GOVERNMENT,
                'is_active' => true,
            ],

            // Barangays (Sample - add more as needed)
            [
                'name' => 'Barangay Poblacion',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6720,
                'longitude' => 123.8740,
                'category' => Place::CATEGORY_BARANGAY,
                'is_active' => true,
            ],
            [
                'name' => 'Barangay Zone 1',
                'address' => 'Zone 1, Bulan, Sorsogon',
                'latitude' => 12.6800,
                'longitude' => 123.8750,
                'category' => Place::CATEGORY_BARANGAY,
                'is_active' => true,
            ],
            [
                'name' => 'Barangay Zone 2',
                'address' => 'Zone 2, Bulan, Sorsogon',
                'latitude' => 12.6810,
                'longitude' => 123.8760,
                'category' => Place::CATEGORY_BARANGAY,
                'is_active' => true,
            ],
            [
                'name' => 'Barangay Zone 3',
                'address' => 'Zone 3, Bulan, Sorsogon',
                'latitude' => 12.6820,
                'longitude' => 123.8770,
                'category' => Place::CATEGORY_BARANGAY,
                'is_active' => true,
            ],

            // Establishments
            [
                'name' => 'Bulan Bus Terminal',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6780,
                'longitude' => 123.8780,
                'category' => Place::CATEGORY_ESTABLISHMENT,
                'is_active' => true,
            ],
            [
                'name' => 'Bulan Church',
                'address' => 'Poblacion, Bulan, Sorsogon',
                'latitude' => 12.6735,
                'longitude' => 123.8735,
                'category' => Place::CATEGORY_LANDMARK,
                'is_active' => true,
            ],
        ];

        foreach ($places as $place) {
            Place::firstOrCreate(
                ['name' => $place['name']],
                $place
            );
        }

        $this->command->info('Bulan places seeded successfully!');
    }
}
