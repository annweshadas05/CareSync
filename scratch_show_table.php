<?php
require_once 'dbconnect.php';
$res = $conn->query("SHOW CREATE TABLE vitals");
if ($res) {
    $row = $res->fetch_assoc();
    echo $row['Create Table'];
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
