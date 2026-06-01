<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['patient_id'])) {
    header('location:../login.php');
    exit();
}

$patient_code = $_SESSION['patient_id'];
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $aadhar = trim($_POST['aadhar'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($password !== '' && $password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    $checkEmailStmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND patient_code != ? LIMIT 1');
    $checkEmailStmt->bind_param('ss', $email, $patient_code);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();
    if ($checkEmailResult->num_rows > 0) {
        $errors[] = 'This email is already registered by another account.';
    }
    $checkEmailStmt->close();

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $updatePatients = 'UPDATE patients SET full_name=?, email=?, mobile=?, dob=?, gender=?, aadhar=?, blood_group=?, city=?, address=?';
            $dobValue = $dob !== '' ? $dob : null;
            $types = 'ssssssssss';

            if ($password !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $updatePatients .= ', password=?';
                $types = 'sssssssssss';
            }

            $updatePatients .= ' WHERE patient_code=?';
            $stmt = $conn->prepare($updatePatients);
            if ($password !== '') {
                $stmt->bind_param($types, $full_name, $email, $mobile, $dobValue, $gender, $aadhar, $blood_group, $city, $address, $passwordHash, $patient_code);
            } else {
                $stmt->bind_param($types, $full_name, $email, $mobile, $dobValue, $gender, $aadhar, $blood_group, $city, $address, $patient_code);
            }
            $stmt->execute();
            $stmt->close();

            $updateUsers = 'UPDATE users SET name=?, email=?';
            if ($password !== '') {
                $updateUsers .= ', password=?';
            }
            $updateUsers .= ' WHERE patient_code=?';

            $stmtUser = $conn->prepare($updateUsers);
            if ($password !== '') {
                $stmtUser->bind_param('ssss', $full_name, $email, $passwordHash, $patient_code);
            } else {
                $stmtUser->bind_param('sss', $full_name, $email, $patient_code);
            }
            $stmtUser->execute();
            $stmtUser->close();

            $conn->commit();
            $_SESSION['name'] = $full_name;
            $message = 'Profile updated successfully.';
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Unable to save changes. Please try again.';
        }
    }
}

$select = $conn->prepare('SELECT * FROM patients WHERE patient_code = ? LIMIT 1');
$select->bind_param('s', $patient_code);
$select->execute();
$result = $select->get_result();
$patient = $result->fetch_assoc();
$select->close();

if (!$patient) {
    die('Patient not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    $patient['full_name'] = htmlspecialchars($_POST['full_name'] ?? $patient['full_name']);
    $patient['email'] = htmlspecialchars($_POST['email'] ?? $patient['email']);
    $patient['mobile'] = htmlspecialchars($_POST['mobile'] ?? $patient['mobile']);
    $patient['dob'] = htmlspecialchars($_POST['dob'] ?? $patient['dob']);
    $patient['gender'] = htmlspecialchars($_POST['gender'] ?? $patient['gender']);
    $patient['aadhar'] = htmlspecialchars($_POST['aadhar'] ?? $patient['aadhar']);
    $patient['blood_group'] = htmlspecialchars($_POST['blood_group'] ?? $patient['blood_group']);
    $patient['city'] = htmlspecialchars($_POST['city'] ?? $patient['city']);
    $patient['address'] = htmlspecialchars($_POST['address'] ?? $patient['address']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Patient Profile</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/patient_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
    <style>
        .profile-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .profile-summary .profile-card {
            background: rgba(255,255,255,0.92);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 55px rgba(0,0,0,0.08);
        }
        .profile-detail {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .profile-detail .item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px;
            border-radius: 16px;
            background: #f8fafc;
        }
        .profile-detail .item .label {
            color: #4b5563;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .profile-detail .item .value {
            color: #111827;
            font-weight: 600;
            text-align: right;
        }
        .profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .profile-form .form-control,
        .profile-form .form-select {
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }
        .profile-form .form-label {
            font-weight: 700;
            font-size: 0.85rem;
        }
        .form-note {
            font-size: 0.88rem;
            color: #52525b;
        }
        @media (max-width: 991px) {
            .profile-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="text-center mb-5">
        <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
        <h4 class="text-white fw-bold mt-2" style="font-family: 'Custom';">CareSync</h4>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="patient_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="search_doctor.php"><i data-lucide="search"></i> <span>Search Doctor</span></a>
        <a class="nav-link" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
        <a class="nav-link" href="medical_records.php"><i data-lucide="pill"></i> <span>Medical Records</span></a>
        <a class="nav-link active" href="profile.php"><i data-lucide="user"></i> <span>Profile</span></a>
        <a href="../logout.php" class="nav-link text-danger fw-bold mt-auto"><i data-lucide="log-out"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Patient Profile</h2>
            <p class="text-muted">Manage your personal information and account settings.</p>
        </div>
        <div class="glass-card p-3 d-flex align-items-center gap-3 shadow-sm">
            <i data-lucide="user-check" size="20" class="text-primary"></i>
            <div>
                <div class="small text-muted">Logged in as</div>
                <div class="fw-bold"><?php echo htmlspecialchars(explode(' ', $patient['full_name'])[0]); ?></div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success shadow-sm"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="profile-summary mb-5">
        <div class="profile-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Profile Overview</h5>
                    <p class="text-muted mb-0">Your current patient details.</p>
                </div>
                <button class="btn btn-primary btn-sm" id="editProfileBtn">Edit Profile</button>
            </div>
            <div class="profile-detail">
                <div class="item"><span class="label">Full Name</span><span class="value"><?php echo htmlspecialchars($patient['full_name']); ?></span></div>
                <div class="item"><span class="label">Email</span><span class="value"><?php echo htmlspecialchars($patient['email']); ?></span></div>
                <div class="item"><span class="label">Mobile</span><span class="value"><?php echo htmlspecialchars($patient['mobile']); ?></span></div>
                <div class="item"><span class="label">Date of Birth</span><span class="value"><?php echo htmlspecialchars($patient['dob'] ?: 'Not set'); ?></span></div>
                <div class="item"><span class="label">Gender</span><span class="value"><?php echo htmlspecialchars(ucfirst($patient['gender'] ?: 'Not set')); ?></span></div>
                <div class="item"><span class="label">Blood Group</span><span class="value"><?php echo htmlspecialchars($patient['blood_group'] ?: 'Not set'); ?></span></div>
                <div class="item"><span class="label">Aadhar</span><span class="value"><?php echo htmlspecialchars($patient['aadhar'] ?: 'Not set'); ?></span></div>
                <div class="item"><span class="label">City</span><span class="value"><?php echo htmlspecialchars($patient['city'] ?: 'Not set'); ?></span></div>
                <div class="item"><span class="label">Address</span><span class="value"><?php echo nl2br(htmlspecialchars($patient['address'] ?: 'Not set')); ?></span></div>
            </div>
        </div>

        <div class="profile-card">
            <h5 class="fw-bold mb-4">Edit Profile</h5>
            <form class="profile-form" method="post" id="patientProfileForm" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($patient['full_name']); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="mobile">Mobile</label>
                        <input type="tel" id="mobile" name="mobile" class="form-control" value="<?php echo htmlspecialchars($patient['mobile']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="form-control" value="<?php echo htmlspecialchars($patient['dob']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="gender">Gender</label>
                        <select id="gender" class="form-select" disabled>
                            <option value="male" <?php echo ($patient['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($patient['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo (strtolower($patient['gender']) === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <input type="hidden" name="gender" value="<?php echo htmlspecialchars($patient['gender']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="blood_group">Blood Group</label>
                        <input type="text" id="blood_group" name="blood_group" class="form-control" value="<?php echo htmlspecialchars($patient['blood_group']); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="aadhar">Aadhar</label>
                        <input type="text" id="aadhar" name="aadhar" class="form-control" value="<?php echo htmlspecialchars($patient['aadhar']); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="city">City</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?php echo htmlspecialchars($patient['city']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat new password">
                    </div>
                    <div class="col-12">
                        <div class="form-note">Only fill password fields if you want to change your login password.</div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="cancelEditBtn">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../Bootstrap/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();

    const editBtn = document.getElementById('editProfileBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const profileForm = document.getElementById('patientProfileForm');

    if (editBtn) {
        editBtn.addEventListener('click', () => {
            window.scrollTo({ top: profileForm.offsetTop - 20, behavior: 'smooth' });
            profileForm.querySelector('input, select, textarea').focus();
        });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            window.location.reload();
        });
    }
</script>
</body>
</html>
