<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComplaintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxpayers = Taxpayer::limit(5)->get();
        $admin = User::where('role', 'admin')->first() ?? User::first();

        $categories = [
            'Pelayanan Umum',
            'Sistem / Aplikasi',
            'Kesalahan Penagihan',
            'Sarana Prasarana',
            'Lainnya'
        ];

        $complaints = [
            [
                'category' => 'Pelayanan Umum',
                'complaint_text' => 'Petugas di kantor sangat lama melayani, antrian tidak teratur.',
                'rating' => 2,
                'suggestion_text' => 'Mohon ditambah loket pelayanan.',
                'status' => 'resolved',
                'admin_notes' => 'Telah ditambahkan 2 loket baru sejak 1 Maret 2026.',
                'resolved_by' => $admin->id ?? null,
                'resolved_at' => now()->subDays(5),
            ],
            [
                'category' => 'Sistem / Aplikasi',
                'complaint_text' => 'Aplikasi sering error saat mau upload bukti bayar.',
                'rating' => 1,
                'suggestion_text' => 'Mohon diperbaiki bug pada bagian upload.',
                'status' => 'processing',
                'admin_notes' => 'Sedang dicek oleh tim IT.',
                'resolved_by' => null,
                'resolved_at' => null,
            ],
            [
                'category' => 'Kesalahan Penagihan',
                'complaint_text' => 'Tagihan bulan Februari kenapa muncul dua kali ya?',
                'rating' => 3,
                'suggestion_text' => 'Mohon dicek kembali data penagihannya.',
                'status' => 'pending',
                'admin_notes' => null,
                'resolved_by' => null,
                'resolved_at' => null,
            ],
            [
                'category' => 'Sarana Prasarana',
                'complaint_text' => 'Parkir di kantor Bapenda kurang luas dan berdebu.',
                'rating' => 2,
                'suggestion_text' => 'Mohon diaspal dan diperluas.',
                'status' => 'rejected',
                'admin_notes' => 'Lahan parkir sudah sesuai dengan rencana tata ruang kantor.',
                'resolved_by' => $admin->id ?? null,
                'resolved_at' => now()->subDays(2),
            ]
        ];

        foreach ($complaints as $index => $data) {
            $taxpayer = $taxpayers->get($index % $taxpayers->count());
            
            Complaint::create(array_merge($data, [
                'taxpayer_id' => $taxpayer->id ?? null,
                'name' => $taxpayer->name ?? 'User Anonim ' . ($index + 1),
                'email' => $taxpayer->email ?? 'anon' . ($index + 1) . '@example.com',
                'phone' => $taxpayer->phone ?? '0812345678' . $index,
                'attachments' => [],
            ]));
        }

        // Add some random ones using factory if exists, but we stick to manual for stability
        for ($i = 0; $i < 10; $i++) {
            Complaint::create([
                'taxpayer_id' => null,
                'name' => 'Warga Umum ' . ($i + 1),
                'email' => 'warga' . ($i + 1) . '@example.com',
                'phone' => '0877' . rand(10000000, 99999999),
                'category' => $categories[array_rand($categories)],
                'complaint_text' => 'Contoh pengaduan publik yang dikirim secara anonim nomor ' . ($i + 1),
                'status' => 'pending',
                'attachments' => [],
            ]);
        }
    }
}
