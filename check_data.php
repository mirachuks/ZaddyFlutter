<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'zaddy');
if ($mysqli->connect_error) {
    die('DB Error: ' . $mysqli->connect_error);
}

echo "=== Latest Job ===\n";
$result = $mysqli->query('SELECT * FROM jobs ORDER BY id DESC LIMIT 1');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "No jobs found\n";
}

echo "\n=== Latest Job Items ===\n";
$result = $mysqli->query('SELECT * FROM job_items ORDER BY job_id DESC LIMIT 2');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "No items found\n";
}

$countResult = $mysqli->query('SELECT COUNT(*) as cnt FROM jobs');
$countRow = $countResult->fetch_assoc();
echo "\nTotal Jobs: " . $countRow['cnt'] . "\n";

$countResult2 = $mysqli->query('SELECT COUNT(*) as cnt FROM job_items');
$countRow2 = $countResult2->fetch_assoc();
echo "Total JobItems: " . $countRow2['cnt'] . "\n";
