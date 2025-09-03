<?php
// salas.php — CRUD de salas (apenas admin)

$rooms = [];
foreach ($pdo->query('SELECT * FROM rooms ORDER BY id') as $r) {
    $rooms[(int)$r['id']] = $r;
}
?>

<div class="bg-white rounded-2xl shadow p-6 lg:p-8 space-y-8">
  <h1 class="text-2xl font-bold">Salas</h1>

  <!-- Adicionar sala -->
  <form method="post" class="grid md:grid-cols-5 gap-4 p-4 border border-slate-200 rounded-2xl">
    <input type="hidden" name="action" value="create_room"/>
    <div>
      <label class="text-sm font-medium">Nome</label>
      <input name="name" required class="mt-1 w-full rounded-xl border-slate-300"/>
    </div>
    <div>
      <label class="text-sm font-medium">Cor</label>
      <input name="color" type="color" value="#3b82f6" class="mt-1 w-full h-10 rounded-xl border-slate-300"/>
    </div>
    <div>
      <label class="text-sm font-medium">Capacidade</label>
      <input name="capacity" type="number" min="1" value="8" class="mt-1 w-full rounded-xl border-slate-300"/>
    </div>
    <div class="md:col-span-2">
      <label class="text-sm font-medium">Extras</label>
      <div class="mt-1 grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 border border-slate-200 rounded-xl bg-slate-50">
        <label class="feature"><input type="checkbox" name="has_wifi" class="mr-1" checked/>📶 Wi-Fi</label>
        <label class="feature"><input type="checkbox" name="has_tv" class="mr-1" checked/>📺 TV</label>
        <label class="feature"><input type="checkbox" name="has_board" class="mr-1" checked/>🧑‍🏫 Quadro</label>
        <label class="feature"><input type="checkbox" name="has_ac" class="mr-1" checked/>❄️ Ar-cond.</label>
        <label class="feature bg-rose-50"><input type="checkbox" name="is_blocked" class="mr-1"/>⛔ Bloquear sala</label>
      </div>
    </div>
    <div class="md:col-span-5 flex justify-end">
      <button class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700">Adicionar sala</button>
    </div>
  </form>

  <!-- Lista de salas -->
  <div class="overflow-auto">
    <table class="w-full min-w-[900px]">
      <thead class="bg-slate-100">
        <tr>
          <th class="text-left px-3 py-2 text-sm">Sala</th>
          <th class="text-left px-3 py-2 text-sm">Capacidade</th>
          <th class="text-left px-3 py-2 text-sm">Facilidades</th>
          <th class="text-left px-3 py-2 text-sm">Status</th>
          <th class="text-left px-3 py-2 text-sm">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rooms as $rid => $r): ?>
          <tr class="border-t border-slate-200">
            <td class="px-3 py-2">
              <div class="flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded-full" style="background: <?= h($r['color']) ?>"></span>
                <span class="font-medium"><?= h($r['name']) ?></span>
              </div>
            </td>
            <td class="px-3 py-2"><?= (int)$r['capacity'] ?></td>
            <td class="px-3 py-2">
              <div class="flex flex-wrap gap-2 text-xs">
                <?php if ((int)$r['has_wifi']) : ?><span class="feature">📶 Wi-Fi</span><?php endif; ?>
                <?php if ((int)$r['has_tv'])   : ?><span class="feature">📺 TV</span><?php endif; ?>
                <?php if ((int)$r['has_board']): ?><span class="feature">🧑‍🏫 Quadro</span><?php endif; ?>
                <?php if ((int)$r['has_ac'])   : ?><span class="feature">❄️ Ar-cond.</span><?php endif; ?>
              </div>
            </td>
            <td class="px-3 py-2">
              <?php if ((int)$r['is_blocked']): ?>
                <span class="feature bg-rose-100">⛔ Bloqueada</span>
              <?php else: ?>
                <span class="feature bg-emerald-100">✅ Disponível</span>
              <?php endif; ?>
            </td>
            <td class="px-3 py-2">
              <details class="inline-block">
                <summary class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 cursor-pointer text-sm">Editar</summary>
                <form method="post" class="mt-2 grid md:grid-cols-5 gap-3 p-3 border border-slate-200 rounded-xl bg-white">
                  <input type="hidden" name="action" value="update_room"/>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>"/>
                  <div>
                    <label class="text-xs">Nome</label>
                    <input name="name" value="<?= h($r['name']) ?>" class="mt-1 w-full rounded-xl border-slate-300"/>
                  </div>
                  <div>
                    <label class="text-xs">Cor</label>
                    <input name="color" type="color" value="<?= h($r['color']) ?>" class="mt-1 w-full h-10 rounded-xl border-slate-300"/>
                  </div>
                  <div>
                    <label class="text-xs">Capacidade</label>
                    <input name="capacity" type="number" min="1" value="<?= (int)$r['capacity'] ?>" class="mt-1 w-full rounded-xl border-slate-300"/>
                  </div>
                  <div class="md:col-span-2">
                    <label class="text-xs">Extras</label>
                    <div class="mt-1 grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 border border-slate-200 rounded-xl bg-slate-50">
                      <label class="feature"><input type="checkbox" name="has_wifi" <?= (int)$r['has_wifi']? 'checked':'' ?> class="mr-1"/>📶 Wi-Fi</label>
                      <label class="feature"><input type="checkbox" name="has_tv" <?= (int)$r['has_tv']? 'checked':'' ?> class="mr-1"/>📺 TV</label>
                      <label class="feature"><input type="checkbox" name="has_board" <?= (int)$r['has_board']? 'checked':'' ?> class="mr-1"/>🧑‍🏫 Quadro</label>
                      <label class="feature"><input type="checkbox" name="has_ac" <?= (int)$r['has_ac']? 'checked':'' ?> class="mr-1"/>❄️ Ar-cond.</label>
                      <label class="feature bg-rose-50"><input type="checkbox" name="is_blocked" <?= (int)$r['is_blocked']? 'checked':'' ?> class="mr-1"/>⛔ Bloquear sala</label>
                    </div>
                  </div>
                  <div class="md:col-span-5 flex justify-end gap-2">
                    <button class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-sm">Salvar</button>
                  </div>
                </form>
              </details>

              <form method="post" onsubmit="return confirm('Excluir a sala &quot;<?= h($r['name']) ?>&quot;? Todas as reservas dela serão removidas.')" class="inline-block ml-2">
                <input type="hidden" name="action" value="delete_room"/>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>"/>
                <button class="px-3 py-1 rounded-lg bg-rose-600 text-white text-sm">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
