<?php
// api_evento_salvar.php — cria linha em `eventos` (reserva ou bloqueio)
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

function bad($m){ http_response_code(400); echo json_encode(['ok'=>false,'erro'=>$m]); exit; }

$tipo = $_POST['tipo'] ?? '';
$id_sala = (int)($_POST['id_sala'] ?? 0);
$data = $_POST['data'] ?? '';
$ini  = $_POST['hora_inicio'] ?? '';
$fim  = $_POST['hora_fim'] ?? '';

if (!in_array($tipo, ['reserva','bloqueio'], true)) bad('Tipo inválido.');
if ($id_sala<=0 || !$data || !$ini || !$fim) bad('Parâmetros obrigatórios ausentes.');
if (!preg_match('/^\d{2}:(00|30)$/',$ini) || !preg_match('/^\d{2}:(00|30)$/',$fim)) bad('Horários devem ser :00 ou :30.');
if (!($ini>='08:00' && $fim<='18:00' && $ini<$fim)) bad('Janela deve ser 08:00–18:00, início < fim.');

try {
  // conflito: qualquer evento que intersecte [ini,fim)
  $sqlC = "SELECT COUNT(*) c FROM eventos WHERE id_sala=? AND data=? AND NOT (hora_fim<=? OR hora_inicio>=?)";
  $stC = $pdo->prepare($sqlC); $stC->execute([$id_sala, $data, $ini, $fim]);
  if ((int)$stC->fetch()['c']>0) bad('Conflito de horário com outro evento.');

  if ($tipo==='reserva'){
    $sql = "INSERT INTO eventos (id_sala, data, hora_inicio, hora_fim, status_sala, setor, solicitante, tipo_reuniao, quitutes, pessoas, observacoes)
            VALUES (?,?,?,?, 'reserva', ?,?,?,?,?, ?)";
    $st = $pdo->prepare($sql);
    $st->execute([
      $id_sala, $data, $ini, $fim,
      $_POST['setor'] ?? 'Reservado',
      $_POST['solicitante'] ?? '',
      $_POST['tipo_reuniao'] ?? 'online',
      $_POST['quitutes'] ?? 'nao',
      (int)($_POST['pessoas'] ?? 0),
      $_POST['observacoes'] ?? null
    ]);
  } else {
    $sql = "INSERT INTO eventos (id_sala, data, hora_inicio, hora_fim, status_sala, setor, solicitante, tipo_reuniao, quitutes, pessoas, observacoes)
            VALUES (?,?,?,?, 'bloqueio', '', '', 'online', 'nao', 0, ?)";
    $st = $pdo->prepare($sql);
    $st->execute([$id_sala, $data, $ini, $fim, $_POST['observacoes'] ?? 'Bloqueado']);
  }

  echo json_encode(['ok'=>true, 'id'=>$pdo->lastInsertId()]);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false, 'erro'=>'Erro interno: '.$e->getMessage()]);
}
