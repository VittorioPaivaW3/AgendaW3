<?php


// index.php — Navbar + Roteamento + Modo Admin simples

session_start();
require __DIR__.'/bootstrap.php';

// === Config: senha admin ===
const ADMIN_PASS = 'w3admin';

// Controle de login admin
$is_admin = !empty($_SESSION['is_admin']);

// Roteamento simples
$page = $_GET['page'] ?? 'calendario';
$flash = null;

// --- Ações POST (centralizadas) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- Admin: login/logout ----
    if ($action === 'admin_login') {
        $pass = $_POST['admin_password'] ?? '';
        if ($pass === ADMIN_PASS) {
            $_SESSION['is_admin'] = true;
            $is_admin = true;
            $flash = ['type'=>'success','msg'=>'Você entrou como administrador.'];
        } else {
            $flash = ['type'=>'error','msg'=>'Senha incorreta.'];
        }
        $page = 'calendario';
    }

    if ($action === 'logout') {
        session_destroy();
        session_start();
        $is_admin = false;
        $flash = ['type'=>'success','msg'=>'Você saiu do modo admin.'];
        $page = 'calendario';
    }

    // ---- Reservas (usuário comum pode criar/excluir) ----
    if ($action === 'save_booking') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $participants = trim($_POST['participants'] ?? '');
        $is_online = isset($_POST['is_online']) ? 1 : 0;
        $meeting_link = trim($_POST['meeting_link'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $requester = trim($_POST['requester'] ?? '');
        $participants_count = max(1, (int)($_POST['participants_count'] ?? 1));
        $needs_coffee = isset($_POST['needs_coffee']) ? 1 : 0;

        $errors = [];
        if ($room_id <= 0) $errors[] = 'Selecione uma sala.';
        if ($title === '') $errors[] = 'Informe um título.';
        if ($requester === '') $errors[] = 'Informe o solicitante.';
        $start = parseDateTime($date, $start_time);
        $end   = parseDateTime($date, $end_time);
        if (!$start || !$end) $errors[] = 'Datas/horários inválidos.';

        // Sem finais de semana
        if (!$errors && $start) {
            $dow = (int)$start->format('N'); // 1=Seg..7=Dom
            if ($dow > 5) { $errors[] = 'Agendamentos apenas de segunda a sexta.'; }
        }

        // Janela de expediente
        if (!$errors && !withinBusinessHours($start, $end)) {
            $errors[] = 'Horário fora do expediente (08:00–18:00) ou início ≥ término.';
        }

        // Sala bloqueada + capacidade
        if (!$errors) {
            $stBlk = $pdo->prepare('SELECT is_blocked, capacity FROM rooms WHERE id = ?');
            $stBlk->execute([$room_id]);
            $roomRow = $stBlk->fetch(PDO::FETCH_ASSOC);
            if ($roomRow && (int)$roomRow['is_blocked'] === 1) {
                $errors[] = 'Esta sala está bloqueada para agendamentos.';
            }
            $capacity = (int)($roomRow['capacity'] ?? 0);
            if ($capacity > 0 && $participants_count > $capacity) {
                $errors[] = 'Quantidade de participantes excede a capacidade da sala (' . $capacity . ').';
            }
        }

        // Conflito na mesma sala
        if (!$errors) {
            $sql = 'SELECT COUNT(*) FROM bookings WHERE room_id = :room_id AND (:start < date_end) AND (:end > date_start)';
            $st = $pdo->prepare($sql);
            $st->execute([
                ':room_id' => $room_id,
                ':start'   => toSql($start),
                ':end'     => toSql($end)
            ]);
            if ((int)$st->fetchColumn() > 0) {
                $errors[] = 'Conflito de agendamento nesta sala para o período escolhido.';
            }
        }

        if (empty($errors)) {
            $ins = $pdo->prepare('INSERT INTO bookings (room_id, title, date_start, date_end, participants, is_online, meeting_link, notes, requester, participants_count, needs_coffee, created_at)
            VALUES (:room_id, :title, :date_start, :date_end, :participants, :is_online, :meeting_link, :notes, :requester, :participants_count, :needs_coffee, :created_at)');
            $ins->execute([
                ':room_id' => $room_id,
                ':title' => $title,
                ':date_start' => toSql($start),
                ':date_end' => toSql($end),
                ':participants' => $participants,
                ':is_online' => $is_online,
                ':meeting_link' => $meeting_link,
                ':notes' => $notes,
                ':requester' => $requester,
                ':participants_count' => $participants_count,
                ':needs_coffee' => $needs_coffee,
                ':created_at' => date('Y-m-d H:i:s')
            ]);
            $flash = ['type' => 'success', 'msg' => 'Reserva criada com sucesso!'];
        } else {
            $flash = ['type' => 'error', 'msg' => implode(' ', $errors)];
        }
        $page = 'calendario';
    }

    if ($action === 'delete_booking') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
            $flash = ['type' => 'success', 'msg' => 'Reserva excluída.'];
        }
        $page = 'calendario';
    }

    // ---- CRUD de Salas (somente Admin) ----
    if ($action === 'create_room' && $is_admin) {
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#78BE20';
        $capacity = max(1, (int)($_POST['capacity'] ?? 1));
        $has_wifi = isset($_POST['has_wifi']) ? 1 : 0;
        $has_tv = isset($_POST['has_tv']) ? 1 : 0;
        $has_board = isset($_POST['has_board']) ? 1 : 0;
        $has_ac = isset($_POST['has_ac']) ? 1 : 0;
        $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;
        if ($name !== '') {
            $pdo->prepare('INSERT INTO rooms (name, color, capacity, has_wifi, has_tv, has_board, has_ac, is_blocked) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$name, $color, $capacity, $has_wifi, $has_tv, $has_board, $has_ac, $is_blocked]);
            $flash = ['type' => 'success', 'msg' => 'Sala adicionada.'];
        } else {
            $flash = ['type' => 'error', 'msg' => 'Informe o nome da sala.'];
        }
        $page = 'salas';
    }

    if ($action === 'update_room' && $is_admin) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#78BE20';
        $capacity = max(1, (int)($_POST['capacity'] ?? 1));
        $has_wifi = isset($_POST['has_wifi']) ? 1 : 0;
        $has_tv = isset($_POST['has_tv']) ? 1 : 0;
        $has_board = isset($_POST['has_board']) ? 1 : 0;
        $has_ac = isset($_POST['has_ac']) ? 1 : 0;
        $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;
        if ($id > 0 && $name !== '') {
            $pdo->prepare('UPDATE rooms SET name=?, color=?, capacity=?, has_wifi=?, has_tv=?, has_board=?, has_ac=?, is_blocked=? WHERE id=?')
                ->execute([$name,$color,$capacity,$has_wifi,$has_tv,$has_board,$has_ac,$is_blocked,$id]);
            $flash = ['type' => 'success', 'msg' => 'Sala atualizada.'];
        } else {
            $flash = ['type' => 'error', 'msg' => 'Dados inválidos para atualização.'];
        }
        $page = 'salas';
    }

    if ($action === 'delete_room' && $is_admin) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM rooms WHERE id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM bookings WHERE room_id=?')->execute([$id]);
            $flash = ['type' => 'success', 'msg' => 'Sala e reservas associadas removidas.'];
        }
        $page = 'salas';
    }
}

// Após processar POST, garanta que $rooms exista para os modais em qualquer página
$rooms = [];
foreach ($pdo->query('SELECT * FROM rooms ORDER BY id') as $r) {
    $rooms[(int)$r['id']] = $r;
}

// Restringir acesso
if (!$is_admin && in_array($page, ['dashboard','salas'], true)) {
    $page = 'calendario';
    $flash = $flash ?: ['type'=>'error','msg'=>'Área restrita a administradores. Clique em “Admin” e informe a senha.'];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Agenda de Salas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    dialog::backdrop { background: rgba(15,23,42,.45); }
    .feature { display:inline-flex; align-items:center; gap:.375rem; padding:.3rem .55rem; border-radius:.5rem; background:#f1f5f9; font-size:.75rem; }
    .booking-card { display:flex; flex-direction:column; gap:.25rem; border:1px solid #e2e8f0; background:#f8fafc; border-radius:.75rem; padding:.6rem .7rem; }
    .booking-title { font-weight:700; line-height:1.1; }
    .booking-meta { font-size:.8rem; color:#475569; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
      :root { --w3-green: #78BE20; }
  .active-tab{ background: var(--w3-green) !important; color:#fff !important; }
  .active-tab:hover{ filter: brightness(0.95); }
  .btn-w3{ background: var(--w3-green); color:#fff; }
  .btn-w3:hover{ filter: brightness(0.95); }
  </style>
</head>
<body class="bg-slate-50 text-slate-800">

  <!-- NAVBAR -->
  <nav class="bg-white shadow sticky top-0 z-40">
    <div class="max-w-[1600px] mx-auto px-6 lg:px-12 h-16 flex items-center justify-between">
      <a href="?page=calendario" class="flex items-center gap-3">
        <img src="logo-w3.png" alt="Grupo W3" class="h-8 w-auto"/>
        <span class="sr-only">Grupo W3</span>
      </a>
      <?php $active = function($p) use ($page){ return $page===$p ? 'active-tab' : 'text-slate-700 hover:bg-slate-100'; }; ?>

      <div class="flex items-center gap-2">
        <a href="?page=calendario" class="px-3 py-2 rounded-xl <?= $active('calendario') ?>">Reservas</a>

        <?php if ($is_admin): ?>
          <a href="?page=dashboard" class="px-3 py-2 rounded-xl <?= $active('dashboard') ?>">Dashboard</a>
          <a href="?page=salas" class="px-3 py-2 rounded-xl <?= $active('salas') ?>">Salas</a>
          <form method="post" class="ml-2 inline">
            <input type="hidden" name="action" value="logout"/>
            <button class="px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100" title="Sair">Sair</button>
          </form>
        <?php else: ?>
          <button id="openAdmin" class="px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100">Admin</button>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="max-w-[1600px] mx-auto px-6 lg:px-12 py-8 space-y-6">
    <?php if ($flash): ?>
      <div class="p-3 <?= $flash['type']==='success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'; ?> text-sm rounded-xl shadow">
        <?= h($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <?php
      if ($page==='calendario') include __DIR__.'/calendario.php';
      if ($page==='dashboard' && $is_admin) include __DIR__.'/dashboard.php';
      if ($page==='salas' && $is_admin) include __DIR__.'/salas.php';
    ?>
  </div>
  


  <?php include __DIR__.'/partials/modals.php'; ?>
  <?php include __DIR__.'/partials/scripts.php'; ?>

</body>
</html>
