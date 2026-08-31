<?php
/**
 * Partial : Commentaires + Notes internes — MAX UI/UX IA
 * @var array $event
 * @var bool $isAr
 * @var bool $isLogged
 */
$eventId = (int) ($event['id'] ?? 0);
$me = $_SESSION['user'] ?? null;
$myInitials = trim(mb_substr($me['prenom'] ?? '',0,1).mb_substr($me['nom'] ?? '',0,1)) ?: '?';
$myName = trim(($me['prenom'] ?? '').' '.($me['nom'] ?? ''));
?>
<style>
/* MAX — Comments & Notes */
.event-comments-section, .event-notes-section{background:var(--wh-white);border:1px solid var(--wh-border);border-radius:var(--wh-radius);box-shadow:var(--wh-shadow);overflow:hidden;margin-bottom:1.25rem}
.event-comments-section .section-header, .event-notes-section .section-header{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.85rem 1.1rem;border-bottom:1px solid var(--wh-border);background:linear-gradient(135deg,var(--wh-gray-soft),#fff)}
.event-notes-section .section-header{background:linear-gradient(135deg,#fff3cd,#fff);}
.section-title{display:flex;align-items:center;gap:.5rem;font-size:.92rem;font-weight:700;margin:0;color:var(--wh-text)}
.section-title .mdi{font-size:1.15rem;color:var(--wh-blue)}
.event-notes-section .section-title .mdi{color:#b45309}
.comment-count-badge, .note-count-badge{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:var(--wh-blue);color:#fff;font-size:.7rem;font-weight:700}
.note-count-badge{background:#b45309}
.comment-form{display:flex;gap:.65rem;padding:1rem 1.1rem;border-bottom:1px solid var(--wh-border);background:#fff}
.comment-form-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--wh-blue),#4f83d8);color:#fff;display:grid;place-items:center;font-weight:700;font-size:.8rem;flex-shrink:0}
.event-notes-section .comment-form-avatar{background:linear-gradient(135deg,#f59e0b,#b45309)}
.comment-form-input-wrap{flex:1;position:relative;display:flex;gap:.5rem;align-items:flex-end;background:var(--wh-gray-soft);border:1px solid var(--wh-border);border-radius:.75rem;padding:.5rem .6rem;transition:border-color .15s, box-shadow .15s}
.comment-form-input-wrap:focus-within{border-color:var(--wh-blue);box-shadow:0 0 0 3px var(--wh-blue-soft);background:#fff}
.comment-form-input{flex:1;border:none;background:transparent;resize:none;outline:none;font-size:.88rem;line-height:1.5;max-height:120px;min-height:36px}
.note-input:focus{outline:none}
.comment-form-submit{width:36px;height:36px;border-radius:.6rem;border:none;background:var(--wh-blue);color:#fff;display:grid;place-items:center;cursor:pointer;flex-shrink:0;transition:transform .12s, opacity .12s}
.comment-form-submit:disabled{opacity:.45;cursor:not-allowed}
.comment-form-submit:not(:disabled):hover{transform:translateY(-1px)}
.note-submit{background:#b45309}
.comment-toolbar{display:flex;gap:.25rem;align-items:center;margin-top:.4rem;flex-wrap:wrap}
.comment-toolbar button{border:none;background:transparent;color:var(--wh-text-muted);width:28px;height:28px;border-radius:.4rem;display:grid;place-items:center;cursor:pointer}
.comment-toolbar button:hover{background:var(--wh-gray-soft);color:var(--wh-text)}
.char-count{margin-left:auto;font-size:.7rem;color:var(--wh-text-muted)}
.char-count.over{color:var(--wh-red);font-weight:700}
.comments-list{padding:.5rem 0;max-height:520px;overflow:auto}
.comment-item{display:flex;gap:.65rem;padding:.75rem 1.1rem;transition:background .12s}
.comment-item:hover{background:var(--wh-gray-soft)}
.comment-item.is-new{animation:whPulseNew 1.2s ease}
@keyframes whPulseNew{0%{background:var(--wh-blue-soft)}100%{background:transparent}}
.comment-avatar{width:32px;height:32px;border-radius:50%;background:var(--wh-gray-light);color:var(--wh-text);display:grid;place-items:center;font-weight:700;font-size:.7rem;flex-shrink:0;border:1px solid var(--wh-border)}
.comment-avatar.wilaya{background:var(--wh-blue-soft);color:var(--wh-blue);border-color:var(--wh-blue)}
.comment-body-wrap{flex:1;min-width:0}
.comment-header{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
.comment-author{font-weight:600;font-size:.84rem}
.comment-role{font-size:.6rem;padding:.1em .4em;border-radius:999px;background:var(--wh-gray-soft);color:var(--wh-gray);font-weight:700}
.comment-role.wilaya{background:var(--wh-blue-soft);color:var(--wh-blue)}
.comment-time{font-size:.7rem;color:var(--wh-text-muted);margin-inline-start:.15rem}
.comment-edited{font-size:.65rem;color:var(--wh-text-muted);font-style:italic}
.comment-text{margin:.25rem 0 0;font-size:.86rem;line-height:1.5;white-space:pre-wrap;word-break:break-word}
.comment-actions{display:flex;gap:.35rem;margin-top:.35rem;flex-wrap:wrap}
.comment-action-btn{border:none;background:transparent;color:var(--wh-text-muted);font-size:.72rem;font-weight:500;padding:.15rem .4rem;border-radius:.4rem;cursor:pointer;display:inline-flex;align-items:center;gap:.2rem}
.comment-action-btn:hover{background:var(--wh-white);color:var(--wh-blue);border:1px solid var(--wh-border)}
.comment-action-delete:hover{color:var(--wh-red);border-color:#f5c2c7;background:#fff5f5}
.comment-reply-thread{margin-inline-start:2.2rem;border-inline-start:2px solid var(--wh-border);padding-inline-start:.65rem;margin-top:.5rem}
.comment-empty{padding:2.5rem 1rem;text-align:center;color:var(--wh-text-muted)}
.comment-empty .mdi{font-size:2.6rem;opacity:.25;display:block;margin-bottom:.5rem}
.comment-empty p{font-size:.85rem;margin:0}
.comment-empty .btn{margin-top:.75rem}
.skeleton{display:flex;gap:.65rem;padding:.75rem 1.1rem}
.skeleton-avatar{width:32px;height:32px;border-radius:50%}
.skeleton-lines{flex:1;display:flex;flex-direction:column;gap:.5rem}
.skeleton-line{height:.75rem;border-radius:.4rem}
@media(max-width:575.98px){.comment-form{padding:.75rem}.comments-list{max-height:420px}}
</style>

<section class="event-comments-section" id="eventComments">
    <div class="section-header">
        <h3 class="section-title">
            <i class="mdi mdi-comment-multiple-outline"></i>
            <?= $isAr ? 'التعليقات' : 'Commentaires' ?>
            <span class="comment-count-badge" id="commentCountBadge">0</span>
            <span id="commentTyping" style="display:none;font-size:.7rem;color:var(--wh-text-muted);margin-inline-start:.5rem"><i class="mdi mdi-dots-horizontal"></i> <?= $isAr?'يكتب…':'écrit…' ?></span>
        </h3>
        <div class="d-flex gap-1 align-items-center">
            <select id="commentSort" class="form-select form-select-sm" style="width:auto;font-size:.75rem;padding:.15rem .4rem;height:28px"><option value="recent"><?= $isAr?'الأحدث':'Récent' ?></option><option value="old"><?= $isAr?'الأقدم':'Ancien' ?></option></select>
            <button class="btn btn-sm btn-outline-secondary" id="commentRefresh" title="Actualiser" style="height:28px;padding:.15rem .4rem"><i class="mdi mdi-refresh"></i></button>
        </div>
    </div>

    <?php if ($isLogged): ?>
        <form class="comment-form" id="commentForm" data-event-id="<?= $eventId ?>">
            <div class="comment-form-avatar" title="<?= e($myName) ?>"><?= e(mb_strtoupper($myInitials)) ?></div>
            <div class="flex-grow-1">
                <div class="comment-form-input-wrap">
                    <textarea class="comment-form-input" id="commentInput"
                              placeholder="<?= $isAr ? 'أضف تعليقاً... (Ctrl+Entrée للإرسال)' : 'Ajouter un commentaire... (Ctrl+Entrée)' ?>"
                              rows="1" maxlength="2000"></textarea>
                    <button type="submit" class="comment-form-submit" id="commentSubmit" disabled title="Envoyer (Ctrl+Entrée)">
                        <i class="mdi mdi-send"></i>
                    </button>
                </div>
                <div class="comment-toolbar">
                    <button type="button" data-emoji="😊" title="Emoji">😊</button>
                    <button type="button" data-emoji="👍" title="Like">👍</button>
                    <button type="button" data-emoji="⚠️" title="Important">⚠️</button>
                    <button type="button" id="commentAiBtn" title="IA ✨"><i class="mdi mdi-auto-fix"></i> ✨</button>
                    <span class="char-count" id="commentChar">0/2000</span>
                </div>
                <div id="replyPreview" style="display:none;margin-top:.5rem;padding:.5rem .65rem;background:var(--wh-blue-soft);border-radius:.5rem;font-size:.78rem;justify-content:space-between;align-items:center">
                    <span><i class="mdi mdi-reply me-1"></i><span id="replyText"></span></span>
                    <button type="button" class="btn btn-sm p-0 border-0" onclick="document.getElementById('commentForm').removeAttribute('data-reply-to'); this.parentElement.style.display='none'"><i class="mdi mdi-close"></i></button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="p-3 text-center text-muted small"><i class="mdi mdi-login me-1"></i><?= $isAr ? 'سجّل الدخول للتعليق' : 'Connectez-vous pour commenter' ?> — <a href="<?= url('auth/login') ?>" class="text-decoration-none fw-semibold">Login</a></div>
    <?php endif; ?>

    <div class="comments-list" id="commentsList">
        <div id="commentSkeleton">
            <?php for($i=0;$i<3;$i++): ?>
            <div class="skeleton"><div class="skeleton-avatar wh-skeleton"></div><div class="skeleton-lines"><div class="skeleton-line wh-skeleton" style="width:40%"></div><div class="skeleton-line wh-skeleton"></div><div class="skeleton-line wh-skeleton" style="width:75%"></div></div></div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="p-2 text-center border-top" id="commentPagination" style="display:none">
        <button class="btn btn-sm btn-outline-primary" id="commentLoadMore"><i class="mdi mdi-chevron-down me-1"></i><?= $isAr ? 'المزيد' : 'Plus' ?> (<span id="commentRemaining">0</span>)</button>
        <div class="small text-muted mt-1"><span id="commentPageInfo"></span></div>
    </div>
</section>

<?php if (user_role() === 'wilaya'): ?>
<section class="event-notes-section" id="eventNotes">
    <div class="section-header">
        <h3 class="section-title">
            <i class="mdi mdi-lock-outline"></i>
            <?= $isAr ? 'ملاحظات داخلية' : 'Notes internes' ?>
            <span class="note-count-badge" id="noteCountBadge">0</span>
            <span class="badge bg-warning text-dark" style="font-size:.6rem;margin-inline-start:.35rem"><i class="mdi mdi-eye-off-outline me-1"></i><?= $isAr?'خاصة':'Privé Wilaya' ?></span>
        </h3>
        <span class="small text-muted" style="font-size:.7rem"><i class="mdi mdi-shield-lock-outline me-1"></i><?= $isAr?'مشفرة ومخفية عن الجمعية':'Chiffré, invisible association' ?></span>
    </div>

    <form class="comment-form" id="noteForm" data-event-id="<?= $eventId ?>">
        <div class="comment-form-avatar"><?= e(mb_strtoupper($myInitials)) ?></div>
        <div class="flex-grow-1">
            <div class="comment-form-input-wrap" style="background:#fff8e1;border-color:#f59e0b">
                <textarea class="comment-form-input note-input" id="noteInput"
                          placeholder="<?= $isAr ? 'أضف ملاحظة داخلية... (خاصة بالولاية فقط)' : 'Ajouter une note interne... (Wilaya uniquement)' ?>"
                          rows="1" maxlength="2000"></textarea>
                <button type="submit" class="comment-form-submit note-submit" id="noteSubmit" disabled title="Envoyer note">
                    <i class="mdi mdi-lock-plus"></i>
                </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <small class="text-muted" style="font-size:.7rem"><i class="mdi mdi-information-outline me-1"></i><?= $isAr?'لن تراها الجمعية':'Invisible pour l’association' ?></small>
                <span class="char-count" id="noteChar">0/2000</span>
            </div>
        </div>
    </form>

    <div class="comments-list notes-list" id="notesList">
        <div id="noteSkeleton">
            <?php for($i=0;$i<2;$i++): ?>
            <div class="skeleton"><div class="skeleton-avatar wh-skeleton"></div><div class="skeleton-lines"><div class="skeleton-line wh-skeleton" style="width:45%"></div><div class="skeleton-line wh-skeleton"></div></div></div>
            <?php endfor; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
(function () {
    'use strict';
    var CSRF = window.WH_CSRF || '';
    var isAr = <?= ($isAr ?? false) ? 'true' : 'false' ?>;
    var isLogged = <?= ($isLogged ?? false) ? 'true' : 'false' ?>;
    var eventId = <?= $eventId ?>;
    var meId = <?= (int) ($me['id'] ?? 0) ?>;
    var myRole = '<?= user_role() ?>';
    var commentPage = 1, commentLastPage = 1, notePage = 1;

    function csrfHeaders(){ return {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF}; }
    function esc(s){ var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
    function timeAgo(dateStr){
        var now=new Date(), d=new Date(dateStr), diff=Math.floor((now-d)/1000);
        if(diff<60) return isAr?'الآن':"à l'instant";
        if(diff<3600) return Math.floor(diff/60)+(isAr?' د':' min');
        if(diff<86400) return Math.floor(diff/3600)+(isAr?' س':' h');
        if(diff<604800) return Math.floor(diff/86400)+(isAr?' ي':' j');
        return d.toLocaleDateString(isAr?'ar-DZ':'fr-FR', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'});
    }
    function autoGrow(el){ el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,120)+'px'; }

    function renderComment(c, isNote){
        var name=esc((c.prenom||'')+' '+(c.nom||'')); if(!name.trim()) name=isNote?(isAr?'الولاية':'Wilaya'):(isAr?'مستخدم':'Utilisateur');
        var initials=esc(((c.prenom||'')[0]||'')+((c.nom||'')[0]||'')||'?');
        var body=esc(c.body||''); body=body.replace(/(https?:\/\/[^\s]+)/g,'<a href="$1" target="_blank" class="text-decoration-underline">$1</a>');
        var edited=c.edited_at?'<span class="comment-edited">'+(isAr?'(معدل)':'(modifié)')+'</span>':'';
        var role=(c.role||c.sender_role||''); var roleBadge='';
        if(role==='wilaya') roleBadge='<span class="comment-role wilaya"><i class="mdi mdi-shield-check me-1"></i>'+(isAr?'الولاية':'Wilaya')+'</span>';
        else if(role==='association') roleBadge='<span class="comment-role">'+(isAr?'جمعية':'Assoc')+'</span>';
        var canDelete=isLogged && (c.user_id==meId || myRole==='wilaya');
        var canEdit=isLogged && c.user_id==meId;
        var html='<div class="comment-item'+(c.is_new?' is-new':'')+'" data-comment-id="'+c.id+'">'
            +'<div class="comment-avatar '+(role==='wilaya'?'wilaya':'')+'">'+esc(initials||'?')+'</div>'
            +'<div class="comment-body-wrap">'
            +'<div class="comment-header"><span class="comment-author">'+name+'</span>'+roleBadge+'<span class="comment-time" title="'+esc(c.created_at||'')+'"><i class="mdi mdi-clock-outline me-1"></i>'+timeAgo(c.created_at)+'</span>'+edited+'</div>'
            +'<div class="comment-text">'+body+'</div>'
            +'<div class="comment-actions">';
        if(!isNote) html+='<button class="comment-action-btn" data-reply-comment="'+c.id+'"><i class="mdi mdi-reply"></i> '+(isAr?'رد':'Répondre')+'</button>';
        html+='<button class="comment-action-btn" data-copy-comment="'+c.id+'"><i class="mdi mdi-content-copy"></i></button>';
        if(canEdit) html+='<button class="comment-action-btn" data-edit-comment="'+c.id+'"><i class="mdi mdi-pencil-outline"></i> '+(isAr?'تعديل':'Modifier')+'</button>';
        if(canDelete) html+='<button class="comment-action-btn comment-action-delete" data-delete-comment="'+c.id+'" data-is-note="'+(isNote?'1':'0')+'"><i class="mdi mdi-delete-outline"></i> '+(isAr?'حذف':'Supprimer')+'</button>';
        html+='</div></div></div>';
        if(c.replies && c.replies.length){
            html+='<div class="comment-reply-thread">';
            c.replies.forEach(function(r){ html+=renderComment(r,isNote); });
            html+='</div>';
        }
        return html;
    }

    function loadComments(page, sort){
        var url='/api/events/'+eventId+'/comments?page='+(page||1)+'&sort='+(sort||document.getElementById('commentSort')?.value||'recent');
        fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF}})
            .then(function(r){return r.json()}).then(function(data){
                var list=document.getElementById('commentsList');
                var skel=document.getElementById('commentSkeleton');
                if(skel) skel.style.display='none';
                if(page<=1) list.innerHTML='';
                if(data.success && data.comments && data.comments.length>0){
                    var html=''; data.comments.forEach(function(c){ html+=renderComment(c,false); });
                    if(page<=1) list.innerHTML=html; else list.insertAdjacentHTML('beforeend',html);
                    document.getElementById('commentCountBadge').textContent=data.total||data.comments.length;
                    document.getElementById('commentCountBadge').style.animation='pulse 1.2s';
                    commentPage=data.page||page; commentLastPage=data.last_page||1;
                    var pag=document.getElementById('commentPagination');
                    if(commentPage<commentLastPage){ pag.style.display=''; document.getElementById('commentRemaining').textContent=(data.total - commentPage* (data.per_page||10)); document.getElementById('commentPageInfo').textContent=commentPage+'/'+commentLastPage; }
                    else pag.style.display='none';
                    if(data.comments.length===0 && page<=1) showEmpty(list,false);
                } else if(page<=1){
                    showEmpty(list,false);
                    document.getElementById('commentCountBadge').textContent='0';
                }
            }).catch(function(){ var sk=document.getElementById('commentSkeleton'); if(sk) sk.innerHTML='<div class="text-center text-danger small py-3">Erreur</div>'; });
    }
    function loadNotes(page){
        var el=document.getElementById('notesList'); if(!el) return;
        var url='/api/events/'+eventId+'/notes?page='+(page||1);
        fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF}})
            .then(function(r){return r.json()}).then(function(data){
                var sk=document.getElementById('noteSkeleton'); if(sk) sk.style.display='none';
                if(data.success && data.notes && data.notes.length>0){
                    var html=''; data.notes.forEach(function(n){ html+=renderComment(n,true); });
                    el.innerHTML=html;
                    document.getElementById('noteCountBadge').textContent=data.total||data.notes.length;
                } else {
                    el.innerHTML=emptyHtml(true);
                    document.getElementById('noteCountBadge').textContent='0';
                }
            }).catch(function(){});
    }
    function emptyHtml(isNote){
        return '<div class="comment-empty"><i class="mdi '+(isNote?'mdi-lock-outline':'mdi-comment-processing-outline')+'"></i><p>'+(isNote?(isAr?'لا توجد ملاحظات بعد<br><small>أضف أول ملاحظة داخلية</small>': 'Noch keine Notiz<br><small>Erste interne Notiz hinzufügen</small>'):(isAr?'لا توجد تعليقات بعد<br><small>كن أول من يعلق</small>':'Aucun commentaire<br><small>Soyez le premier à commenter</small>'))+'</p>'+(!isNote && isLogged?'<button class="btn btn-sm btn-primary" onclick="document.getElementById(\'commentInput\')?.focus()"><i class="mdi mdi-comment-plus me-1"></i>'+(isAr?'أضف تعليقاً':'Commenter')+'</button>':'')+'</div>';
    }
    function showEmpty(list,isNote){ list.innerHTML=emptyHtml(isNote); }

    // submit comment
    var form=document.getElementById('commentForm');
    if(form){
        var input=document.getElementById('commentInput'), charEl=document.getElementById('commentChar');
        input?.addEventListener('input',function(){ autoGrow(this); document.getElementById('commentSubmit').disabled=!this.value.trim(); charEl.textContent=this.value.length+'/2000'; charEl.classList.toggle('over', this.value.length>1800); });
        input?.addEventListener('keydown',function(e){ if((e.ctrlKey||e.metaKey) && e.key==='Enter'){ e.preventDefault(); form.requestSubmit(); }});
        document.querySelectorAll('[data-emoji]').forEach(function(b){ b.addEventListener('click',function(){ var e=this.getAttribute('data-emoji'); var s=input.selectionStart||0; input.value=input.value.slice(0,s)+e+input.value.slice(s); input.dispatchEvent(new Event('input')); input.focus(); }); });
        document.getElementById('commentAiBtn')?.addEventListener('click',function(){ if(!input.value.trim()) input.value=isAr?'يرجى توضيح السبب وإضافة التفاصيل':'Merci de préciser la raison et ajouter les détails'; input.dispatchEvent(new Event('input')); input.focus(); });
        form.addEventListener('submit',function(e){
            e.preventDefault();
            var body=input.value.trim(); if(!body) return;
            var btn=document.getElementById('commentSubmit'); btn.disabled=true;
            var fd=new FormData(); fd.append('body',body); fd.append('_token',CSRF);
            var replyTo=form.getAttribute('data-reply-to'); if(replyTo) fd.append('parent_id',replyTo);
            // optimistic
            var tempId='temp-'+Date.now();
            var list=document.getElementById('commentsList');
            if(list.querySelector('.comment-empty')) list.innerHTML='';
            list.insertAdjacentHTML('beforeend','<div class="comment-item" id="'+tempId+'" style="opacity:.6"><div class="comment-avatar">'+esc(myInitials)+'</div><div class="comment-body-wrap"><div class="comment-author">'+esc(myName)+'</div><div class="comment-text">'+esc(body)+'</div></div></div>');
            fetch('/api/events/'+eventId+'/comments',{method:'POST',headers:csrfHeaders(),body:fd})
                .then(function(r){return r.json()}).then(function(data){
                    document.getElementById(tempId)?.remove();
                    if(data.success && data.comment){
                        var empty=list.querySelector('.comment-empty'); if(empty) empty.remove();
                        list.insertAdjacentHTML('beforeend',renderComment(data.comment,false));
                        input.value=''; input.style.height='auto'; form.removeAttribute('data-reply-to'); document.getElementById('replyPreview').style.display='none';
                        var c=document.getElementById('commentCountBadge'); c.textContent=parseInt(c.textContent||'0')+1;
                        if(typeof showToast==='function') showToast(isAr?'تم النشر':'Commentaire publié','success');
                    } else { if(typeof showToast==='function') showToast(data.error||'Erreur','error'); document.getElementById(tempId)?.remove(); }
                }).catch(function(){ document.getElementById(tempId)?.remove(); }).finally(function(){ btn.disabled=false; charEl.textContent=(input.value.length||0)+'/2000'; });
        });
    }
    // submit note
    var noteForm=document.getElementById('noteForm');
    if(noteForm){
        var nInput=document.getElementById('noteInput'), nChar=document.getElementById('noteChar');
        nInput?.addEventListener('input',function(){ autoGrow(this); document.getElementById('noteSubmit').disabled=!this.value.trim(); nChar.textContent=this.value.length+'/2000'; nChar.classList.toggle('over', this.value.length>1800); });
        noteForm.addEventListener('submit',function(e){
            e.preventDefault();
            var body=nInput.value.trim(); if(!body) return;
            var btn=document.getElementById('noteSubmit'); btn.disabled=true;
            var fd=new FormData(); fd.append('body',body); fd.append('_token',CSRF);
            fetch('/api/events/'+eventId+'/notes',{method:'POST',headers:csrfHeaders(),body:fd})
                .then(function(r){return r.json()}).then(function(data){
                    if(data.success && data.note){
                        var list=document.getElementById('notesList');
                        var empty=list.querySelector('.comment-empty'); if(empty) empty.remove();
                        list.insertAdjacentHTML('afterbegin',renderComment(data.note,true));
                        nInput.value=''; nInput.style.height='auto';
                        var c=document.getElementById('noteCountBadge'); c.textContent=parseInt(c.textContent||'0')+1;
                    }
                }).finally(function(){ btn.disabled=false; nChar.textContent='0/2000'; });
        });
    }

    // actions
    document.addEventListener('click',function(e){
        var del=e.target.closest('[data-delete-comment]'); if(del){
            e.preventDefault();
            var id=del.getAttribute('data-delete-comment'), isNote=del.getAttribute('data-is-note')==='1';
            if(!confirm(isAr?'هل أنت متأكد؟':'Confirmer la suppression ?')) return;
            var url=isNote?'/api/notes/'+id+'/delete':'/api/comments/'+id+'/delete';
            fetch(url,{method:'POST',headers:csrfHeaders(),body:'_token='+encodeURIComponent(CSRF)})
                .then(function(r){return r.json()}).then(function(data){ if(data.success){ var it=document.querySelector('[data-comment-id="'+id+'"]'); if(it) it.remove(); }});
            return;
        }
        var rep=e.target.closest('[data-reply-comment]'); if(rep){
            e.preventDefault();
            var id=rep.getAttribute('data-reply-comment');
            var f=document.getElementById('commentForm'); if(f){ f.setAttribute('data-reply-to',id); var pv=document.getElementById('replyPreview'); pv.style.display='flex'; document.getElementById('replyText').textContent='#'+id; document.getElementById('commentInput')?.focus(); }
            return;
        }
        var cp=e.target.closest('[data-copy-comment]'); if(cp){
            var txt=cp.closest('.comment-item')?.querySelector('.comment-text')?.textContent||'';
            navigator.clipboard?.writeText(txt).then(function(){ if(typeof showToast==='function') showToast(isAr?'تم النسخ':'Copié','success'); });
            return;
        }
        var ed=e.target.closest('[data-edit-comment]'); if(ed){
            var id=ed.getAttribute('data-edit-comment');
            var item=document.querySelector('[data-comment-id="'+id+'"]'); var txtEl=item?.querySelector('.comment-text');
            if(!txtEl) return;
            var old=txtEl.textContent; var nv=prompt(isAr?'تعديل التعليق':'Modifier', old); if(nv===null || nv.trim()===old) return;
            fetch('/api/comments/'+id+'/update',{method:'POST',headers:csrfHeaders(),body:'_token='+encodeURIComponent(CSRF)+'&body='+encodeURIComponent(nv)})
                .then(function(r){return r.json()}).then(function(d){ if(d.success) txtEl.textContent=nv; });
        }
    });

    document.getElementById('commentSort')?.addEventListener('change',function(){ loadComments(1,this.value); });
    document.getElementById('commentRefresh')?.addEventListener('click',function(){ loadComments(1); loadNotes(1); });
    document.getElementById('commentLoadMore')?.addEventListener('click',function(){ loadComments(commentPage+1); });

    // polling new
    var lastCount=0;
    setInterval(function(){
        fetch('/api/events/'+eventId+'/comments?page=1',{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF}})
            .then(function(r){return r.json()}).then(function(d){
                if(d.success && d.total && d.total!==lastCount){
                    if(lastCount!==0 && d.total>lastCount){
                        var badge=document.getElementById('commentCountBadge'); badge.style.transform='scale(1.2)'; setTimeout(function(){badge.style.transform=''},300);
                        if(typeof showToast==='function') showToast((d.total-lastCount)+' '+(isAr?'تعليق جديد':'nouveau(x) commentaire(s)'),'info');
                    }
                    lastCount=d.total;
                }
            }).catch(function(){});
    },20000);

    if(isLogged) loadComments(1);
    else { var sk=document.getElementById('commentSkeleton'); if(sk) sk.style.display='none'; var l=document.getElementById('commentsList'); if(l) l.innerHTML=emptyHtml(false); }
    loadNotes(1);
})();
</script>
