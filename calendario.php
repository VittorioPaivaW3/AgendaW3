<?php
// calendario.php — regras e renderização do calendário (inclui modal do usuário)

function obter_segunda(string $data): int {
  $ts = strtotime($data);
  $dow = (int)date('w', $ts); // 0=Dom ... 6=Sáb
  $monday = strtotime("-".($dow == 0 ? 6 : $dow-1)." days", $ts);
  return $monday;
}

function renderizar_calendario(): void {
  $dataAtual = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d');
  $segunda = obter_segunda($dataAtual);
  $dias = [];
  for ($i=0;$i<5;$i++){ $dias[] = strtotime("+$i day", $segunda); }
  $nomes = ["Segunda","Terça","Quarta","Quinta","Sexta"];
  ?>
  <div class="calendar-header">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a class="btn btn-outline-secondary btn-sm" href="?week=<?= date('Y-m-d', strtotime('-7 days', $segunda)) ?>">&larr; Semana Anterior</a>
      <a class="btn btn-outline-secondary btn-sm" href="?week=<?= date('Y-m-d', strtotime('+7 days', $segunda)) ?>">Próxima Semana &rarr;</a>
    </div>
    <div class="text-end">
      <div class="calendar-title"><span id="calRoomName">Selecione uma sala</span> - Calendário</div>
      <div class="calendar-sub">Semana de <?= date('d/m/Y', $dias[0]) ?> a <?= date('d/m/Y', end($dias)) ?></div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered text-center align-middle calendar">
      <thead>
        <tr>
          <th style="width:86px;">Horário</th>
          <?php foreach ($dias as $i=>$d): ?>
          <th>
            <div class="fw-semibold"><?= $nomes[$i] ?></div>
            <div class="text-muted small"><?= date('d/m', $d) ?></div>
          </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        for ($h=8; $h<18; $h++) {
          foreach ([0,30] as $m) {
            $hora = sprintf('%02d:%02d', $h, $m);
            echo '<tr>';
            echo "<td class='hora-col'><strong>$hora</strong></td>";
            foreach ($dias as $d) {
              $data = date('Y-m-d', $d);
              echo "<td class='horario' data-date='$data' data-hora='$hora'></td>";
            }
            echo '</tr>';
          }
        }
        ?>
      </tbody>
    </table>
  </div>

  <!-- Modal do Usuário: Reservar -->
  <div class="modal fade" id="salaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Nova Reserva</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <form id="formReservaUsuario" class="row g-3">
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
              <label class="form-label">Setor</label>
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
          <button type="button" class="btn btn-primary" id="btnSalvarReservaUsuario">Salvar</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // ===== Utilitários de horário (JS) =====
    function duas_casas(n){ n = parseInt(n,10); return (n<10?'0':'') + n; }
    function para_minutos(t){ const [h,m] = t.split(':').map(Number); return h*60+m; }
    function eh_meia_hora(t){ return /:(00|30)$/.test(t); }
    function dentro_janela(inicio, fim){
      const S = para_minutos(inicio), E = para_minutos(fim);
      const WMIN = 8*60, WMAX = 18*60;
      return S >= WMIN && E <= WMAX && S < E;
    }

    // Impedir 18:30 ou além
    ['horaInicioH','horaFimH'].forEach(id=>{
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('change', e=>{
        if (e.target.value === '18') {
          const idM = id.replace('H','M');
          const m = document.getElementById(idM);
          if (m) m.value = '00';
        }
      });
    });

    // Pintura no calendário
    function aplicar_reserva(dataISO, horaInicio, horaFim, rotulo='Reservado'){
      const cells = document.querySelectorAll(`[data-date='${dataISO}']`);
      let run = false;
      cells.forEach(c=>{
        const h = c.dataset.hora;
        if (h === horaInicio) run = true;
        if (run) {
          c.classList.remove('selected');
          c.classList.add('reservado');
          c.innerText = rotulo;
        }
        if (h === horaFim) run = false;
      });
    }
    function aplicar_bloqueio(dataISO, horaInicio, horaFim, rotulo='Bloqueado'){
      const cells = document.querySelectorAll(`[data-date='${dataISO}']`);
      let run = false;
      cells.forEach(c=>{
        const h = c.dataset.hora;
        if (h === horaInicio) run = true;
        if (run) {
          c.classList.remove('selected','reservado');
          c.classList.add('bloqueado');
          c.innerText = rotulo;
        }
        if (h === horaFim) run = false;
      });
    }
    window.aplicar_bloqueio = aplicar_bloqueio;
    window.aplicar_reserva  = aplicar_reserva;

    // Seleção manual de células
    document.addEventListener('click', function(e){
      if(e.target.classList.contains('horario') &&
         !e.target.classList.contains('reservado') &&
         !e.target.classList.contains('bloqueado')){
        e.target.classList.toggle('selected');
      }
    });

    // Salvar (usuário) — apenas visual por enquanto
    document.getElementById('btnSalvarReservaUsuario')?.addEventListener('click', ()=>{
      const hI = duas_casas(document.getElementById('horaInicioH').value)+':'+document.getElementById('horaInicioM').value;
      const hF = duas_casas(document.getElementById('horaFimH').value)+':'+document.getElementById('horaFimM').value;
      const dataISO = document.getElementById('dataReuniao').value;
      const setor = document.getElementById('setor').value;

      if (!dataISO) { alert('Selecione a data.'); return; }
      if (!eh_meia_hora(hI) || !eh_meia_hora(hF)) { alert('Use :00 ou :30.'); return; }
      if (!dentro_janela(hI, hF)) { alert('08:00–18:00 e fim > início.'); return; }

      aplicar_reserva(dataISO, hI, hF, setor || 'Reservado');
      const modalEl = document.getElementById('salaModal');
      if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });
  </script>
  <?php
}
