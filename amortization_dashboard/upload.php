<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['pricingFile']) || $_FILES['pricingFile']['error'] != 0) {
    echo json_encode(['success' => false, 'message' => 'File upload failed.']);
    exit;
}

$fileName = 'pricing_data';
$fileInfo = pathinfo($_FILES['pricingFile']['name']);
$fileExtension = strtolower($fileInfo['extension']);
$permanentFilePath = $uploadDir . $fileName . '.' . $fileExtension;

if ($fileExtension != 'xlsx' && $fileExtension != 'csv') {
    echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
    exit;
}

array_map('unlink', glob($uploadDir . $fileName . '.*'));
move_uploaded_file($_FILES['pricingFile']['tmp_name'], $permanentFilePath);

try {
    $spreadsheet = IOFactory::load($permanentFilePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $dataRows = $worksheet->toArray();

    $motorcycles = [];
    $current_brand = '';
    $detected_brands = [];
    
    foreach ($dataRows as $rowIndex => $row) {
        $colA = $row[0] ?? '';
        $colB = $row[1] ?? '';
        $colC = $row[2] ?? '';
        
        if (empty(trim($colA)) && empty(trim($colB)) && empty(trim($colC))) continue;
        
        $colAUpper = strtoupper(trim($colA));
        $isBrandRow = empty(trim($colB)) && 
                     !empty(trim($colA)) && 
                     !isNumericValue($colA) &&
                     preg_match('/^[A-Z\s]{3,20}$/', $colAUpper);
        
        if ($isBrandRow) {
            $current_brand = trim($colA);
            if (!in_array($current_brand, $detected_brands)) {
                $detected_brands[] = $current_brand;
            }
            continue;
        }
        
        $colALower = strtolower($colA);
        $colBLower = strtolower($colB);
        if (strpos($colALower, 'd.price') !== false || 
            strpos($colBLower, 'brand/model') !== false ||
            strpos($colALower, 'dp') !== false) {
            continue;
        }

        if (trim($colB) === 'BELOW' || trim($colA) === 'BELOW' || empty(trim($colB))) {
            continue;
        }
        
        if (isNumericValue($colA) && !empty(trim($colB)) && !empty($current_brand)) {
            $motorcycle = [
                'brand' => $current_brand,
                'model' => trim($colB),
                'd_price' => parseNumericValue($colA), // Column A
                'dp' => parseNumericValue($row[2] ?? 0), // Column C
                'lcp' => parseNumericValue($row[10] ?? 0), // Column K
                'ir' => parseNumericValue($row[15] ?? 2.17), // Column P
                'fee_q' => parseNumericValue($row[16] ?? 0), // Column Q
                'fee_r' => parseNumericValue($row[17] ?? 0), // Column R
                'fee_s' => parseNumericValue($row[18] ?? 0), // Column S
                'rebate' => parseNumericValue($row[20] ?? 0), // Column U
            ];
            
            // Only add if we have valid data
            if (!empty($motorcycle['model']) && $motorcycle['lcp'] > 0) {
                $motorcycles[] = $motorcycle;
            }
        }
    }

    if (empty($motorcycles)) {
        echo json_encode([
            'success' => false,
            'message' => 'No motorcycle data found in file.',
            'debug' => [
                'total_rows' => count($dataRows),
                'detected_brands' => $detected_brands,
                'sample_rows' => array_slice($dataRows, 0, 15)
            ]
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Successfully processed ' . count($motorcycles) . ' motorcycles',
        'rowCount' => count($motorcycles),
        'motorcycles' => $motorcycles,
        'debug' => [
            'detected_brands' => $detected_brands,
            'brand_counts' => array_count_values(array_column($motorcycles, 'brand')),
            'first_5_models' => array_map(function($m) { 
                return $m['brand'] . ' - ' . $m['model']; 
            }, array_slice($motorcycles, 0, 5))
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function isNumericValue($value) {
    if (is_numeric($value)) return true;
    if (is_string($value)) {
        $cleanValue = str_replace([',', '₱', ' '], '', trim($value));
        return is_numeric($cleanValue);
    }
    return false;
}

function parseNumericValue($value) {
    if (is_numeric($value)) {
        return floatval($value);
    }
    if (is_string($value)) {
        $cleanValue = str_replace([',', '₱', ' '], '', trim($value));
        if (is_numeric($cleanValue)) {
            return floatval($cleanValue);
        }
        if (preg_match('/=*(\d+,?\d*\.?\d*)/', $value, $matches)) {
            $formulaValue = str_replace(',', '', $matches[1]);
            return floatval($formulaValue);
        }
    }
    return 0;
}
?>