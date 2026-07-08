<?php
$pdo = new PDO('sqlite:database/database.sqlite');

echo "=== Latest Job ===\n";
$jobs = $pdo->query('SELECT * FROM jobs ORDER BY id DESC LIMIT 1')->fetchAll(PDO::FETCH_ASSOC);
foreach ($jobs as $job) {
    echo "Job ID: " . $job['id'] . "\n";
    echo "User ID: " . $job['user_id'] . "\n";
    echo "Title: " . $job['title'] . "\n";
    echo "Price: " . $job['price'] . "\n";
    echo "Status: " . $job['status'] . "\n";
}

echo "\n=== Latest Job Items ===\n";
$items = $pdo->query('SELECT * FROM job_items ORDER BY job_id DESC LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
foreach ($items as $item) {
    echo "Item ID: " . $item['id'] . ", Job ID: " . $item['job_id'] . ", Title: " . $item['title'] . ", Category: " . $item['item_category'] . "\n";
}

echo "\nTotal Jobs: " . $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn() . "\n";
echo "Total JobItems: " . $pdo->query('SELECT COUNT(*) FROM job_items')->fetchColumn() . "\n";
