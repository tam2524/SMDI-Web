<?php
$conn = new mysqli("127.0.0.1", "mbc-smdi", "mbccreations0331", "smdi_website_db", 3308);
if ($conn->connect_error) { die("Conn failed: " . $conn->connect_error); }

$engine = 'G3V9E-0026195';
$branch = 'JARO-1';
$endDate = '2026-03-31';

$sql = "
    SELECT 
        mi.id, mi.engine_number,
        COALESCE(
            (SELECT it.to_branch 
             FROM inventory_transfers it 
             WHERE it.motorcycle_id = mi.id 
               AND it.transfer_status = 'completed' 
               AND it.transfer_date <= '$endDate'
             ORDER BY it.transfer_date DESC, it.id DESC 
             LIMIT 1),
            (SELECT it.from_branch
             FROM inventory_transfers it
             WHERE it.motorcycle_id = mi.id
               AND it.transfer_status = 'completed'
             ORDER BY it.transfer_date ASC, it.id ASC
             LIMIT 1),
            mi.current_branch
        ) AS branch_as_of_report_date
    FROM motorcycle_inventory mi
    WHERE mi.engine_number = '$engine'
    HAVING branch_as_of_report_date = '$branch'
";

$res = $conn->query($sql);
if ($res && $row = $res->fetch_assoc()) {
    echo "UNIT FOUND WITH SQL:\n";
    print_r($row);
} else {
    echo "UNIT NOT FOUND WITH SQL.\n";
    // Let's see what the branch_as_of_report_date actually is
    $sql2 = "
        SELECT 
            mi.id, mi.engine_number,
            (SELECT it.to_branch 
             FROM inventory_transfers it 
             WHERE it.motorcycle_id = mi.id 
               AND it.transfer_status = 'completed' 
               AND it.transfer_date <= '$endDate'
             ORDER BY it.transfer_date DESC, it.id DESC 
             LIMIT 1) as sub1,
            (SELECT it.from_branch
             FROM inventory_transfers it
             WHERE it.motorcycle_id = mi.id
               AND it.transfer_status = 'completed'
             ORDER BY it.transfer_date ASC, it.id ASC
             LIMIT 1) as sub2,
            mi.current_branch
        FROM motorcycle_inventory mi
        WHERE mi.engine_number = '$engine'
    ";
    $res2 = $conn->query($sql2);
    if ($res2 && $row2 = $res2->fetch_assoc()) {
        echo "Details for exclusion:\n";
        print_r($row2);
    }
}
?>
