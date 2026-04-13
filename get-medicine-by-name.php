<?php
// Lookup medicine by name (used by OCR auto-fill to get MED_ID).
header('Content-Type: application/json');
include "config.php";

$name = isset($_GET['name']) ? trim($_GET['name']) : '';
if ($name === '') {
    echo json_encode(['found' => false, 'error' => 'No name provided']);
    exit;
}

$nameEsc = mysqli_real_escape_string($conn, $name);

// Try exact match first (case-insensitive)
$sqlExact = "SELECT MED_ID, MED_NAME FROM meds WHERE LOWER(MED_NAME) = LOWER('$nameEsc') LIMIT 1";
$res = mysqli_query($conn, $sqlExact);
if ($res && ($row = mysqli_fetch_row($res))) {
    echo json_encode(['found' => true, 'med_id' => $row[0], 'med_name' => $row[1], 'mode' => 'exact']);
    $conn->close();
    exit;
}

// Fallback to LIKE match (best-effort)
$like = mysqli_real_escape_string($conn, '%' . $name . '%');
$sqlLike = "SELECT MED_ID, MED_NAME FROM meds WHERE MED_NAME LIKE '$like' ORDER BY LENGTH(MED_NAME) ASC LIMIT 1";
$res2 = mysqli_query($conn, $sqlLike);
if ($res2 && ($row2 = mysqli_fetch_row($res2))) {
    echo json_encode(['found' => true, 'med_id' => $row2[0], 'med_name' => $row2[1], 'mode' => 'like']);
} else {
    echo json_encode(['found' => false, 'error' => 'Medicine not found']);
}
$conn->close();

