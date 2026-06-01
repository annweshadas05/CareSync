<?php

require_once "../dbconnect.php";

$name = "Admin";
$email = "admincaresync@gmail.com";
$password = "Admin@123";

$hash = password_hash($password, PASSWORD_DEFAULT);

$role = "admin";

$qry = "INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)";

$stmt = $conn->prepare($qry);
$stmt->bind_param("ssss",$name,$email,$hash,$role);

$stmt->execute();

echo "Admin created successfully";

?>