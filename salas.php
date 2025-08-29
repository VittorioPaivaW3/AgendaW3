<?php
// salas.php — renderiza cards com dados da tabela `salas`
require __DIR__ . '/db.php';

$stmt = $pdo->query("SELECT id, nome, capacidade, sede, status, tv, ar_condicionado, video_conferencia, quadro
                     FROM salas ORDER BY id");
$salas = $stmt->fetchAll();
?>
<div class="d-grid gap-2">
<?php foreach ($salas as $s):
  $id   = (int)$s['id'];
  $nome = htmlspecialchars($s['nome']);
  $cap  = (int)$s['capacidade'];
  $st   = ($s['status'] === 'bloqueado') ? 'bloqueado' : 'disponivel';
  $disabled = $st === 'bloqueado' ? 'disabled' : '';
?>
  <button type="button"
          class="room-card btn-sala <?= $disabled; ?>"
          data-sala="<?= $id; ?>"
          data-nome="<?= $nome; ?>"
          data-cap="<?= $cap; ?>"
          data-status="<?= $st; ?>"
          data-sede="<?= htmlspecialchars($s['sede']); ?>"
          data-tv="<?= $s['tv']; ?>"
          data-ar="<?= $s['ar_condicionado']; ?>"
          data-vc="<?= $s['video_conferencia']; ?>"
          data-quadro="<?= $s['quadro']; ?>">
    <div class="room-header">
      <div class="room-name"><?= $nome; ?></div>
      <span class="status-pill <?= $st==='bloqueado' ? 'status-block':'status-ok'; ?>">
        <?= $st==='bloqueado' ? 'Bloqueado' : 'Disponível'; ?>
      </span>
    </div>
    <div class="d-flex mt-2" style="gap:1rem;">
      <div class="meta"><i class="bi bi-people"></i><?= $cap; ?> pessoas</div>
      <div class="meta"><i class="bi bi-geo-alt"></i><?= htmlspecialchars($s['sede']); ?></div>
    </div>
    <div class="amenities mt-2">
      <?php if ($s['tv']==='sim'): ?><span><i class="bi bi-tv"></i>TV</span><?php endif; ?>
      <?php if ($s['video_conferencia']==='sim'): ?><span><i class="bi bi-camera-video"></i>Videoconf.</span><?php endif; ?>
      <?php if ($s['ar_condicionado']==='sim'): ?><span><i class="bi bi-snow"></i>Ar</span><?php endif; ?>
      <?php if ($s['quadro']==='sim'): ?><span><i class="bi bi-easel"></i>Quadro</span><?php endif; ?>
    </div>
  </button>
<?php endforeach; ?>
</div>
