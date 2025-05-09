<?php
$password = 'employee123'; // Ganti dengan password yang kamu inginkan
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash untuk employee123: <br><textarea rows='3' cols='70'>" . $hash . "</textarea>";

$password = 'admin123'; // Ganti dengan password yang kamu inginkan
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash untuk admin123: <br><textarea rows='3' cols='70'>" . $hash . "</textarea>";
?>
