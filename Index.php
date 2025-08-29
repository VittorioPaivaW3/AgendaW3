<?php
// index.php — página do usuário (somente consome salas.php e calendario.php)
require_once __DIR__ . '/salas.php';
require_once __DIR__ . '/calendario.php';
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
        <a href="admin.php" class="btn btn-outline-secondary btn-sm">Modo Admin</a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Modo Usuário</a>
      </div>
    </div>
  </nav>

  <div class="container pb-4">
    <!-- Seleção de Funcionário -->
    <div class="mb-4">
      <label class="form-label fw-semibold">Funcionário Solicitante:</label>
      <select class="form-select" id="funcionario">
        <option value="">Selecione...</option>
        <option>João Silva</option>
        <option>Maria Oliveira</option>
        <option>Lucas Kolody</option>
      </select>
    </div>

    <div class="row g-3">
      <!-- Coluna esquerda: Salas -->
      <div class="col-lg-4 col-xl-3">
        <div class="side-title mb-2">Salas Disponíveis</div>
        <?php
          $salas = carregar_salas();
          renderizar_lista_salas($salas);
        ?>
      </div>

      <!-- Coluna direita: Calendário -->
      <div class="col-lg-8 col-xl-9">
        <div class="calendar-wrap">
          <?php renderizar_calendario(); ?>
        </div>

        <div class="mt-3 ps-1 legend">
          <span class="badge bg-primary">Selecionado</span>
          <span class="badge bg-success">Reservado</span>
          <span class="badge bg-secondary">Bloqueado</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
