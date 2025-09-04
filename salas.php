<?php
// salas.php — Admin: gerenciamento de salas (edição em modal centralizado)

if (empty($_SESSION['is_admin'])) {
  header('Location: index.php'); exit;
}

// --------- Garantias de schema ---------
$cols = $pdo->query("PRAGMA table_info(rooms)")->fetchAll(PDO::FETCH_ASSOC);
$hasExtras = false;
foreach ($cols as $c) { if (strcasecmp($c['name'], 'extras') === 0) { $hasExtras = true; break; } }
if (!$hasExtras) {
  $pdo->exec("ALTER TABLE rooms ADD COLUMN extras TEXT DEFAULT '[]'");
}

// Garantir coluna de Videoconferência
$hasVc = false;
foreach ($cols as $c) { if (strcasecmp($c['name'], 'has_vc') === 0) { $hasVc = true; break; } }
if (!$hasVc) {
  $pdo->exec("ALTER TABLE rooms ADD COLUMN has_vc INTEGER DEFAULT 0");
}

// --------- Flash helper ---------
$flash = $flash ?? null;
if (!empty($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

// --------- Processamento (PRG) ---------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create_room') {
      $name = trim($_POST['name'] ?? '');
      $color = $_POST['color'] ?? '#3b82f6';
      $capacity = max(1, (int)($_POST['capacity'] ?? 1));
      $has_wifi = !empty($_POST['has_wifi']) ? 1 : 0;
      $has_tv = !empty($_POST['has_tv']) ? 1 : 0;
      $has_board = !empty($_POST['has_board']) ? 1 : 0;
      $has_ac = !empty($_POST['has_ac']) ? 1 : 0;
      $has_vc = !empty($_POST['has_vc']) ? 1 : 0;
            $is_blocked = !empty($_POST['is_blocked']) ? 1 : 0;
      if ($name === '') throw new Exception('Informe um nome.');
      $st = $pdo->prepare("INSERT INTO rooms (name,color,capacity,has_wifi,has_tv,has_board,has_ac,has_vc,is_blocked,extras) VALUES (?,?,?,?,?,?,?,?,?,?)");
      $st->execute([$name,$color,$capacity,$has_wifi,$has_tv,$has_board,$has_ac,$has_vc,$is_blocked,'[]']);
      $_SESSION['flash'] = ['type'=>'success','msg'=>'Sala criada com sucesso.'];
    }

    if ($action === 'update_room') {
      $id = (int)($_POST['room_id'] ?? 0);
      if ($id <= 0) throw new Exception('Sala inválida.');
      $name = trim($_POST['name'] ?? '');
      $color = $_POST['color'] ?? '#3b82f6';
      $capacity = max(1, (int)($_POST['capacity'] ?? 1));
      $has_wifi = !empty($_POST['has_wifi']) ? 1 : 0;
      $has_tv = !empty($_POST['has_tv']) ? 1 : 0;
      $has_board = !empty($_POST['has_board']) ? 1 : 0;
      $has_ac = !empty($_POST['has_ac']) ? 1 : 0;
      $has_vc = !empty($_POST['has_vc']) ? 1 : 0;
      $is_blocked = !empty($_POST['is_blocked']) ? 1 : 0;
      if ($name === '') throw new Exception('Informe um nome.');
      $st = $pdo->prepare("UPDATE rooms SET name=?, color=?, capacity=?, has_wifi=?, has_tv=?, has_board=?, has_ac=?, has_vc=?, is_blocked=? WHERE id=?");
      $st->execute([$name,$color,$capacity,$has_wifi,$has_tv,$has_board,$has_ac,$has_vc,$is_blocked,$id]);
      $_SESSION['flash'] = ['type'=>'success','msg'=>'Sala atualizada.'];
    }

    if ($action === 'delete_room') {
      $id = (int)($_POST['room_id'] ?? 0);
      if ($id <= 0) throw new Exception('Sala inválida.');
      $pdo->prepare("DELETE FROM rooms WHERE id=?")->execute([$id]);
      $_SESSION['flash'] = ['type'=>'success','msg'=>'Sala removida.'];
    }

  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type'=>'error','msg'=>$e->getMessage()];
  }
  header('Location: index.php?page=salas'); exit;
}

// --------- Carregamento ---------
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
  .btn-w3{ background:#78BE20; color:#fff } .btn-w3:hover{ filter:brightness(.95) }
  .btn-ghost{ background:#f8fafc; border:1px solid #e2e8f0; color:#0f172a } .btn-ghost:hover{ background:#f1f5f9 }
  .btn-danger{ background:#fef2f2; border:1px solid #fee2e2; color:#b91c1c } .btn-danger:hover{ filter:brightness(.98) }

  .toggle { position:relative; display:inline-flex; align-items:center; gap:.5rem; padding:.5rem .75rem;
    border-radius:9999px; border:1px solid #e2e8f0; font-size:.875rem; cursor:pointer; user-select:none }
  .peer:checked + .toggle{ border-color:#c7f0a7; background:#f0fdf4 }
  .toggle .dot{ width:8px; height:8px; border-radius:9999px; background:#94a3b8 }
  .peer:checked + .toggle .dot{ background:#78BE20 }

  /* Modal (dialog) */
  dialog#editRoomDialog{
    border:none; border-radius:16px; padding:0; width:min(720px,95vw);
    box-shadow:0 20px 60px rgba(15,23,42,.2);
  }
  dialog#editRoomDialog::backdrop{ background:rgba(15,23,42,.45) }
</style>

<div class="max-w-6xl mx-auto space-y-6">
  <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <h1 class="text-2xl font-bold">Salas</h1>

    <div class="flex flex-col md:flex-row md:items-center gap-3 w-full md:w-auto">
      <!-- + Nova sala (mantém inline) -->
      <button onclick="document.getElementById('newRoom').classList.toggle('hidden')"
              class="px-4 h-10 rounded-xl btn-w3 font-semibold">+ Nova sala</button>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="rounded-xl px-4 py-3 text-sm <?= $flash['type']==='success'?'bg-emerald-50 text-emerald-700':'bg-rose-50 text-rose-700' ?>">
      <?= h($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Criar nova sala -->
  <div id="newRoom" class="hidden" style="border:1px solid #e2e8f0;border-radius:1rem;padding:1rem;background:#fff">
    <form method="post" class="space-y-4">
      <input type="hidden" name="action" value="create_room">
      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-medium">Nome</label>
          <input name="name" required class="mt-1 w-full rounded-xl border-slate-300" placeholder="Ex.: Sala A">
        </div>
        <div>
          <label class="text-sm font-medium">Cor</label>
          <input name="color" type="color" value="#3b82f6" class="mt-1 w-16 h-10 p-1 rounded-xl border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium">Capacidade</label>
          <input name="capacity" type="number" min="1" value="6" required class="mt-1 w-full rounded-xl border-slate-300">
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <input id="nwifi" type="checkbox" name="has_wifi" class="peer hidden">
        <label for="nwifi" class="toggle"><span class="dot"></span>Wi-Fi</label>

        <input id="ntv" type="checkbox" name="has_tv" class="peer hidden">
        <label for="ntv" class="toggle"><span class="dot"></span>TV</label>

        <input id="nboard" type="checkbox" name="has_board" class="peer hidden">
        <label for="nboard" class="toggle"><span class="dot"></span>Quadro</label>

        <input id="nac" type="checkbox" name="has_ac" class="peer hidden">
        <label for="nac" class="toggle"><span class="dot"></span>Ar-condicionado</label>

        <input id="nvc" type="checkbox" name="has_vc" class="peer hidden">
        <label for="nvc" class="toggle"><span class="dot"></span>Video Conferência</label>

        <input id="nblock" type="checkbox" name="is_blocked" class="peer hidden">
        <label for="nblock" class="toggle"><span class="dot"></span>Bloquear sala</label>
      </div>

      <div class="flex items-center gap-2 justify-end">
        <button type="button" class="px-4 h-10 rounded-xl btn-ghost" onclick="this.closest('#newRoom').classList.add('hidden')">Cancelar</button>
        <button type="submit" type="submit" class="px-4 h-10 rounded-xl btn-w3 font-semibold">Salvar</button>
      </div>
    </form>
  </div>

  <!-- Lista de salas -->
  <div class="grid md:grid-cols-2 gap-4">
    <?php foreach ($rooms as $r): $rid=(int)$r['id']; ?>
      <div class="bg-white rounded-2xl shadow p-4 space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="inline-block w-3 h-3 rounded-full" style="background:<?= h($r['color']) ?>"></span>
            <div>
              <div class="font-semibold"><?= h($r['name']) ?> <span class="text-slate-500">(cap. <?= (int)$r['capacity'] ?>)</span></div>
              <div class="text-xs text-slate-500">
                <?php if ((int)$r['has_wifi']): ?>📶 Wi-Fi · <?php endif; ?>
                <?php if ((int)$r['has_tv']): ?>📺 TV · <?php endif; ?>
                <?php if ((int)$r['has_board']): ?>🧑‍🏫 Quadro · <?php endif; ?>
                <?php if ((int)$r['has_ac']): ?>❄️ Ar · <?php endif; ?>
                <?= (int)$r['is_blocked'] ? '⛔ Bloqueada' : '✅ Ativa' ?>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <!-- Botão de editar com dados em data-* -->
            <button
              class="px-3 h-9 rounded-xl btn-ghost"
              onclick="openEditDialog(this)"
              data-rid="<?= $rid ?>"
              data-name="<?= h($r['name']) ?>"
              data-color="<?= h($r['color']) ?>"
              data-capacity="<?= (int)$r['capacity'] ?>"
              data-wifi="<?= (int)$r['has_wifi'] ?>"
              data-tv="<?= (int)$r['has_tv'] ?>"
              data-board="<?= (int)$r['has_board'] ?>"
              data-ac="<?= (int)$r['has_ac'] ?>"
              data-vc="<?= isset($r['has_vc']) ? (int)$r['has_vc'] : 0 ?>"
              data-blocked="<?= (int)$r['is_blocked'] ?>"
            >Editar</button>

            <form method="post" onsubmit="return confirm('Remover esta sala? Isso não apaga reservas existentes.');">
              <input type="hidden" name="action" value="delete_room">
              <input type="hidden" name="room_id" value="<?= $rid ?>">
              <button class="px-3 h-9 rounded-xl btn-danger">Excluir</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal: Editar Sala -->
<dialog id="editRoomDialog">
  <form method="post" class="p-6 space-y-4" style="max-height:80vh;overflow:auto">
    <input type="hidden" name="action" value="update_room">
    <input type="hidden" name="room_id" id="er_id">

    <div class="flex items-start justify-between">
      <h2 class="text-lg font-semibold">Editar sala</h2>
      <button type="button" class="text-slate-400 hover:text-slate-700" onclick="closeEditDialog()">✕</button>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="text-sm font-medium">Nome</label>
        <input id="er_name" name="name" required class="mt-1 w-full rounded-xl border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium">Cor</label>
        <input id="er_color" name="color" type="color" class="mt-1 w-16 h-10 p-1 rounded-xl border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium">Capacidade</label>
        <input id="er_capacity" name="capacity" type="number" min="1" required class="mt-1 w-full rounded-xl border-slate-300">
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 pt-2">
      <input id="er_wifi" type="checkbox" name="has_wifi" class="peer hidden">
      <label for="er_wifi" class="toggle"><span class="dot"></span>Wi-Fi</label>

      <input id="er_tv" type="checkbox" name="has_tv" class="peer hidden">
      <label for="er_tv" class="toggle"><span class="dot"></span>TV</label>

      <input id="er_board" type="checkbox" name="has_board" class="peer hidden">
      <label for="er_board" class="toggle"><span class="dot"></span>Quadro</label>

      <input id="er_ac" type="checkbox" name="has_ac" class="peer hidden">
      <label for="er_ac" class="toggle"><span class="dot"></span>Ar-condicionado</label>

      <input id="er_vc" type="checkbox" name="has_vc" class="peer hidden">
      <label for="er_vc" class="toggle"><span class="dot"></span>Video Conferência</label>

      <input id="er_blocked" type="checkbox" name="is_blocked" class="peer hidden">
      <label for="er_blocked" class="toggle"><span class="dot"></span>Bloquear sala</label>
    </div>

    <div class="flex items-center justify-end gap-2 pt-2">
      <button type="button" class="px-4 h-10 rounded-xl btn-ghost" onclick="closeEditDialog()">Cancelar</button>
      <button class="px-4 h-10 rounded-xl btn-w3 font-semibold">Salvar</button>
    </div>
  </form>
</dialog>

<script>
  const dlg = document.getElementById('editRoomDialog');

  function openEditDialog(btn){
    const d = btn.dataset;
    document.getElementById('er_id').value = d.rid;
    document.getElementById('er_name').value = d.name || '';
    document.getElementById('er_color').value = d.color || '#3b82f6';
    document.getElementById('er_capacity').value = d.capacity || 1;

    document.getElementById('er_wifi').checked    = d.wifi === '1';
    document.getElementById('er_tv').checked      = d.tv === '1';
    document.getElementById('er_board').checked   = d.board === '1';
    document.getElementById('er_ac').checked      = d.ac === '1';
    document.getElementById('er_vc').checked      = d.vc === '1';
    document.getElementById('er_blocked').checked = d.blocked === '1';

    dlg.showModal();
  }
  function closeEditDialog(){ dlg.close(); }
</script>