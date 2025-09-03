<?php
// partials/scripts.php — JS e CSS compartilhados (NÃO iniciar sessão, NÃO requerer bootstrap)
?>
<script>
  // Referências dos modais (se existirem na página atual)
  const createDialog  = document.getElementById('createDialog');
  const detailsDialog = document.getElementById('detailsDialog');
  const adminDialog   = document.getElementById('adminDialog');
  const roomsDialog   = document.getElementById('roomsDialog');

  // Botões de atalho (se existirem)
  const btnCreate = document.getElementById('openCreate');
  if (btnCreate && createDialog) btnCreate.addEventListener('click', () => {
    if (createDialog.querySelector('form')?.reset) createDialog.querySelector('form').reset();
    // garante estado inicial do link
    const cb = document.getElementById('is_online');
    if (cb) toggleLink();
    createDialog.showModal();
  });

  const btnAdmin = document.getElementById('openAdmin');
  if (btnAdmin && adminDialog) btnAdmin.addEventListener('click', () => adminDialog.showModal());

  const btnRooms = document.getElementById('openRooms');
  if (btnRooms && roomsDialog) btnRooms.addEventListener('click', () => roomsDialog.showModal());

  // Link de reunião: sempre OPCIONAL
  function toggleLink(){
    const c = document.getElementById('is_online');
    const link = document.getElementById('meeting_link');
    if (!c || !link) return;
    if(c.checked){
      link.classList.remove('hidden');
      link.required = false; // <- fica OPCIONAL mesmo online
    } else {
      link.classList.add('hidden');
      link.required = false; // <- continua OPCIONAL
      link.value='';
    }
  }

  // Estado inicial ao carregar a página
  document.addEventListener('DOMContentLoaded', () => {
    const c = document.getElementById('is_online');
    if (c) toggleLink();

    // dica de capacidade
    const sel = document.getElementById('room_id_form');
    const hint = document.getElementById('roomCapHint');
    if (sel && hint) {
      const updateHint = () => {
        const txt = sel.options[sel.selectedIndex]?.textContent || '';
        const capMatch = txt.match(/\(cap\.?\s*([0-9]+)\)/i);
        hint.textContent = capMatch ? `Capacidade da sala: ${capMatch[1]} pessoas.` : 'Selecione uma sala para ver a capacidade.';
      };
      sel.addEventListener('change', updateHint);
      updateHint();
    }
  });

  function openCreateWith(date, start, end){
    if (!createDialog) return;
    const f = createDialog.querySelector('form');
    const actionInput = document.getElementById('booking_action');
    const idInput = document.getElementById('booking_id');
    if (actionInput) actionInput.value = 'save_booking';
    if (idInput) idInput.value = '';
    if (f?.reset) f.reset();
    if (f) {
      if (f.date) f.date.value = date;
      if (f.start_time) f.start_time.value = start;
      if (f.end_time) f.end_time.value = end;
    }
    toggleLink();
    createDialog.showModal();
  }

  function showBooking(b){
  window._lastBooking = b;
  const wrap = document.getElementById('detailsContent');
  if (!wrap) return;

  // Força a leitura de "is_online" como booleano (aceita 1/"1"/true)
  const isOnline = (parseInt(b.is_online, 10) === 1) || (b.is_online === true) || (b.is_online === 'true');

  const onlineText = isOnline
    ? `Sim${b.meeting_link ? ` — <a class="text-indigo-600 underline" target="_blank" href="${b.meeting_link}">${b.meeting_link}</a>` : ''}`
    : 'Não';

  const cafe = (parseInt(b.needs_coffee, 10) === 1) || (b.needs_coffee === true) ? 'Sim' : 'Não';

  wrap.innerHTML = `
    <div class="flex items-center gap-2">
      <span class="inline-block w-3 h-3 rounded-full" style="background:${b.color}"></span>
      <div>
        <div class="font-semibold">${escapeHtml(b.title||'')}</div>
        <div class="text-slate-500">${escapeHtml(b.room||'')}</div>
      </div>
    </div>
    <div><span class="font-medium">Quando:</span> ${b.start} – ${b.end}</div>
    <div><span class="font-medium">Solicitante:</span> ${escapeHtml(b.requester||'—')}</div>
    <div><span class="font-medium">Participantes:</span> ${escapeHtml(String(b.participants_count||'—'))}</div>
    <div><span class="font-medium">Reunião online:</span> ${onlineText}</div>
    <div><span class="font-medium">Café:</span> ${cafe}</div>
    <div><span class="font-medium">Observações:</span> ${escapeHtml(b.notes || '—')}</div>
  `;

  const del = document.getElementById('del_id');
  if (del) del.value = b.id;
  if (detailsDialog) detailsDialog.showModal();
}

  function openEditBooking(){
    if (!window._lastBooking || !createDialog) return;
    const b = window._lastBooking;
    if (detailsDialog) detailsDialog.close();
    const f = createDialog.querySelector('form');
    const actionInput = document.getElementById('booking_action');
    const idInput = document.getElementById('booking_id');
    if (actionInput) actionInput.value = 'update_booking';
    if (idInput) idInput.value = b.id;

    if (f) {
      if (f.room_id) f.room_id.value = b.room_id;
      if (f.title) f.title.value = b.title || '';
      if (f.requester) f.requester.value = b.requester || '';
      if (f.participants_count) f.participants_count.value = b.participants_count || 1;
      if (f.date) f.date.value = b.date_sql || '';
      if (f.start_time) f.start_time.value = b.start_hh || '';
      if (f.end_time) f.end_time.value = b.end_hh || '';
      if (f.participants) f.participants.value = b.participants || '';
      if (f.is_online) f.is_online.checked = !!b.is_online;
      toggleLink();
      if (f.meeting_link && b.is_online) f.meeting_link.value = b.meeting_link || '';
      if (f.needs_coffee) f.needs_coffee.checked = !!b.needs_coffee;
      if (f.notes) f.notes.value = b.notes || '';
    }
    createDialog.showModal();
  }

  function onSubmitCreate(){
    const form = event.target;
    const s = form.start_time.value;
    const e = form.end_time.value;
    if (s >= e) { alert('Início deve ser menor que término.'); return false; }
    if (s < '08:00' || e > '18:00') { alert('Agende no horário comercial (08:00–18:00).'); return false; }
    // meeting_link é sempre opcional
    return true;
  }

  function escapeHtml(unsafe){
    return (unsafe||'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
  }

  // expo
  window.openCreateWith = openCreateWith;
  window.showBooking = showBooking;
  window.onSubmitCreate = onSubmitCreate;
  window.openEditBooking = openEditBooking;
</script>

<style>
  /* Chips de recurso */
  .feature { display:inline-flex; align-items:center; gap:.375rem; padding:.25rem .5rem; border-radius:9999px; background:#f8faf9; border:1px solid #e2e8f0; font-size:.75rem; color:#334155; }

  /* Cartão de reserva */
  .booking-card { display:flex; flex-direction:column; gap:.25rem; border:1px solid rgba(100,116,139,.2); border-left-width:4px; border-radius:.75rem; padding:.6rem .7rem; box-shadow: 0 1px 0 rgba(0,0,0,.03); }
  .booking-card:hover { filter: brightness(0.98); }
  .booking-title { font-weight:700; line-height:1.1; }
  .booking-meta { font-size:.8rem; color:#475569; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

  .btn-w3{ background:#78BE20; color:#fff } .btn-w3:hover{ filter:brightness(.95) }
</style>
