<?php
// partials/modals.php — todos os modais (delete somente para admin)
?>

<!-- Modal: Login Admin -->
<dialog id="adminDialog" class="rounded-2xl w-[min(400px,95vw)] p-0">
  <form method="post" class="p-6 space-y-4">
    <input type="hidden" name="action" value="admin_login"/>
    <h2 class="text-lg font-semibold">Entrar como Admin</h2>
    <p class="text-sm text-slate-600">Digite a senha de administrador para acessar a dashboard e a edição de salas.</p>
    <input type="password" name="admin_password" required class="w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500" placeholder="Senha"/>
    <div class="flex justify-end gap-2">
      <button type="button" onclick="this.closest('dialog').close()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200">Cancelar</button>
      <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">Entrar</button>
    </div>
  </form>
</dialog>

<!-- Modal: Criar reserva -->
<dialog id="createDialog" class="rounded-2xl w-[min(800px,95vw)] p-0">
  <form method="post" class="p-6 space-y-4" onsubmit="return onSubmitCreate()">
    <input type="hidden" name="action" value="save_booking"/>
    <div class="flex items-start justify-between">
      <h2 class="text-lg font-semibold">Nova reserva</h2>
      <button type="button" class="text-slate-400 hover:text-slate-700" onclick="this.closest('dialog').close()">✕</button>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Sala</label>
        <select name="room_id" id="room_id_form" required class="mt-1 w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500">
          <option value="">Selecione…</option>
          <?php foreach ($rooms as $rid => $r): ?>
            <option value="<?= $rid ?>" <?= (int)$r['is_blocked']? 'disabled' : '' ?>>
              <?= h($r['name']) ?> (cap. <?= (int)$r['capacity'] ?>)<?= (int)$r['is_blocked'] ? ' — BLOQUEADA' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div id="roomCapHint" class="text-xs text-slate-500 mt-1">Selecione uma sala para ver a capacidade.</div>
      </div>
      <div>
        <label class="text-sm font-medium">Título</label>
        <input name="title" type="text" required class="mt-1 w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500" placeholder="Ex.: Reunião de status"/>
      </div>

      <div>
        <label class="text-sm font-medium">Solicitante</label>
        <input name="requester" type="text" required class="mt-1 w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500" placeholder="Nome de quem solicita"/>
      </div>
      <div>
        <label class="text-sm font-medium">Qtd. participantes</label>
        <input name="participants_count" id="participants_count" type="number" min="1" value="1" required class="mt-1 w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500"/>
      </div>

      <div>
        <label class="text-sm font-medium">Data</label>
        <input name="date" type="date" required class="mt-1 w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500"/>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Início</label>
          <input name="start_time" type="time" required value="08:00" min="08:00" max="18:00" step="900" class="mt-1 w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500"/>
        </div>
        <div>
          <label class="text-sm font-medium">Término</label>
          <input name="end_time" type="time" required value="09:00" min="08:00" max="18:00" step="900" class="mt-1 w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500"/>
        </div>
      </div>

      <div class="md:col-span-2">
        <label class="text-sm font-medium">Participantes (e-mails/nome, opcional)</label>
        <textarea name="participants" rows="2" class="mt-1 w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500"></textarea>
      </div>

      <div class="md:col-span-2 flex flex-wrap items-center gap-6">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" name="is_online" id="is_online" onchange="toggleLink()"/>
          <span>Reunião online</span>
        </label>
        <input type="url" name="meeting_link" id="meeting_link" placeholder="Link (Teams, Meet, Zoom…)" class="flex-1 rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500 hidden"/>

        <label class="inline-flex items-center gap-2">
          <input type="checkbox" name="needs_coffee" id="needs_coffee"/>
          <span>Necessário café</span>
        </label>
      </div>

      <div class="md:col-span-2">
        <label class="text-sm font-medium">Observações</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-indigo-500"></textarea>
      </div>
    </div>

    <div class="flex justify-end gap-2 pt-2">
      <button type="button" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200" onclick="this.closest('dialog').close()">Cancelar</button>
      <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">Salvar</button>
    </div>
  </form>
</dialog>

<!-- Modal: Detalhes da reserva -->
<dialog id="detailsDialog" class="rounded-2xl w-[min(640px,95vw)] p-0">
  <div class="p-6 space-y-4">
    <div class="flex items-start justify-between">
      <h2 class="text-lg font-semibold">Detalhes da reserva</h2>
      <button type="button" class="text-slate-400 hover:text-slate-700" onclick="this.closest('dialog').close()">✕</button>
    </div>
    <div id="detailsContent" class="space-y-2 text-sm"></div>

    <?php if (!empty($_SESSION['is_admin'])): ?>
    <form method="post" onsubmit="return confirm('Excluir esta reserva?')">
      <input type="hidden" name="action" value="delete_booking"/>
      <input type="hidden" name="id" id="del_id" value=""/>
      <div class="flex justify-end">
        <button class="px-3 py-2 rounded-xl bg-rose-600 text-white font-semibold hover:bg-rose-700">Excluir</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</dialog>

<!-- Modal: Informações das salas -->
<dialog id="roomsDialog" class="rounded-2xl w-[min(800px,95vw)] p-0">
  <div class="p-6 space-y-4">
    <div class="flex items-start justify-between">
      <h2 class="text-lg font-semibold">Salas e facilidades</h2>
      <button type="button" class="text-slate-400 hover:text-slate-700" onclick="this.closest('dialog').close()">✕</button>
    </div>
    <div class="space-y-4 text-sm">
      <?php foreach ($rooms as $rid => $r): ?>
        <div class="border border-slate-200 rounded-xl p-4 flex flex-col gap-3">
          <div class="flex items-center gap-3">
            <span class="inline-block w-3 h-3 rounded-full" style="background: <?= h($r['color']) ?>"></span>
            <div class="font-semibold"><?= h($r['name']) ?> <span class="text-slate-500">(cap. <?= (int)$r['capacity'] ?>)</span></div>
          </div>
          <div class="flex flex-wrap gap-2">
            <?php if ((int)$r['has_wifi']) : ?><span class="feature">📶 Wi-Fi</span><?php endif; ?>
            <?php if ((int)$r['has_tv'])   : ?><span class="feature">📺 TV</span><?php endif; ?>
            <?php if ((int)$r['has_board']): ?><span class="feature">🧑‍🏫 Quadro</span><?php endif; ?>
            <?php if ((int)$r['has_ac'])   : ?><span class="feature">❄️ Ar-condicionado</span><?php endif; ?>
            <?php if ((int)$r['is_blocked']): ?><span class="feature" style="background:#fee2e2;border-color:#fecaca">⛔ Bloqueada</span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="flex justify-end">
      <button class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200" onclick="this.closest('dialog').close()">Fechar</button>
    </div>
  </div>
</dialog>
