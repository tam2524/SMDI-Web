<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory; 


// Helper function to sanitize input
function sanitizeInput($data) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

$action = isset($_REQUEST['action']) ? sanitizeInput($_REQUEST['action']) : '';

switch ($action) {
    case 'get_sales':
        getSales();
        break;
    case 'get_sale':
        getSale();
        break;
    case 'add_sale':
        addSale();
        break;
    case 'update_sale':
        updateSale();
        break;
    case 'delete_sale':
        deleteSales();
        break;
    case 'get_quotas':
        getQuotas();
        break;
    case 'get_quota':
        getQuota();
        break;
    case 'set_quota':
        setQuota();
        break;
    case 'delete_quota':
        deleteQuota();
        break;
    case 'get_summary_report':
        getSummaryReport();
        break;
    case 'upload_sales_data':
        uploadSalesData();     
    break; 
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getSales() {
    global $conn;
    
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : '';
    
    $itemsPerPage = 10;
    $offset = ($page - 1) * $itemsPerPage;
    
    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM sales WHERE 1=1";
    $params = [];
    $types = '';
    
    if (!empty($query)) {
        $sql .= " AND (branch LIKE ? OR brand LIKE ? OR model LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
        $params[] = "%$query%";
        $types .= 'sss';
    }
    
    // Apply sorting
    switch ($sort) {
        case 'date_asc':
            $sql .= " ORDER BY sales_date ASC";
            break;
        case 'date_desc':
            $sql .= " ORDER BY sales_date DESC";
            break;
        case 'branch_asc':
            $sql .= " ORDER BY branch ASC";
            break;
        case 'branch_desc':
            $sql .= " ORDER BY branch DESC";
            break;
        case 'brand_asc':
            $sql .= " ORDER BY brand ASC";
            break;
        case 'brand_desc':
            $sql .= " ORDER BY brand DESC";
            break;
        default:
            $sql .= " ORDER BY sales_date DESC";
            break;
    }
    
    $sql .= " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $itemsPerPage;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $sales = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get total count
    $totalRows = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
    $totalPages = ceil($totalRows / $itemsPerPage);
    
    echo json_encode([
        'success' => true,
        'data' => $sales,
        'totalPages' => $totalPages
    ]);
}

function getSale() {
    global $conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sale = $result->fetch_assoc();
    
    if ($sale) {
        echo json_encode(['success' => true, 'data' => $sale]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
    }
}

function addSale() {
    global $conn;
    
    $sales_date = isset($_POST['sales_date']) ? sanitizeInput($_POST['sales_date']) : '';
    $branch = isset($_POST['branch']) ? sanitizeInput($_POST['branch']) : '';
    $brand = isset($_POST['brand']) ? sanitizeInput($_POST['brand']) : '';
    $model = isset($_POST['model']) ? sanitizeInput($_POST['model']) : '';
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
    
    // Validate input
    if (empty($sales_date) || empty($branch) || empty($brand) || empty($model) || $qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required and quantity must be positive']);
        return;
    }
    
    // Check for duplicate entry
    $checkStmt = $conn->prepare("SELECT id FROM sales WHERE sales_date = ? AND branch = ? AND brand = ? AND model = ?");
    $checkStmt->bind_param('ssss', $sales_date, $branch, $brand, $model);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Duplicate entry: A sale with the same date, branch, brand, and model already exists']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO sales (sales_date, branch, brand, model, qty) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssi', $sales_date, $branch, $brand, $model, $qty);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sale added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding sale: ' . $conn->error]);
    }
}

function updateSale() {
    global $conn;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $sales_date = isset($_POST['sales_date']) ? sanitizeInput($_POST['sales_date']) : '';
    $branch = isset($_POST['branch']) ? sanitizeInput($_POST['branch']) : '';
    $brand = isset($_POST['brand']) ? sanitizeInput($_POST['brand']) : '';
    $model = isset($_POST['model']) ? sanitizeInput($_POST['model']) : '';
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
    
    // Validate input
    if (empty($sales_date) || empty($branch) || empty($brand) || empty($model) || $qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required and quantity must be positive']);
        return;
    }
    
    // Check for duplicate entry (excluding current record)
    $checkStmt = $conn->prepare("SELECT id FROM sales WHERE sales_date = ? AND branch = ? AND brand = ? AND model = ? AND id != ?");
    $checkStmt->bind_param('ssssi', $sales_date, $branch, $brand, $model, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Duplicate entry: A sale with the same date, branch, brand, and model already exists']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE sales SET sales_date = ?, branch = ?, brand = ?, model = ?, qty = ? WHERE id = ?");
    $stmt->bind_param('ssssii', $sales_date, $branch, $brand, $model, $qty, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Sale updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or sale not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating sale: ' . $conn->error]);
    }
}

function deleteSales() {
    global $conn;
    
    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
    
    if (!is_array($ids)) {
        $ids = [$ids];
    }
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'No sales selected for deletion']);
        return;
    }
    
    // Convert all IDs to integers for safety
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    
    $stmt = $conn->prepare("DELETE FROM sales WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    
    if ($stmt->execute()) {
        $deletedCount = $stmt->affected_rows;
        
        if ($deletedCount > 0) {
            echo json_encode(['success' => true, 'message' => "Successfully deleted $deletedCount sale(s)"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No sales found to delete']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting sales: ' . $conn->error]);
    }
}

function getQuotas() {
    global $conn;
    
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    
    $sql = "SELECT * FROM sales_quotas WHERE 1=1";
    $params = [];
    $types = '';
    
    if (!empty($query)) {
        $sql .= " AND (branch LIKE ? OR brand LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
        $types .= 'ss';
    }
    
    $sql .= " ORDER BY year DESC, branch ASC";
    
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $quotas = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $quotas
    ]);
}

function getQuota() {
    global $conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $stmt = $conn->prepare("SELECT * FROM sales_quotas WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quota = $result->fetch_assoc();
    
    if ($quota) {
        echo json_encode(['success' => true, 'data' => $quota]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Quota not found']);
    }
}

function setQuota() {
    global $conn;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
    $branch = isset($_POST['branch']) ? sanitizeInput($_POST['branch']) : '';
    $brand = isset($_POST['brand']) ? sanitizeInput($_POST['brand']) : '';
    $quota = isset($_POST['quota']) ? intval($_POST['quota']) : 0;
    
    // Validate input
    if ($year < 2000 || $year > 2100 || empty($branch) || empty($brand) || $quota <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    if ($id > 0) {
        // Update existing quota
        $stmt = $conn->prepare("UPDATE sales_quotas SET year = ?, branch = ?, brand = ?, quota = ? WHERE id = ?");
        $stmt->bind_param('issii', $year, $branch, $brand, $quota, $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Quota updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes made or quota not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating quota: ' . $conn->error]);
        }
    } else {
        // Insert new quota
        // Check for duplicate first
        $checkStmt = $conn->prepare("SELECT id FROM sales_quotas WHERE year = ? AND branch = ? AND brand = ?");
        $checkStmt->bind_param('iss', $year, $branch, $brand);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'A quota already exists for this year, branch, and brand combination']);
            return;
        }
        
        $stmt = $conn->prepare("INSERT INTO sales_quotas (year, branch, brand, quota) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('issi', $year, $branch, $brand, $quota);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Quota added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding quota: ' . $conn->error]);
        }
    }
}

function deleteQuota() {
    global $conn;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $stmt = $conn->prepare("DELETE FROM sales_quotas WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Quota deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Quota not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting quota: ' . $conn->error]);
    }
}
function getSummaryReport() {
    global $conn;
    
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : 'all';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    
    // Build the query for sales data
    $salesSql = "SELECT branch, brand, model, SUM(qty) as qty FROM sales WHERE YEAR(sales_date) = ?";
    $salesParams = [$year];
    $salesTypes = 'i';
    
    if ($month !== 'all') {
        $salesSql .= " AND MONTH(sales_date) = ?";
        $salesParams[] = $month;
        $salesTypes .= 'i';
    }
    
    if ($branch !== 'all') {
        $salesSql .= " AND branch = ?";
        $salesParams[] = $branch;
        $salesTypes .= 's';
    }
    
    $salesSql .= " GROUP BY branch, brand, model";
    
    $salesStmt = $conn->prepare($salesSql);
    $salesStmt->bind_param($salesTypes, ...$salesParams);
    $salesStmt->execute();
    $salesResult = $salesStmt->get_result();
    $salesData = $salesResult->fetch_all(MYSQLI_ASSOC);
    
    // Get quotas data - branch level only
    $quotasSql = "SELECT branch, quota FROM sales_quotas WHERE year = ?";
    $quotasParams = [$year];
    $quotasTypes = 'i';
    
    if ($branch !== 'all') {
        $quotasSql .= " AND branch = ?";
        $quotasParams[] = $branch;
        $quotasTypes .= 's';
    }
    
    $quotasStmt = $conn->prepare($quotasSql);
    $quotasStmt->bind_param($quotasTypes, ...$quotasParams);
    $quotasStmt->execute();
    $quotasResult = $quotasStmt->get_result();
    $quotasData = $quotasResult->fetch_all(MYSQLI_ASSOC);
    
    // Extract unique brands from sales data
    $brands = array_unique(array_column($salesData, 'brand'));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'sales' => $salesData,
            'quotas' => $quotasData,
            'brands' => array_values($brands) // Include brands array in response
        ]
    ]);
}

function uploadSalesData() {
    global $conn;

    if (isset($_FILES['file']['name']) && isset($_POST['sales_date'])) {
        $fileType = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $sales_date = sanitizeInput($_POST['sales_date']);
        
        // Validate date
        if (empty($sales_date)) {
            echo json_encode(['success' => false, 'message' => 'Sales date is required']);
            return;
        }

        // Check file extension
        if (strtolower($fileType) != "csv") {
            echo json_encode(['success' => false, 'message' => 'Only CSV files are allowed']);
            return;
        }

        $fileName = $_FILES['file']['tmp_name'];
        
        if (!file_exists($fileName)) {
            echo json_encode(['success' => false, 'message' => 'File not found']);
            return;
        }

        $file = fopen($fileName, "r");
        if ($file === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to open file']);
            return;
        }

        // Get header row (branches)
        $header = fgetcsv($file);
        if ($header === false) {
            echo json_encode(['success' => false, 'message' => 'Empty file']);
            return;
        }

        // Remove "MODEL" from header and trim branch names
        array_shift($header);
        $branches = array_map('trim', $header);

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Begin transaction
        $conn->begin_transaction();

        try {
            while (($data = fgetcsv($file)) !== false) {
                // Skip empty rows
                if (empty(array_filter($data))) {
                    continue;
                }

                $model = sanitizeInput(array_shift($data));
                if (empty($model)) {
                    continue;
                }

                // Process each branch's quantity
                foreach ($branches as $index => $branch) {
                    $qty = isset($data[$index]) ? intval($data[$index]) : 0;
                    
                    // Skip if quantity is 0 or empty
                    if ($qty <= 0) {
                        continue;
                    }

                    // Check for duplicate entry
                    $checkStmt = $conn->prepare("SELECT id FROM sales WHERE sales_date = ? AND branch = ? AND model = ?");
                    $checkStmt->bind_param('sss', $sales_date, $branch, $model);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows > 0) {
                        $errors[] = "Duplicate skipped: $sales_date, $branch, $model";
                        $errorCount++;
                        continue;
                    }

                    // Insert the record
                    $stmt = $conn->prepare("INSERT INTO sales (sales_date, branch, brand, model, qty) VALUES (?, ?, 'Suzuki', ?, ?)");
                    $stmt->bind_param('sssi', $sales_date, $branch, $model, $qty);
                    
                    if ($stmt->execute()) {
                        $successCount++;
                    } else {
                        $errors[] = "Error inserting: $sales_date, $branch, $model - " . $conn->error;
                        $errorCount++;
                    }
                }
            }

            $conn->commit();
            
            $message = "Successfully imported $successCount records for $sales_date";
            if ($errorCount > 0) {
                $message .= " with $errorCount errors";
            }
            

            echo json_encode([
                'success' => true,
                'message' => $message,
                'imported' => $successCount,
                'errors' => $errorCount
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ]);
        } finally {
            fclose($file);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sales date and file are required']);
    }
}
?>