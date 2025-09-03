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

<hr class="my-8">

<h2 class="text-xl font-bold mb-4">Calendário de Reservas</h2>
<?php include __DIR__.'/calendario.php'; ?>


<div class="bg-white rounded-2xl shadow p-6 lg:p-8 space-y-8">
  <h1 class="text-2xl font-bold">Dashboard — <?= h($monday->format('d/m')) ?> a <?= h($friday->format('d/m')) ?></h1>
  <div class="grid md:grid-cols-4 gap-6">
    <div class="rounded-2xl border border-slate-200 p-5"><div class="text-slate-500 text-sm">Reservas</div><div class="text-3xl font-bold mt-1"><?= (int)$metrics['total_bookings'] ?></div></div>
    <div class="rounded-2xl border border-slate-200 p-5"><div class="text-slate-500 text-sm">Reuniões online</div><div class="text-3xl font-bold mt-1"><?= (int)$metrics['online'] ?></div></div>
    <div class="rounded-2xl border border-slate-200 p-5"><div class="text-slate-500 text-sm">Pedidos de café</div><div class="text-3xl font-bold mt-1"><?= (int)$metrics['coffee'] ?></div></div>
    <div class="rounded-2xl border border-slate-200 p-5"><div class="text-slate-500 text-sm">Total de pessoas (informado)</div><div class="text-3xl font-bold mt-1"><?= (int)$metrics['total_people'] ?></div></div>
  </div>
  <div class="space-y-4">
    <h2 class="text-lg font-semibold">Ocupação por sala (horas da semana)</h2>
    <div class="space-y-3">
      <?php foreach ($metrics['per_room'] as $rid=>$row): $pct = min(100, round(($row['hours']/$availableHoursPerRoom)*100)); ?>
        <div>
          <div class="flex items-center justify-between text-sm mb-1">
            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-full" style="background: <?= h($row['color']) ?>"></span><strong><?= h($row['name']) ?></strong></div>
            <div><?= number_format($row['hours'],1,',','.') ?>h / <?= $availableHoursPerRoom ?>h</div>
          </div>
          <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-3 rounded-full" style="width: <?= $pct ?>%; background: <?= h($row['color']) ?>55"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
