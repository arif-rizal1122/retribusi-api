<?php
require 'vendor/autoload.php';

use Cloudinary\Cloudinary;

$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => 'ddhgtgsed',
        'api_key' => '959653666618869',
        'api_secret' => 'dEf_O5M5tD-dFkYKf83dnSdEjZ0',
    ],
    'url' => [
        'secure' => true
    ]
]);

$images = [
    'login' => '../retribusi-petugas/public/user-guide/login.png',
    'dashboard' => '../retribusi-petugas/public/user-guide/dashboard.png',
    'wp_list' => '../retribusi-petugas/public/user-guide/wp_list.png',
    'wp_form' => '../retribusi-petugas/public/user-guide/wp_form.png',
    'scanner' => '../retribusi-petugas/public/user-guide/scanner.png',
    'master_data' => '../retribusi-petugas/public/user-guide/master_data.png',
    'billing' => '../retribusi-petugas/public/user-guide/billing.png',
    'reporting' => '../retribusi-petugas/public/user-guide/reporting.png',
    'profile' => '../retribusi-petugas/public/user-guide/profile.png',
];

foreach ($images as $name => $path) {
    echo "Uploading $name...\n";
    try {
        $result = $cloudinary->uploadApi()->upload($path, [
            'folder' => 'retribusi/userguide/petugas',
            'public_id' => $name,
            'overwrite' => true,
        ]);
        echo "  URL: " . $result['secure_url'] . "\n";
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}
