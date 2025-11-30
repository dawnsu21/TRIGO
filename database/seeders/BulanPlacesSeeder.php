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

        // All Barangays in Bulan, Sorsogon
        // Note: Coordinates are approximate - may need adjustment based on actual locations
        // Base coordinates: Bulan, Sorsogon (approximately 12.67°N, 123.87°E)
        $baseLat = 12.6714;
        $baseLng = 123.8750;
        
        $barangays = [
            // Urban Zones (Poblacion areas)
            ['name' => 'Zone 1 (Ilawod)', 'lat' => $baseLat + 0.008, 'lng' => $baseLng + 0.000],
            ['name' => 'Zone 2 (Sabang)', 'lat' => $baseLat + 0.009, 'lng' => $baseLng + 0.001],
            ['name' => 'Zone 3 (Central)', 'lat' => $baseLat + 0.010, 'lng' => $baseLng + 0.002],
            ['name' => 'Zone 4 (Central Business District)', 'lat' => $baseLat + 0.000, 'lng' => $baseLng - 0.001],
            ['name' => 'Zone 5 (Canipaan)', 'lat' => $baseLat + 0.011, 'lng' => $baseLng + 0.003],
            ['name' => 'Zone 6 (Baybay)', 'lat' => $baseLat + 0.012, 'lng' => $baseLng + 0.004],
            ['name' => 'Zone 7 (Iraya)', 'lat' => $baseLat + 0.002, 'lng' => $baseLng - 0.003],
            ['name' => 'Zone 8 (Loyo)', 'lat' => $baseLat + 0.003, 'lng' => $baseLng - 0.004],
            
            // Named Barangays
            ['name' => 'A. Bonifacio (Tinurilan)', 'lat' => $baseLat + 0.013, 'lng' => $baseLng + 0.005],
            ['name' => 'Abad Santos (Kambal)', 'lat' => $baseLat + 0.014, 'lng' => $baseLng + 0.006],
            ['name' => 'Aguinaldo (Lipata Dako)', 'lat' => $baseLat + 0.015, 'lng' => $baseLng + 0.007],
            ['name' => 'Antipolo', 'lat' => $baseLat + 0.016, 'lng' => $baseLng + 0.008],
            ['name' => 'Aquino (Imelda)', 'lat' => $baseLat + 0.017, 'lng' => $baseLng + 0.009],
            ['name' => 'Bical', 'lat' => $baseLat + 0.018, 'lng' => $baseLng + 0.010],
            ['name' => 'Beguin', 'lat' => $baseLat + 0.019, 'lng' => $baseLng + 0.011],
            ['name' => 'Bonga', 'lat' => $baseLat + 0.020, 'lng' => $baseLng + 0.012],
            ['name' => 'Butag', 'lat' => $baseLat + 0.021, 'lng' => $baseLng + 0.013],
            ['name' => 'Cadandanan', 'lat' => $baseLat + 0.022, 'lng' => $baseLng + 0.014],
            ['name' => 'Calomagon', 'lat' => $baseLat + 0.023, 'lng' => $baseLng + 0.015],
            ['name' => 'Calpi', 'lat' => $baseLat + 0.024, 'lng' => $baseLng + 0.016],
            ['name' => 'Cocok-Cabitan', 'lat' => $baseLat + 0.025, 'lng' => $baseLng + 0.017],
            ['name' => 'Daganas', 'lat' => $baseLat + 0.026, 'lng' => $baseLng + 0.018],
            ['name' => 'Danao', 'lat' => $baseLat + 0.027, 'lng' => $baseLng + 0.019],
            ['name' => 'Dolos', 'lat' => $baseLat + 0.028, 'lng' => $baseLng + 0.020],
            ['name' => 'E. Quirino (Pinangomhan)', 'lat' => $baseLat + 0.029, 'lng' => $baseLng + 0.021],
            ['name' => 'Fabrica', 'lat' => $baseLat + 0.030, 'lng' => $baseLng + 0.022],
            ['name' => 'G. Del Pilar (Tanga)', 'lat' => $baseLat + 0.031, 'lng' => $baseLng + 0.023],
            ['name' => 'Gate', 'lat' => $baseLat + 0.032, 'lng' => $baseLng + 0.024],
            ['name' => 'Inararan', 'lat' => $baseLat + 0.033, 'lng' => $baseLng + 0.025],
            ['name' => 'J. Gerona (Biton)', 'lat' => $baseLat + 0.034, 'lng' => $baseLng + 0.026],
            ['name' => 'J.P. Laurel (Pon-od)', 'lat' => $baseLat + 0.035, 'lng' => $baseLng + 0.027],
            ['name' => 'Jamorawon', 'lat' => $baseLat + 0.036, 'lng' => $baseLng + 0.028],
            ['name' => 'Libertad (Calle Putol)', 'lat' => $baseLat + 0.037, 'lng' => $baseLng + 0.029],
            ['name' => 'Lajong', 'lat' => $baseLat + 0.038, 'lng' => $baseLng + 0.030],
            ['name' => 'Magsaysay (Bongog)', 'lat' => $baseLat + 0.039, 'lng' => $baseLng + 0.031],
            ['name' => 'Managa-naga', 'lat' => $baseLat + 0.040, 'lng' => $baseLng + 0.032],
            ['name' => 'Marinab', 'lat' => $baseLat + 0.041, 'lng' => $baseLng + 0.033],
            ['name' => 'Nasuje', 'lat' => $baseLat + 0.042, 'lng' => $baseLng + 0.034],
            ['name' => 'Montecalvario', 'lat' => $baseLat + 0.043, 'lng' => $baseLng + 0.035],
            ['name' => 'N. Roque (Calayugan)', 'lat' => $baseLat + 0.044, 'lng' => $baseLng + 0.036],
            ['name' => 'Namo', 'lat' => $baseLat + 0.045, 'lng' => $baseLng + 0.037],
            ['name' => 'Obrero', 'lat' => $baseLat + 0.046, 'lng' => $baseLng + 0.038],
            ['name' => 'Osmeña (Lipata Saday)', 'lat' => $baseLat + 0.047, 'lng' => $baseLng + 0.039],
            ['name' => 'Otavi', 'lat' => $baseLat + 0.048, 'lng' => $baseLng + 0.040],
            ['name' => 'Padre Diaz', 'lat' => $baseLat + 0.049, 'lng' => $baseLng + 0.041],
            ['name' => 'Palale', 'lat' => $baseLat + 0.050, 'lng' => $baseLng + 0.042],
            ['name' => 'Quezon (Cabarawan)', 'lat' => $baseLat + 0.051, 'lng' => $baseLng + 0.043],
            ['name' => 'R. Gerona (Butag)', 'lat' => $baseLat + 0.052, 'lng' => $baseLng + 0.044],
            ['name' => 'Recto', 'lat' => $baseLat + 0.053, 'lng' => $baseLng + 0.045],
            ['name' => 'Roxas (Busay)', 'lat' => $baseLat + 0.054, 'lng' => $baseLng + 0.046],
            ['name' => 'Sagrada', 'lat' => $baseLat + 0.055, 'lng' => $baseLng + 0.047],
            ['name' => 'San Francisco (Polot)', 'lat' => $baseLat + 0.056, 'lng' => $baseLng + 0.048],
            ['name' => 'San Isidro (Cabugaan)', 'lat' => $baseLat + 0.057, 'lng' => $baseLng + 0.049],
            ['name' => 'San Juan Bag-o', 'lat' => $baseLat + 0.058, 'lng' => $baseLng + 0.050],
            ['name' => 'San Juan Daan', 'lat' => $baseLat + 0.059, 'lng' => $baseLng + 0.051],
            ['name' => 'San Rafael (Togbongon)', 'lat' => $baseLat + 0.060, 'lng' => $baseLng + 0.052],
            ['name' => 'San Ramon', 'lat' => $baseLat + 0.061, 'lng' => $baseLng + 0.053],
            ['name' => 'San Vicente', 'lat' => $baseLat + 0.062, 'lng' => $baseLng + 0.054],
            ['name' => 'Santa Remedios', 'lat' => $baseLat + 0.063, 'lng' => $baseLng + 0.055],
            ['name' => 'Santa Teresita (Trece)', 'lat' => $baseLat + 0.064, 'lng' => $baseLng + 0.056],
            ['name' => 'Sigad', 'lat' => $baseLat + 0.065, 'lng' => $baseLng + 0.057],
            ['name' => 'Somagongsong', 'lat' => $baseLat + 0.066, 'lng' => $baseLng + 0.058],
            ['name' => 'Tarhan', 'lat' => $baseLat + 0.067, 'lng' => $baseLng + 0.059],
            ['name' => 'Taromata', 'lat' => $baseLat + 0.068, 'lng' => $baseLng + 0.060],
        ];

        // Add barangays to places array
        foreach ($barangays as $barangay) {
            $places[] = [
                'name' => $barangay['name'],
                'address' => $barangay['name'] . ', Bulan, Sorsogon',
                'latitude' => $barangay['lat'],
                'longitude' => $barangay['lng'],
                'category' => Place::CATEGORY_BARANGAY,
                'is_active' => true,
            ];
        }

        foreach ($places as $place) {
            Place::firstOrCreate(
                ['name' => $place['name']],
                $place
            );
        }

        $this->command->info('Bulan places seeded successfully! Total: ' . count($places) . ' places');
    }
}
