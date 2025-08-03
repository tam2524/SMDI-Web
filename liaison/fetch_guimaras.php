<?php
include 'db_config.php';

$query = "SELECT * FROM inquiries WHERE nearestbranch = 'Guimaras'";
$result = $conn->query($query);


if ($result->num_rows > 0) {
    echo '<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inquiries</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
        <style type="text/css">
            @media print {
                body * {
                    visibility: hidden;
                }
                #printable-content, #printable-content * {
                    visibility: visible;
                }
                #printable-content {
                    position: absolute;
                    left: 0;
                    top: 0;
                }
            }
            /* Add this style for the button */
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

            body {
                margin: 0;
            }
        </style>
    </head>
    <body>
        <div class="container mt-4" id="printable-content">
            <h1 class="text-center mb-4">Guimaras</h1>
            <h4 class= "text-center mb-4">Inquiries as of Today</h4>
            <table class="table table-bordered">
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
                <td>' . $row['firstname'] . '</td>
                <td>' . $row['middlename'] . '</td>
                <td>' . $row['lastname'] . '</td>
                <td>' . $row['address'] . '</td>
                <td>' . $row['incomesource'] . '</td>
                <td>' . $row['withvalidid'] . '</td>
                <td>' . $row['mobilenumber'] . '</td>
                <td>' . $row['mcbrand'] . '</td>
                <td>' . $row['mcmodel'] . '</td>
                <td>' . $row['plandatepurchase'] . '</td>
                <td>' . $row['nearestbranch'] . '</td>
              </tr>';
    }
   echo '</tbody>
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