<?php

namespace Database\Factories;

use App\Models\InvitationDetail;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvitationDetail>
 */
class InvitationDetailFactory extends Factory
{
    protected $model = InvitationDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Indonesian female names
        $femaleNames = [
            'Siti Nurhaliza',
            'Dewi Lestari',
            'Putri Anggraini',
            'Aisyah Kamila',
            'Fitri Handayani',
            'Ratna Sari',
            'Maya Puspita',
            'Dian Permata',
            'Ayu Wulandari',
            'Rani Kartika',
        ];

        // Indonesian male names
        $maleNames = [
            'Ahmad Rizki',
            'Budi Santoso',
            'Rizky Firmansyah',
            'Deni Pratama',
            'Eko Wijaya',
            'Farhan Hakim',
            'Gilang Ramadhan',
            'Hendra Kusuma',
            'Indra Gunawan',
            'Joko Susilo',
        ];

        // Indonesian nicknames - female
        $femaleNicknames = ['Siti', 'Dewi', 'Putri', 'Aisyah', 'Fitri', 'Ratna', 'Maya', 'Dian', 'Ayu', 'Rani'];

        // Indonesian nicknames - male
        $maleNicknames = ['Ahmad', 'Budi', 'Rizky', 'Deni', 'Eko', 'Farhan', 'Gilang', 'Hendra', 'Indra', 'Joko'];

        // Indonesian parent names
        $parentPairs = [
            'Bapak Suparman & Ibu Sumiati',
            'Bapak Sutrisno & Ibu Wahyuni',
            'Bapak Bambang Wijaya & Ibu Sri Lestari',
            'Bapak Haryanto & Ibu Endang Rahayu',
            'Bapak Agus Setiawan & Ibu Wulan Sari',
            'Bapak Joko Purnomo & Ibu Siti Maryam',
            'Bapak Adi Nugroho & Ibu Ani Susanti',
            'Bapak Rudi Hartono & Ibu Dewi Kartika',
        ];

        // Indonesian cities and venues
        $venues = [
            ['city' => 'Jakarta', 'venue' => 'Gedung Serbaguna Al-Hikmah', 'address' => 'Jl. Raya Fatmawati No. 123, Jakarta Selatan'],
            ['city' => 'Bandung', 'venue' => 'Balai Pertemuan Saung Angklung', 'address' => 'Jl. Pahlawan No. 45, Bandung'],
            ['city' => 'Surabaya', 'venue' => 'Graha Wisata Convention Hall', 'address' => 'Jl. Ahmad Yani No. 78, Surabaya'],
            ['city' => 'Yogyakarta', 'venue' => 'Pendopo Taman Sari', 'address' => 'Jl. Malioboro No. 56, Yogyakarta'],
            ['city' => 'Semarang', 'venue' => 'Gedung Wanita Semarang', 'address' => 'Jl. Pandanaran No. 34, Semarang'],
            ['city' => 'Medan', 'venue' => 'Balai Resepsi Istana Medan', 'address' => 'Jl. Sisingamangaraja No. 90, Medan'],
            ['city' => 'Makassar', 'venue' => 'Ballroom Grand Clarion', 'address' => 'Jl. Jend. Sudirman No. 12, Makassar'],
            ['city' => 'Denpasar', 'venue' => 'Gedung Kesenian Bali', 'address' => 'Jl. Teuku Umar No. 67, Denpasar'],
        ];

        $brideIndex = $this->faker->numberBetween(0, count($femaleNames) - 1);
        $groomIndex = $this->faker->numberBetween(0, count($maleNames) - 1);
        $venue = $this->faker->randomElement($venues);

        // Generate wedding date (mix of past and future)
        $isPastWedding = $this->faker->boolean(70); // 70% past weddings (for completed orders)
        $weddingDate = $isPastWedding
            ? $this->faker->dateTimeBetween('-6 months', '-1 month')
            : $this->faker->dateTimeBetween('+1 month', '+6 months');

        // Reception date is same day or next day
        $receptionDate = clone $weddingDate;
        if ($this->faker->boolean(30)) { // 30% chance reception is next day
            $receptionDate->modify('+1 day');
        }

        return [
            'order_id' => Order::factory(),
            'bride_full_name' => $femaleNames[$brideIndex],
            'bride_nickname' => $femaleNicknames[$brideIndex],
            'groom_full_name' => $maleNames[$groomIndex],
            'groom_nickname' => $maleNicknames[$groomIndex],
            'bride_parents' => $this->faker->randomElement($parentPairs),
            'groom_parents' => $this->faker->randomElement($parentPairs),
            'akad_date' => $weddingDate->format('Y-m-d'),
            'akad_time' => $this->faker->randomElement(['08:00:00', '09:00:00', '10:00:00', '11:00:00', '13:00:00', '14:00:00']),
            'akad_location' => $venue['venue'].', '.$venue['address'],
            'reception_date' => $receptionDate->format('Y-m-d'),
            'reception_time' => $this->faker->randomElement(['16:00:00', '17:00:00', '18:00:00', '19:00:00', '20:00:00']),
            'reception_location' => $venue['venue'].', '.$venue['address'],
            'gmaps_link' => 'https://maps.google.com/?q='.$venue['city'].'+'.urlencode($venue['venue']),
            'prewedding_photo_path' => null, // Optional - can be added later
        ];
    }

    /**
     * Indicate that the wedding is in the past (for completed orders).
     */
    public function past(): static
    {
        return $this->state(function () {
            $weddingDate = $this->faker->dateTimeBetween('-6 months', '-1 month');
            $receptionDate = clone $weddingDate;

            if ($this->faker->boolean(30)) { // 30% chance reception is next day
                $receptionDate->modify('+1 day');
            }

            return [
                'akad_date' => $weddingDate->format('Y-m-d'),
                'reception_date' => $receptionDate->format('Y-m-d'),
            ];
        });
    }

    /**
     * Indicate that the wedding is in the future (for pending/active orders).
     */
    public function future(): static
    {
        return $this->state(function () {
            $weddingDate = $this->faker->dateTimeBetween('+1 month', '+6 months');
            $receptionDate = clone $weddingDate;

            if ($this->faker->boolean(30)) { // 30% chance reception is next day
                $receptionDate->modify('+1 day');
            }

            return [
                'akad_date' => $weddingDate->format('Y-m-d'),
                'reception_date' => $receptionDate->format('Y-m-d'),
            ];
        });
    }
}
