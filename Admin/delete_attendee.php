<?php
if (!isset($_GET['id'])) {
    header('location:manage_attendee.php');
}

require_once "../dbconnect.php";

$id = $_GET['id'];

$conn->begin_transaction();

try {

    /* DELETE FROM ATTENDEES TABLE */
    $qry1 = "DELETE FROM attendees WHERE attendee_code=?";
    $stmt1 = $conn->prepare($qry1);
    $stmt1->bind_param("s", $id);
    $stmt1->execute();

    /* DELETE FROM USERS TABLE */
    $qry2 = "DELETE FROM users WHERE attendee_code=?";
    $stmt2 = $conn->prepare($qry2);
    $stmt2->bind_param("s", $id);
    $stmt2->execute();

    /* COMMIT BOTH */
    $conn->commit();

    $status = "Success";
    $message = "Attendee Deleted Successfully";
    $color = "success";

} catch (Exception $e) {

    $conn->rollback();

    $status = "Failed";
    $message = "Attendee Not Deleted";
    $color = "danger";
}
?>


<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</head>
<body style="background-color:#2563EB">
  <!-- MODAL POPUP -->
    <div class="modal fade" id="resultModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-<?php echo $color; ?> text-white">
                    <h5 class="modal-title"><?php echo $status; ?></h5>
                </div>
                <div class="modal-body text-center">
                    <h5><?php echo $message; ?></h5>
                    <p class="text-muted">Redirecting to attendee list...</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        var modal = new bootstrap.Modal(document.getElementById('resultModal'));
        modal.show();
        /* AUTO REDIRECT */
        setTimeout(function () {
            window.location = "manage_attendee.php";
        }, 2000);

    </script>
</body>
</html>
