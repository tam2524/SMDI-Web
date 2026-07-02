<?php
require_once 'api/db_config.php';

// Check if user has some minimal authorization (optional but good practice)
// Since this is a one-time utility, we'll just run it.

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Customer Synchronization Tool</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; }
        .log-box { background: #1e2b3c; color: #00ff00; padding: 15px; border-radius: 5px; height: 400px; overflow-y: scroll; font-family: monospace; white-space: pre-wrap; }
        .success-text { color: #2ecc71; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
    <h1>Customer Sync Utility</h1>
    <p>Scanning past transactions to backfill missing customers into the Customer Ledger...</p>
    <div class='log-box'>";

// Get unique customers from transactions that don't exist in customers table
$sql = "
    SELECT DISTINCT t.customer_name, t.from_location as branch, t.category 
    FROM spareparts_transactions t
    WHERE t.customer_name IS NOT NULL 
      AND t.customer_name != ''
      AND NOT EXISTS (
          SELECT 1 FROM spareparts_customers c 
          WHERE c.name = t.customer_name AND c.branch = t.from_location
      )
";

$result = $conn->query($sql);

if ($result) {
    $count = 0;
    $insertStmt = $conn->prepare("INSERT INTO spareparts_customers (name, branch, category) VALUES (?, ?, ?)");
    
    while ($row = $result->fetch_assoc()) {
        $name = $row['customer_name'];
        $branch = $row['branch'];
        // Default category if not specified in transaction
        $category = !empty($row['category']) ? $row['category'] : 'Wholesale'; 
        
        $insertStmt->bind_param("sss", $name, $branch, $category);
        if ($insertStmt->execute()) {
            $count++;
            echo "Added: <strong>" . htmlspecialchars($name) . "</strong> (Branch: $branch, Category: $category)<br>";
        } else {
            echo "<span style='color:red;'>Failed to add: " . htmlspecialchars($name) . " - " . htmlspecialchars($insertStmt->error) . "</span><br>";
        }
    }
    
    echo "</div>";
    echo "<h3 class='success-text'>Done! Successfully synced $count missing customers from previous transactions.</h3>";
    echo "<p>You can now safely delete this file (`sync_customers.php`) from your server.</p>";
} else {
    echo "<span style='color:red;'>Error querying transactions: " . $conn->error . "</span>";
    echo "</div>";
}

echo "
</div>
</body>
</html>";
?>
