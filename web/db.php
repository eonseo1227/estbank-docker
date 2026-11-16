<?php
$host = 'intra-db'; // 예: 192.168.x.x
$user = 'est_user';
$pass = '12345678';
$dbname = 'est_bank';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("데이터베이스 연결 실패: " . $conn->connect_error);
}
session_start();
?>
