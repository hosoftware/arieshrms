<?php
ini_set('display_errors', '0');
    $host="localhost";
    $username="effism_live";
    $password="4G&L6b^GFQNB!U-WT)";
    $database="effism_live";
    $mysqli = new mysqli($host, $username, $password, $database);
    define('VALIDITY_SECONDS', 600); //600 for 10 mins
    define('QR_HMAC_SECRET', 'AriesHRMS@2024#xK9$mP2&qL7!nR4@zWq8^vYt');
?>  