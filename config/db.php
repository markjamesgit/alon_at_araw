<?php

$host = 'localhost';
$db = 'alon_at_araw_db';
$user = 'root';
$pass = '';

try{
    $conn = new PDO("mysql:host=$host; dbname=$db",$user,$pass);
     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    die("Connection failed: " . $e->getMessage());
}
?>