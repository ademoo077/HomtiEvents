<?php $isAr = \App\Helpers\I18n::direction() === 'rtl'; ?>
<?php $deadline = $event['deadline_at'] ?? null; ?>
<?php $now = new \DateTimeImmutable(); ?>
<?php $deadlineDate = $deadline ? new \DateTimeImmutable((string) $deadline) : null; ?>
<?php $isOverdue = $deadlineDate && $deadlineDate < $now; ?>
<?php $isUrgent = $deadlineDate && !$isOverdue && $deadlineDate->diff($now)->days <= 2; ?>
<?php $remaining = $deadlineDate ? $now->diff($deadlineDate) : null; ?>
<?php $remainingText = $remaining
    ? ($remaining->days > 0 ? $remaining->days . 'j ' . $remaining->h . 'h' : $remaining->h . 'h ' . $remaining->i . 'min')
    : '—'; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
        <span>
            <i class="mdi mdi-clipboard-check-outline me-1"></i>
            <?= $isAr ? 'قائمة المهام' : 'Checklist' ?>
        </span>
        <span class="badge <?= $isOverdue ? 'bg-danger' : ($isUrgent ? 'bg-warning text-dark' : 'bg-success') ?>" id="slaBadge"
              style="font-size:.7rem;">
            <i class="mdi mdi-clock-outline me-1"></i>
            <?= $isOverdue ? ($isAr ? 'متأخر' : 'En retard') : ($isUrgent ? ($isAr ? 'عاجل' : 'Urgent') : ($isAr ? 'في الوقت' : 'Dans les temps')) ?>
            — <?= $remainingText ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div id="checklistItems" class="list-group list-group-flush">
            <div class="text-center text-muted py-3">
                <div class="spinner-border spinner-border-sm" role="status"></div>
            </div>
        </div>
        <div class="p-2 border-top">
            <form id="checklistAddForm" class="d-flex gap-2">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="text" class="form-control form-control-sm" id="checklistNewInput"
                       placeholder="<?= $isAr ? 'إضافة مهمة...' : 'Ajouter une tâche...' ?>" maxlength="255" required>
                <button type="submit" class="btn btn-outline-primary btn-sm flex-shrink-0">
                    <i class="mdi mdi-plus"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const evenementId = <?= (int) $eventId ?>;
    const isAr = <?= $isAr ? 'true' : 'false' ?>;
    const list = document.getElementById('checklistItems');
    const form = document.getElementById('checklistAddForm');
    const input = document.getElementById('checklistNewInput');
    const csrfToken = window.WH_CSRF;

    function escapeHtml(s) { const d = document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML; }

    function renderItems(items) {
        list.innerHTML = '';
        if (items.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-3" style="font-size:.85rem;">' +
                (isAr ? 'لا توجد مهام' : 'Aucune tâche.') + '</div>';
            return;
        }
        const total = items.length;
        const done = items.filter(i => i.fait == 1).length;

        const progress = document.createElement('div');
        progress.className = 'px-3 py-2';
        progress.innerHTML = '<div class="d-flex justify-content-between small mb-1"><span>' + done + '/' + total + '</span><span>' +
            Math.round((done/total)*100) + '%</span></div>' +
            '<div class="progress" style="height:4px;"><div class="progress-bar bg-success" style="width:' + (done/total*100) + '%;"></div></div>';
        list.appendChild(progress);

        items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-group-item d-flex align-items-center justify-content-between py-2';
            row.style.cursor = 'pointer';
            row.innerHTML =
                '<div class="d-flex align-items-center gap-2">' +
                    '<input type="checkbox" class="form-check-input m-0" ' + (item.fait == 1 ? 'checked' : '') + ' data-item-id="' + item.id + '">' +
                    '<span style="' + (item.fait == 1 ? 'text-decoration:line-through;color:#6b7280;' : '') + '">' + escapeHtml(item.libelle) + '</span>' +
                '</div>' +
                (item.fait == 1 && item.fait_by_nom
                    ? '<small class="text-muted">' + escapeHtml(item.fait_by_nom || '') + ' ' + escapeHtml(item.fait_by_prenom || '') + '</small>'
                    : '<button class="btn btn-sm text-danger border-0 p-0 delete-checklist" data-item-id="' + item.id + '"><i class="mdi mdi-close-circle-outline"></i></button>');
            list.appendChild(row);
        });

        list.querySelectorAll('.form-check-input').forEach(cb => {
            cb.addEventListener('change', function() {
                const body = new FormData();
                body.append('item_id', this.dataset.itemId);
                body.append('_token', csrfToken);
                fetch('/api/events/' + evenementId + '/checklist/toggle', {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest'},
                    body: body
                }).then(r => r.json()).then(d => { if (d.success) fetchItems(); });
            });
        });

        list.querySelectorAll('.delete-checklist').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!confirm(isSupprConfirm())) return;
                const body = new FormData();
                body.append('_token', csrfToken);
                body.append('_method', 'DELETE');
                fetch('/api/events/checklist/' + this.dataset.itemId, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest'},
                    body: body
                }).then(r => r.json()).then(d => { if (d.success) fetchItems(); });
            });
        });
    }

    function isSupprConfirm() { return isAr ? 'هل أنت متأكد من الحذف؟' : 'Supprimer cet élément ?'; }

    function fetchItems() {
        fetch('/api/events/' + evenementId + '/checklist', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.json()).then(d => { if (d.success) renderItems(d.items || []); });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const val = input.value.trim();
        if (!val) return;
        const body = new FormData();
        body.append('libelle', val);
        body.append('_token', csrfToken);
        fetch('/api/events/' + evenementId + '/checklist/add', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest'},
            body: body
        }).then(r => r.json()).then(d => { if (d.success) { input.value = ''; fetchItems(); } });
    });

    fetchItems();
})();
</script>
