<?php
// db.php — conexão PDO com MySQL (XAMPP padrão)
$host  = "127.0.0.1";
$porta = "3306";         // ajuste se seu MySQL estiver em outra porta
$banco = "agendaw3";
$user  = "root";
$pass  = "";             // XAMPP padrão: senha vazia

$dsn = "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4";
$opts = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $opts);
} catch (PDOException $e) {
  http_response_code(500);
  exit("Erro na conexão com o banco: " . $e->getMessage());
}
