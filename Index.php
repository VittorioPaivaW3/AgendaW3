<?php
// index.php (frontend corrigido)
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Agenda W3</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    :root{
      --w3-primary:#0d6efd;
      --w3-success:#198754;
      --w3-muted:#6c757d;
      --w3-bg:#f8f9fa;
      --w3-card:#ffffff;
      --w3-border:#e9ecef;
    }
    body{ background: var(--w3-bg); }

    /* ====== Salas (cards) ====== */
    .side-title{ font-weight:700; font-size:1.05rem; }
    .room-card{
      background: var(--w3-card);
      border:1px solid var(--w3-border);
      border-radius:16px;
      padding:14px;
      transition: box-shadow .2s, border-color .2s;
      width: 100%;
      text-align: left;
    }
    .room-card:not(.disabled):hover{ box-shadow:0 6px 24px rgba(0,0,0,.06); border-color:#dfe3e7; }
    .room-card.active{ box-shadow:0 0 0 3px rgba(13,110,253,.25); border-color:var(--w3-primary); }
    .room-header{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; width:100%; }
    .room-name{ font-weight:700; }
    .status-pill{ font-size:.75rem; padding:.2rem .55rem; border-radius:999px; border:1px solid transparent; }
    .status-ok{ background:#e8f5ee; color:#17834d; border-color:#cfe8db; }
    .status-block{ background:#fde9ec; color:#b23b4a; border-color:#f6cfd5; }
    .meta{ color:#6c757d; font-size:.9rem; display:flex; align-items:center; gap:.5rem; }
    .amenities{ display:flex; flex-wrap:wrap; gap:.6rem 1rem; margin-top:.35rem; color:#5b6672; font-size:.9rem; }
    .amenities i{ margin-right:.25rem; }
    .btn-sala.disabled{ opacity:.65; cursor:not-allowed; }

    /* ====== Calendário ====== */
    .calendar-wrap{ background:var(--w3-card); border:1px solid var(--w3-border); border-radius:16px; }
    .calendar-header{ padding:12px 16px; border-bottom:1px solid var(--w3-border); display:flex; flex-wrap:wrap; align-items:center; gap:.75rem; justify-content:space-between; }
    .calendar-title{ font-weight:700; }
    .calendar-sub{ color:#6c757d; font-size:.9rem; }
    table.calendar{ width:100%; margin:0; }
    table.calendar th, table.calendar td{ border:1px solid var(--w3-border) !important; }
    table.calendar thead th{ background:#fcfcfd; position:sticky; top:0; z-index:1; }
    table.calendar td.hora-col{ background:#fcfcfd; font-weight:600; width:86px; }
    table.calendar td.horario{ height:48px; min-width:120px; cursor:pointer; }

    .legend{ display:flex; gap:1rem; align-items:center; font-size:.9rem; }

    /* Estados das células */
    .horario { cursor: pointer; }
    .horario.selected  { background-color: var(--w3-primary); color: #fff; }
    .horario.reservado { background-color: var(--w3-success); color: #fff; }
    .horario.bloqueado { background-color: var(--w3-muted);   color: #fff; }
  </style>
</head>
<body>
  <!-- Header -->
  <nav class="navbar bg-white border-bottom mb-3">
    <div class="container-fluid py-2">
      <span class="navbar-brand mb-0 h1">Agenda W3</span>
      <div class="d-flex gap-2">
        <a href="?admin=1" class="btn btn-outline-secondary btn-sm">Modo Admin</a>
        <a href="?" class="btn btn-outline-secondary btn-sm">Modo Usuário</a>
      </div>
    </div>
  </nav>

  <div class="container pb-4">
    
    <div class="row g-3">
      <!-- Coluna esquerda: Salas -->
      <div class="col-lg-4 col-xl-3">
        <div class="side-title mb-2">Salas Disponíveis</div>
        <?php include 'salas.php'; ?>
      </div>

      <!-- Coluna direita: Calendário -->
      <div class="col-lg-8 col-xl-9">
        <div class="calendar-wrap">
          <?php include 'calendario.php'; ?>
        </div>

        <div class="mt-3 ps-1 legend">
          <span class="badge bg-primary">Selecionado</span>
          <span class="badge bg-success">Reservado</span>
          <span class="badge bg-secondary">Bloqueado</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Configuração da Sala / Reserva -->
  <div class="modal fade" id="salaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Nova Reserva</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <form id="salaForm" class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Número de Pessoas</label>
              <input type="number" class="form-control" id="numPessoas" min="1" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Reunião</label>
              <select class="form-select" id="tipoReuniao" required>
                <option value="presencial">Presencial</option>
                <option value="online">Online</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Quitutes</label>
              <select class="form-select" id="quitutes" required>
                <option value="nao">Não</option>
                <option value="sim">Sim</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Setor da Empresa</label>
              <select class="form-select" id="setor" required>
                <option value="">Selecione...</option>
                <option value="RH">RH</option>
                <option value="TI">TI</option>
                <option value="Compras">Compras</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Solicitante</label>
              <input type="text" class="form-control" id="solicitante" placeholder="Nome do responsável" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Data</label>
              <input type="date" class="form-control" id="dataReuniao" required>
            </div>

            <!-- Hora Início -->
            <div class="col-md-4">
              <label class="form-label d-block">Hora Início</label>
              <div class="d-flex gap-2">
                <select class="form-select" id="horaInicioH" required>
                  <?php for ($h=8; $h<=18; $h++): ?>
                    <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option>
                  <?php endfor; ?>
                </select>
                <select class="form-select" id="horaInicioM" required>
                  <option value="00">00</option>
                  <option value="30">30</option>
                </select>
              </div>
            </div>

            <!-- Hora Fim -->
            <div class="col-md-4">
              <label class="form-label d-block">Hora Fim</label>
              <div class="d-flex gap-2">
                <select class="form-select" id="horaFimH" required>
                  <?php for ($h=8; $h<=18; $h++): ?>
                    <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option>
                  <?php endfor; ?>
                </select>
                <select class="form-select" id="horaFimM" required>
                  <option value="00">00</option>
                  <option value="30">30</option>
                </select>
              </div>
            </div>

            <div class="col-md-12">
              <label class="form-label">Observações</label>
              <textarea class="form-control" id="observacoes" rows="3" placeholder="Digite observações adicionais..."></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="salvarSala">Salvar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const salaModal = new bootstrap.Modal(document.getElementById('salaModal'));
    let salaSelecionada = null;

    // Clique nas salas (ignora as bloqueadas)
    document.addEventListener('click', (e) => {
      const btnSala = e.target.closest('.btn-sala');
      if (btnSala && !btnSala.classList.contains('disabled')) {
        document.querySelectorAll('.btn-sala').forEach(el => el.classList.remove('active'));
        btnSala.classList.add('active');

        salaSelecionada = btnSala.dataset.sala;
        const nomeSala = btnSala.dataset.nome || 'Sala';
        const span = document.getElementById('calRoomName');
        if (span) span.textContent = nomeSala;

        // valores padrão nos selects
        document.getElementById('horaInicioH').value = '08';
        document.getElementById('horaInicioM').value = '00';
        document.getElementById('horaFimH').value = '09';
        document.getElementById('horaFimM').value = '00';
        salaModal.show();
      }
    });

    // Utilitários de horário
    function pad2(n){ n = parseInt(n,10); return (n<10?'0':'') + n; }
    function toMinutes(t) { const [h,m] = t.split(':').map(Number); return h*60 + m; }
    function isHalfHour(t) { return /:(00|30)$/.test(t); }
    function withinWindow(start, end) {
      const S = toMinutes(start), E = toMinutes(end);
      const WMIN = 8*60, WMAX = 18*60; // 08:00–18:00
      return S >= WMIN && E <= WMAX && S < E;
    }

    // Impedir 18:30 ou além
    document.getElementById('horaInicioH').addEventListener('change', (e)=>{
      if (e.target.value === '18') document.getElementById('horaInicioM').value = '00';
    });
    document.getElementById('horaFimH').addEventListener('change', (e)=>{
      if (e.target.value === '18') document.getElementById('horaFimM').value = '00';
    });

    // Salvar (reserva) -> valida e marca no calendário
    document.getElementById('salvarSala').addEventListener('click', () => {
      const horaInicio = pad2(document.getElementById('horaInicioH').value) + ':' + document.getElementById('horaInicioM').value;
      const horaFim    = pad2(document.getElementById('horaFimH').value)    + ':' + document.getElementById('horaFimM').value;

      const dados = {
        sala: salaSelecionada,
        numPessoas: document.getElementById('numPessoas').value,
        tipoReuniao: document.getElementById('tipoReuniao').value,
        quitutes: document.getElementById('quitutes').value,
        setor: document.getElementById('setor').value,
        solicitante: document.getElementById('solicitante').value,
        data: document.getElementById('dataReuniao').value,
        horaInicio,
        horaFim,
        observacoes: document.getElementById('observacoes').value
      };

      if (!dados.data) { alert('Selecione a data.'); return; }
      if (!isHalfHour(dados.horaInicio) || !isHalfHour(dados.horaFim)) {
        alert('Horários devem estar em intervalos de 30 minutos (:00 ou :30).');
        return;
      }
      if (!withinWindow(dados.horaInicio, dados.horaFim)) {
        alert('A reserva deve estar entre 08:00 e 18:00 e o fim maior que o início.');
        return;
      }

      aplicarReserva(dados.data, dados.horaInicio, dados.horaFim, dados.setor || 'Reservado');
      salaModal.hide();
    });

    // Selecionar múltiplos horários manualmente
    document.addEventListener("click", function(e){
      if(e.target.classList.contains("horario") &&
         !e.target.classList.contains("reservado") &&
         !e.target.classList.contains("bloqueado")){
        e.target.classList.toggle("selected");
      }
    });

    // Helpers para pintar calendário
    function aplicarReserva(dataISO, horaInicio, horaFim, label='Reservado'){
      const cells = document.querySelectorAll(`[data-date='${dataISO}']`);
      let run = false;
      cells.forEach(c => {
        const h = c.dataset.hora;
        if (h === horaInicio) run = true;
        if (run) {
          c.classList.remove('selected');
          c.classList.add('reservado');
          c.innerText = label;
        }
        if (h === horaFim) run = false;
      });
    }

    function aplicarBloqueio(dataISO, horaInicio, horaFim, label='Bloqueado'){
      const cells = document.querySelectorAll(`[data-date='${dataISO}']`);
      let run = false;
      cells.forEach(c => {
        const h = c.dataset.hora;
        if (h === horaInicio) run = true;
        if (run) {
          c.classList.remove('selected','reservado');
          c.classList.add('bloqueado');
          c.innerText = label;
        }
        if (h === horaFim) run = false;
      });
    }

    // expõe para chamadas vindas do PHP (salas.php)
    window.aplicarBloqueio = aplicarBloqueio;
    window.aplicarReserva  = aplicarReserva;
  </script>
</body>
</html>
