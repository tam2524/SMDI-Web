<?php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mcbrand = $_POST["mcbrand"];
    $mcmodel = $_POST["mcmodel"];
    $nearestbranch = $_POST["nearestbranch"];
    $plandatepurchase = $_POST["plandatepurchase"];
    $incomesource = $_POST["incomesource"];
    $withvalidid = $_POST["withvalidid"];
    $firstname = $_POST["firstname"];
    $middlename = $_POST["middlename"];
    $lastname = $_POST["lastname"];
    $address = $_POST["address"];
    $mobilenumber = $_POST["mobilenumber"];

    $stmt = $conn->prepare("INSERT INTO inquiries(mcbrand, mcmodel, nearestbranch, plandatepurchase, incomesource, withvalidid, firstname, middlename, lastname, address, mobilenumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssssssss", $mcbrand, $mcmodel, $nearestbranch, $plandatepurchase, $incomesource, $withvalidid, $firstname, $middlename, $lastname, $address, $mobilenumber);

    if ($stmt->execute()) {
        
        echo '<script>alert("Inquiry successful!");</script>';
    } else {
        
        echo "Error: " . $stmt->error;
    }

   
    $stmt->close();
    $conn->close();
}


header("Location: index.html");
exit();

?>
