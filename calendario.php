<?php
// calendario.php — Tela de calendário e reservas (desktop + mobile)

// Carregar salas
$rooms = [];
foreach ($pdo->query('SELECT * FROM rooms ORDER BY id') as $r) {
    $rooms[(int)$r['id']] = $r;
}

$selectedRoom = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0; // 0 = todas

// Filtros adicionais (somente admin)
$filterOnline = !empty($_GET['filter_online']);
$filterCoffee = !empty($_GET['filter_coffee']);

// Semana (Seg–Sex)
$weekParam = $_GET['week'] ?? '';
$today = new DateTime('today');
$baseDate = $weekParam ? DateTime::createFromFormat('Y-m-d', $weekParam) : clone $today;
if (!$baseDate) { $baseDate = clone $today; }
$dayOfWeek = (int)$baseDate->format('N');
$monday = (clone $baseDate)->modify('-' . ($dayOfWeek - 1) . ' days');
$friday = (clone $monday)->modify('+4 days');

$weekDays = [];
for ($i = 0; $i < 5; $i++) { $weekDays[] = (clone $monday)->modify("+$i days"); }

$weekStartSql = (clone $monday)->setTime(0,0,0)->format('Y-m-d H:i:s');
$weekEndSql   = (clone $friday)->setTime(23,59,59)->format('Y-m-d H:i:s');

// Reservas da semana
$q = 'SELECT b.*, r.name AS room_name, r.color AS room_color
      FROM bookings b
      JOIN rooms r ON r.id = b.room_id
      WHERE b.date_start < :week_end AND b.date_end > :week_start';
$params = [':week_end' => $weekEndSql, ':week_start' => $weekStartSql];

if ($selectedRoom > 0) {
    $q .= ' AND b.room_id = :rid';
    $params[':rid'] = $selectedRoom;
}

// Aplica filtros extras apenas se admin
if (!empty($_SESSION['is_admin'])) {
    if ($filterOnline) $q .= ' AND b.is_online = 1';
    if ($filterCoffee) $q .= ' AND b.needs_coffee = 1';
}

$q .= ' ORDER BY b.date_start';
$st = $pdo->prepare($q);
$st->execute($params);
$allBookings = $st->fetchAll(PDO::FETCH_ASSOC);

// Index por dia
$bookingsByDay = [];
foreach ($allBookings as $b) {
    $start = new DateTime($b['date_start']);
    $end = new DateTime($b['date_end']);
    $span = new DatePeriod((clone $start)->setTime(0,0,0), new DateInterval('P1D'), (clone $end)->setTime(23,59,59)->modify('+1 second'));
    foreach ($span as $d) {
        $key = $d->format('Y-m-d');
        $bookingsByDay[$key] = $bookingsByDay[$key] ?? [];
        $bookingsByDay[$key][] = $b;
    }
}

// Todas as reservas (todas as salas) — para saber se o slot está completo
$stAll = $pdo->prepare('SELECT b.*, r.name AS room_name, r.color AS room_color
                        FROM bookings b
                        JOIN rooms r ON r.id = b.room_id
                        WHERE b.date_start < :week_end AND b.date_end > :week_start
                        ORDER BY b.date_start');
$stAll->execute([':week_end' => $weekEndSql, ':week_start' => $weekStartSql]);
$allBookingsAllRooms = $stAll->fetchAll(PDO::FETCH_ASSOC);
$bookingsByDayAll = [];
foreach ($allBookingsAllRooms as $b) {
    $start = new DateTime($b['date_start']);
    $end = new DateTime($b['date_end']);
    $span = new DatePeriod((clone $start)->setTime(0,0,0), new DateInterval('P1D'), (clone $end)->setTime(23,59,59)->modify('+1 second'));
    foreach ($span as $d) {
        $key = $d->format('Y-m-d');
        $bookingsByDayAll[$key] = $bookingsByDayAll[$key] ?? [];
        $bookingsByDayAll[$key][] = $b;
    }
}

// Helper para preservar filtros na navegação
$filtersQS = '';
if (!empty($_SESSION['is_admin'])) {
    if ($filterOnline) $filtersQS .= '&filter_online=1';
    if ($filterCoffee) $filtersQS .= '&filter_coffee=1';
}

// ---------- Mobile helpers ----------
$mobileDay = isset($_GET['mday']) ? (int)$_GET['mday'] : 0;      // 0..4
if ($mobileDay < 0) $mobileDay = 0;
if ($mobileDay > 4) $mobileDay = 4;
$mobileDate = $weekDays[$mobileDay] ?? $weekDays[0];
$weekdayLabels = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex'];
?>
<!-- WRAPPER: alinha Topbar e Calendário e controla a largura -->
<div class="max-w-6xl mx-auto space-y-6">

  <!-- Topbar Reservas -->
  <div class="bg-white rounded-2xl shadow p-6 lg:p-8 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold">
          Reservas — Semana de <strong><?= h($monday->format('d/m/Y')) ?></strong>
          a <strong><?= h($friday->format('d/m/Y')) ?></strong>
        </h1>
      </div>

      <?php
        $prevWeek = (clone $monday)->modify('-7 days')->format('Y-m-d');
        $nextWeek = (clone $monday)->modify('+7 days')->format('Y-m-d');

        // preserva filtros e sala na navegação
        $qsBase = 'page=calendario&room_id='.$selectedRoom;
        if (!empty($_SESSION['is_admin'])) {
          if (!empty($_GET['filter_online'])) $qsBase .= '&filter_online=1';
          if (!empty($_GET['filter_coffee'])) $qsBase .= '&filter_coffee=1';
        }
        // mantém o dia selecionado no mobile (se presente)
        if (isset($_GET['mday'])) $qsBase .= '&mday='.(int)$mobileDay;
      ?>
      <style>
        .cal-nav{display:flex;align-items:center;gap:.5rem}
        .cal-nav a{display:inline-flex;align-items:center;gap:.5rem;border-radius:.75rem;padding:.5rem .75rem;color:#0f172a}
        .cal-nav .nav-today,.cal-nav .nav-arrow{border:1px solid #e2e8f0}
        .cal-nav .nav-today:hover,.cal-nav .nav-arrow:hover{background:#f8fafc}
        .cal-nav .icon{width:18px;height:18px;opacity:.85}
        .btn-rooms{background:#035F1D;color:#fff}
        .btn-rooms:hover{filter:brightness(.95)}
      </style>

      <div class="cal-nav">
        <a class="nav-today" href="?<?= h($qsBase) ?>&week=<?= h((new DateTime('today'))->format('Y-m-d')) ?>">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8"  y1="2" x2="8"  y2="6"></line>
            <line x1="3"  y1="10" x2="21" y2="10"></line>
            <circle cx="8.5" cy="14.5" r="1.1"></circle>
          </svg>
          Hoje
        </a>
        <a class="nav-arrow" href="?<?= h($qsBase) ?>&week=<?= h($prevWeek) ?>" aria-label="Semana anterior">‹</a>
        <a class="nav-arrow" href="?<?= h($qsBase) ?>&week=<?= h($nextWeek) ?>" aria-label="Próxima semana">›</a>
      </div>
    </div>

    <!-- Ações -->
    <div class="flex flex-wrap items-end gap-4">
      <button id="openCreate" class="px-4 h-10 rounded-xl btn-w3 font-semibold">+ Nova reserva</button>
      <button id="openRooms" class="px-4 h-10 rounded-xl btn-rooms">Informações das salas</button>

      <form method="get" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="page" value="calendario" />
        <input type="hidden" name="week" value="<?= h($monday->format('Y-m-d')) ?>" />
        <?php if (isset($_GET['mday'])): ?><input type="hidden" name="mday" value="<?= (int)$mobileDay ?>"/><?php endif; ?>

        <div class="flex items-end gap-2">
          <label for="room_id" class="text-sm font-semibold">Filtrar por sala</label>
          <select id="room_id" name="room_id" class="w-56 rounded-xl border-slate-200 focus:ring-2 focus:ring-indigo-500">
            <option value="0" <?= $selectedRoom===0?'selected':'' ?>>Todas as salas</option>
            <?php foreach ($rooms as $rid => $r): ?>
              <option value="<?= $rid ?>" <?= $selectedRoom===$rid?'selected':'' ?>>
                <?= h($r['name']) ?> (<?= (int)$r['capacity'] ?>)<?= (int)$r['is_blocked'] ? ' — BLOQUEADA' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (!empty($_SESSION['is_admin'])): ?>
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="filter_online" value="1" <?= !empty($_GET['filter_online']) ? 'checked' : '' ?>>
            <span>Reuniões online</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="filter_coffee" value="1" <?= !empty($_GET['filter_coffee']) ? 'checked' : '' ?>>
            <span>Com café</span>
          </label>
        <?php endif; ?>

        <button class="px-4 h-10 rounded-xl btn-w3 font-semibold">Aplicar</button>
      </form>
    </div>
  </div>

  <!-- Calendário DESKTOP -->
  <div class="hidden md:block">
    <main class="bg-white rounded-2xl shadow p-6 lg:p-8">
      <div>
        <table class="w-full table-fixed">
          <thead class="bg-slate-100 border-b border-slate-200">
            <tr>
              <th class="w-28 px-4 py-3 text-left text-xs font-semibold text-slate-600 sticky left-0 bg-slate-100">Hora</th>
              <?php foreach ($weekDays as $d): ?>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">
                  <?php $label = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex'][(int)$d->format('N')]; ?>
                  <?= h($label) ?> <span class="text-slate-400"><?= h($d->format('d/m')) ?></span>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php for ($h = 8; $h < 18; $h++): ?>
              <tr class="border-t border-slate-200">
                <td class="px-4 py-4 text-sm text-slate-600 sticky left-0 bg-white border-r border-slate-200"><?= hh($h) ?></td>
                <?php foreach ($weekDays as $d): ?>
                  <?php
                    $cellStart = (clone $d)->setTime($h, 0, 0);
                    $cellEnd   = (clone $d)->setTime($h+1, 0, 0);
                    $key = $d->format('Y-m-d');
                    $list = $bookingsByDay[$key] ?? [];
                    $cellBookings = bookingsForCell($list, $cellStart, $cellEnd);

                    // Slot completo: ocupadas + bloqueadas
                    $allList = $bookingsByDayAll[$key] ?? [];
                    $cellBookingsAll = bookingsForCell($allList, $cellStart, $cellEnd);
                    $occupiedRooms = [];
                    foreach ($cellBookingsAll as $cb) { $occupiedRooms[(int)$cb['room_id']] = true; }
                    foreach ($rooms as $rid => $rInfo) { if((int)$rInfo['is_blocked']===1) { $occupiedRooms[$rid] = true; } }
                    $slotFull = count($occupiedRooms) >= count($rooms);
                  ?>
                  <td class="align-top px-3 py-4">
                    <div class="min-h-[72px] flex flex-col gap-3 <?= !$slotFull ? 'cursor-pointer hover:bg-slate-50' : 'opacity-60' ?> rounded-lg p-1"
                         <?= !$slotFull ? "onclick=\"openCreateWith('".h($key)."','".hh($h)."','".hh($h+1)."')\"" : '' ?>>
                      <?php if (!$slotFull): ?>
                        <button class="self-end text-slate-300 hover:text-slate-600" title="Agendar neste horário"
                                onclick="event.stopPropagation(); openCreateWith('<?= h($key) ?>','<?= hh($h) ?>','<?= hh($h+1) ?>')">＋</button>
                      <?php else: ?>
                        <span class="self-end text-slate-500 cursor-not-allowed" title="Horário indisponível (salas ocupadas/bloqueadas)">—</span>
                      <?php endif; ?>

                      <?php foreach ($cellBookings as $b): ?>
                        <button class="booking-card text-left"
                                style="border-color: <?= h($b['room_color']) ?>33; background: <?= h($b['room_color']) ?>0d"
                                onclick='event.stopPropagation(); showBooking(<?= json_encode([
                                    "id" => (int)$b["id"],
                                    "title" => $b["title"],
                                    "room" => $b["room_name"],
                                    "room_id" => (int)$b["room_id"],
                                    "color" => $b["room_color"],
                                    "requester" => $b["requester"],
                                    "is_online" => (int)$b["is_online"],
                                    "meeting_link" => $b["meeting_link"],
                                    "needs_coffee" => (int)$b["needs_coffee"],
                                    "participants" => $b["participants"],
                                    "participants_count" => (int)$b["participants_count"],
                                    "notes" => $b["notes"],
                                    "start" => (new DateTime($b["date_start"]))->format("d/m/Y H:i"),
                                    "end" => (new DateTime($b["date_end"]))->format("d/m/Y H:i"),
                                    "date_sql" => (new DateTime($b["date_start"]))->format("Y-m-d"),
                                    "start_hh" => (new DateTime($b["date_start"]))->format("H:i"),
                                    "end_hh" => (new DateTime($b["date_end"]))->format("H:i"),
                                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)'>
                          <div class="booking-title truncate"><?= h($b['title']) ?></div>
                          <div class="booking-meta">
                            <?= h($b['requester'] ?: '—') ?> — <?= h($b['room_name']) ?> — <?= (new DateTime($b['date_start']))->format('H:i') ?>–<?= (new DateTime($b['date_end']))->format('H:i') ?>
                          </div>
                        </button>
                      <?php endforeach; ?>
                    </div>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <!-- Calendário MOBILE -->
  <div class="md:hidden">
    <main class="bg-white rounded-2xl shadow p-4">
      <div class="flex items-center justify-between mb-4">
        <?php
          $baseQS = 'page=calendario&week='.$monday->format('Y-m-d').'&room_id='.$selectedRoom.$filtersQS;
          $prevDayUrl = $mobileDay > 0 ? '?'.$baseQS.'&mday='.($mobileDay-1) : null;
          $nextDayUrl = $mobileDay < 4 ? '?'.$baseQS.'&mday='.($mobileDay+1) : null;
          $wdLabel = $weekdayLabels[(int)$mobileDate->format('N')];
        ?>
        <a class="px-3 py-1 rounded-lg border border-slate-200 <?= $prevDayUrl ? 'text-slate-700' : 'opacity-40 pointer-events-none' ?>" href="<?= $prevDayUrl ?: '#' ?>">‹</a>
        <div class="text-sm font-medium text-slate-700"><?= h($wdLabel) ?> <span class="text-slate-400"><?= h($mobileDate->format('d/m')) ?></span></div>
        <a class="px-3 py-1 rounded-lg border border-slate-200 <?= $nextDayUrl ? 'text-slate-700' : 'opacity-40 pointer-events-none' ?>" href="<?= $nextDayUrl ?: '#' ?>">›</a>
      </div>

      <div class="space-y-3">
        <?php for ($h = 8; $h < 18; $h++): ?>
          <?php
            $cellStart = (clone $mobileDate)->setTime($h, 0, 0);
            $cellEnd   = (clone $mobileDate)->setTime($h+1, 0, 0);
            $key = $mobileDate->format('Y-m-d');
            $list = $bookingsByDay[$key] ?? [];
            $cellBookings = bookingsForCell($list, $cellStart, $cellEnd);

            // Slot completo: ocupadas + bloqueadas (todas salas)
            $allList = $bookingsByDayAll[$key] ?? [];
            $cellBookingsAll = bookingsForCell($allList, $cellStart, $cellEnd);
            $occupiedRooms = [];
            foreach ($cellBookingsAll as $cb) { $occupiedRooms[(int)$cb['room_id']] = true; }
            foreach ($rooms as $rid => $rInfo) { if((int)$rInfo['is_blocked']===1) { $occupiedRooms[$rid] = true; } }
            $slotFull = count($occupiedRooms) >= count($rooms);
          ?>
          <div class="rounded-xl border border-slate-200 p-3">
            <div class="flex items-center justify-between mb-2">
              <div class="text-sm font-semibold text-slate-700"><?= hh($h) ?></div>
              <?php if (!$slotFull): ?>
                <button class="px-2 py-1 text-sm rounded-lg border border-slate-200 text-slate-600"
                        onclick="openCreateWith('<?= h($key) ?>','<?= hh($h) ?>','<?= hh($h+1) ?>')">＋ Agendar</button>
              <?php else: ?>
                <span class="text-slate-400 text-sm">Indisponível</span>
              <?php endif; ?>
            </div>

            <div class="flex flex-col gap-2">
              <?php if (empty($cellBookings)): ?>
                <div class="text-xs text-slate-400">Sem reservas neste horário.</div>
              <?php else: ?>
                <?php foreach ($cellBookings as $b): ?>
                  <button class="booking-card text-left"
                          style="border-color: <?= h($b['room_color']) ?>33; background: <?= h($b['room_color']) ?>0d"
                          onclick='event.stopPropagation(); showBooking(<?= json_encode([
                              "id" => (int)$b["id"],
                              "title" => $b["title"],
                              "room" => $b["room_name"],
                              "room_id" => (int)$b["room_id"],
                              "color" => $b["room_color"],
                              "requester" => $b["requester"],
                              "is_online" => (int)$b["is_online"],
                              "meeting_link" => $b["meeting_link"],
                              "needs_coffee" => (int)$b["needs_coffee"],
                              "participants" => $b["participants"],
                              "participants_count" => (int)$b["participants_count"],
                              "notes" => $b["notes"],
                              "start" => (new DateTime($b["date_start"]))->format("d/m/Y H:i"),
                              "end" => (new DateTime($b["date_end"]))->format("d/m/Y H:i"),
                              "date_sql" => (new DateTime($b["date_start"]))->format("Y-m-d"),
                              "start_hh" => (new DateTime($b["date_start"]))->format("H:i"),
                              "end_hh" => (new DateTime($b["date_end"]))->format("H:i"),
                          ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)'>
                    <div class="booking-title truncate"><?= h($b['title']) ?></div>
                    <div class="booking-meta">
                      <?= h($b['requester'] ?: '—') ?> — <?= h($b['room_name']) ?> — <?= (new DateTime($b['date_start']))->format('H:i') ?>–<?= (new DateTime($b['date_end']))->format('H:i') ?>
                    </div>
                  </button>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endfor; ?>
      </div>
    </main>
  </div>

</div>
