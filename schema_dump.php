<?php
require 'dbconnect.php';
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    echo "Table: " . $row[0] . "\n";
    $res2 = $conn->query("DESCRIBE " . $row[0]);
    while ($r2 = $res2->fetch_assoc()) {
        echo "  " . $r2['Field'] . " - " . $r2['Type'] . "\n";
    }
}
?>
