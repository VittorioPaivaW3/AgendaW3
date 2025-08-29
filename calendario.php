<?php
// calendario.php — grade semanal; JS busca eventos no banco e pinta
function segunda(string $data): int {
  $ts = strtotime($data);
  $dow = (int)date('w', $ts); // 0=dom
  return strtotime("-".($dow===0?6:$dow-1)." days", $ts);
}
$dataRef = $_GET['week'] ?? date('Y-m-d');
$seg = segunda($dataRef);
$dias = []; for($i=0;$i<5;$i++) $dias[] = strtotime("+$i day", $seg);
$nomes = ["Segunda","Terça","Quarta","Quinta","Sexta"];
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <a class="btn btn-outline-secondary btn-sm" href="?week=<?= date('Y-m-d', strtotime('-7 days', $seg)) ?>">&larr; Semana Anterior</a>
  <h5 class="mb-0">Semana de <?= date('d/m/Y',$dias[0]) ?> a <?= date('d/m/Y', end($dias)) ?></h5>
  <a class="btn btn-outline-secondary btn-sm" href="?week=<?= date('Y-m-d', strtotime('+7 days', $seg)) ?>">Próxima Semana &rarr;</a>
</div>
<div class="table-responsive">
  <table class="table table-bordered text-center align-middle">
    <thead><tr>
      <th>Horário</th>
      <?php foreach ($dias as $i=>$d): ?><th><?= $nomes[$i] ?><br><?= date('d/m',$d) ?></th><?php endforeach; ?>
    </tr></thead>
    <tbody>
      <?php for($h=8;$h<18;$h++): foreach([0,30] as $m):
        $hora = sprintf('%02d:%02d',$h,$m);
        echo "<tr>";
        echo "<td><strong>$hora</strong></td>";
        foreach ($dias as $d){
          $iso = date('Y-m-d',$d);
          echo "<td class='horario' data-date='$iso' data-hora='$hora'></td>";
        }
        echo "</tr>";
      endforeach; endfor; ?>
    </tbody>
  </table>
</div>
<script>
  // Helpers de pintura
  function pintarFaixa(tipo, dataISO, ini, fim, rot){
    const cells = document.querySelectorAll(`[data-date='${dataISO}']`);
    let run=false;
    cells.forEach(td=>{
      const h = td.dataset.hora;
      if(h===ini) run=true;
      if(run){
        td.classList.remove('selected');
        td.classList.remove(tipo==='reserva'?'bloqueado':'reservado');
        td.classList.add(tipo==='reserva'?'reservado':'bloqueado');
        td.innerText = rot || (tipo==='reserva'?'Reservado':'Bloqueado');
      }
      if(h===fim) run=false;
    });
  }
  function limparFaixa(dataISO, ini, fim){
    const cells = document.querySelectorAll(`[data-date='${dataISO}']`);
    let run=false;
    cells.forEach(td=>{
      const h = td.dataset.hora;
      if(h===ini) run=true;
      if(run){ td.classList.remove('reservado','bloqueado','selected'); td.innerText=''; }
      if(h===fim) run=false;
    });
  }
  window.aplicarReserva  = (d, i, f, r) => pintarFaixa('reserva',  d, i, f, r);
  window.aplicarBloqueio = (d, i, f, r) => pintarFaixa('bloqueio', d, i, f, r);
  window.limparFaixa     = limparFaixa;

  // Carregar eventos da semana a partir do banco
  async function carregarSemana(roomId = null, mondayISO = '<?= date('Y-m-d',$seg) ?>'){
    const u = new URL('api_eventos_semana.php', location.href);
    u.searchParams.set('monday', mondayISO);
    if(roomId) u.searchParams.set('id_sala', roomId);
    const r = await fetch(u), j = await r.json().catch(()=>({}));
    document.querySelectorAll('.horario').forEach(td=>{ td.classList.remove('reservado','bloqueado','selected'); td.innerText=''; });
    if(!j.ok) return;
    j.eventos.forEach(ev=>{
      const label = ev.status_sala==='bloqueio' ? (ev.observacoes||'Bloqueado') : (ev.setor||'Reservado');
      if(ev.status_sala==='bloqueio') aplicarBloqueio(ev.data, ev.hora_inicio.substring(0,5), ev.hora_fim.substring(0,5), label);
      else aplicarReserva(ev.data, ev.hora_inicio.substring(0,5), ev.hora_fim.substring(0,5), label);
    });
  }
  async function carregarSemanaAtual(){
    const mondayISO = '<?= date('Y-m-d',$seg) ?>';
    await carregarSemana(null, mondayISO);
  }
  // Carrega de cara
  carregarSemanaAtual();
</script>
