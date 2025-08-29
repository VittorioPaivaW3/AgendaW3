<?php
// salas.php — regras e renderização das salas (sem painel Admin aqui)

const ARQUIVO_DADOS_SALAS = __DIR__ . '/data/salas.json';

/* ===== Funções de dados (PHP) ===== */

function garantir_diretorio_dados(): void {
  $dir = dirname(ARQUIVO_DADOS_SALAS);
  if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
}

function carregar_salas(): array {
  garantir_diretorio_dados();
  if (!file_exists(ARQUIVO_DADOS_SALAS)) {
    $seed = [
      ["id"=>1,"nome"=>"Sala Executiva","capacidade"=>12],
      ["id"=>2,"nome"=>"Sala Reunião A","capacidade"=>8],
      ["id"=>3,"nome"=>"Auditório","capacidade"=>50],
    ];
    file_put_contents(ARQUIVO_DADOS_SALAS, json_encode($seed, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
  }
  $arr = json_decode(file_get_contents(ARQUIVO_DADOS_SALAS), true);
  return is_array($arr) ? $arr : [];
}

function salvar_salas(array $salas): void {
  garantir_diretorio_dados();
  file_put_contents(ARQUIVO_DADOS_SALAS, json_encode(array_values($salas), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

function criar_sala(array $salas, string $nome, int $capacidade): array {
  $maxId = 0;
  foreach ($salas as $s) { if (($s['id'] ?? 0) > $maxId) $maxId = (int)$s['id']; }
  $salas[] = ["id"=>$maxId+1, "nome"=>$nome, "capacidade"=>$capacidade];
  return $salas;
}

function atualizar_sala(array $salas, int $id, ?string $nome, ?int $capacidade): array {
  foreach ($salas as &$s) {
    if ((int)$s['id'] === $id) {
      if ($nome !== null && $nome !== '') $s['nome'] = $nome;
      if ($capacidade !== null && $capacidade > 0) $s['capacidade'] = $capacidade;
    }
  }
  unset($s);
  return $salas;
}

function excluir_sala(array $salas, int $id): array {
  return array_values(array_filter($salas, fn($s)=> (int)$s['id'] !== $id));
}

/* ===== Dados visuais extras (apenas para exibição) ===== */
function extras_sala(): array {
  return [
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
}

/* ===== Renderização (HTML) ===== */

function renderizar_lista_salas(array $salas): void {
  $extras = extras_sala();
  echo '<div class="d-grid gap-2">';
  foreach ($salas as $sala) {
    $id = (int)$sala['id'];
    $ex = $extras[$id] ?? ['andar'=>'–','status'=>'disponivel','amenities'=>[]];
    $bloq = ($ex['status'] ?? '') === 'bloqueada';
    ?>
    <button type="button"
            class="room-card btn-sala <?php echo $bloq? 'disabled' : ''; ?>"
            data-sala="<?php echo $id; ?>"
            data-nome="<?php echo htmlspecialchars($sala['nome']); ?>">
      <div class="room-header">
        <div class="room-name"><?php echo htmlspecialchars($sala['nome']); ?></div>
        <span class="status-pill <?php echo $bloq? 'status-block':'status-ok'; ?>">
          <?php echo $bloq? 'Bloqueada':'Disponível'; ?>
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
    <?php
  }
  echo '</div>';

  // JS: clique nas salas abre o modal correto (usuário ou admin)
  ?>
  <script>
    (function(){
      let salaSelecionada = null;

      document.addEventListener('click', function(e){
        const btn = e.target.closest('.btn-sala');
        if (!btn || btn.classList.contains('disabled')) return;

        document.querySelectorAll('.btn-sala').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');

        salaSelecionada = btn.dataset.sala;
        const nomeSala = btn.dataset.nome || 'Sala';
        const span = document.getElementById('calRoomName');
        if (span) span.textContent = nomeSala;

        // Decide qual modal abrir conforme a página
        const modalAdminEl = document.getElementById('adminModal');
        const modalUserEl  = document.getElementById('salaModal');

        if (modalAdminEl) {
          const modal = bootstrap.Modal.getOrCreateInstance(modalAdminEl);
          // defaults nas abas admin
          ['a_horaInicioH','a_horaInicioM','a_horaFimH','a_horaFimM','b_horaInicioH','b_horaInicioM','b_horaFimH','b_horaFimM'].forEach(id=>{
            const el = document.getElementById(id); if (!el) return;
            if (id.includes('InicioH') || id.includes('FimH')) el.value = '08';
            if (id.includes('InicioM') || id.includes('FimM')) el.value = '00';
          });
          const hiF = document.getElementById('a_horaFimH'); if (hiF) hiF.value = '09';
          modal.show();
        } else if (modalUserEl) {
          const modal = bootstrap.Modal.getOrCreateInstance(modalUserEl);
          const hiH = document.getElementById('horaInicioH'); if (hiH) hiH.value = '08';
          const hiM = document.getElementById('horaInicioM'); if (hiM) hiM.value = '00';
          const hfH = document.getElementById('horaFimH');    if (hfH) hfH.value = '09';
          const hfM = document.getElementById('horaFimM');    if (hfM) hfM.value = '00';
          modal.show();
        }
      });
      // Expor globalmente (se precisar ler a sala selecionada)
      window.salaSelecionadaGlobal = () => salaSelecionada;
    })();
  </script>
  <?php
}
