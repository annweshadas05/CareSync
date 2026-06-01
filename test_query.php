<?php
require 'dbconnect.php';
$query = "SELECT a.id, a.status, a.p_id, a.reason, a.slot_id, u.name as username, t.start_time, t.end_time, t.location, t.doctor_code
    FROM appointments a JOIN time_slots t ON a.slot_id = t.id JOIN users u ON t.doctor_code = u.id
    WHERE a.p_id = 1 AND t.start_time >= NOW() ORDER BY t.start_time ASC LIMIT 50";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo "Prepare failed: " . $conn->error;
} else {
    if (!$stmt->execute()) {
        echo "Execute failed: " . $stmt->error;
    } else {
        $res = $stmt->get_result();
        if (!$res) {
            echo "Get result failed: " . $stmt->error;
        } else {
            echo "Success! Rows: " . $res->num_rows;
            print_r($res->fetch_all(MYSQLI_ASSOC));
        }
    }
}
?>
