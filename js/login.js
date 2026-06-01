function validateLogin() {

    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value.trim();

    let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    let passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    document.getElementById("emailError").textContent = "";
    document.getElementById("passwordError").textContent = "";

    let isValid = true;

    // email validation
    if (email === "") {
        document.getElementById("emailError").textContent = "Email or username is required";
        isValid = false;
    }
    else if (!emailRegex.test(email)) {
        document.getElementById("emailError").textContent = "Enter a valid email address";
        isValid = false;
    }

    // password validation
    if (password === "") {
        document.getElementById("passwordError").textContent = "Password is required";
        isValid = false;
    }
    else if (!passwordRegex.test(password)) {
        document.getElementById("passwordError").textContent = "Invalid password format";
        isValid = false;
    }

    return isValid;

}