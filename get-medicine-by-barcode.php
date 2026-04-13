<?php
// Returns medicine details as JSON for barcode or MED_ID lookup (for POS/inventory auto-fill)
header('Content-Type: application/json');
include "config.php";

$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if ($code === '') {
    echo json_encode(['found' => false, 'error' => 'No code provided']);
    exit;
}

// Look up by BARCODE column (if exists) or by MED_ID
$code_esc = mysqli_real_escape_string($conn, $code);

// Try by barcode first (column may not exist on old DBs)
$sql = "SELECT MED_ID, MED_NAME, MED_QTY, CATEGORY, MED_PRICE, LOCATION_RACK FROM meds WHERE MED_ID = '$code_esc' LIMIT 1";

// If meds has BARCODE column, check both
$check = @mysqli_query($conn, "SHOW COLUMNS FROM meds LIKE 'BARCODE'");
if ($check && mysqli_num_rows($check) > 0) {
    $sql = "SELECT MED_ID, MED_NAME, MED_QTY, CATEGORY, MED_PRICE, LOCATION_RACK FROM meds WHERE BARCODE = '$code_esc' OR MED_ID = '$code_esc' LIMIT 1";
} else {
    $sql = "SELECT MED_ID, MED_NAME, MED_QTY, CATEGORY, MED_PRICE, LOCATION_RACK FROM meds WHERE MED_ID = '$code_esc' LIMIT 1";
}

$result = mysqli_query($conn, $sql);

if ($result && $row = mysqli_fetch_row($result)) {
    echo json_encode([
        'found' => true,
        'med_id' => $row[0],
        'med_name' => $row[1],
        'med_qty' => (int)$row[2],
        'category' => $row[3],
        'med_price' => (float)$row[4],
        'location_rack' => $row[5]
    ]);
} else {
    echo json_encode(['found' => false, 'error' => 'Medicine not found']);
}
$conn->close();
