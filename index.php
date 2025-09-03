<?php
// index.php — Navbar + Roteamento + Modo Admin simples

// Inicia sessão apenas se necessário
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Bootstrap do app (DB, helpers etc.)
require __DIR__.'/bootstrap.php';

// === Config: senha admin ===
const ADMIN_PASS = 'w3admin';

// Controle de login admin
$is_admin = !empty($_SESSION['is_admin']);

// Roteamento simples
$page = $_GET['page'] ?? 'calendario';
$flash = null;

// Trata ações POST (login, logout, criar/editar/excluir reservas)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- Admin login/logout ----
    if ($action === 'admin_login') {
        $pwd = $_POST['admin_password'] ?? '';
        if ($pwd === ADMIN_PASS) {
            $_SESSION['is_admin'] = true;
            $is_admin = true;
            $flash = ['type'=>'success','msg'=>'Você entrou como admin.'];
            $page = 'dashboard';
        } else {
            $flash = ['type'=>'error','msg'=>'Senha inválida.'];
            $page = 'calendario';
        }
    }

    if ($action === 'admin_logout') {
        $_SESSION = [];
        session_destroy();
        session_start();
        $is_admin = false;
        $flash = ['type'=>'success','msg'=>'Você saiu do modo admin.'];
        $page = 'calendario';
    }

    // ---- Criar reserva (usuário comum pode criar) ----
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
            $blocked = (int)$pdo->query("SELECT is_blocked FROM rooms WHERE id=".$room_id)->fetchColumn();
            if ($blocked === 1) $errors[] = 'Esta sala está bloqueada para agendamentos.';
            $cap = (int)$pdo->query("SELECT capacity FROM rooms WHERE id=".$room_id)->fetchColumn();
            if ($participants_count > $cap) $errors[] = 'Participantes excedem a capacidade da sala.';
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
            $conflicts = (int)$st->fetchColumn();
            if ($conflicts > 0) {
                $errors[] = 'Conflito de agendamento nesta sala para o período escolhido.';
            }
        }

        if (empty($errors)) {
            $ins = $pdo->prepare('INSERT INTO bookings (room_id, title, requester, participants_count, date_start, date_end, participants, is_online, meeting_link, needs_coffee, notes, created_at) VALUES (:room_id, :title, :requester, :pc, :date_start, :date_end, :participants, :is_online, :meeting_link, :needs_coffee, :notes, :created_at)');
            $ins->execute([
                ':room_id' => $room_id,
                ':title' => $title,
                ':requester' => $requester,
                ':pc' => $participants_count,
                ':date_start' => toSql($start),
                ':date_end' => toSql($end),
                ':participants' => $participants,
                ':is_online' => $is_online,
                ':meeting_link' => $meeting_link,
                ':needs_coffee' => $needs_coffee,
                ':notes' => $notes,
                ':created_at' => date('Y-m-d H:i:s')
            ]);
            $flash = ['type' => 'success', 'msg' => 'Reserva criada com sucesso!'];
        } else {
            $flash = ['type' => 'error', 'msg' => implode(' ', $errors)];
        }
        $page = 'calendario';
    }

    // ---- Editar reserva (apenas admin) ----
    if ($action === 'update_booking') {
        $id = (int)($_POST['booking_id'] ?? 0);
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

        if (empty($_SESSION['is_admin'])) {
            $flash = ['type'=>'error','msg'=>'Apenas admin pode editar reservas.'];
            $page = 'calendario';
        } else {
            $errors = [];
            if ($id <= 0) $errors[] = 'Reserva inválida.';
            if ($room_id <= 0) $errors[] = 'Selecione uma sala.';
            if ($title === '') $errors[] = 'Informe um título.';
            $start = parseDateTime($date, $start_time);
            $end   = parseDateTime($date, $end_time);
            if (!$start || !$end) $errors[] = 'Datas/horários inválidos.';

            if (!$errors && $start) {
                $dow = (int)$start->format('N');
                if ($dow > 5) { $errors[] = 'Agendamentos apenas de segunda a sexta.'; }
            }
            if (!$errors && !withinBusinessHours($start, $end)) {
                $errors[] = 'Horário fora do expediente (08:00–18:00) ou início ≥ término.';
            }

            if (!$errors) {
                $blocked = (int)$pdo->query("SELECT is_blocked FROM rooms WHERE id=".$room_id)->fetchColumn();
                if ($blocked === 1) $errors[] = 'Esta sala está bloqueada para agendamentos.';
                $cap = (int)$pdo->query("SELECT capacity FROM rooms WHERE id=".$room_id)->fetchColumn();
                if ($participants_count > $cap) $errors[] = 'Participantes excedem a capacidade da sala.';
            }

            if (!$errors) {
                $sql = 'SELECT COUNT(*) FROM bookings 
                        WHERE room_id = :room_id 
                          AND id <> :id
                          AND (:start < date_end) AND (:end > date_start)';
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':room_id' => $room_id,
                    ':id' => $id,
                    ':start'   => toSql($start),
                    ':end'     => toSql($end)
                ]);
                $conflicts = (int)$st->fetchColumn();
                if ($conflicts > 0) {
                    $errors[] = 'Conflito de agendamento nesta sala para o período escolhido.';
                }
            }

            if (empty($errors)) {
                $up = $pdo->prepare('UPDATE bookings 
                    SET room_id=:room_id, title=:title, requester=:requester, participants_count=:pc,
                        date_start=:ds, date_end=:de, participants=:participants, is_online=:is_online,
                        meeting_link=:meeting_link, needs_coffee=:needs_coffee, notes=:notes
                    WHERE id=:id');
                $up->execute([
                    ':room_id'=>$room_id,
                    ':title'=>$title,
                    ':requester'=>$requester,
                    ':pc'=>$participants_count,
                    ':ds'=>toSql($start),
                    ':de'=>toSql($end),
                    ':participants'=>$participants,
                    ':is_online'=>$is_online,
                    ':meeting_link'=>$meeting_link,
                    ':needs_coffee'=>$needs_coffee,
                    ':notes'=>$notes,
                    ':id'=>$id,
                ]);
                $flash = ['type'=>'success','msg'=>'Reserva atualizada com sucesso!'];
            } else {
                $flash = ['type'=>'error','msg'=>implode(' ', $errors)];
            }
            $page = 'calendario';
        }
    }

    // ---- Excluir reserva ----
    if ($action === 'delete_booking') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
            $flash = ['type' => 'success', 'msg' => 'Reserva excluída.'];
        }
        $page = 'calendario';
    }
}

// Navbar + roteamento
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Agenda de Salas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .btn-w3{ background:#78BE20; color:#fff } .btn-w3:hover{ filter:brightness(.95) }
  </style>
</head>
<body class="bg-slate-50 text-slate-800">
  <nav class="bg-white border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-6 lg:px-12 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <img src="logo-w3.png" alt="W3" class="h-8 w-auto"/>
        <a href="index.php?page=calendario" class="px-3 py-2 rounded-xl hover:bg-slate-50">Reservas</a>
        <?php if (!empty($_SESSION['is_admin'])): ?>
          <a href="index.php?page=dashboard" class="px-3 py-2 rounded-xl hover:bg-slate-50">Dashboard</a>
          <a href="index.php?page=salas" class="px-3 py-2 rounded-xl hover:bg-slate-50">Salas</a>
        <?php endif; ?>
      </div>
      <div class="flex items-center gap-2">
        <?php if (!empty($_SESSION['is_admin'])): ?>
          <form method="post">
            <input type="hidden" name="action" value="admin_logout"/>
            <button class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200">Sair</button>
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
      if ($page==='dashboard') { if (empty($_SESSION['is_admin'])) { echo '<div class="bg-rose-50 text-rose-700 rounded-xl p-4">Apenas admin.</div>'; } else { include __DIR__.'/dashboard.php'; } }
      if ($page==='salas')     { if (empty($_SESSION['is_admin'])) { echo '<div class="bg-rose-50 text-rose-700 rounded-xl p-4">Apenas admin.</div>'; } else { include __DIR__.'/salas.php'; } }

      // === Includes resilientes ===
      // modals.php em raiz ou /partials
      $modalsCandidates = [__DIR__.'/modals.php', __DIR__.'/partials/modals.php'];
      $loaded = false;
      foreach ($modalsCandidates as $p) {
          if (file_exists($p)) { include $p; $loaded = true; break; }
      }
      if (!$loaded) { echo "<!-- Aviso: modals.php não encontrado -->"; }

      // scripts.php em raiz ou /partials
      $scriptsCandidates = [__DIR__.'/scripts.php', __DIR__.'/partials/scripts.php'];
      $loaded = false;
      foreach ($scriptsCandidates as $p) {
          if (file_exists($p)) { include $p; $loaded = true; break; }
      }
      if (!$loaded) { echo "<!-- Aviso: scripts.php não encontrado -->"; }
    ?>
  </div>
</body>
</html>
