<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// --- Check if we're just looking for existing data ---
if (isset($_POST['check_existing']) && $_POST['check_existing'] === 'true') {
    $data_files = glob('uploads/pricing_data.*');
    if (empty($data_files)) {
        echo json_encode(['success' => false, 'message' => 'No pricing file found.']);
        exit;
    }

    try {
        $spreadsheet = IOFactory::load($data_files[0]);
        $dataRows = $spreadsheet->getActiveSheet()->toArray();
        
        $motorcycles = parseMotorcycleData($dataRows);
        
        if (!empty($motorcycles)) {
            echo json_encode([
                'success' => true, 
                'motorcycles' => $motorcycles,
                'count' => count($motorcycles),
                'message' => 'Found existing pricing data'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No valid motorcycle data found in file']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error reading file: ' . $e->getMessage()]);
    }
    exit;
}

// --- Main logic for calculations ---
$data_files = glob('uploads/pricing_data.*');
if (empty($data_files)) {
    echo json_encode(['success' => false, 'message' => 'No pricing file found. Please upload first.']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($data_files[0]);
    $dataRows = $spreadsheet->getActiveSheet()->toArray();
    
    $motorcycles = parseMotorcycleData($dataRows);
    
    if (empty($motorcycles)) {
        echo json_encode(['success' => false, 'message' => 'No valid motorcycle data found in the pricing file.']);
        exit;
    }
    
    // Return motorcycles for the calculator (when no specific calculation is requested)
    $model_name = $_POST['model'] ?? null;
    $downpayment = $_POST['downpayment'] ?? null;
    
    // If no specific calculation, just return the motorcycle list
    if (!$model_name && !$downpayment) {
        echo json_encode([
            'success' => true, 
            'motorcycles' => $motorcycles,
            'count' => count($motorcycles)
        ]);
        exit;
    }
    
    // Validate calculation parameters
    if (!$model_name) {
        echo json_encode(['success' => false, 'message' => 'Model name is required.']);
        exit;
    }
    
    if (!is_numeric($downpayment)) {
        echo json_encode(['success' => false, 'message' => 'Valid down payment amount is required.']);
        exit;
    }
    
    $downpayment = floatval($downpayment);
    
    // Find the selected motorcycle
    $selected_motorcycle = null;
    foreach ($motorcycles as $motorcycle) {
        if ($motorcycle['model'] === $model_name) {
            $selected_motorcycle = $motorcycle;
            break;
        }
    }
    
    if (!$selected_motorcycle) {
        echo json_encode([
            'success' => false, 
            'message' => 'Motorcycle model not found in the data file.',
            'debug' => [
                'searched_model' => $model_name,
                'available_models' => array_map(function($m) { 
                    return $m['brand'] . ' - ' . $m['model']; 
                }, $motorcycles),
                'total_motorcycles' => count($motorcycles)
            ]
        ]);
        exit;
    }

    // Perform calculation
    $lcp = $selected_motorcycle['lcp'];
    $interest_rate = $selected_motorcycle['ir'];
    $amount_financed = $lcp - $downpayment;
    
    if ($amount_financed < 0) {
        echo json_encode(['success' => false, 'message' => 'Down payment cannot exceed the List Cash Price.']);
        exit;
    }

    $terms = [6, 12, 18, 24, 30, 36];
    $amortization_results = [];
    
    foreach ($terms as $term) {
        $base_payment = ($amount_financed * (1 + ($interest_rate * $term / 100))) / $term;
        
        if ($term == 6) {
            $fixed_fees = $selected_motorcycle['fee_q'] + $selected_motorcycle['fee_r'];
        } else {
            $fixed_fees = $selected_motorcycle['fee_q'] + $selected_motorcycle['fee_s'];
        }
        
        $monthly_payment = $base_payment + $fixed_fees;
        $amortization_results[$term] = round($monthly_payment);
    }
    
    echo json_encode([
        'success'         => true,
        'model'           => $selected_motorcycle['model'],
        'brand'           => $selected_motorcycle['brand'],
        'lcp'             => $lcp,
        'downpayment'     => $downpayment,
        'amount_financed' => $amount_financed,
        'interest_rate'   => $interest_rate,
        'fee_q'           => $selected_motorcycle['fee_q'],
        'fee_r'           => $selected_motorcycle['fee_r'],
        'fee_s'           => $selected_motorcycle['fee_s'],
        'amortization'    => $amortization_results
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error reading file: ' . $e->getMessage()]);
}

// Helper function to parse motorcycle data from spreadsheet rows
function parseMotorcycleData($dataRows) {
    $motorcycles = [];
    $current_brand = '';
    
    foreach ($dataRows as $row) {
        $colA = $row[0] ?? '';
        $colB = $row[1] ?? '';
        
        if (empty(trim($colA)) && empty(trim($colB))) continue;
        
        if (in_array(strtoupper(trim($colA)), ['SUZUKI', 'KAWASAKI', 'YAMAHA']) && empty(trim($colB))) {
            $current_brand = trim($colA);
            continue;
        }
        
        if (strpos($colA, 'D.Price') !== false || strpos($colB, 'BRAND/MODEL') !== false) {
            continue;
        }
        
        $isPriceLike = isNumericValue($colA);
        if ($isPriceLike && !empty(trim($colB)) && !empty($current_brand)) {
            $lcp_value = $row[10] ?? 0;
            
            $motorcycles[] = [
                'brand' => $current_brand,
                'model' => trim($colB),
                'd_price' => parseNumericValue($colA),
                'dp' => parseNumericValue($row[2] ?? 0),
                'lcp' => parseNumericValue($lcp_value),
                'ir' => parseNumericValue($row[15] ?? 2.17),
                'fee_q' => parseNumericValue($row[16] ?? 0),
                'fee_r' => parseNumericValue($row[17] ?? 0),
                'fee_s' => parseNumericValue($row[18] ?? 0),
            ];
        }
    }
    
    return $motorcycles;
}

// Helper functions
function isNumericValue($value) {
    if (is_numeric($value)) return true;
    if (is_string($value)) {
        $cleanValue = str_replace(',', '', trim($value));
        return is_numeric($cleanValue);
    }
    return false;
}

function parseNumericValue($value) {
    if (is_numeric($value)) {
        return floatval($value);
    }
    if (is_string($value)) {
        $cleanValue = str_replace(',', '', trim($value));
        if (is_numeric($cleanValue)) {
            return floatval($cleanValue);
        }
        if (preg_match('/=*(\d+,?\d*)/', $value, $matches)) {
            $formulaValue = str_replace(',', '', $matches[1]);
            return floatval($formulaValue);
        }
    }
    return 0;
}
?>