<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function httpRequest($method, $url, $token = null, $data = null) {
    $options = [
        'http' => [
            'method' => $method,
            'header' => "Accept: application/json\r\n",
            'ignore_errors' => true,
        ],
    ];
    if ($token) {
        $options['http']['header'] .= "Authorization: Bearer {$token}\r\n";
    }
    if ($data !== null) {
        $json = json_encode($data);
        $options['http']['header'] .= "Content-Type: application/json\r\n";
        $options['http']['content'] = $json;
    }
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    $status = null;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('#HTTP/\\d+\\.\\d+\\s+(\\d+)#', $header, $m)) {
                $status = (int)$m[1];
                break;
            }
        }
    }
    return ['status' => $status, 'body' => $result, 'headers' => $http_response_header ?? []];
}

function safeJson($string) {
    $decoded = json_decode($string, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['raw' => $string, 'json_error' => json_last_error_msg()];
    }
    return $decoded;
}

function printResult($label, $result) {
    echo "=== {$label} ===\n";
    echo "status: " . ($result['status'] ?? 'null') . "\n";
    $decoded = safeJson($result['body']);
    if (is_array($decoded)) {
        echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        echo $result['body'] . "\n";
    }
    echo "\n";
}

$knownPassword = 'Password123!';
$emails = ['lorduser@gmail.com', 'lordrider@gmail.com', 'benchuks010@gmail.com'];
foreach ($emails as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $user->password = Hash::make($knownPassword);
        $user->status = 'active';
        $user->save();
        echo "Updated password for {$email}\n";
    } else {
        echo "Missing user {$email}\n";
    }
}

$base = 'http://127.0.0.1:8000/api';
$customerLogin = httpRequest('POST', $base . '/login', null, ['email' => 'lorduser@gmail.com', 'password' => $knownPassword]);
printResult('customerLogin', $customerLogin);
$customerData = safeJson($customerLogin['body']);
if (empty($customerData['token'])) {
    exit("Customer login failed\n");
}
$customerToken = $customerData['token'];

$jobPayload = [
    'title' => 'API Simulation Delivery',
    'description' => 'Test delivery job for API simulation',
    'pickup_address' => '1 Test Street',
    'dropoff_address' => '2 Demo Avenue',
    'pickup_lat' => 6.5244,
    'pickup_lng' => 3.3792,
    'dropoff_lat' => 6.5245,
    'dropoff_lng' => 3.3800,
    'price' => 150,
    'platform_charge' => 15,
    'total_price' => 165,
];
$jobCreate = httpRequest('POST', $base . '/jobs', $customerToken, $jobPayload);
printResult('jobCreate', $jobCreate);
$jobData = safeJson($jobCreate['body']);
$jobId = $jobData['job_id'] ?? ($jobData['data']['id'] ?? null);
if (!$jobId) {
    exit('Job creation failed\n');
}

$riderLogin = httpRequest('POST', $base . '/rider/login', null, ['email' => 'lordrider@gmail.com', 'password' => $knownPassword]);
printResult('riderLogin', $riderLogin);
$riderData = safeJson($riderLogin['body']);
if (empty($riderData['token'])) {
    exit('Rider login failed\n');
}
$riderToken = $riderData['token'];

$apply = httpRequest('POST', $base . '/jobs/' . $jobId . '/applications', $riderToken, ['msg' => 'I can deliver this now', 'bid_price' => 150]);
printResult('apply', $apply);
$applyData = safeJson($apply['body']);
$appId = $applyData['data']['id'] ?? null;
if (!$appId) {
    exit('Application creation failed\n');
}

$jobAppsBefore = httpRequest('GET', $base . '/jobs/' . $jobId . '/applications', $customerToken);
printResult('jobApplicationsBefore', $jobAppsBefore);

$accept = httpRequest('PATCH', $base . '/job-applications/' . $appId . '/status', $customerToken, ['status' => 'accepted']);
printResult('accept', $accept);

$jobAppsAfter = httpRequest('GET', $base . '/jobs/' . $jobId . '/applications', $customerToken);
printResult('jobApplicationsAfter', $jobAppsAfter);

$jobDetails = httpRequest('GET', $base . '/jobs/' . $jobId, $customerToken);
printResult('jobDetailsAfterAccept', $jobDetails);

$topup = httpRequest('POST', $base . '/wallet/topup', $customerToken, ['amount' => 200]);
printResult('walletTopup', $topup);

$debit = httpRequest('POST', $base . '/wallet/debit', $customerToken, ['amount' => 150, 'job_id' => $jobId]);
printResult('walletDebit', $debit);

$jobDetailsAfterPay = httpRequest('GET', $base . '/jobs/' . $jobId, $customerToken);
printResult('jobDetailsAfterPay', $jobDetailsAfterPay);

$jobAppsAfterPay = httpRequest('GET', $base . '/jobs/' . $jobId . '/applications', $customerToken);
printResult('jobApplicationsAfterPay', $jobAppsAfterPay);

$notifications = httpRequest('GET', $base . '/notifications', $customerToken);
printResult('notifications', $notifications);
