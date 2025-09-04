<?php
// dashboard.php — métricas semanais (apenas admin)

// Buscar reservas da semana
$today = new DateTime('today');
$dayOfWeek = (int)$today->format('N');
$monday = (clone $today)->modify('-' . ($dayOfWeek - 1) . ' days');
$friday = (clone $monday)->modify('+4 days');

$weekStartSql = (clone $monday)->setTime(0,0,0)->format('Y-m-d H:i:s');
$weekEndSql   = (clone $friday)->setTime(23,59,59)->format('Y-m-d H:i:s');

$q = 'SELECT b.*, r.name AS room_name, r.color AS room_color FROM bookings b JOIN rooms r ON r.id = b.room_id WHERE b.date_start < :week_end AND b.date_end > :week_start';
$st = $pdo->prepare($q);
$st->execute([':week_end' => $weekEndSql, ':week_start' => $weekStartSql]);
$allBookingsAllRooms = $st->fetchAll(PDO::FETCH_ASSOC);

// Calcular métricas
$rooms = [];
foreach ($pdo->query('SELECT * FROM rooms ORDER BY id') as $r) { $rooms[(int)$r['id']] = $r; }

$metrics = [
  'total_bookings' => count($allBookingsAllRooms),
  'online' => 0,
  'coffee' => 0,
  'total_people' => 0,
  'per_room' => []
];
foreach ($rooms as $rid => $r) { $metrics['per_room'][$rid] = ['name'=>$r['name'],'color'=>$r['color'],'hours'=>0]; }
$weekStartTs = strtotime($weekStartSql); $weekEndTs = strtotime($weekEndSql);
foreach ($allBookingsAllRooms as $b) {
    if ((int)$b['is_online'] === 1) $metrics['online']++;
    if ((int)$b['needs_coffee'] === 1) $metrics['coffee']++;
    $metrics['total_people'] += max(0, (int)$b['participants_count']);
    $s = strtotime($b['date_start']); $e = strtotime($b['date_end']);
    $start = max($s, $weekStartTs); $end = min($e, $weekEndTs); $sec = max(0, $end - $start); $hours = $sec / 3600.0;
    $rid = (int)$b['room_id']; if (isset($metrics['per_room'][$rid])) $metrics['per_room'][$rid]['hours'] += $hours;
}
$availableHoursPerRoom = 10 * 5; // 10h por dia * 5 dias
?>
<?php include __DIR__.'/calendario.php'; ?>
