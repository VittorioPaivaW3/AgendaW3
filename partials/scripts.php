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
  if (btnCreate && createDialog) btnCreate.addEventListener('click', () => createDialog.showModal());
  const btnAdmin = document.getElementById('openAdmin');
  if (btnAdmin && adminDialog) btnAdmin.addEventListener('click', () => adminDialog.showModal());
  const btnRooms = document.getElementById('openRooms');
  if (btnRooms && roomsDialog) btnRooms.addEventListener('click', () => roomsDialog.showModal());

  // Exibir/ocultar campo de link quando marcar "Reunião online"
  function toggleLink(){
    const c = document.getElementById('is_online');
    const link = document.getElementById('meeting_link');
    if(!c || !link) return;
    if(c.checked){ link.classList.remove('hidden'); link.required = false; }
    else { link.classList.add('hidden'); link.required = false; link.value=''; }
  }

  // Abrir modal de criação já com data/hora sugeridas
  function openCreateWith(date, start, end){
    if(!createDialog) return;
    createDialog.showModal();
    const f = createDialog.querySelector('form');
    if(!f) return;
    f.date.value = date;
    f.start_time.value = start;
    f.end_time.value = end;
  }

  // Exibir detalhes de uma reserva no modal
  function showBooking(b){
    const wrap = document.getElementById('detailsContent');
    if(!wrap || !detailsDialog) return;
    const meet = b.is_online ? `<div><span class=\"font-medium\">Reunião online:</span> <a class=\"text-indigo-600 underline\" target=\"_blank\" href=\"${b.meeting_link}\">${b.meeting_link}</a></div>` : '';
    const coffee = b.needs_coffee ? '☕ Sim' : '—';
    wrap.innerHTML = `
      <div class=\"flex items-center gap-2\">
        <span class=\"inline-block w-3 h-3 rounded-full\" style=\"background:${b.color}\"></span>
        <div>
          <div class=\"font-semibold\">${escapeHtml(b.title)}</div>
          <div class=\"text-slate-500\">${escapeHtml(b.room)}</div>
        </div>
      </div>
      <div><span class=\"font-medium\">Solicitante:</span> ${escapeHtml(b.requester || '—')}</div>
      <div><span class=\"font-medium\">Quando:</span> ${b.start} – ${b.end}</div>
      <div><span class=\"font-medium\">Qtd. participantes:</span> ${b.participants_count}</div>
      <div><span class=\"font-medium\">Participantes:</span> ${escapeHtml(b.participants || '—')}</div>
      ${meet}
      <div><span class=\"font-medium\">Café:</span> ${coffee}</div>
      <div><span class=\"font-medium\">Observações:</span> ${escapeHtml(b.notes || '—')}</div>
    `;
    const del = document.getElementById('del_id');
    if (del) del.value = b.id; // se o form de exclusão existir (apenas admin), preenche
    detailsDialog.showModal();
  }

  // Validações na criação de reserva
  function onSubmitCreate(){
    const form = event.target;
    const s = form.start_time.value;
    const e = form.end_time.value;
    if (s >= e) { alert('Início deve ser menor que término.'); return false; }
    if (s < '08:00' || e > '18:00') { alert('Agende no horário comercial (08:00–18:00).'); return false; }

    // Bloquear finais de semana
    const date = form.date.value;
    if (date) {
      const [yy,mm,dd] = date.split('-').map(Number);
      const weekday = new Date(yy, mm-1, dd).getDay(); // 0=Dom,6=Sáb
      if (weekday === 0 || weekday === 6) { alert('Agendamentos apenas de segunda a sexta.'); return false; }
    }

    // Checar capacidade
    const sel = document.getElementById('room_id_form');
    if(sel){
      const selected = sel.options[sel.selectedIndex];
      if(selected){
        const capText = selected.textContent.match(/cap\.\s*(\d+)/i);
        if(capText){
          const cap = parseInt(capText[1]);
          const qtd = parseInt(form.participants_count.value);
          if(qtd > cap){ alert('Número de participantes maior que a capacidade da sala.'); return false; }
        }
      }
    }
    return true;
  }

  // Utilitários
  function escapeHtml(unsafe){
    return (unsafe||'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
  }

  // Expõe funções no escopo global
  window.toggleLink = toggleLink;
  window.openCreateWith = openCreateWith;
  window.showBooking = showBooking;
  window.onSubmitCreate = onSubmitCreate;
</script>

<style>
  /* Chips/Badges de recursos */
  .feature { display:inline-flex; align-items:center; gap:.375rem; padding:.3rem .55rem; border-radius:.5rem; background:#f1f5f9; border:1px solid #e2e8f0; font-size:.75rem; color:#334155; }

  /* Cartão de reserva (as cores são aplicadas inline por dia/hora) */
  .booking-card { display:flex; flex-direction:column; gap:.25rem; border:2px solid var(--booking-border, #e2e8f0); background: var(--booking-bg, #f8fafc); border-radius:.75rem; padding:.6rem .7rem; box-shadow: 0 1px 0 rgba(0,0,0,.03); }
  .booking-card:hover { filter: brightness(0.98); }
  .booking-title { font-weight:700; line-height:1.1; }
  .booking-meta { font-size:.8rem; color:#475569; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
</style>
