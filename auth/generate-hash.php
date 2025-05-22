<?php
$plainPassword = 'admin123';  // your default admin password
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
echo $hashedPassword;
