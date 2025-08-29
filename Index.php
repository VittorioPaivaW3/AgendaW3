<?php /* index.php — modo usuário com modal de reserva funcional */ ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Agenda W3</title>
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
    .meta{ color:#6c757d; font-size:.9rem; display:flex; align-items:center; gap:.5rem; }
    .amenities{ display:flex; gap:1rem; flex-wrap:wrap; color:#5b6672; font-size:.9rem; }
    .calendar-wrap{ background:var(--w3-card); border:1px solid var(--w3-border); border-radius:16px; }
    .horario.selected{ background:var(--w3-primary); color:#fff; }
    .horario.reservado{ background:var(--w3-success); color:#fff; }
    .horario.bloqueado{ background:var(--w3-muted); color:#fff; }
  </style>
</head>
<body>
  <nav class="navbar bg-white border-bottom mb-3">
    <div class="container-fluid py-2">
      <span class="navbar-brand mb-0 h1">Agenda W3</span>
      <div class="d-flex gap-2">
        <a href="admin.php" class="btn btn-outline-secondary btn-sm">Modo Admin</a>
      </div>
    </div>
  </nav>

  <div class="container pb-4">
    <div class="row g-3">
      <div class="col-lg-4 col-xl-3">
        <?php include __DIR__ . '/salas.php'; ?>
      </div>
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

  <!-- MODAL DO USUÁRIO (reservar) -->
  <div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reservar Sala <span class="text-muted" id="nomeSalaModal"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <form id="formReservar" class="row g-3">
          <input type="hidden" id="r_salaId">
          <div class="col-md-4">
            <label class="form-label">Número de Pessoas</label>
            <input type="number" class="form-control" id="r_pessoas" min="1" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Reunião</label>
            <select class="form-select" id="r_tipo" required>
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
              <option>RH</option>
              <option>TI</option>
              <option>Compras</option>
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
              <select class="form-select" id="r_hIniH" required>
                <?php for($h=8;$h<=18;$h++): ?>
                  <option value="<?= sprintf('%02d',$h) ?>"><?= sprintf('%02d',$h) ?></option>
                <?php endfor; ?>
              </select>
              <select class="form-select" id="r_hIniM" required>
                <option>00</option>
                <option>30</option>
              </select>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label d-block">Hora Fim</label>
            <div class="d-flex gap-2">
              <select class="form-select" id="r_hFimH" required>
                <?php for($h=8;$h<=18;$h++): ?>
                  <option value="<?= sprintf('%02d',$h) ?>"><?= sprintf('%02d',$h) ?></option>
                <?php endfor; ?>
              </select>
              <select class="form-select" id="r_hFimM" required>
                <option>00</option>
                <option>30</option>
              </select>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Observações</label>
            <textarea class="form-control" id="r_obs" rows="3" placeholder="Digite observações adicionais..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="btnSalvarReserva">Salvar Reserva</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ===== Modal usuário =====
    const modalUsuario = new bootstrap.Modal(document.getElementById('modalUsuario'));
    let salaSel = null; // {id, nome, cap, status}

    // abrir modal ao clicar no card de sala
    document.addEventListener('click', (e)=>{
      const btn = e.target.closest('.btn-sala');
      if(!btn || btn.classList.contains('disabled')) return; // sala bloqueada não abre
      salaSel = {
        id: parseInt(btn.dataset.sala,10),
        nome: btn.dataset.nome,
        cap: parseInt(btn.dataset.cap||'1',10) || 1,
        status: btn.dataset.status
      };
      document.getElementById('r_salaId').value = salaSel.id;
      document.getElementById('nomeSalaModal').textContent = '— ' + salaSel.nome;

      // defaults de horário
      document.getElementById('r_hIniH').value = '08';
      document.getElementById('r_hIniM').value = '00';
      document.getElementById('r_hFimH').value = '09';
      document.getElementById('r_hFimM').value = '00';
      modalUsuario.show();
    });

    // impedir 18:30
    ['r_hIniH','r_hFimH'].forEach(id=>{
      const el=document.getElementById(id);
      el.addEventListener('change', e=>{
        if(e.target.value==='18'){
          const m = document.getElementById(id==='r_hIniH'?'r_hIniM':'r_hFimM');
          m.value='00';
        }
      });
    });

    function dois(n){ n=parseInt(n,10); return (n<10?'0':'')+n; }
    function hhmm(hSel,mSel){ return dois(document.getElementById(hSel).value)+':'+document.getElementById(mSel).value; }
    function validaJanela(ini,fim){
      const okStep = /:(00|30)$/.test(ini) && /:(00|30)$/.test(fim);
      return okStep && ini>='08:00' && fim<='18:00' && ini<fim;
    }

    // salvar reserva (POST -> api_evento_salvar.php)
    document.getElementById('btnSalvarReserva').addEventListener('click', async ()=>{
      const data = document.getElementById('r_data').value;
      const ini  = hhmm('r_hIniH','r_hIniM');
      const fim  = hhmm('r_hFimH','r_hFimM');

      if(!salaSel?.id){ alert('Selecione uma sala.'); return; }
      if(!data){ alert('Selecione a data.'); return; }
      if(!validaJanela(ini,fim)){ alert('Horários devem ser 08:00–18:00 em passos de 30 minutos, e fim > início.'); return; }

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

      try{
        const resp = await fetch('api_evento_salvar.php', { method:'POST', body: fd });
        const json = await resp.json();
        if(!json.ok){ alert(json.erro || 'Falha ao salvar.'); return; }
        modalUsuario.hide();
        // repintar a semana a partir do banco
        if (typeof carregarSemanaAtual === 'function') {
          await carregarSemanaAtual();
        }
      }catch(e){
        alert('Erro ao comunicar com o servidor.');
      }
    });

    // Seleção manual de slots (mantido)
    document.addEventListener("click", function(e){
      if(e.target.classList.contains("horario") &&
         !e.target.classList.contains("reservado") &&
         !e.target.classList.contains("bloqueado")){
        e.target.classList.toggle("selected");
      }
    });
  </script>
</body>
</html>
