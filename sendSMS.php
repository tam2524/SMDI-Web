<?php
require 'twilio-php-8.2.2/src/Twilio/autoload.php';

use Twilio\Rest\Client;

// Twilio credentials
$account_sid = 'ACa2ce3fe4129a21f493e5c4444ba5de61';
$auth_token = '57b3495a4f3751742bde52b57fdabbf5';
$twilio_number = '+12513561918';

$client = new Client($account_sid, $auth_token);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Extract POST data
    $mcbrand = $_POST["mcbrand"];
    $mcmodel = $_POST["mcmodel"];
    $plandatepurchase = $_POST["plandatepurchase"];
    $nearestbranch = $_POST["nearestbranch"];
    $firstname = $_POST["firstname"];
    $middlename = $_POST["middlename"];
    $lastname = $_POST["lastname"];
    $address = $_POST["address"];
    $income_source = $_POST["incomesource"];
    $with_valid_id = $_POST["withvalidid"];
    $mobile_number = $_POST["mobilenumber"];

    // Prepare the message body
    $message_body = " \n INQUIRY!! \n $mcbrand $mcmodel \n Name: $firstname $middlename $lastname \n Income Source: $income_source \n CP Number: $mobile_number ";

    // Fixed recipient number
    $recipient = '+639638044001';

    try {
        $message = $client->messages->create(
            $recipient,
            [
                'from' => $twilio_number,
                'body' => "$message_body"
            ]
        );
        echo '<script>alert("Inquiry submitted and SMS sent!"); window.close();</script>';
    } catch (Exception $e) {
        echo "Failed to send message: " . $e->getMessage();
        error_log("Twilio Error: " . $e->getMessage());
    }
}

header("Location: index.html");
exit();
?>
