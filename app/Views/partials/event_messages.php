<?php $isAr = \App\Helpers\I18n::direction() === 'rtl'; ?>
<div class="card border-0 shadow-sm mb-3" id="eventMessagesCard">
    <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
        <span>
            <i class="mdi mdi-message-text-outline me-1"></i>
            <?= $isAr ? 'مراسلات الفعالية' : 'Messages de l\'événement' ?>
        </span>
        <span class="badge bg-secondary" id="msgCount">0</span>
    </div>
    <div class="card-body p-0">
        <div id="msgList" class="p-3" style="max-height:350px; overflow-y:auto; background:#f8f9fa;">
            <div class="text-center text-muted py-3" id="msgLoading">
                <div class="spinner-border spinner-border-sm" role="status"></div>
            </div>
        </div>
        <form id="msgForm" class="border-top p-3" style="background:#fff;">
            <div class="d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <textarea class="form-control form-control-sm" id="msgInput" rows="2"
                              placeholder="<?= $isAr ? 'اكتب رسالة...' : 'Écrire un message...' ?>"
                              maxlength="2000" required></textarea>
                </div>
                <div class="d-flex flex-column gap-1">
                    <?php if (user_role() === 'wilaya'): ?>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" id="msgInternal" value="1">
                            <label class="form-check-label small" for="msgInternal" style="font-size:.7rem;">
                                <?= $isAr ? 'داخلي فقط' : 'Interne' ?>
                            </label>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm" id="msgSend">
                        <i class="mdi mdi-send"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const evenementId = <?= (int) $eventId ?>;
    const isAr = <?= $isAr ? 'true' : 'false' ?>;
    const list = document.getElementById('msgList');
    const form = document.getElementById('msgForm');
    const input = document.getElementById('msgInput');
    const sendBtn = document.getElementById('msgSend');
    const countBadge = document.getElementById('msgCount');
    const loading = document.getElementById('msgLoading');
    const internalCheck = document.getElementById('msgInternal');
    const currentUserId = <?= (int) (current_user()['id'] ?? 0) ?>;
    const currentRole = '<?= user_role() ?>';

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function renderMessages(messages) {
        if (loading) loading.remove();
        list.innerHTML = '';
        countBadge.textContent = messages.length;
        if (messages.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-3" style="font-size:.85rem;">' +
                (isAr ? 'لا توجد رسائل بعد' : 'Aucun message pour le moment.') + '</div>';
            return;
        }
        messages.forEach(m => {
            const isMine = parseInt(m.sender_id, 10) === currentUserId;
            const roleLabel = m.sender_role === 'wilaya' ? (isAr ? 'الولاية' : 'Wilaya')
                : m.sender_role === 'association' ? (isAr ? 'الجمعية' : 'Association')
                : 'EPIC';
            const internalBadge = m.is_internal == 1
                ? ' <span class="badge" style="background:#f59e0b20;color:#b45309;font-size:.6rem;">' + (isAr ? 'داخلي' : 'Interne') + '</span>'
                : '';
            const bubble = document.createElement('div');
            bubble.className = 'd-flex mb-2 ' + (isMine ? 'justify-content-end' : 'justify-content-start');
            bubble.innerHTML =
                '<div style="max-width:75%;" class="' + (isMine ? 'text-end' : '') + '">' +
                    '<div class="small text-muted mb-1">' + escapeHtml(roleLabel) + ' — ' + escapeHtml(m.sender_nom || '') + ' ' + escapeHtml(m.sender_prenom || '') + internalBadge + '</div>' +
                    '<div style="display:inline-block;text-align:left;padding:.4rem .7rem;border-radius:.75rem;font-size:.85rem;'
                    + (isMine ? 'background:#2563eb;color:#fff;' : 'background:#e9ecef;color:#212529;')
                    + '">' + escapeHtml(m.message) + '</div>' +
                    '<div class="small text-muted mt-1">' + new Date(m.created_at).toLocaleString('fr-DZ') + '</div>' +
                '</div>';
            list.appendChild(bubble);
        });
        list.scrollTop = list.scrollHeight;
    }

    function fetchMessages() {
        fetch('/api/events/' + evenementId + '/messages', {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) renderMessages(data.messages || []);
        })
        .catch(() => {});
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = input.value.trim();
        if (!msg) return;

        sendBtn.disabled = true;
        const body = new FormData();
        body.append('message', msg);
        if (internalCheck && internalCheck.checked) {
            body.append('is_internal', '1');
        }
        body.append('_token', window.WH_CSRF);

        fetch('/api/events/' + evenementId + '/messages', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.WH_CSRF
            },
            body: body
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                fetchMessages();
            }
        })
        .finally(() => { sendBtn.disabled = false; });
    });

    fetchMessages();
    setInterval(fetchMessages, 15000);
})();
</script>
