<?php
$host = 'db.fr-pari1.bengt.wasmernet.com';
$db   = 'koperasi_rbb'; 
$user = 'e981327a74758000174a6fc8d572';              
$pass = '0693e981-327b-7062-8000-f447b0597729'; 
$port = '10272'
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Koneksi Gagal: " . $e->getMessage());
}
