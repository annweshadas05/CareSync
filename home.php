<?php
require_once "./dbconnect.php";
require_once "./contact_mail.php";

$success = false; 
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    $qry = "INSERT INTO contact(name,email,message) VALUES(?,?,?)";
    $stmt = $conn->prepare($qry);
    $stmt->bind_param("sss", $name, $email, $message);
    $res = $stmt->execute();

    if ($res) {
        $subject = "New Contact Message";
        $body = "
        <h3>New Message Received</h3>
        <b>Name:</b> $name <br><br>
        <b>Email:</b> $email <br><br>
        <b>Message:</b><br>$message
        ";

        sendMail("caresyncbbsr@gmail.com", "CareSync Admin", $subject, $body, $email, $name);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Smart Digital Healthcare</title>
    <link rel="stylesheet" href="./Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="styles/home.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light custom-navbar sticky-top">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center" >
                <img src="./Assets/CareSyncLogo.png" alt="Logo" class="logo-img">
                <span class="text-light">CareSync</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav ms-auto align-items-center">
                    <a class="nav-link active" href="#">Home</a>
                    <a class="nav-link" href="#services">Services</a>
                    <a class="nav-link" href="#contact">Contact</a>
                    <a class="nav-link login-nav-btn" href="/CARESYNC/login.php">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 text-center text-lg-start">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3 rounded-pill fw-bold">Smart Digital Healthcare</span>
                    <h1 class="display-4 fw-bold mb-4">Digitizing Prescriptions. <br><span class="text-primary">Simplifying Healthcare.</span></h1>
                    <p class="hero-lead fw-bold mb-5">
                        CareSync transforms traditional medical records into secure, organized digital data. 
                        We help doctors and patients access vital information anywhere, anytime.
                    </p>
                    <div class="d-flex justify-content-center justify-content-lg-start gap-3">
                        <a href="#" class="btn btn-primary book-btn btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">Book Appointment</a>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0 text-center">
                    <img src="./Assets/Hero.jpg" alt="Healthcare" class="img-fluid hero-img">
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" id="services">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h6 class="text-primary fw-bold text-uppercase tracking-widest">Departments</h6>
                <h2 class="display-6 fw-bold">Our Medical Services</h2>
                <div class="title-underline mx-auto"></div>
            </div>

            <div class="row g-4">
                <?php
                $services = [
                    ['icon' => 'doctor.png', 'title' => 'General Medicine', 'desc' => 'Primary healthcare for routine checkups.'],
                    ['icon' => 'cardiology.png', 'title' => 'Cardiology', 'desc' => 'Advanced heart care with modern diagnostics.'],
                    ['icon' => 'brain-icon.png', 'title' => 'Neurology', 'desc' => 'Specialized treatment for neurological disorders.'],
                    ['icon' => 'lab-icon.png', 'title' => 'Diagnostics', 'desc' => 'Lab tests and digital reports.'],
                    ['icon' => 'pediatrics.png', 'title' => 'Pediatrics', 'desc' => 'Comprehensive healthcare for children.'],
                    ['icon' => 'orthopedist.png', 'title' => 'Orthopedics', 'desc' => 'Treatment for bone and muscle injuries.'],
                    ['icon' => 'pediatrician.png', 'title' => 'Gynecology', 'desc' => "Care for women's reproductive health."],
                    ['icon' => 'emergency.png', 'title' => 'Emergency Care', 'desc' => '24/7 medical services for critical conditions.']
                ];
                foreach ($services as $s): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 service-card text-center border-0">
                            <div class="icon-wrapper mx-auto">
                                <img src="icons/<?= $s['icon'] ?>" width="35" alt="icon">
                            </div>
                            <h5 class="fw-bold mb-3"><?= $s['title'] ?></h5>
                            <p class="text-muted fw-bold small mb-0"><?= $s['desc'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="contact" class="py-5 bg-light">
        <div class="container py-5">
            <div class="contact-card row g-0 shadow-lg">
                <div class="col-lg-5 contact-info">
                    <h2 class="text-white mb-4">Let's Connect</h2>
                    <p class="mb-5 opacity-75">Our team is here to help you anytime.</p>
                    <div class="contact-detail mb-3">📍 Bhubaneswar, Odisha, India</div>
                    <div class="contact-detail mb-3">📞 +91 98765 43210</div>
                    <div class="contact-detail mb-5">✉️ caresyncbbsr@gmail.com</div>
                    <div class="rounded-4 overflow-hidden" style="height: 200px;">
                         <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d119743.40927376042!2d85.75041285260195!3d20.301024345398246!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a1909d2a5170341%3A0x58767e74443bc202!2sBhubaneswar%2C%20Odisha!5e0!3m2!1sen!2sin!4v1700000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
                <div class="col-lg-7 contact-right">
                    <h4 class="mb-4 fw-bold">Send us a Message</h4>
                    <form method="post">
                        <input type="text" name="name" class="form-control-custom" placeholder="Full Name" required>
                        <input type="email" name="email" class="form-control-custom" placeholder="Email Address" required>
                        <textarea name="message" class="form-control-custom" rows="5" placeholder="Your Message" required></textarea>
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="row gy-5">
                <div class="col-lg-4">
                    <h4 class="footer-title">CareSync</h4>
                    <p class="pe-lg-5 text-white-50 mb-4">
                        Smart digital healthcare solution helping doctors and patients manage medical data efficiently and securely.
                    </p>
                    <div class="social-icons d-flex gap-3">
                        <a href="#" class="social-link">
                            <img src="icons/facebook.png" alt="Facebook">
                        </a>
                        <a href="#" class="social-link">
                            <img src="icons/social.png" alt="Instagram">
                        </a>
                        <a href="#" class="social-link">
                            <img src="icons/twitter.jpeg" alt="Twitter" class="rounded-circle">
                        </a>
                        <a href="#" class="social-link">
                            <img src="icons/linkedin.jpeg" alt="LinkedIn" class="rounded-circle">
                        </a>
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <h6 class="footer-title">Quick Links</h6>
                    <div class="footer-links">
                        <a href="#home">Home</a>
                        <a href="#services">Services</a>
                        <a href="#contact">Contact</a>
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <h6 class="footer-title">Legal</h6>
                    <div class="footer-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <h6 class="footer-title">Newsletter</h6>
                    <p class="small text-white-50 mb-3">Subscribe for health tech updates.</p>
                    <div class="input-group mb-3">
                        <input type="email" class="form-control bg-dark border-0 text-white p-3" placeholder="Your Email">
                        <button class="btn btn-primary px-4" type="button">Go</button>
                    </div>
                </div>
            </div>
            <div class="pt-5 mt-5 border-top border-secondary text-center small text-white-50">
                © 2026 CareSync. All Rights Reserved.
            </div>
        </div>
    </footer>
    <div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content care-modal">

            <div class="modal-header custom-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <img src="/CARESYNC/Assets/CareSyncLogo.png" alt="Logo" class="logo-circle me-2">
                    <h5 class="modal-title h6 mb-0 text-white">CareSync</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center p-4 p-md-5">
                <h4 class="fw-bold mb-3">Book Appointment</h4>
                
                <p class="question-text mb-2">
                    Have you registered with us?
                </p>

                <p class="info-text mb-4">
                    Login to continue or create a new account to book your appointment.
                </p>

                <div class="d-flex gap-3 justify-content-center flex-column flex-sm-row">
                    <a href="./login.php" class="btn login-btn py-3 px-4 flex-grow-1">
                        Login
                    </a>
                    <a href="./Admin/add_patient.php" class="btn signup-btn py-3 px-4 flex-grow-1">
                        Signup
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="./Bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>