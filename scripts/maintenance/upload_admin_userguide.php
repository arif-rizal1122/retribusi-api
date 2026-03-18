<?php
require 'vendor/autoload.php';

use Cloudinary\Cloudinary;

$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => 'ddhgtgsed',
        'api_key' => '684916711959441',
        'api_secret' => 'J8OsO7AxNTFG1jbSFk1t1RZ2t0s',
    ],
    'url' => [
        'secure' => true
    ]
]);

// Admin userguide images from public/user-guide folder
$images = [
    'dashboard_reporting' => '../retribusi-admin/public/user-guide/dashboard_reporting_1770153649423.png',
    'user_list' => '../retribusi-admin/public/user-guide/user_list_1770153659668.png',
    'add_user_form' => '../retribusi-admin/public/user-guide/add_user_form_1770153671797.png',
    'taxpayer_list_view' => '../retribusi-admin/public/user-guide/taxpayer_list_view_1770153706164.png',
    'add_taxpayer_form' => '../retribusi-admin/public/user-guide/add_taxpayer_form_1770153727175.png',
    'edit_taxpayer_form' => '../retribusi-admin/public/user-guide/edit_taxpayer_form_1770155428850.png',
    'verification_list' => '../retribusi-admin/public/user-guide/verification_list_1770153816385.png',
    'billing_list' => '../retribusi-admin/public/user-guide/billing_list_1770153757542.png',
    'generate_billing_form' => '../retribusi-admin/public/user-guide/generate_billing_form_view_1770153773584.png',
    'reporting_page' => '../retribusi-admin/public/user-guide/reporting_page_1770153833778.png',
    'master_data_jenis' => '../retribusi-admin/public/user-guide/master_data_jenis_1770153857421.png',
    'master_data_klasifikasi' => '../retribusi-admin/public/user-guide/master_data_klasifikasi_1770153877679.png',
    'master_data_zona' => '../retribusi-admin/public/user-guide/master_data_zona_1770153885093.png',
    'master_data_tarif' => '../retribusi-admin/public/user-guide/master_data_tarif_1770153898982.png',
];

$results = [];

foreach ($images as $name => $path) {
    echo "Uploading $name...\n";
    try {
        $result = $cloudinary->uploadApi()->upload($path, [
            'folder' => 'retribusi/userguide/admin',
            'public_id' => $name,
            'overwrite' => true,
        ]);
        $url = $result['secure_url'];
        echo "  ✓ URL: $url\n";
        $results[$name] = $url;
    } catch (Exception $e) {
        echo "  ✗ ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n=== UPLOAD SUMMARY ===\n";
echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
