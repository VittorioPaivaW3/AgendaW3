<?php
// api_eventos_semana.php — lista eventos de segunda a sexta a partir de uma data monday
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

$monday = $_GET['monday'] ?? date('Y-m-d');
$id_sala = isset($_GET['id_sala']) ? (int)$_GET['id_sala'] : 0;

$ini = new DateTime($monday);
$fim = (clone $ini)->modify('+4 day');

try{
  $sql = "SELECT e.id, e.id_sala, e.data, e.hora_inicio, e.hora_fim, e.status_sala, e.setor, e.solicitante, e.tipo_reuniao, e.quitutes, e.pessoas, e.observacoes,
                 s.nome AS sala_nome
          FROM eventos e
          JOIN salas s ON s.id = e.id_sala
          WHERE e.data BETWEEN ? AND ?".
          ($id_sala>0 ? " AND e.id_sala=?" : "").
          " ORDER BY e.data, e.hora_inicio";
  $params = [$ini->format('Y-m-d'), $fim->format('Y-m-d')];
  if ($id_sala>0) $params[] = $id_sala;
  $st = $pdo->prepare($sql); $st->execute($params);
  $rows = $st->fetchAll();
  echo json_encode(['ok'=>true,'eventos'=>$rows], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'erro'=>$e->getMessage()]);
}
