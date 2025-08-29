<?php
// admin.php — Administração com MySQL
require __DIR__ . '/db.php';

// ===== Ações de SALAS (CRUD) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
  $acao = $_POST['acao'];

  if ($acao === 'criar_sala') {
    $sql = "INSERT INTO salas (nome, capacidade, sede, status, tv, ar_condicionado, video_conferencia, quadro)
            VALUES (?, ?, ?, 'disponivel', ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      $_POST['nome'], (int)$_POST['capacidade'], $_POST['sede'],
      $_POST['tv'] ?? 'nao', $_POST['ar_condicionado'] ?? 'nao',
      $_POST['video_conferencia'] ?? 'nao', $_POST['quadro'] ?? 'nao'
    ]);
  }

  if ($acao === 'atualizar_sala') {
    $sql = "UPDATE salas SET nome=?, capacidade=?, sede=?, status=?, tv=?, ar_condicionado=?, video_conferencia=?, quadro=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      $_POST['nome'], (int)$_POST['capacidade'], $_POST['sede'], $_POST['status'],
      $_POST['tv'] ?? 'nao', $_POST['ar_condicionado'] ?? 'nao',
      $_POST['video_conferencia'] ?? 'nao', $_POST['quadro'] ?? 'nao',
      (int)$_POST['id']
    ]);
  }

  if ($acao === 'excluir_sala') {
    $stmt = $pdo->prepare("DELETE FROM salas WHERE id=?");
    $stmt->execute([(int)$_POST['id']]);
  }

  header('Location: '.$_SERVER['PHP_SELF']); exit;
}

// Carrega salas para os cards e selects
$salas = $pdo->query("SELECT * FROM salas ORDER BY id")->fetchAll();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Agenda W3 — Administração</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    :root{ --w3-primary:#0d6efd; --w3-success:#198754; --w3-muted:#6c757d; --w3-bg:#f8f9fa; --w3-card:#fff; --w3-border:#e9ecef; }
    body{ background:var(--w3-bg); }
    .room-card{ background:var(--w3-card); border:1px solid var(--w3-border); border-radius:16px; padding:14px; text-align:left; }
    .room-header{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .status-pill{ font-size:.75rem; padding:.2rem .55rem; border-radius:999px; border:1px solid transparent; }
    .status-ok{ background:#e8f5ee; color:#17834d; border-color:#cfe8db; }
    .status-block{ background:#fde9ec; color:#b23b4a; border-color:#f6cfd5; }
    .calendar-wrap{ background:var(--w3-card); border:1px solid var(--w3-border); border-radius:16px; }
    .horario.selected{ background:var(--w3-primary); color:#fff; }
    .horario.reservado{ background:var(--w3-success); color:#fff; }
    .horario.bloqueado{ background:var(--w3-muted); color:#fff; }
  </style>
</head>
<body>
  <nav class="navbar bg-white border-bottom mb-3">
    <div class="container-fluid py-2">
      <span class="navbar-brand mb-0 h1">Agenda W3 — Administração</span>
      <div class="d-flex gap-2"><a href="index.php" class="btn btn-outline-secondary btn-sm">Modo Usuário</a></div>
    </div>
  </nav>

  <div class="container pb-4">
    <div class="row g-3">
      <div class="col-lg-4 col-xl-3">
        <?php include __DIR__ . '/salas.php'; ?>
        <div class="card mt-3">
          <div class="card-header fw-semibold">Gerenciar Salas</div>
          <div class="card-body">
            <!-- Criar -->
            <form method="post" class="row g-2 mb-3">
              <input type="hidden" name="acao" value="criar_sala">
              <div class="col-12"><input type="text" class="form-control" name="nome" placeholder="Nome" required></div>
              <div class="col-6"><input type="number" class="form-control" name="capacidade" placeholder="Capacidade" min="1" required></div>
              <div class="col-6">
                <select class="form-select" name="sede" required>
                  <option value="Lake Mall">Lake Mall</option>
                  <option value="Industrial">Industrial</option>
                </select>
              </div>
              <div class="col-3"><label class="form-label small">TV</label><select class="form-select" name="tv"><option>nao</option><option>sim</option></select></div>
              <div class="col-3"><label class="form-label small">Ar</label><select class="form-select" name="ar_condicionado"><option>nao</option><option>sim</option></select></div>
              <div class="col-3"><label class="form-label small">Vídeo</label><select class="form-select" name="video_conferencia"><option>nao</option><option>sim</option></select></div>
              <div class="col-3"><label class="form-label small">Quadro</label><select class="form-select" name="quadro"><option>nao</option><option>sim</option></select></div>
              <div class="col-12"><button class="btn btn-primary w-100">Adicionar</button></div>
            </form>

            <!-- Atualizar -->
            <form method="post" class="row g-2 mb-3">
              <input type="hidden" name="acao" value="atualizar_sala">
              <div class="col-12">
                <select class="form-select" name="id" required>
                  <option value="">Selecione a sala...</option>
                  <?php foreach ($salas as $r): ?>
                    <option value="<?= (int)$r['id']; ?>"><?= htmlspecialchars($r['nome']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6"><input type="text" class="form-control" name="nome" placeholder="Novo nome (opcional)"></div>
              <div class="col-6"><input type="number" class="form-control" name="capacidade" placeholder="Capacidade (opcional)" min="1"></div>
              <div class="col-6">
                <select class="form-select" name="sede">
                  <option value="">Sede (opcional)</option>
                  <option value="Lake Mall">Lake Mall</option>
                  <option value="Industrial">Industrial</option>
                </select>
              </div>
              <div class="col-6">
                <select class="form-select" name="status">
                  <option value="">Status (opcional)</option>
                  <option value="disponivel">Disponível</option>
                  <option value="bloqueado">Bloqueado</option>
                </select>
              </div>
              <div class="col-3"><label class="form-label small">TV</label><select class="form-select" name="tv"><option value="">—</option><option>nao</option><option>sim</option></select></div>
              <div class="col-3"><label class="form-label small">Ar</label><select class="form-select" name="ar_condicionado"><option value="">—</option><option>nao</option><option>sim</option></select></div>
              <div class="col-3"><label class="form-label small">Vídeo</label><select class="form-select" name="video_conferencia"><option value="">—</option><option>nao</option><option>sim</option></select></div>
              <div class="col-3"><label class="form-label small">Quadro</label><select class="form-select" name="quadro"><option value="">—</option><option>nao</option><option>sim</option></select></div>
              <div class="col-12"><button class="btn btn-outline-primary w-100">Salvar</button></div>
            </form>

            <!-- Excluir -->
            <div class="mb-2">
              <?php foreach ($salas as $r): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="acao" value="excluir_sala">
                  <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger me-1 mb-1" onclick="return confirm('Excluir sala <?= htmlspecialchars($r['nome']); ?>?')">
                    Excluir <?= htmlspecialchars($r['nome']); ?>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="mt-3">
          <button type="button" class="btn btn-success w-100" id="btnAbrirModalAdmin">Abrir painel do administrador</button>
        </div>
      </div>

      <div class="col-lg-8 col-xl-9">
        <div class="calendar-wrap p-2"><?php include __DIR__ . '/calendario.php'; ?></div>
        <div class="mt-3 ps-1">
          <span class="badge bg-primary">Selecionado</span>
          <span class="badge bg-success">Reservado</span>
          <span class="badge bg-secondary">Bloqueado</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal do usuário + abas extra do admin -->
  <div class="modal fade" id="modalAdmin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Gerenciar Sala <span class="text-muted" id="nomeSalaModal"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body">
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#aba-reservar" type="button">Reservar</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#aba-bloquear" type="button">Bloquear horário</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#aba-desbloquear" type="button">Desbloquear horário</button></li>
        </ul>

        <div class="tab-content pt-3">
          <!-- Reservar -->
          <div class="tab-pane fade show active" id="aba-reservar">
            <form id="formReservar" class="row g-3">
              <input type="hidden" id="r_salaId">
              <div class="col-md-4"><label class="form-label">Número de Pessoas</label><input type="number" class="form-control" id="r_pessoas" min="1" required></div>
              <div class="col-md-4"><label class="form-label">Reunião</label><select class="form-select" id="r_tipo" required><option value="presencial">Presencial</option><option value="online">Online</option></select></div>
              <div class="col-md-4"><label class="form-label">Quitutes</label><select class="form-select" id="r_quitutes" required><option value="nao">Não</option><option value="sim">Sim</option></select></div>
              <div class="col-md-6"><label class="form-label">Setor</label><select class="form-select" id="r_setor" required><option value="">Selecione...</option><option>RH</option><option>TI</option><option>Compras</option></select></div>
              <div class="col-md-6"><label class="form-label">Solicitante</label><input type="text" class="form-control" id="r_solicitante" required></div>
              <div class="col-md-4"><label class="form-label">Data</label><input type="date" class="form-control" id="r_data" required></div>
              <div class="col-md-4">
                <label class="form-label d-block">Hora Início</label>
                <div class="d-flex gap-2">
                  <select class="form-select" id="r_hIniH" required><?php for($h=8;$h<=18;$h++): ?><option value="<?= sprintf('%02d',$h) ?>"><?= sprintf('%02d',$h) ?></option><?php endfor; ?></select>
                  <select class="form-select" id="r_hIniM" required><option>00</option><option>30</option></select>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label d-block">Hora Fim</label>
                <div class="d-flex gap-2">
                  <select class="form-select" id="r_hFimH" required><?php for($h=8;$h<=18;$h++): ?><option value="<?= sprintf('%02d',$h) ?>"><?= sprintf('%02d',$h) ?></option><?php endfor; ?></select>
                  <select class="form-select" id="r_hFimM" required><option>00</option><option>30</option></select>
                </div>
              </div>
              <div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" id="r_obs" rows="3"></textarea></div>
            </form>
          </div>

          <!-- Bloquear -->
          <div class="tab-pane fade" id="aba-bloquear">
            <form id="formBloquear" class="row g-3">
              <input type="hidden" id="b_salaId">
              <div class="col-md-4"><label class="form-label">Data</label><input type="date" class="form-control" id="b_data" required></div>
              <div class="col-md-4">
                <label class="form-label d-block">Hora Início</label>
                <div class="d-flex gap-2">
                  <select class="form-select" id="b_hIniH" required><?php for($h=8;$h<=18;$h++): ?><option value="<?= sprintf('%02d',$h) ?>"><?= sprintf('%02d',$h) ?></option><?php endfor; ?></select>
                  <select class="form-select" id="b_hIniM" required><option>00</option><option>30</option></select>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label d-block">Hora Fim</label>
                <div class="d-flex gap-2">
                  <select class="form-select" id="b_hFimH" required><?php for($h=8;$h<=18;$h++): ?><option value="<?= sprintf('%02d',$h) ?>"><?= sprintf('%02d',$h) ?></option><?php endfor; ?></select>
                  <select class="form-select" id="b_hFimM" required><option>00</option><option>30</option></select>
                </div>
              </div>
              <div class="col-12"><label class="form-label">Motivo (opcional)</label><input type="text" class="form-control" id="b_rotulo" placeholder="Manutenção, limpeza..."></div>
            </form>
          </div>

          <!-- Desbloquear -->
          <div class="tab-pane fade" id="aba-desbloquear">
            <form id="formDesbloquear" class="row g-3">
              <input type="hidden" id="d_salaId">
              <div class="col-md-4"><label class="form-label">Data</label><input type="date" class="form-control" id="d_data" required></div>
              <div class="col-md-4">
                <label class="form-label d-block">Hora Início</label>
                <div class="d-flex gap-2">
                  <select class="form-select" id="d_hIniH" required><?php for($h=8;$h<=18;$h++): ?><option value="<?= sprintf('%02d',$h) ?>"><?= sprintf('%02d',$h) ?></option><?php endfor; ?></select>
                  <select class="form-select" id="d_hIniM" required><option>00</option><option>30</option></select>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label d-block">Hora Fim</label>
                <div class="d-flex gap-2">
                  <select class="form-select" id="d_hFimH" required><?php for($h=8;$h<=18;$h++): ?><option value="<?= sprintf('%02d',$h) ?>"><?= sprintf('%02d',$h) ?></option><?php endfor; ?></select>
                  <select class="form-select" id="d_hFimM" required><option>00</option><option>30</option></select>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="btnSalvarReserva">Salvar Reserva</button>
        <button type="button" class="btn btn-outline-danger" id="btnAplicarBloqueio">Aplicar Bloqueio</button>
        <button type="button" class="btn btn-outline-secondary" id="btnAplicarDesbloqueio">Desbloquear Faixa</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const modalAdmin = new bootstrap.Modal(document.getElementById('modalAdmin'));
    let salaSel = null;

    // Abrir painel (pega a primeira sala não bloqueada, se necessário)
    document.getElementById('btnAbrirModalAdmin').addEventListener('click', ()=>{
      if(!salaSel){
        const b = document.querySelector('.btn-sala:not(.disabled)');
        if(b){ salaSelFromBtn(b); }
      }
      preencherIds();
      modalAdmin.show();
    });

    // Clicar em um card de sala
    document.addEventListener('click', (e)=>{
      const btn = e.target.closest('.btn-sala');
      if(!btn || btn.classList.contains('disabled')) return;
      salaSelFromBtn(btn);
      preencherIds();
      modalAdmin.show();
    });

    function salaSelFromBtn(btn){
      salaSel = {
        id: parseInt(btn.dataset.sala,10),
        nome: btn.dataset.nome, cap: parseInt(btn.dataset.cap||'1',10),
        status: btn.dataset.status, sede: btn.dataset.sede
      };
      document.getElementById('nomeSalaModal').textContent = '— ' + salaSel.nome;
    }
    function dois(n){ n=parseInt(n,10); return (n<10?'0':'')+n; }
    function hhmm(hSel,mSel){ return dois(document.getElementById(hSel).value)+':'+document.getElementById(mSel).value; }
    function preencherIds(){
      ['r_salaId','b_salaId','d_salaId'].forEach(id=>{ const el=document.getElementById(id); if(el) el.value = salaSel?.id||''; });
      // defaults
      [['r_hIniH','r_hIniM','r_hFimH','r_hFimM'],['b_hIniH','b_hIniM','b_hFimH','b_hFimM'],['d_hIniH','d_hIniM','d_hFimH','d_hFimM']].forEach(ids=>{
        const [h1,m1,h2,m2]=ids.map(i=>document.getElementById(i));
        if(h1) h1.value='08'; if(m1) m1.value='00'; if(h2) h2.value='09'; if(m2) m2.value='00';
      });
    }
    function validaJanela(ini,fim){
      const okStep = /:(00|30)$/.test(ini) && /:(00|30)$/.test(fim);
      return okStep && ini>='08:00' && fim<='18:00' && ini<fim;
    }

    // Salvar RESERVA em eventos (status_sala = 'reserva')
    document.getElementById('btnSalvarReserva').addEventListener('click', async ()=>{
      const data = document.getElementById('r_data').value;
      const ini  = hhmm('r_hIniH','r_hIniM'), fim = hhmm('r_hFimH','r_hFimM');
      if(!salaSel?.id || !data || !validaJanela(ini,fim)) { alert('Preencha sala, data e horários válidos (08:00–18:00, :00/:30).'); return; }
      const fd = new FormData();
      fd.set('tipo','reserva');
      fd.set('id_sala', salaSel.id);
      fd.set('data', data);
      fd.set('hora_inicio', ini);
      fd.set('hora_fim', fim);
      fd.set('setor', document.getElementById('r_setor').value);
      fd.set('solicitante', document.getElementById('r_solicitante').value);
      fd.set('tipo_reuniao', document.getElementById('r_tipo').value);
      fd.set('quitutes', document.getElementById('r_quitutes').value);
      fd.set('pessoas', document.getElementById('r_pessoas').value);
      fd.set('observacoes', document.getElementById('r_obs').value);

      const r = await fetch('api_evento_salvar.php', { method:'POST', body: fd });
      const j = await r.json().catch(()=>({}));
      if(!j.ok){ alert(j.erro||'Falha ao salvar.'); return; }
      await carregarSemanaAtual(); // repinta
    });

    // BLOQUEAR (status_sala = 'bloqueio')
    document.getElementById('btnAplicarBloqueio').addEventListener('click', async ()=>{
      const data = document.getElementById('b_data').value;
      const ini  = hhmm('b_hIniH','b_hIniM'), fim = hhmm('b_hFimH','b_hFimM');
      if(!salaSel?.id || !data || !validaJanela(ini,fim)) { alert('Preencha sala, data e horários válidos.'); return; }
      const fd = new FormData();
      fd.set('tipo','bloqueio');
      fd.set('id_sala', salaSel.id);
      fd.set('data', data);
      fd.set('hora_inicio', ini);
      fd.set('hora_fim', fim);
      fd.set('setor', ''); fd.set('solicitante',''); fd.set('tipo_reuniao','online'); fd.set('quitutes','nao'); fd.set('pessoas','0'); fd.set('observacoes', document.getElementById('b_rotulo').value||'Bloqueado');
      const r = await fetch('api_evento_salvar.php', { method:'POST', body: fd });
      const j = await r.json().catch(()=>({}));
      if(!j.ok){ alert(j.erro||'Falha ao bloquear.'); return; }
      await carregarSemanaAtual();
    });

    // DESBLOQUEAR: remove eventos tipo "bloqueio" naquele intervalo
    document.getElementById('btnAplicarDesbloqueio').addEventListener('click', async ()=>{
      const data = document.getElementById('d_data').value;
      const ini  = hhmm('d_hIniH','d_hIniM'), fim = hhmm('d_hFimH','d_hFimM');
      if(!salaSel?.id || !data || !validaJanela(ini,fim)) { alert('Preencha sala, data e horários válidos.'); return; }
      const fd = new FormData();
      fd.set('id_sala', salaSel.id);
      fd.set('data', data);
      fd.set('hora_inicio', ini);
      fd.set('hora_fim', fim);
      const r = await fetch('api_bloqueio_remover.php', { method:'POST', body: fd });
      const j = await r.json().catch(()=>({}));
      if(!j.ok){ alert(j.erro||'Falha ao desbloquear.'); return; }
      await carregarSemanaAtual();
    });

    // Abrir modal via botão
    const btnModal = document.getElementById('btnAbrirModalAdmin');
    btnModal && btnModal.addEventListener('click', ()=> modalAdmin.show());
  </script>
</body>
</html>
