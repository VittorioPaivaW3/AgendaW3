<?php
// calendario.php (corrigido)

// Segunda-feira da semana de uma data
function getMonday($date) {
    $dayOfWeek = date('w', strtotime($date)); // 0=domingo ... 6=sábado
    $monday = strtotime("-".($dayOfWeek == 0 ? 6 : $dayOfWeek-1)." days", strtotime($date));
    return $monday;
}

$currentDate = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d');
$monday = getMonday($currentDate);

// Seg a Sex
$days = [];
for ($i = 0; $i < 5; $i++) {
    $days[] = strtotime("+$i day", $monday);
}

$diasSemana = ["Segunda", "Terça", "Quarta", "Quinta", "Sexta"];
?>

<div class="calendar-header">
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <a class="btn btn-outline-secondary btn-sm"
       href="?week=<?= date('Y-m-d', strtotime('-7 days', $monday)) ?>">&larr; Semana Anterior</a>
    <a class="btn btn-outline-secondary btn-sm"
       href="?week=<?= date('Y-m-d', strtotime('+7 days', $monday)) ?>">Próxima Semana &rarr;</a>
  </div>
  <div class="text-end">
    <div class="calendar-title"><span id="calRoomName">Selecione uma sala</span> - Calendário</div>
    <div class="calendar-sub">Semana de <?= date('d/m/Y', $days[0]) ?> a <?= date('d/m/Y', end($days)) ?></div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-bordered text-center align-middle calendar">
    <thead>
      <tr>
        <th style="width:86px;">Horário</th>
        <?php foreach ($days as $i => $d) { ?>
          <th>
            <div class="fw-semibold"><?= $diasSemana[$i] ?></div>
            <div class="text-muted small"><?= date('d/m', $d) ?></div>
          </th>
        <?php } ?>
      </tr>
    </thead>
    <tbody>
      <?php
      // 08:00 a 18:00, a cada 30min
      for ($h = 8; $h < 18; $h++) {
        foreach ([0, 30] as $m) {
          $hora = sprintf("%02d:%02d", $h, $m);
          echo "<tr>";
          echo "<td class='hora-col'><strong>$hora</strong></td>";
          foreach ($days as $d) {
            $data = date('Y-m-d', $d);
            echo "<td class='horario' data-date='$data' data-hora='$hora'></td>";
          }
          echo "</tr>";
        }
      }
      ?>
    </tbody>
  </table>
</div>
