<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Faq;
use App\Models\Customer;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        Faq::create([
            'question' => 'jam berapa buka?',
            'answer' => 'Jam 8 buka, tutup jam 16.30',
        ]);
        Faq::create([
            'question' => 'Barang apa yg kamu jual?',
            'answer' => 'Saya menjual jasa IT untuk manajemen stok barang dan kasir, sistem absensi canggih, chatbot bisnis Kingapp',
        ]);
        Customer::create([
            'email' => 'daniel@gmail.com',
            'name' => 'daniel',
        ]);
    }
}
