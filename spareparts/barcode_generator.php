<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], ['Spareparts-Warehouse', 'Spareparts-Admin', 'Spareparts-Owner'])) {
    header('Location: ../login.html');
    exit();
}
$username = $_SESSION['username'];
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Generator - Spareparts MS</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #004d40;
            --primary-hover: #003d33;
            --bg: #f8fafb;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); padding: 2rem; }
        .card { border-radius: 16px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-header { background: var(--primary); color: white; border-radius: 16px 16px 0 0 !important; padding: 1.2rem; }
        .btn-primary { background: var(--primary); border: none; }
        .btn-primary:hover { background: var(--primary-hover); }
        #barcode-container { margin-top: 2rem; text-align: center; background: white; padding: 2rem; border-radius: 12px; display: none; }
        @media print {
            body * { visibility: hidden; }
            #printable-area, #printable-area * { visibility: visible; }
            #printable-area { position: absolute; left: 0; top: 0; width: 100%; text-align: center; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="container" style="max-width: 600px;">
        <div class="card">
            <div class="card-header overflow-hidden">
                <h5 class="mb-0"><i class="bi bi-upc-scan me-2"></i> Barcode Generator</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Part Number / SKU</label>
                    <input type="text" id="part_no" class="form-control form-control-lg" placeholder="Type or scan part number..." autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Description (Optional)</label>
                    <input type="text" id="part_desc" class="form-control" placeholder="Product name or details">
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-8">
                        <button class="btn btn-primary w-100 btn-lg" onclick="generateBarcode()">
                            <i class="bi bi-gear-fill me-1"></i> Generate
                        </button>
                    </div>
                    <div class="col-4">
                        <button class="btn btn-outline-secondary w-100 btn-lg" onclick="clearAll()">Clear</button>
                    </div>
                    <div class="col-12 mt-2">
                        <a href="warehouse_dashboard.php" class="btn btn-link w-100 text-decoration-none text-muted small">
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div id="barcode-container" class="card mt-4">
            <div id="printable-area">
                <div id="print-desc" class="fw-bold mb-1" style="font-size: 0.9rem;"></div>
                <svg id="barcode"></svg>
            </div>
            <div class="mt-4 no-print border-top pt-3">
                <button class="btn btn-success px-4" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Barcode
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        function generateBarcode() {
            const partNo = $('#part_no').val().trim();
            const desc = $('#part_desc').val().trim();
            
            if (!partNo) {
                alert('Please enter a part number.');
                return;
            }

            try {
                JsBarcode("#barcode", partNo, {
                    format: "CODE128",
                    lineColor: "#000",
                    width: 2,
                    height: 100,
                    displayValue: true,
                    fontSize: 16,
                    margin: 10
                });
                $('#print-desc').text(desc.toUpperCase());
                $('#barcode-container').fadeIn();
            } catch (e) {
                alert('Invalid character for barcode: ' + e.message);
            }
        }

        function clearAll() {
            $('#part_no').val('');
            $('#part_desc').val('');
            $('#barcode-container').hide();
        }

        $('#part_no').on('keypress', function(e) {
            if (e.which == 13) generateBarcode();
        });
    </script>
</body>
</html>
