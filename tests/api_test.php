<?php

// توکن دسترسی که از دستور ایجاد کاربر تست گرفتیم
$token = '1|v216vbkCAfkPmGQL43GQqrMqDOK5aXKsx4dMcpqw8a903754';

// آدرس پایه API
$baseUrl = 'http://localhost:8000/api';

// لیست API های مالی که باید تست شوند
$apis = [
    'financial/dashboard' => 'GET',
    'financial/bank-cards' => 'GET',
    'financial/gift-cards' => 'GET',
    'financial/transactions' => 'GET',
];

// تابع ارسال درخواست به API
function call_api($url, $method, $token, $data = []) {
    $curl = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ];

    if ($method == 'POST' || $method == 'PUT') {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    }

    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [
        'status' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// تست همه API ها
echo "شروع تست API های مالی...\n";
echo "===================================\n\n";

foreach ($apis as $endpoint => $method) {
    $url = $baseUrl . '/' . $endpoint;
    echo "تست API: $method $url\n";

    $result = call_api($url, $method, $token);

    echo "کد وضعیت: " . $result['status'] . "\n";

    if ($result['status'] == 200) {
        echo "وضعیت: موفقیت‌آمیز ✓\n";
        echo "پاسخ: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "وضعیت: ناموفق ✗\n";
        echo "پاسخ: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "===================================\n\n";
}

// تست افزودن کارت بانکی جدید
echo "تست افزودن کارت بانکی جدید\n";
$url = $baseUrl . '/financial/bank-cards';
$data = [
    'card_number' => '6037991012345678',
    'sheba_number' => 'IR123456789012345678901234',
    'bank_name' => 'بانک ملی ایران',
    'expiry_date' => '2026-12-01',
    'cvv' => '123',
    'set_as_active' => true,
];

$result = call_api($url, 'POST', $token, $data);

echo "کد وضعیت: " . $result['status'] . "\n";

if ($result['status'] == 200 || $result['status'] == 201) {
    echo "وضعیت: موفقیت‌آمیز ✓\n";
    echo "پاسخ: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    // اگر کارت اضافه شد، حالا آن را حذف می‌کنیم
    if (isset($result['response']['data']['card_id'])) {
        $cardId = $result['response']['data']['card_id'];

        echo "\nتست حذف کارت بانکی\n";
        $url = $baseUrl . '/financial/bank-cards/' . $cardId;

        $result = call_api($url, 'DELETE', $token);

        echo "کد وضعیت: " . $result['status'] . "\n";

        if ($result['status'] == 200) {
            echo "وضعیت: موفقیت‌آمیز ✓\n";
            echo "پاسخ: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "وضعیت: ناموفق ✗\n";
            echo "پاسخ: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
} else {
    echo "وضعیت: ناموفق ✗\n";
    echo "پاسخ: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

echo "===================================\n";
echo "تست API های مالی به پایان رسید\n";
