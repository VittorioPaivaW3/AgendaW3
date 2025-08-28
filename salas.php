<?php
// salas.php (corrigido)

$DATA_DIR   = __DIR__ . '/data';
$FILE_SALAS = $DATA_DIR . '/salas.json';
if (!is_dir($DATA_DIR)) { @mkdir($DATA_DIR, 0777, true); }

// ---- Funções auxiliares ----
function load_rooms($file) {
  if (!file_exists($file)) {
    $seed = [
      ["id"=>1,"nome"=>"Sala Executiva","capacidade"=>12],
      ["id"=>2,"nome"=>"Sala Reunião A","capacidade"=>8],
      ["id"=>3,"nome"=>"Auditório","capacidade"=>20],
    ];
    file_put_contents($file, json_encode($seed, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
  }
  $arr = json_decode(file_get_contents($file), true);
  return is_array($arr) ? $arr : [];
}
function save_rooms($file, $rooms) {
  file_put_contents($file, json_encode(array_values($rooms), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

// ---- Estado ----
$rooms   = load_rooms($FILE_SALAS);
$isAdmin = (isset($_GET['admin']) && $_GET['admin'] == '1');

// ---- Handlers de Admin (POST) ----
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $nome = trim($_POST['nome'] ?? '');
    $cap  = (int)($_POST['capacidade'] ?? 0);
    if ($nome !== '' && $cap > 0) {
      $maxId = 0; foreach ($rooms as $r) { if ($r['id'] > $maxId) $maxId = $r['id']; }
      $rooms[] = ["id" => $maxId + 1, "nome" => $nome, "capacidade" => $cap];
      save_rooms($FILE_SALAS, $rooms);
    }
  }

  if ($action === 'update') {
    $id   = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $cap  = (int)($_POST['capacidade'] ?? 0);
    foreach ($rooms as &$r) {
      if ($r['id'] === $id) {
        if ($nome !== '') $r['nome'] = $nome;
        if ($cap > 0)     $r['capacidade'] = $cap;
      }
    }
    unset($r);
    save_rooms($FILE_SALAS, $rooms);
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $rooms = array_values(array_filter($rooms, fn($r)=>$r['id'] !== $id));
    save_rooms($FILE_SALAS, $rooms);
  }

  // Bloquear horário (efeito visual; sem persistência ainda)
  if ($action === 'block') {
    $data = $_POST['data'] ?? '';
    $ini  = $_POST['hora_inicio'] ?? '';
    $fim  = $_POST['hora_fim'] ?? '';

    $validHalf = function($t){ return preg_match('/^\d{2}:(00|30)$/', $t); };
    $within = function($s,$e){
      [$sh,$sm] = array_map('intval', explode(':',$s));
      [$eh,$em] = array_map('intval', explode(':',$e));
      $S = $sh*60+$sm; $E = $eh*60+$em; return $S >= 480 && $E <= 1080 && $S < $E;
    };

    if (!$data || !$validHalf($ini) || !$validHalf($fim) || !$within($ini,$fim)) {
      echo '<script>alert("Bloqueio deve ser entre 08:00 e 18:00 e em intervalos de 30 minutos.");</script>';
    } else {
      echo '<script>
        window.addEventListener("DOMContentLoaded", function(){
          if (window.aplicarBloqueio) {
            aplicarBloqueio("'.htmlspecialchars($data).'", "'.htmlspecialchars($ini).'", "'.htmlspecialchars($fim).'", "Bloqueado");
          }
        });
      </script>';
    }
  }
}

/*
 * Dados visuais extras para os cards (apenas frontend).
 * Se preferir, mova estes campos para o JSON futuramente.
*/
$extras = [
  1 => [
    'andar' => '2º Andar',
    'status' => 'disponivel',
    'amenities' => [
      ['i'=>'bi-easel','t'=>'Projetor'],
      ['i'=>'bi-wifi','t'=>'Wi-Fi'],
      ['i'=>'bi-snow','t'=>'Ar Condicionado']
    ]
  ],
  2 => [
    'andar' => '1º Andar',
    'status' => 'disponivel',
    'amenities' => [
      ['i'=>'bi-tv','t'=>'TV'],
      ['i'=>'bi-wifi','t'=>'Wi-Fi']
    ]
  ],
  3 => [
    'andar' => 'Térreo',
    'status' => 'bloqueada',
    'amenities' => [
      ['i'=>'bi-easel','t'=>'Projetor'],
      ['i'=>'bi-speaker','t'=>'Sistema de Som'],
      ['i'=>'bi-wifi','t'=>'Wi-Fi']
    ]
  ],
];
?>

<div class="d-grid gap-2">
<?php foreach ($rooms as $sala):
  $id = (int)$sala['id'];
  $ex = $extras[$id] ?? ['andar'=>'–','status'=>'disponivel','amenities'=>[]];
  $isBlocked = ($ex['status'] ?? '') === 'bloqueada';
?>
  <button type="button"
          class="room-card btn-sala <?php echo $isBlocked? 'disabled' : ''; ?>"
          data-sala="<?php echo $id; ?>"
          data-nome="<?php echo htmlspecialchars($sala['nome']); ?>">
    <div class="room-header">
      <div class="room-name"><?php echo htmlspecialchars($sala['nome']); ?></div>
      <span class="status-pill <?php echo $isBlocked? 'status-block':'status-ok'; ?>">
        <?php echo $isBlocked? 'Bloqueada':'Disponível'; ?>
      </span>
    </div>

    <div class="d-flex mt-2" style="gap:1rem;">
      <div class="meta"><i class="bi bi-people"></i><?php echo (int)$sala['capacidade']; ?> pessoas</div>
      <div class="meta"><i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($ex['andar']); ?></div>
    </div>

    <?php if (!empty($ex['amenities'])): ?>
      <div class="amenities mt-2">
        <?php foreach ($ex['amenities'] as $a): ?>
          <div><i class="bi <?php echo $a['i']; ?>"></i><?php echo htmlspecialchars($a['t']); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </button>
<?php endforeach; ?>
</div>

<?php if ($isAdmin): ?>
  <!-- Painel do Administrador -->
  <div class="card mt-3">
    <div class="card-header fw-semibold">Administrador</div>
    <div class="card-body">
      <!-- Criar Sala -->
      <div class="mb-4">
        <h6 class="mb-2">Criar Sala</h6>
        <form method="post" class="row g-2">
          <input type="hidden" name="action" value="create">
          <div class="col-7"><input type="text" class="form-control" name="nome" placeholder="Nome da sala" required></div>
          <div class="col-3"><input type="number" class="form-control" name="capacidade" placeholder="Capacidade" min="1" required></div>
          <div class="col-2"><button class="btn btn-primary w-100">Adicionar</button></div>
        </form>
      </div>

      <!-- Editar Sala -->
      <div class="mb-4">
        <h6 class="mb-2">Editar Sala</h6>
        <form method="post" class="row g-2">
          <input type="hidden" name="action" value="update">
          <div class="col-4">
            <select class="form-select" name="id" required>
              <option value="">Selecione a sala...</option>
              <?php foreach ($rooms as $r): ?>
                <option value="<?php echo (int)$r['id']; ?>"><?php echo htmlspecialchars($r['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-4"><input type="text" class="form-control" name="nome" placeholder="Novo nome (opcional)"></div>
          <div class="col-2"><input type="number" class="form-control" name="capacidade" placeholder="Cap." min="1"></div>
          <div class="col-2"><button class="btn btn-outline-primary w-100">Salvar</button></div>
        </form>
      </div>

      <!-- Excluir Sala -->
      <div class="mb-4">
        <h6 class="mb-2">Excluir Sala</h6>
        <?php foreach ($rooms as $r): ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
            <button class="btn btn-sm btn-outline-danger me-1 mb-1"
                    onclick="return confirm('Excluir sala <?php echo htmlspecialchars($r['nome']); ?>?')">
              Excluir <?php echo htmlspecialchars($r['nome']); ?>
            </button>
          </form>
        <?php endforeach; ?>
      </div>

      <!-- Bloquear Horário -->
      <div class="mb-4">
        <h6 class="mb-2">Bloquear Horário (08:00–18:00)</h6>
        <form method="post" class="row g-2">
          <input type="hidden" name="action" value="block">
          <div class="col-5"><input type="date" class="form-control" name="data" required></div>
          <div class="col-3"><input type="time" class="form-control" name="hora_inicio" step="1800" min="08:00" max="18:00" required></div>
          <div class="col-3"><input type="time" class="form-control" name="hora_fim" step="1800" min="08:00" max="18:00" required></div>
          <div class="col-1"><button class="btn btn-outline-secondary w-100" title="Aplicar no calendário">OK</button></div>
        </form>
        <small class="text-muted">Bloqueio é visual por enquanto (sem persistência).</small>
      </div>

      <!-- Reservar (Admin) -->
      <div>
        <h6 class="mb-2">Reservar (Admin)</h6>
        <p class="small text-muted mb-2">Abre o mesmo modal usado pelo usuário.</p>
        <button type="button" class="btn btn-success btn-sm" onclick="abrirReservaAdmin()">Reservar Sala</button>
      </div>
    </div>
  </div>

  <script>
    function abrirReservaAdmin(){
      const anyBtnSala = document.querySelector('.btn-sala:not(.disabled)');
      if (anyBtnSala) { anyBtnSala.click(); } else { alert('Crie uma sala disponível primeiro.'); }
    }
  </script>
<?php endif; ?>
