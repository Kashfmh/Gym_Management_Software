<?php
$admin_email = 'admin@gmail.com'; // Replace with the admin's email
$admin_password = 'admin123'; // Replace with the desired password

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Output the hashed password
echo "Hashed Password: " . $hashed_password;