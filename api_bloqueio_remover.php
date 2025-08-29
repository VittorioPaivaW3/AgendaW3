<?php
// api_bloqueio_remover.php — remove eventos status_sala='bloqueio' no intervalo
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

function bad($m){ http_response_code(400); echo json_encode(['ok'=>false,'erro'=>$m]); exit; }

$id_sala = (int)($_POST['id_sala'] ?? 0);
$data    = $_POST['data'] ?? '';
$ini     = $_POST['hora_inicio'] ?? '';
$fim     = $_POST['hora_fim'] ?? '';

if ($id_sala<=0 || !$data || !$ini || !$fim) bad('Parâmetros obrigatórios ausentes.');
if (!preg_match('/^\d{2}:(00|30)$/',$ini) || !preg_match('/^\d{2}:(00|30)$/',$fim)) bad('Horários devem ser :00 ou :30.');
if (!($ini>='08:00' && $fim<='18:00' && $ini<$fim)) bad('Janela deve ser 08:00–18:00, início < fim.');

try{
  $sql = "DELETE FROM eventos
          WHERE id_sala=? AND data=? AND status_sala='bloqueio'
            AND NOT (hora_fim<=? OR hora_inicio>=?)";
  $st = $pdo->prepare($sql);
  $st->execute([$id_sala, $data, $ini, $fim]);
  echo json_encode(['ok'=>true, 'removidos'=>$st->rowCount()]);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'erro'=>$e->getMessage()]);
}
