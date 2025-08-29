<?php
// admin.php — Área do Administrador
// - Mantém o mesmo modal do usuário, com abas extras: Bloquear, Desbloquear, Configurar Sala
// - Toda a lógica de ADMIN centralizada aqui (CRUD de sala e marcações visuais)
// - salas.php: apenas listagem/regras visuais das salas
// - calendario.php: apenas regras do calendário
declare(strict_types=1);

// ===== Caminhos / Arquivos =====
$DIR_DADOS = __DIR__ . '/data';
$ARQ_SALAS = $DIR_DADOS . '/salas.json';
if (!is_dir($DIR_DADOS)) { @mkdir($DIR_DADOS, 0777, true); }

// ===== Utilidades (português) =====
function carregar_salas(string $arquivo): array {
  if (!file_exists($arquivo)) {
    $seed = [
      ["id"=>1,"nome"=>"Sala Executiva","capacidade"=>12,"status"=>"disponivel"],
      ["id"=>2,"nome"=>"Sala Reunião A","capacidade"=>8,"status"=>"disponivel"],
      ["id"=>3,"nome"=>"Auditório","capacidade"=>50,"status"=>"disponivel"],
    ];
    file_put_contents($arquivo, json_encode($seed, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
  }
  $dados = json_decode((string)file_get_contents($arquivo), true);
  if (!is_array($dados)) return [];
  // garantir chave status
  foreach ($dados as &$s) { if (!isset($s['status'])) $s['status'] = 'disponivel'; }
  unset($s);
  return $dados;
}
function salvar_salas(string $arquivo, array $salas): void {
  file_put_contents($arquivo, json_encode(array_values($salas), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
function validar_meia_hora(string $hora): bool { return (bool)preg_match('/^\d{2}:(00|30)$/', $hora); }
function dentro_horario(string $ini, string $fim): bool { return ($ini >= '08:00' && $fim <= '18:00' && $ini < $fim); }

// ===== Estado =====
$salas = carregar_salas($ARQ_SALAS);

// ===== Ações do Admin (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';

  // Criar sala
  if ($acao === 'criar_sala') {
    $nome = trim($_POST['nome'] ?? '');
    $cap  = (int)($_POST['capacidade'] ?? 0);
    if ($nome !== '' && $cap > 0) {
      $novoId = 1; foreach ($salas as $s) { if ((int)$s['id'] >= $novoId) $novoId = (int)$s['id'] + 1; }
      $salas[] = ["id"=>$novoId,"nome"=>$nome,"capacidade"=>$cap,"status"=>"disponivel"];
      salvar_salas($ARQ_SALAS, $salas);
    }
  }

  // Atualizar sala (nome/capacidade/status)
  if ($acao === 'atualizar_sala') {
    $id      = (int)($_POST['id'] ?? 0);
    $nome    = trim($_POST['nome'] ?? '');
    $cap     = (int)($_POST['capacidade'] ?? 0);
    $status  = $_POST['status'] ?? null; // disponivel|bloqueada

    foreach ($salas as &$s) {
      if ((int)$s['id'] === $id) {
        if ($nome !== '') $s['nome'] = $nome;
        if ($cap > 0)     $s['capacidade'] = $cap;
        if ($status === 'disponivel' || $status === 'bloqueada') $s['status'] = $status;
      }
    }
    unset($s);
    salvar_salas($ARQ_SALAS, $salas);
  }

  // Excluir sala
  if ($acao === 'excluir_sala') {
    $id = (int)($_POST['id'] ?? 0);
    $salas = array_values(array_filter($salas, fn($s) => (int)$s['id'] !== $id));
    salvar_salas($ARQ_SALAS, $salas);
  }

  // Ações VISUAIS de calendário (pintam a grade; sem persistência)
  // Bloquear faixa
  if ($acao === 'bloquear_faixa') {
    $data = $_POST['data'] ?? '';
    $ini  = $_POST['hora_inicio'] ?? '';
    $fim  = $_POST['hora_fim'] ?? '';
    $rot  = $_POST['rotulo'] ?? 'Bloqueado';
    if (!$data || !validar_meia_hora($ini) || !validar_meia_hora($fim) || !dentro_horario($ini, $fim)) {
      echo '<script>alert("Bloqueio deve ser entre 08:00 e 18:00 e em intervalos de 30 minutos.");</script>';
    } else {
      echo '<script>
        window.addEventListener("DOMContentLoaded",function(){
          if(window.aplicarBloqueio){ aplicarBloqueio("'.htmlspecialchars($data).'", "'.htmlspecialchars($ini).'", "'.htmlspecialchars($fim).'", "'.htmlspecialchars($rot).'"); }
        });
      </script>';
    }
  }

  // Desbloquear faixa (limpa a grade no intervalo)
  if ($acao === 'desbloquear_faixa') {
    $data = $_POST['data'] ?? '';
    $ini  = $_POST['hora_inicio'] ?? '';
    $fim  = $_POST['hora_fim'] ?? '';
    if (!$data || !validar_meia_hora($ini) || !validar_meia_hora($fim) || !dentro_horario($ini, $fim)) {
      echo '<script>alert("Desbloqueio deve respeitar 08:00–18:00 e intervalos de 30 minutos.");</script>';
    } else {
      echo '<script>
        window.addEventListener("DOMContentLoaded",function(){
          if(typeof limparFaixa === "function"){ limparFaixa("'.htmlspecialchars($data).'", "'.htmlspecialchars($ini).'", "'.htmlspecialchars($fim).'"); }
        });
      </script>';
    }
  }

  // Reservar faixa (visual)
  if ($acao === 'reservar_faixa') {
    $data = $_POST['data'] ?? '';
    $ini  = $_POST['hora_inicio'] ?? '';
    $fim  = $_POST['hora_fim'] ?? '';
    $rot  = $_POST['rotulo'] ?? 'Reservado';
    if (!$data || !validar_meia_hora($ini) || !validar_meia_hora($fim) || !dentro_horario($ini, $fim)) {
      echo '<script>alert("Reserva deve ser entre 08:00 e 18:00 e em intervalos de 30 minutos.");</script>';
    } else {
      echo '<script>
        window.addEventListener("DOMContentLoaded",function(){
          if(window.aplicarReserva){ aplicarReserva("'.htmlspecialchars($data).'", "'.htmlspecialchars($ini).'", "'.htmlspecialchars($fim).'", "'.htmlspecialchars($rot).'"); }
        });
      </script>';
    }
  }

  // Redireciona para evitar reenvio de formulário (F5)
  header('Location: '.$_SERVER['PHP_SELF']);
  exit;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Agenda W3 — Administração</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    /* Mantém o visual do seu front */
    :root{
      --w3-primary:#0d6efd; --w3-success:#198754; --w3-muted:#6c757d;
      --w3-bg:#f8f9fa; --w3-card:#ffffff; --w3-border:#e9ecef;
    }
    body{ background: var(--w3-bg); }

    .room-card{ background:var(--w3-card); border:1px solid var(--w3-border); border-radius:16px; padding:14px; width:100%; text-align:left; }
    .room-header{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .status-pill{ font-size:.75rem; padding:.2rem .55rem; border-radius:999px; border:1px solid transparent; }
    .status-ok{ background:#e8f5ee; color:#17834d; border-color:#cfe8db; }
    .status-block{ background:#fde9ec; color:#b23b4a; border-color:#f6cfd5; }
    .meta{ color:#6c757d; font-size:.9rem; display:flex; align-items:center; gap:.5rem; }

    .calendar-wrap{ background:var(--w3-card); border:1px solid var(--w3-border); border-radius:16px; }
    .horario.selected  { background-color: var(--w3-primary); color: #fff; }
    .horario.reservado { background-color: var(--w3-success); color: #fff; }
    .horario.bloqueado { background-color: var(--w3-muted);   color: #fff; }
  </style>
</head>
<body>
  <nav class="navbar bg-white border-bottom mb-3">
    <div class="container-fluid py-2">
      <span class="navbar-brand mb-0 h1">Agenda W3 — Administração</span>
      <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Modo Usuário</a>
      </div>
    </div>
  </nav>

  <div class="container pb-4">
    <div class="row g-3">
      <!-- Esquerda: Cards das salas (mesmo front) + botão abrir modal admin -->
      <div class="col-lg-4 col-xl-3">
        <?php
          // Reutiliza seus cards existentes
          include __DIR__ . '/salas.php';
        ?>
        <div class="mt-3">
          <button type="button" class="btn btn-success w-100" id="btnAbrirModalAdmin">Abrir painel do administrador</button>
        </div>
      </div>

      <!-- Direita: Calendário (mesmo front) -->
      <div class="col-lg-8 col-xl-9">
        <div class="calendar-wrap p-2">
          <?php include __DIR__ . '/calendario.php'; ?>
        </div>
        <div class="mt-3 ps-1">
          <span class="badge bg-primary">Selecionado</span>
          <span class="badge bg-success">Reservado</span>
          <span class="badge bg-secondary">Bloqueado</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal DO USUÁRIO (layout idêntico) + Abas extras para Admin -->
  <div class="modal fade" id="modalAdmin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Gerenciar Sala <span class="text-muted" id="nomeSalaModal"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>

        <div class="modal-body">
          <ul class="nav nav-tabs" id="abasAdmin" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#aba-reservar" type="button">Reservar</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#aba-bloquear" type="button">Bloquear horário</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#aba-desbloquear" type="button">Desbloquear horário</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#aba-configurar" type="button">Configurar sala</button></li>
          </ul>

          <div class="tab-content pt-3">
            <!-- Reservar (idêntico ao modal do usuário) -->
            <div class="tab-pane fade show active" id="aba-reservar">
              <form id="formReservar" class="row g-3">
                <input type="hidden" id="r_salaId">
                <div class="col-md-4">
                  <label class="form-label">Número de Pessoas</label>
                  <input type="number" class="form-control" id="r_numPessoas" min="1" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Reunião</label>
                  <select class="form-select" id="r_tipoReuniao" required>
                    <option value="presencial">Presencial</option>
                    <option value="online">Online</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Quitutes</label>
                  <select class="form-select" id="r_quitutes" required>
                    <option value="nao">Não</option>
                    <option value="sim">Sim</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Setor da Empresa</label>
                  <select class="form-select" id="r_setor" required>
                    <option value="">Selecione...</option>
                    <option value="RH">RH</option>
                    <option value="TI">TI</option>
                    <option value="Compras">Compras</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Solicitante</label>
                  <input type="text" class="form-control" id="r_solicitante" placeholder="Nome do responsável" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Data</label>
                  <input type="date" class="form-control" id="r_data" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label d-block">Hora Início</label>
                  <div class="d-flex gap-2">
                    <select class="form-select" id="r_horaInicioH" required>
                      <?php for ($h=8; $h<=18; $h++): ?>
                        <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option>
                      <?php endfor; ?>
                    </select>
                    <select class="form-select" id="r_horaInicioM" required>
                      <option value="00">00</option>
                      <option value="30">30</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label d-block">Hora Fim</label>
                  <div class="d-flex gap-2">
                    <select class="form-select" id="r_horaFimH" required>
                      <?php for ($h=8; $h<=18; $h++): ?>
                        <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option>
                      <?php endfor; ?>
                    </select>
                    <select class="form-select" id="r_horaFimM" required>
                      <option value="00">00</option>
                      <option value="30">30</option>
                    </select>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label">Observações</label>
                  <textarea class="form-control" id="r_observacoes" rows="3" placeholder="Digite observações adicionais..."></textarea>
                </div>
              </form>
            </div>

            <!-- Bloquear Horário -->
            <div class="tab-pane fade" id="aba-bloquear">
              <form id="formBloquear" class="row g-3">
                <input type="hidden" id="b_salaId">
                <div class="col-md-4">
                  <label class="form-label">Data</label>
                  <input type="date" class="form-control" id="b_data" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label d-block">Hora Início</label>
                  <div class="d-flex gap-2">
                    <select class="form-select" id="b_horaInicioH" required>
                      <?php for ($h=8; $h<=18; $h++): ?>
                        <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option>
                      <?php endfor; ?>
                    </select>
                    <select class="form-select" id="b_horaInicioM" required>
                      <option value="00">00</option>
                      <option value="30">30</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label d-block">Hora Fim</label>
                  <div class="d-flex gap-2">
                    <select class="form-select" id="b_horaFimH" required>
                      <?php for ($h=8; $h<=18; $h++): ?>
                        <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option>
                      <?php endfor; ?>
                    </select>
                    <select class="form-select" id="b_horaFimM" required>
                      <option value="00">00</option>
                      <option value="30">30</option>
                    </select>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label">Motivo (opcional)</label>
                  <input type="text" class="form-control" id="b_rotulo" placeholder="Manutenção, limpeza, etc.">
                </div>
              </form>
            </div>

            <!-- Desbloquear Horário -->
            <div class="tab-pane fade" id="aba-desbloquear">
              <form id="formDesbloquear" class="row g-3">
                <input type="hidden" id="d_salaId">
                <div class="col-md-4">
                  <label class="form-label">Data</label>
                  <input type="date" class="form-control" id="d_data" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label d-block">Hora Início</label>
                  <div class="d-flex gap-2">
                    <select class="form-select" id="d_horaInicioH" required>
                      <?php for ($h=8; $h<=18; $h++): ?>
                        <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option>
                      <?php endfor; ?>
                    </select>
                    <select class="form-select" id="d_horaInicioM" required>
                      <option value="00">00</option>
                      <option value="30">30</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label d-block">Hora Fim</label>
                  <div class="d-flex gap-2">
                    <select class="form-select" id="d_horaFimH" required>
                      <?php for ($h=8; $h<=18; $h++): ?>
                        <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option>
                      <?php endfor; ?>
                    </select>
                    <select class="form-select" id="d_horaFimM" required>
                      <option value="00">00</option>
                      <option value="30">30</option>
                    </select>
                  </div>
                </div>
              </form>
            </div>

            <!-- Configurar Sala (nome/capacidade/status) -->
            <div class="tab-pane fade" id="aba-configurar">
              <form method="post" class="row g-3">
                <input type="hidden" name="acao" value="atualizar_sala">
                <input type="hidden" name="id" id="c_salaId">
                <div class="col-md-6">
                  <label class="form-label">Nome da sala</label>
                  <input type="text" class="form-control" name="nome" id="c_nome" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Capacidade</label>
                  <input type="number" class="form-control" name="capacidade" id="c_capacidade" min="1" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status" id="c_status" required>
                    <option value="disponivel">Disponível</option>
                    <option value="bloqueada">Bloqueada</option>
                  </select>
                </div>
                <div class="col-12">
                  <button class="btn btn-primary">Salvar configurações</button>
                </div>
              </form>

              <hr class="my-3">

              <!-- Criar / Excluir rápido -->
              <form method="post" class="row g-2">
                <input type="hidden" name="acao" value="criar_sala">
                <div class="col-7"><input type="text" class="form-control" name="nome" placeholder="Nova sala" required></div>
                <div class="col-3"><input type="number" class="form-control" name="capacidade" placeholder="Cap." min="1" required></div>
                <div class="col-2"><button class="btn btn-outline-success w-100">Criar</button></div>
              </form>

              <div class="mt-2">
                <?php foreach ($salas as $s): ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="acao" value="excluir_sala">
                    <input type="hidden" name="id" value="<?= (int)$s['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger me-1 mb-1"
                      onclick="return confirm('Excluir sala <?= htmlspecialchars($s['nome']); ?>?')">
                      Excluir <?= htmlspecialchars($s['nome']); ?>
                    </button>
                  </form>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <!-- Botões de ação das abas (somente JS visual) -->
          <button type="button" class="btn btn-success" id="btnSalvarReserva">Salvar Reserva (visual)</button>
          <button type="button" class="btn btn-outline-danger" id="btnAplicarBloqueio">Aplicar Bloqueio</button>
          <button type="button" class="btn btn-outline-secondary" id="btnAplicarDesbloqueio">Desbloquear Faixa</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // ====== Estado do modal ======
    const modalAdmin = new bootstrap.Modal(document.getElementById('modalAdmin'));
    let salaSelecionadaId = null;
    let salaSelecionadaNome = '';
    let salaSelecionadaCap  = 0;
    let salaSelecionadaStatus = 'disponivel';

    // Preencher campos da aba "Configurar"
    function preencherConfigurar(){
      document.getElementById('c_salaId').value    = salaSelecionadaId || '';
      document.getElementById('c_nome').value      = salaSelecionadaNome || '';
      document.getElementById('c_capacidade').value= salaSelecionadaCap || 1;
      document.getElementById('c_status').value    = salaSelecionadaStatus || 'disponivel';
    }

    // Abrir modal ao clicar em "Abrir painel" OU ao clicar em um card (se desejar)
    document.getElementById('btnAbrirModalAdmin').addEventListener('click', ()=>{
      if(!salaSelecionadaId){
        // tenta pegar a primeira sala disponível
        const btn = document.querySelector('.btn-sala:not(.disabled)');
        if(btn){
          salaSelecionadaId = parseInt(btn.dataset.sala,10);
          salaSelecionadaNome = btn.dataset.nome || 'Sala';
          salaSelecionadaCap = parseInt(btn.dataset.cap || '1',10) || 1;
          salaSelecionadaStatus = btn.dataset.status || 'disponivel';
        }
      }
      document.getElementById('nomeSalaModal').textContent = salaSelecionadaNome ? `— ${salaSelecionadaNome}` : '';
      preencherConfigurar();
      // defaults de horários
      ['r_','b_','d_'].forEach(prefix=>{
        const hIni = document.getElementById(prefix+'horaInicioH');
        const mIni = document.getElementById(prefix+'horaInicioM');
        const hFim = document.getElementById(prefix+'horaFimH');
        const mFim = document.getElementById(prefix+'horaFimM');
        if (hIni) hIni.value = '08';
        if (mIni) mIni.value = '00';
        if (hFim) hFim.value = '09';
        if (mFim) mFim.value = '00';
      });
      modalAdmin.show();
    });

    // Se quiser abrir ao clicar no card de sala:
    document.addEventListener('click', (e)=>{
      const btn = e.target.closest('.btn-sala');
      if(!btn || btn.classList.contains('disabled')) return;
      salaSelecionadaId = parseInt(btn.dataset.sala,10);
      salaSelecionadaNome = btn.dataset.nome || 'Sala';
      salaSelecionadaCap = parseInt(btn.dataset.cap || '1',10) || 1;
      salaSelecionadaStatus = btn.dataset.status || 'disponivel';
      document.getElementById('nomeSalaModal').textContent = salaSelecionadaNome ? `— ${salaSelecionadaNome}` : '';
      preencherConfigurar();
      modalAdmin.show();
    });

    // Regras de horário (mesmas do seu front)
    function dois(n){ n=parseInt(n,10); return (n<10?'0':'')+n; }
    function meiaHora(t){ return /:(00|30)$/.test(t); }
    function dentroJanela(ini,fim){ return (ini>='08:00' && fim<='18:00' && ini<fim); }

    // Impedir 18:30
    ['r_horaInicioH','r_horaFimH','b_horaInicioH','b_horaFimH','d_horaInicioH','d_horaFimH'].forEach(id=>{
      const el = document.getElementById(id);
      if(!el) return;
      el.addEventListener('change', (e)=>{ if(e.target.value==='18'){ const mSel=document.getElementById(id.replace('H','M')); if(mSel) mSel.value='00'; } });
    });

    // ====== Ações VISUAIS (dependem dos helpers do calendário) ======
    function aplicarFaixa(tipo, dataISO, hi, hf, rot){
      if(!meiaHora(hi) || !meiaHora(hf) || !dentroJanela(hi,hf)){ alert('Respeite 08:00–18:00 e intervalos de 30 minutos.'); return; }
      if(tipo==='reserva' && typeof window.aplicarReserva==='function') window.aplicarReserva(dataISO, hi, hf, rot||'Reservado');
      if(tipo==='bloqueio' && typeof window.aplicarBloqueio==='function') window.aplicarBloqueio(dataISO, hi, hf, rot||'Bloqueado');
      if(tipo==='limpar'   && typeof window.limparFaixa==='function')    window.limparFaixa(dataISO, hi, hf);
    }

    // Botões do rodapé
    document.getElementById('btnSalvarReserva').addEventListener('click', ()=>{
      const data = document.getElementById('r_data').value;
      const hi = dois(document.getElementById('r_horaInicioH').value)+':'+document.getElementById('r_horaInicioM').value;
      const hf = dois(document.getElementById('r_horaFimH').value)+':'+document.getElementById('r_horaFimM').value;
      const rot= document.getElementById('r_setor').value || 'Reservado';
      if(!data){ alert('Selecione a data.'); return; }
      aplicarFaixa('reserva', data, hi, hf, rot);
    });

    document.getElementById('btnAplicarBloqueio').addEventListener('click', ()=>{
      const data = document.getElementById('b_data').value;
      const hi = dois(document.getElementById('b_horaInicioH').value)+':'+document.getElementById('b_horaInicioM').value;
      const hf = dois(document.getElementById('b_horaFimH').value)+':'+document.getElementById('b_horaFimM').value;
      const rot= document.getElementById('b_rotulo').value || 'Bloqueado';
      if(!data){ alert('Selecione a data.'); return; }
      aplicarFaixa('bloqueio', data, hi, hf, rot);
    });

    document.getElementById('btnAplicarDesbloqueio').addEventListener('click', ()=>{
      const data = document.getElementById('d_data').value;
      const hi = dois(document.getElementById('d_horaInicioH').value)+':'+document.getElementById('d_horaInicioM').value;
      const hf = dois(document.getElementById('d_horaFimH').value)+':'+document.getElementById('d_horaFimM').value;
      if(!data){ alert('Selecione a data.'); return; }
      aplicarFaixa('limpar', data, hi, hf);
    });

    // ====== Fallbacks caso calendario.php não tenha as funções ======
    if(typeof window.limparFaixa !== 'function'){
      window.limparFaixa = function(dataISO, hi, hf){
        const cells = document.querySelectorAll(`[data-date='${dataISO}']`);
        let ativar = false;
        cells.forEach(td=>{
          const h = td.dataset.hora;
          if(h===hi) ativar=true;
          if(ativar){
            td.classList.remove('reservado','bloqueado','selected');
            td.innerText = '';
          }
          if(h===hf) ativar=false;
        });
      };
    }
  </script>
</body>
</html>
