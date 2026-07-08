<?php
$data = [
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test' . date('YmdHis') . '@example.com',
    'mobile_number' => '08012345678',
    'date_of_birth' => '1990-01-01',
    'password' => '12345678',
    'password_confirmation' => '12345678',
    'user_type' => 'user',
];

$ch = curl_init('http://127.0.0.1:51230/api/user/create');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
$res = curl_exec($ch);
if ($res === false) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    echo $res;
}
curl_close($ch);
