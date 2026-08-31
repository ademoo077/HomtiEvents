<?php
/** @var array $associations */
use App\Helpers\I18n;

$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
$title = $isAr ? 'التقويم' : 'Calendrier';
$page  = 'wilaya.calendar';
?>
<div class="wh-page">
    <!-- Gradient Hero -->
    <div class="mb-4" style="background:linear-gradient(135deg, #0B5ED7 0%, #6610f2 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;color:#fff;position:relative;overflow:hidden">
        <div style="position:absolute;top:-40%;right:-8%;width:320px;height:320px;background:rgba(255,255,255,.08);border-radius:50%"></div>
        <div style="position:absolute;bottom:-30%;left:5%;width:200px;height:200px;background:rgba(255,255,255,.05);border-radius:50%"></div>
        <div class="row align-items-center" style="position:relative;z-index:1">
            <div class="col-lg-8">
                <h1 class="mb-1" style="font-size:1.5rem;font-weight:800">
                    <i class="mdi mdi-calendar-month me-2"></i><?= $isAr ? 'التقويم' : 'Calendrier des événements' ?>
                </h1>
                <p class="mb-0" style="opacity:.85;font-size:.9rem">
                    <?= $isAr ? 'تنظيم جميع الفعاليات حسب الجمعية' : 'Organisation de tous les événements par association' ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a class="btn btn-light btn-lg" href="<?= url('wilaya/dashboard') ?>">
                    <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--wh-radius)">
        <div class="card-body d-flex flex-wrap align-items-center gap-3 p-3 p-md-4">
            <div class="d-flex align-items-center gap-2">
                <div style="width:30px;height:30px;border-radius:.5rem;background:var(--wh-blue-soft);display:grid;place-items:center">
                    <i class="mdi mdi-filter-variant" style="color:var(--wh-blue);font-size:.95rem"></i>
                </div>
                <label class="form-label mb-0 fw-medium small" style="color:#475569"><?= $isAr ? 'تصفية حسب الجمعية' : 'Filtrer par association' ?></label>
            </div>
            <select class="form-select form-select-sm" style="max-width:280px;border-radius:.55rem" id="calendarAssocFilter">
                <option value=""><?= $isAr ? 'الكل' : 'Toutes les associations' ?></option>
                <?php foreach ($associations as $a): ?>
                    <option value="<?= (int) $a['id'] ?>"><?= e($a['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="vr d-none d-md-block" style="height:24px"></div>
            <div class="d-flex flex-wrap gap-2 small" id="calendarLegend">
                <span class="wh-cal-legend-chip" style="--dot:#f59e0b"><?= $isAr ? 'بانتظار' : 'En attente' ?></span>
                <span class="wh-cal-legend-chip" style="--dot:#3b82f6"><?= $isAr ? 'موافق عليه' : 'Validé' ?></span>
                <span class="wh-cal-legend-chip" style="--dot:#06b6d4"><?= $isAr ? 'مبرمج' : 'Programmé' ?></span>
                <span class="wh-cal-legend-chip" style="--dot:#8b5cf6"><?= $isAr ? 'QR مولّد' : 'QR généré' ?></span>
                <span class="wh-cal-legend-chip" style="--dot:#2563eb"><?= $isAr ? 'جاري' : 'En cours' ?></span>
                <span class="wh-cal-legend-chip" style="--dot:#10b981"><?= $isAr ? 'منجز' : 'Terminé' ?></span>
                <span class="wh-cal-legend-chip" style="--dot:#ef4444"><?= $isAr ? 'مرفوض' : 'Refusé' ?></span>
            </div>
        </div>
    </div>

    <!-- FullCalendar -->
    <div class="card border-0 shadow-sm" style="border-radius:var(--wh-radius)">
        <div class="card-body p-3">
            <div id="wilayaCalendar"></div>
        </div>
    </div>

    <!-- Modal détail événement -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: .75rem">
                <div class="modal-header border-0 pb-0" style="background:var(--wh-blue-soft);border-radius:.75rem .75rem 0 0">
                    <h5 class="modal-title fw-bold" id="eventModalTitle" style="font-size:1rem"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="eventModalBody"></div>
                <div class="modal-footer border-0 pt-0">
                    <a class="btn btn-sm btn-primary" id="eventModalLink" href="#" style="border-radius:.5rem">
                        <i class="mdi mdi-eye me-1"></i><?= $isAr ? 'عرض الفعالية' : 'Voir l\'événement' ?>
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" id="eventModalIcal" href="#" target="_blank" style="border-radius:.5rem">
                        <i class="mdi mdi-calendar-export me-1"></i>iCal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS (CDN — le fichier vendu est corrompu) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">

<style>
/* ═══════════════════════════════════════════════════════════════════
   CALENDRIER WILAYA — Style extra institutionnel
   ═══════════════════════════════════════════════════════════════════ */
#wilayaCalendar {
    --fc-border-color: var(--wh-border, #dee2e6);
    --fc-page-bg-color: var(--wh-white, #fff);
    --fc-neutral-bg-color: var(--wh-gray-soft, #f8f9fa);
    --fc-today-bg-color: rgba(11, 94, 215, .04);
    --fc-event-border-color: transparent;
    font-family: inherit;
}
#wilayaCalendar .fc {
    border: none;
}
#wilayaCalendar .fc-toolbar-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--wh-text, #212b36);
}
#wilayaCalendar .fc-button {
    background: var(--wh-white, #fff);
    color: var(--wh-text, #212b36);
    border: 1px solid var(--wh-border, #dee2e6);
    border-radius: .5rem;
    padding: .35rem .75rem;
    font-weight: 600;
    font-size: .8rem;
    transition: all .2s;
}
#wilayaCalendar .fc-button:hover {
    background: var(--wh-blue-soft, #e7f1ff);
    border-color: var(--wh-blue, #0B5ED7);
    color: var(--wh-blue, #0B5ED7);
}
#wilayaCalendar .fc-button-active {
    background: var(--wh-blue, #0B5ED7) !important;
    border-color: var(--wh-blue, #0B5ED7) !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(11, 94, 215, .3);
}
#wilayaCalendar .fc-daygrid-day {
    border: 1px solid var(--wh-border, #dee2e6);
    border-radius: .35rem;
    transition: background .15s;
}
#wilayaCalendar .fc-daygrid-day:hover {
    background: var(--wh-blue-soft, #e7f1ff);
}
#wilayaCalendar .fc-day-today {
    background: rgba(11, 94, 215, .06) !important;
    box-shadow: inset 0 0 0 2px rgba(11, 94, 215, .2);
    border-radius: .35rem;
}
#wilayaCalendar .fc-col-header-cell {
    padding: .6rem .25rem;
    font-weight: 700;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--wh-text-muted, #697586);
    background: var(--wh-gray-soft, #f8f9fa);
    border-radius: .35rem .35rem 0 0;
}
#wilayaCalendar .fc-event {
    border-radius: .35rem;
    padding: 2px 6px;
    font-size: .72rem;
    font-weight: 600;
    line-height: 1.3;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,.1);
    transition: transform .15s, box-shadow .15s;
}
#wilayaCalendar .fc-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0,0,0,.15);
}
#wilayaCalendar .fc-daygrid-event {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#wilayaCalendar .fc-more-link {
    font-size: .72rem;
    font-weight: 600;
    color: var(--wh-blue, #0B5ED7);
}

/* Légende */
.wh-cal-legend-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .25rem .7rem;
    border-radius: 999px;
    background: #f1f5f9;
    font-size: .72rem;
    font-weight: 600;
    color: #475569;
    transition: background .15s, transform .15s;
}
.wh-cal-legend-chip:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}
.wh-cal-legend-chip::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--dot, #6b7280);
    flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(255,255,255,.6);
}

/* Tooltip */
.wh-cal-tooltip {
    position: absolute;
    z-index: 1000;
    background: var(--wh-white, #fff);
    border: 1px solid var(--wh-border, #dee2e6);
    border-radius: .5rem;
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
    padding: .75rem 1rem;
    font-size: .78rem;
    max-width: 260px;
    pointer-events: none;
    opacity: 0;
    transition: opacity .15s;
}
.wh-cal-tooltip.show { opacity: 1; }
.wh-cal-tooltip-title { font-weight: 700; margin-bottom: .25rem; }
.wh-cal-tooltip-meta { color: var(--wh-text-muted, #697586); font-size: .72rem; }
.wh-cal-tooltip-statut {
    display: inline-block;
    padding: .15em .5em;
    border-radius: .75rem;
    font-size: .68rem;
    font-weight: 700;
    color: #fff;
    margin-top: .35rem;
}

/* Responsive */
@media (max-width: 767.98px) {
    #wilayaCalendar .fc-toolbar { flex-direction: column; gap: .5rem; }
    #wilayaCalendar .fc-toolbar-title { font-size: 1rem; }
    #calendarLegend { display: none !important; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/fr.global.js"></script>
<script>
(function () {
    var calendarEl = document.getElementById('wilayaCalendar');
    if (!calendarEl) return;

    var csrfToken = (window.WH_CSRF && window.WH_CSRF.token) || '';
    var baseUrl = '<?= rtrim(url(''), '/') ?>/';
    var isAr = <?= $isAr ? 'true' : 'false' ?>;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: isAr ? 'ar' : 'fr',
        firstDay: 6,
        height: 'auto',
        nowIndicator: true,
        navLinks: true,
        dayMaxEvents: 3,
        moreLinkText: isAr ? '+المزيد' : '+ de plus',
        noEventsText: isAr ? 'لا توجد فعاليات' : 'Aucun événement',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },

        views: {
            dayGridMonth: { buttonText: isAr ? 'شهر' : 'Mois' },
            timeGridWeek: { buttonText: isAr ? 'أسبوع' : 'Semaine', allDaySlot: false },
            timeGridDay:  { buttonText: isAr ? 'يوم' : 'Jour', allDaySlot: false },
            listWeek:     { buttonText: isAr ? 'قائمة' : 'Liste' }
        },

        events: function (info, successCallback, failureCallback) {
            var assocId = document.getElementById('calendarAssocFilter').value;
            var url = baseUrl + 'api/wilaya/calendrier'
                + '?start=' + encodeURIComponent(info.startStr)
                + '&end=' + encodeURIComponent(info.endStr);
            if (assocId) url += '&association_id=' + encodeURIComponent(assocId);

            fetch(url, { credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) {
                        console.error('Calendar API error:', r.status, r.statusText);
                        return r.text().then(function(t) { console.error('Response:', t.substring(0, 500)); });
                    }
                    return r.json();
                })
                .then(function (data) {
                    if (data && data.events) {
                        successCallback(data.events);
                    } else {
                        console.error('Calendar API: unexpected response', data);
                        successCallback([]);
                    }
                })
                .catch(function (err) {
                    console.error('Calendar fetch error:', err);
                    failureCallback();
                });
        },

        eventClick: function (info) {
            info.jsEvent.preventDefault();
            var ev = info.event;
            var props = ev.extendedProps || {};
            var modal = document.getElementById('eventModal');
            var modalTitle = document.getElementById('eventModalTitle');
            var modalBody = document.getElementById('eventModalBody');
            var modalLink = document.getElementById('eventModalLink');
            var modalIcal = document.getElementById('eventModalIcal');

            modalTitle.textContent = ev.title;

            var statutLabel = props.statut || '';
            var statutColors = {
                'EN_ATTENTE': '#f59e0b', 'VALIDÉ': '#3b82f6', 'PROGRAMME': '#06b6d4',
                'QR_GENERE': '#8b5cf6', 'EN_COURS': '#2563eb', 'TERMINE': '#10b981',
                'REFUSE': '#ef4444', 'ANNULE': '#6b7280'
            };
            var dateStr = ev.start ? ev.start.toLocaleDateString(isAr ? 'ar-DZ' : 'fr-FR', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            }) : '—';
            var timeStr = ev.start ? ev.start.toLocaleTimeString(isAr ? 'ar-DZ' : 'fr-FR', {
                hour: '2-digit', minute: '2-digit'
            }) : '';

            modalBody.innerHTML =
                '<div class="mb-2">' +
                    '<span class="wh-cal-tooltip-statut" style="background:' + (statutColors[statutLabel] || '#6b7280') + '">' +
                    statutLabel + '</span>' +
                '</div>' +
                '<div class="mb-1"><strong><i class="mdi mdi-calendar me-1"></i>' + dateStr + '</strong> ' +
                    (timeStr ? '<span class="text-muted">' + timeStr + '</span>' : '') + '</div>' +
                '<div class="mb-1 text-muted small"><i class="mdi mdi-map-marker me-1"></i>' + (props.commune_nom || '—') + '</div>' +
                '<div class="mb-1 text-muted small"><i class="mdi mdi-account-group me-1"></i>' +
                    (props.participants || 0) + (props.capacite ? ' / ' + props.capacite : '') +
                    ' <?= $isAr ? 'مشارك' : 'participant(s)' ?></div>' +
                '<div class="mb-1 text-muted small"><i class="mdi mdi-office-building me-1"></i>' +
                    (props.association_nom || '—') + '</div>' +
                (props.description ? '<p class="mb-0 mt-2 small">' + props.description + '</p>' : '');

            modalLink.href = baseUrl + 'wilaya/evenements/' + ev.id;
            modalIcal.href = baseUrl + 'evenement/' + ev.id + '/ical';

            var bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        },

        eventDidMount: function (info) {
            var props = info.event.extendedProps || {};
            var tip = document.createElement('div');
            tip.className = 'wh-cal-tooltip';
            tip.innerHTML =
                '<div class="wh-cal-tooltip-title">' + info.event.title + '</div>' +
                '<div class="wh-cal-tooltip-meta">' +
                    (props.commune_nom || '') +
                    (props.participants !== undefined ? ' · ' + props.participants + ' participants' : '') +
                '</div>';
            document.body.appendChild(tip);

            var rect = info.el.getBoundingClientRect();
            tip.style.left = rect.left + 'px';
            tip.style.top = (rect.bottom + 4) + 'px';

            info.el.addEventListener('mouseenter', function () { tip.classList.add('show'); });
            info.el.addEventListener('mouseleave', function () { tip.classList.remove('show'); });

            info.el.addEventListener('mouseleave', function () {
                setTimeout(function () { if (tip.parentNode) tip.parentNode.removeChild(tip); }, 200);
            });
        },

        datesSet: function () {
            var tips = document.querySelectorAll('.wh-cal-tooltip.show');
            tips.forEach(function (t) { t.classList.remove('show'); });
        }
    });

    calendar.render();

    var filter = document.getElementById('calendarAssocFilter');
    if (filter) {
        filter.addEventListener('change', function () { calendar.refetchEvents(); });
    }
})();
</script>
