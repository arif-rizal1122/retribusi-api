<?php
$phones = [
    '082296104074',
    '082348083809',
    '081341712069',
    '082174013357'
];

foreach ($phones as $phone) {
    echo "$phone: " . password_hash($phone, PASSWORD_BCRYPT) . "\n";
}
