function validateForm() {
    // 1. Clear previous errors
    document.querySelectorAll(".text-danger").forEach(el => el.textContent = "");

    let isValid = true;

    // Helper function to safely set error text
    function setError(id, message) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = message;
            isValid = false;
        }
    }

    // 2. Capture Values
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const mobile = document.getElementById("mobile").value.trim();
    const dob = document.getElementById("dob").value;
    const aadhar = document.getElementById("aadhar").value.trim();
    const blood = document.getElementById("blood").value;
    const city = document.getElementById("city").value.trim();
    const address = document.getElementById("address").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const gender = document.querySelector('input[name="gender"]:checked');

    // 3. Regex Patterns
    const nameRegex = /^[A-Za-z ]{3,}$/;
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const mobileRegex = /^[6-9][0-9]{9}$/;
    const aadharRegex = /^[0-9]{12}$/;
    const cityRegex = /^[A-Za-z ]{2,}$/;
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    // 4. Validation Logic
    if (!nameRegex.test(name)) setError("nameError", "Enter valid name (Min 3 letters)");
    if (!emailRegex.test(email)) setError("emailError", "Enter a valid email address");
    if (!mobileRegex.test(mobile)) setError("mobileError", "Enter valid 10-digit mobile number");
    if (!dob) setError("dobError", "Please select date of birth");
    if (!gender) setError("genderError", "Please select gender");
    if (!aadharRegex.test(aadhar)) setError("aadharError", "Enter valid 12-digit Aadhar");
    if (blood === "") setError("bloodError", "Please select blood group");
    if (!cityRegex.test(city)) setError("cityError", "Enter valid city name");
    if (address.length < 10) setError("addressError", "Min 10 characters required for address");
    if (!passwordRegex.test(password)) {
        setError("passwordError", "Password must be min 8 chars, include uppercase, lowercase, number, and special char (@$!%*?&)");
    }
    if (password !== confirmPassword) setError("confirmPasswordError", "Passwords do not match");

    // 5. UX: Scroll to first error
    if (!isValid) {
        const firstError = document.querySelector(".text-danger:not(:empty)");
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    return isValid;
}