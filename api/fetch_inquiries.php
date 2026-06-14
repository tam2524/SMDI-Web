<?php
/**
 * fetch_inquiries.php
 * Unified inquiry fetch endpoint — replaces all branch-specific fetch_*.php files.
 *
 * Usage:
 *   fetch_inquiries.php                  → All branches  (was: fetch_AllBranches.php)
 *   fetch_inquiries.php?branch=Ajuy      → Single branch (was: fetch_Ajuy.php)
 *
 * Supported branch values and their display titles:
 *   Ajuy, Antique1, Antique2, Bacolod, balasan, Culasi,
 *   DelgadoSuzuki, EEMSIGuimaras, Guimaras, Jaro1, Jaro2,
 *   KaliboHonda, KaliboSuzuki, KridersRoxas, Passi, Passi2,
 *   PEMDIBacolod, RoxasHonda, RoxasSuzuki
 */

include 'db_config.php';

// ---------------------------------------------------------------------------
// Branch → display-title map (preserves every original title exactly)
// ---------------------------------------------------------------------------
$branchTitles = [
    'Ajuy'          => 'Ajuy',
    'Antique1'      => 'Antique 1',
    'Antique2'      => 'Antique 2',
    'Bacolod'       => 'Bacolod',
    'balasan'       => 'Balasan',
    'Culasi'        => 'Culasi',
    'DelgadoSuzuki' => 'Delgado Suzuki',
    'EEMSIGuimaras' => 'EEMSI Guimaras',
    'Guimaras'      => 'Guimaras',
    'Jaro1'         => 'Jaro 1',
    'Jaro2'         => 'Jaro 2',
    'KaliboHonda'   => 'Kalibo Honda',
    'KaliboSuzuki'  => 'Kalibo Suzuki',
    'KridersRoxas'  => 'Kriders Roxas',
    'Passi'         => 'Passi',
    'Passi2'        => 'Passi 2',
    'PEMDIBacolod'  => 'PEMDI Bacolod',
    'RoxasHonda'    => 'Roxas Honda',
    'RoxasSuzuki'   => 'Roxas 3s Suzuki',
];

// ---------------------------------------------------------------------------
// Determine branch filter from ?branch= query param
// ---------------------------------------------------------------------------
$branchParam = isset($_GET['branch']) ? trim($_GET['branch']) : '';

// Validate: must be empty (all branches) or a known branch key
if ($branchParam !== '' && !array_key_exists($branchParam, $branchTitles)) {
    echo '<p>Invalid branch specified.</p>';
    $conn->close();
    exit;
}

// ---------------------------------------------------------------------------
// Build query
// ---------------------------------------------------------------------------
if ($branchParam === '') {
    // All branches
    $pageTitle   = 'All Branches';
    $showSubtitle = false;
    $sql = "SELECT * FROM inquiries";
    $result = $conn->query($sql);
} else {
    // Specific branch — use prepared statement to be safe
    $pageTitle    = $branchTitles[$branchParam];
    $showSubtitle = true;
    $stmt = $conn->prepare("SELECT * FROM inquiries WHERE nearestbranch = ?");
    $stmt->bind_param("s", $branchParam);
    $stmt->execute();
    $result = $stmt->get_result();
}

// ---------------------------------------------------------------------------
// Render HTML output (identical structure to the original files)
// ---------------------------------------------------------------------------
if ($result->num_rows > 0) {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        @media print {
            body * { visibility: hidden; }
            #printable-content, #printable-content * { visibility: visible; }
            #printable-content { position: absolute; left: 0; top: 0; }
        }
        .print-button-container {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 999;
        }
        .print-button {
            background-color: white;
            color: black;
        }
        body { margin: 0; }
    </style>
</head>
<body>
    <div class="container mt-4" id="printable-content">
        <h1 class="text-center mb-4">' . htmlspecialchars($pageTitle) . '</h1>';

    if ($showSubtitle) {
        echo '<h4 class="text-center mb-4">Inquiries as of Today</h4>';
    }

    echo '<table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th>Address</th>
                    <th>Income Source</th>
                    <th>With Valid ID</th>
                    <th>Mobile Number</th>
                    <th>MC Brand</th>
                    <th>MC Model</th>
                    <th>Plan Date of Purchase</th>
                    <th>Nearest Branch</th>
                </tr>
            </thead>
            <tbody>';

    while ($row = $result->fetch_assoc()) {
        echo '<tr>
                <td>' . htmlspecialchars($row['firstname'])        . '</td>
                <td>' . htmlspecialchars($row['middlename'])       . '</td>
                <td>' . htmlspecialchars($row['lastname'])         . '</td>
                <td>' . htmlspecialchars($row['address'])          . '</td>
                <td>' . htmlspecialchars($row['incomesource'])     . '</td>
                <td>' . htmlspecialchars($row['withvalidid'])      . '</td>
                <td>' . htmlspecialchars($row['mobilenumber'])     . '</td>
                <td>' . htmlspecialchars($row['mcbrand'])          . '</td>
                <td>' . htmlspecialchars($row['mcmodel'])          . '</td>
                <td>' . htmlspecialchars($row['plandatepurchase']) . '</td>
                <td>' . htmlspecialchars($row['nearestbranch'])    . '</td>
              </tr>';
    }

    echo '      </tbody>
            </table>
    </div>
    <div class="print-button-container">
        <button class="btn btn-primary print-button" onclick="window.print()">Print Records</button>
    </div>
</body>
</html>';
} else {
    echo '<p>No records found</p>';
}

$conn->close();
?>
