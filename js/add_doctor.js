function validateForm() {

    // input values
    let name = document.getElementById("name").value.trim();
    let department = document.getElementById("department").value;
    let specialization = document.getElementById("specialization").value.trim();
    let experience = document.getElementById("experience").value.trim();
    let contact = document.getElementById("contact").value.trim();
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value;
    let confirmPassword = document.getElementById("confirmPassword").value;

    // regex patterns
    let nameRegex = /^[A-Za-z ]+$/;
    let contactRegex = /^[6-9][0-9]{9}$/;
    let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    let passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    let experienceRegex = /^[0-9]+$/;

    // clear old errors
    document.querySelectorAll(".text-danger").forEach(function (el) {
        el.textContent = "";
    });

    let isValid = true;

    // Name validation
    if (!nameRegex.test(name)) {
        document.getElementById("nameError").textContent = "Enter valid name (letters only)";
        isValid = false;
    }

    // Department validation
    if (department === "") {
        document.getElementById("departmentError").textContent = "Please select department";
        isValid = false;
    }

    // Specialization validation
    if (specialization === "") {
        document.getElementById("specializationError").textContent = "Enter specialization";
        isValid = false;
    }

    // Experience validation
    if (!experienceRegex.test(experience)) {
        document.getElementById("experienceError").textContent = "Enter valid experience";
        isValid = false;
    }

    // Contact validation
    if (!contactRegex.test(contact)) {
        document.getElementById("contactError").textContent = "Enter valid 10 digit mobile number";
        isValid = false;
    }

    // Email validation
    if (!emailRegex.test(email)) {
        document.getElementById("emailError").textContent = "Enter valid email address";
        isValid = false;
    }

    // Password validation
    if (!passwordRegex.test(password)) {
        document.getElementById("passwordError").textContent = "Password must be min 8 chars, include uppercase, lowercase, number, and special char (@$!%*?&)";
        isValid = false;
    }

    // Confirm password validation
    if (password !== confirmPassword) {
        document.getElementById("confirmPasswordError").textContent = "Passwords do not match";
        isValid = false;
    }

    return isValid;
}