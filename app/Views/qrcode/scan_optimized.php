<?php
/** @var array $evenements */
use App\Helpers\I18n;
use App\Helpers\Rbac;
use App\Helpers\Session;

$isAr = I18n::direction() === 'rtl';
$isMember = ($memberScan ?? false) || Rbac::role(Session::user()) === 'membre';
$isAssociation = ($associationScan ?? false) || Rbac::role(Session::user()) === 'association';
$participationsUrl = $isMember ? url('dashboard/participations') : url('citoyen/participations');
$backUrl = $isMember ? url('dashboard') : ($isAssociation ? url('association') : url('citoyen'));
?>
<style>
/* ═══════════════════════════════════════════════════════════════
   SCAN PAGE — Premium Mobile-First Redesign
   ═══════════════════════════════════════════════════════════════ */
.scan-scope {
    --sc-green: #1A4D3E; --sc-green-l: #2E7A5F; --sc-green-d: #0F2B22;
    --sc-gold: #D4AF37; --sc-gold-soft: rgba(212,175,55,.12);
    --sc-red: #E5484D; --sc-bg: #F4F7F5; --sc-surface: #FFFFFF;
    --sc-text: #1A2B22; --sc-text2: #4A6355; --sc-muted: #7A8F82;
    --sc-border: #DDE5E0; --sc-focus: rgba(26,77,62,.25);
    --sc-r: 20px; --sc-font: system-ui, -apple-system, 'Segoe UI', sans-serif;
}
.scan-scope,[dir=rtl] .scan-scope{font-family:var(--sc-font);color:var(--sc-text);padding:0;margin:0;-webkit-font-smoothing:antialiased}
.scan-scope *,.scan-scope *::before,.scan-scope *::after{box-sizing:border-box}

/* ── Reset all buttons inside scope ── */
.scan-scope button,.scan-scope [role="button"]{all:unset;box-sizing:border-box;font-family:inherit;cursor:pointer;-webkit-tap-highlight-color:transparent}
.scan-scope a{color:inherit;text-decoration:none}

/* ── Page ── */
.scan-scope .sc-page{max-width:480px;margin:0 auto;padding:0 16px 100px}

/* ═══ HERO ═══ */
.scan-scope .sc-hero{
    position:relative;overflow:hidden;
    background:linear-gradient(165deg,var(--sc-green-d) 0%,var(--sc-green) 35%,#2B6B55 70%,#1A8A6A 100%);
    border-radius:0 0 32px 32px;padding:14px 16px 28px;margin:0 -16px 24px;
    box-shadow:0 20px 60px rgba(15,43,34,.4);
}
.scan-scope .sc-hero::before{content:'';position:absolute;top:-80px;right:-60px;width:240px;height:240px;background:radial-gradient(circle,rgba(212,175,55,.18),transparent 65%);pointer-events:none}
.scan-scope .sc-hero::after{content:'';position:absolute;bottom:-60px;left:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(46,168,120,.15),transparent 65%);pointer-events:none}

/* ── Header bar ── */
.scan-scope .sc-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;position:relative;z-index:2}
.scan-scope .sc-back{
    display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:14px;
    background:rgba(255,255,255,.1);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.08);color:#fff;font-size:1.2rem;transition:all .2s;flex-shrink:0;
}
.scan-scope .sc-back:hover{background:rgba(255,255,255,.2)}
.scan-scope .sc-back:active{transform:scale(.92)}
[dir="rtl"] .scan-scope .sc-back .mdi-arrow-left{transform:scaleX(-1)}
.scan-scope .sc-title{font-size:1rem;font-weight:700;color:#fff;text-align:center;flex:1;letter-spacing:-.01em}
.scan-scope .sc-header-w{width:44px}

/* ── Network pill ── */
.scan-scope .sc-net{
    display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:10px;
    background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.08);
    color:rgba(255,255,255,.6);font-size:.68rem;font-family:'SF Mono','JetBrains Mono',monospace;
    margin:0 auto 18px;display:flex;justify-content:center;
    direction:ltr;unicode-bidi:isolate;position:relative;z-index:2;
}
.scan-scope .sc-net i{font-size:.8rem;color:var(--sc-gold)}
.scan-scope .sc-net-copy{
    background:none;border:none;color:rgba(255,255,255,.4);cursor:pointer;
    padding:2px 4px;border-radius:6px;font-size:.75rem;transition:all .2s;
}
.scan-scope .sc-net-copy:hover{color:#fff;background:rgba(255,255,255,.1)}
.scan-scope .sc-net-copy.copied{color:#4ade80}

/* ═══ CAMERA VIEWPORT ═══ */
.scan-scope .sc-cam-box{
    position:relative;width:100%;aspect-ratio:1/1;border-radius:24px;overflow:hidden;
    background:#0A0A0A;z-index:2;
    box-shadow:0 0 0 1px rgba(255,255,255,.06),0 20px 50px rgba(0,0,0,.35);
}
.scan-scope .sc-cam-idle{
    position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;
    text-align:center;gap:16px;z-index:1;
    background:linear-gradient(180deg,rgba(26,77,62,.06) 0%,rgba(26,77,62,.02) 100%);
}
.scan-scope .sc-cam-active .sc-cam-idle{display:none}
.scan-scope .sc-cam-ring{
    width:88px;height:88px;border-radius:50%;position:relative;
    display:flex;align-items:center;justify-content:center;
}
.scan-scope .sc-cam-ring::before{
    content:'';position:absolute;inset:0;border-radius:50%;
    border:2px solid rgba(212,175,55,.2);
    animation:scRingPulse 3s ease-in-out infinite;
}
.scan-scope .sc-cam-ring::after{
    content:'';position:absolute;inset:-8px;border-radius:50%;
    border:1px solid rgba(212,175,55,.08);
    animation:scRingPulse 3s ease-in-out infinite .5s;
}
@keyframes scRingPulse{0%,100%{opacity:.4;transform:scale(1)}50%{opacity:1;transform:scale(1.05)}}
.scan-scope .sc-cam-ring-inner{
    width:64px;height:64px;border-radius:50%;
    background:linear-gradient(135deg,rgba(212,175,55,.15),rgba(212,175,55,.05));
    display:flex;align-items:center;justify-content:center;
    border:1px solid rgba(212,175,55,.2);color:var(--sc-gold);font-size:1.8rem;
}
.scan-scope .sc-cam-hint{color:rgba(255,255,255,.4);font-size:.82rem;max-width:200px;line-height:1.5}
.scan-scope .sc-cam-https-hint{display:none;color:rgba(255,200,100,.7);font-size:.72rem;max-width:240px;line-height:1.4;text-align:center;background:rgba(212,175,55,.08);border:1px solid rgba(212,175,55,.15);border-radius:10px;padding:6px 10px;margin-top:-4px}
.scan-scope .sc-cam-go{
    display:inline-flex;align-items:center;gap:8px;padding:12px 28px;
    background:linear-gradient(135deg,var(--sc-gold),#B8932C);color:#fff;
    border-radius:14px;font-weight:700;font-size:.88rem;
    box-shadow:0 6px 24px rgba(212,175,55,.35);transition:all .2s;
}
.scan-scope .sc-cam-go:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(212,175,55,.45)}
.scan-scope .sc-cam-go:active{transform:scale(.96)}

/* ── Active camera ── */
.scan-scope .sc-cam-live{position:absolute;inset:0;display:none;z-index:1}
.scan-scope .sc-cam-active .sc-cam-live{display:block}
.scan-scope .sc-cam-live video{width:100%;height:100%;object-fit:cover}
.scan-scope .sc-cam-live canvas{display:none}

/* ── Scan overlay ── */
.scan-scope .sc-overlay{
    position:absolute;inset:0;display:none;align-items:center;justify-content:center;
    pointer-events:none;z-index:3;
}
.scan-scope .sc-cam-active .sc-overlay{display:flex}
.scan-scope .sc-frame{position:relative;width:68%;height:68%;max-width:250px}
.scan-scope .sc-corner{position:absolute;width:30px;height:30px;border:3px solid var(--sc-gold);transition:border-color .3s}
.scan-scope .sc-corner.tl{top:0;left:0;border-width:3px 0 0 3px;border-radius:10px 0 0 0}
.scan-scope .sc-corner.tr{top:0;right:0;border-width:3px 3px 0 0;border-radius:0 10px 0 0}
.scan-scope .sc-corner.bl{bottom:0;left:0;border-width:0 0 3px 3px;border-radius:0 0 0 10px}
.scan-scope .sc-corner.br{bottom:0;right:0;border-width:0 3px 3px 0;border-radius:0 0 10px 0}
.scan-scope .sc-scanline{
    position:absolute;left:6%;width:88%;height:2px;top:50%;
    background:linear-gradient(90deg,transparent 0%,var(--sc-gold) 20%,var(--sc-gold) 80%,transparent 100%);
    box-shadow:0 0 16px rgba(212,175,55,.6),0 0 4px rgba(212,175,55,.8);
    animation:scLineMove 2.4s ease-in-out infinite;opacity:.9;border-radius:2px;
}
@keyframes scLineMove{0%,100%{top:15%}50%{top:85%}}
.scan-scope .sc-frame-label{
    position:absolute;bottom:-28px;left:50%;transform:translateX(-50%);
    display:flex;align-items:center;gap:5px;color:rgba(255,255,255,.7);
    font-size:.7rem;font-weight:500;white-space:nowrap;
    text-shadow:0 1px 6px rgba(0,0,0,.5);direction:ltr;unicode-bidi:isolate;
}

/* ── Stop button ── */
.scan-scope .sc-stop{
    position:absolute;top:12px;right:12px;width:42px;height:42px;border-radius:50%;
    background:rgba(255,255,255,.9);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    color:var(--sc-red);border:none;display:none;align-items:center;justify-content:center;
    font-size:1.1rem;z-index:5;box-shadow:0 4px 16px rgba(0,0,0,.2);transition:all .2s;
}
.scan-scope .sc-cam-active .sc-stop{display:flex}
.scan-scope .sc-stop:hover{transform:scale(1.08);background:#fff}
[dir="rtl"] .scan-scope .sc-stop{right:auto;left:12px}

/* ═══ LOADING OVERLAY ═══ */
.scan-scope .sc-load{
    position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
    display:none;align-items:center;justify-content:center;z-index:10001;flex-direction:column;gap:16px;
}
.scan-scope .sc-load.show{display:flex}
.scan-scope .sc-load-ring{
    width:52px;height:52px;border-radius:50%;position:relative;
}
.scan-scope .sc-load-ring::before{
    content:'';position:absolute;inset:0;border-radius:50%;
    border:3px solid rgba(255,255,255,.1);
    border-top-color:var(--sc-gold);
    animation:scSpin .75s linear infinite;
}
@keyframes scSpin{to{transform:rotate(360deg)}}
.scan-scope .sc-load-text{color:rgba(255,255,255,.85);font-size:.88rem;font-weight:600}

/* ═══ CONTROLS CARD ═══ */
.scan-scope .sc-ctrl{
    background:var(--sc-surface);border-radius:var(--sc-r);padding:20px;
    box-shadow:0 4px 20px rgba(0,20,10,.05);border:1px solid var(--sc-border);
    margin-bottom:20px;
}
.scan-scope .sc-ctrl-primary{
    width:100%;padding:14px;gap:10px;
    background:linear-gradient(135deg,var(--sc-green),var(--sc-green-l));
    color:#fff;border-radius:16px;font-weight:700;font-size:.92rem;
    display:inline-flex;align-items:center;justify-content:center;
    box-shadow:0 6px 20px rgba(26,77,62,.25);transition:all .2s;
}
.scan-scope .sc-ctrl-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(26,77,62,.35)}
.scan-scope .sc-ctrl-primary:active{transform:scale(.98)}
.scan-scope .sc-ctrl-primary i{font-size:1.15rem}
.scan-scope .sc-sep{display:flex;align-items:center;gap:12px;margin:16px 0;color:var(--sc-muted);font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;font-weight:600}
.scan-scope .sc-sep::before,.scan-scope .sc-sep::after{content:'';flex:1;height:1px;background:var(--sc-border)}
.scan-scope .sc-alt-row{display:flex;gap:10px}
.scan-scope .sc-alt-btn{
    flex:1;padding:12px;gap:7px;background:var(--sc-surface);
    color:var(--sc-text);border:1.5px solid var(--sc-border);border-radius:14px;
    font-size:.82rem;font-weight:600;display:inline-flex;align-items:center;justify-content:center;
    transition:all .2s;
}
.scan-scope .sc-alt-btn:hover{border-color:var(--sc-green);color:var(--sc-green);background:rgba(26,77,62,.03)}
.scan-scope .sc-alt-btn:active{transform:scale(.97)}
.scan-scope .sc-alt-btn i{font-size:1.05rem}

/* ── Manual input ── */
.scan-scope .sc-manual{margin-top:14px}
.scan-scope .sc-manual-lbl{display:block;text-align:center;font-size:.72rem;color:var(--sc-muted);margin-bottom:8px;font-weight:500}
.scan-scope .sc-manual-row{display:flex;gap:8px}
.scan-scope .sc-manual-inp{
    flex:1;padding:12px 14px;border:1.5px solid var(--sc-border);border-radius:12px;
    font-family:'SF Mono','JetBrains Mono',monospace;font-size:.86rem;
    outline:none;transition:border-color .2s,color .2s;direction:ltr;unicode-bidi:isolate;text-align:left;
    background:var(--sc-bg);
}
[dir="rtl"] .scan-scope .sc-manual-inp{text-align:right}
.scan-scope .sc-manual-inp:focus{border-color:var(--sc-green);color:var(--sc-text)}
.scan-scope .sc-manual-inp::placeholder{color:var(--sc-muted);opacity:.6}
.scan-scope .sc-manual-go{
    width:48px;height:46px;border-radius:12px;
    background:var(--sc-green);color:#fff;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.1rem;flex-shrink:0;transition:all .2s;
}
.scan-scope .sc-manual-go:hover{background:var(--sc-green-l)}
.scan-scope .sc-manual-go:active{transform:scale(.92)}
[dir="rtl"] .scan-scope .sc-manual-go .mdi-arrow-right-bold{transform:scaleX(-1)}

/* ═══ OFFLINE BANNER ═══ */
.scan-scope .sc-offline{
    display:none;align-items:center;justify-content:center;gap:8px;
    padding:10px 14px;border-radius:14px;
    background:rgba(212,175,55,.08);border:1px solid rgba(212,175,55,.25);
    color:#7A5C00;font-size:.8rem;font-weight:600;margin-bottom:16px;flex-wrap:wrap;text-align:center;
}
.scan-scope .sc-offline-sync{
    padding:4px 12px !important;font-size:.75rem !important;
    background:var(--sc-gold) !important;color:#fff !important;border:none !important;
    border-radius:8px !important;font-weight:700 !important;
}

/* ═══ EVENTS LIST ═══ */
.scan-scope .sc-events{margin-bottom:20px}
.scan-scope .sc-events-head{
    display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;
}
.scan-scope .sc-events-title{font-size:.85rem;font-weight:700;color:var(--sc-text);display:flex;align-items:center;gap:6px}
.scan-scope .sc-events-title i{color:var(--sc-green);font-size:1rem}
.scan-scope .sc-events-count{
    font-size:.72rem;font-weight:600;color:var(--sc-muted);
    background:var(--sc-bg);padding:3px 10px;border-radius:8px;
}
.scan-scope .sc-ev{
    display:flex;align-items:center;gap:14px;padding:14px 16px;
    background:var(--sc-surface);border-radius:16px;margin-bottom:10px;
    border:1px solid var(--sc-border);cursor:pointer;transition:all .25s;
    position:relative;overflow:hidden;
}
.scan-scope .sc-ev::before{
    content:'';position:absolute;left:0;top:0;bottom:0;width:3px;
    background:var(--sc-green);border-radius:0 3px 3px 0;opacity:0;transition:opacity .2s;
}
.scan-scope .sc-ev:hover{border-color:var(--sc-green-l);box-shadow:0 4px 16px rgba(26,77,62,.08);transform:translateY(-1px)}
.scan-scope .sc-ev:hover::before{opacity:1}
.scan-scope .sc-ev:active{transform:scale(.99)}
.scan-scope .sc-ev-date{min-width:46px;text-align:center;flex-shrink:0}
.scan-scope .sc-ev-day{display:block;font-size:1.35rem;font-weight:800;color:var(--sc-green);line-height:1}
.scan-scope .sc-ev-month{display:block;font-size:.62rem;font-weight:700;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-top:3px}
.scan-scope .sc-ev-body{flex:1;min-width:0}
.scan-scope .sc-ev-name{
    font-size:.84rem;font-weight:600;color:var(--sc-text);margin:0 0 5px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    direction:ltr;unicode-bidi:isolate;text-align:left;
}
[dir="rtl"] .scan-scope .sc-ev-name{text-align:right}
.scan-scope .sc-ev-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.scan-scope .sc-ev-time{font-size:.68rem;color:var(--sc-muted);display:inline-flex;align-items:center;gap:3px;white-space:nowrap}
.scan-scope .sc-ev-badge{
    display:inline-block;padding:3px 9px;border-radius:7px;font-size:.65rem;
    font-weight:700;letter-spacing:.02em;line-height:1.2;white-space:nowrap;
}
.scan-scope .sc-ev-badge.badge-success{background:#065F46;color:#D1FAE5}
.scan-scope .sc-ev-badge.badge-warning{background:#92400E;color:#FEF3C7}
.scan-scope .sc-ev-badge.badge-info{background:#1E40AF;color:#DBEAFE}
.scan-scope .sc-ev-badge.badge-primary{background:var(--sc-green);color:#fff}
.scan-scope .sc-ev-badge.badge-danger{background:#991B1B;color:#FEE2E2}
.scan-scope .sc-ev-badge.badge-secondary,.scan-scope .sc-ev-badge.badge-light{background:#374151;color:#E5E7EB}
.scan-scope .sc-ev-badge:not([class*="badge-success"]):not([class*="badge-warning"]):not([class*="badge-info"]):not([class*="badge-primary"]):not([class*="badge-danger"]):not([class*="badge-secondary"]):not([class*="badge-light"]){background:var(--sc-green);color:#fff}
.scan-scope .sc-ev-action{
    width:40px;height:40px;border-radius:12px;flex-shrink:0;
    background:linear-gradient(135deg,var(--sc-green),var(--sc-green-l));
    color:#fff;display:inline-flex;align-items:center;justify-content:center;
    font-size:1rem;transition:all .2s;
}
.scan-scope .sc-ev-action:hover{transform:scale(1.1);box-shadow:0 4px 14px rgba(26,77,62,.3)}

/* ═══ POPUPS ═══ */
.scan-scope .sc-popup{
    position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
    display:none;align-items:center;justify-content:center;z-index:9999;padding:24px;opacity:0;pointer-events:none;transition:opacity .25s;
}
.scan-scope .sc-popup.show{display:flex;opacity:1;pointer-events:auto}
.scan-scope .sc-popup-card{
    background:var(--sc-surface);border-radius:28px;padding:36px 28px;text-align:center;
    max-width:340px;width:100%;box-shadow:0 32px 80px rgba(0,0,0,.3);
    animation:scPopIn .4s cubic-bezier(.17,.89,.32,1.28);
}
@keyframes scPopIn{from{opacity:0;transform:scale(.8) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
.scan-scope .sc-popup-icon{font-size:3.5rem;margin-bottom:8px;display:inline-block}
.scan-scope .sc-popup-icon.ok{color:var(--sc-green)}
.scan-scope .sc-popup-icon.err{color:var(--sc-red)}
.scan-scope .sc-popup-title{font-size:1.15rem;font-weight:700;margin:8px 0;color:var(--sc-text)}
.scan-scope .sc-popup-msg{font-size:.84rem;color:var(--sc-muted);margin-bottom:20px;line-height:1.5;unicode-bidi:isolate}
.scan-scope .sc-popup-btn{
    display:inline-flex;align-items:center;justify-content:center;gap:7px;
    padding:12px 24px;border-radius:14px;font-weight:700;font-size:.86rem;
    transition:all .2s;min-width:140px;
}
.scan-scope .sc-popup-btn:active{transform:scale(.96)}
.scan-scope .sc-popup-btn.pri{background:linear-gradient(135deg,var(--sc-green),var(--sc-green-l));color:#fff;box-shadow:0 4px 16px rgba(26,77,62,.3)}
.scan-scope .sc-popup-btn.pri:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(26,77,62,.4)}
.scan-scope .sc-popup-btn.sec{background:var(--sc-bg);color:var(--sc-text);border:1.5px solid var(--sc-border)}
.scan-scope .sc-popup-btn.sec:hover{border-color:var(--sc-green-l);background:rgba(26,77,62,.03)}
.scan-scope .sc-popup-btn.gold{background:linear-gradient(135deg,var(--sc-gold),#B8932C);color:#fff;box-shadow:0 4px 16px rgba(212,175,55,.35)}
.scan-scope .sc-popup-btn.gold:hover{transform:translateY(-1px)}
.scan-scope .sc-popup-btns{display:flex;flex-direction:column;gap:8px;align-items:center}

/* ── Badge popup extras ── */
.scan-scope .sc-medal{
    width:96px;height:96px;margin:0 auto 14px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,var(--sc-gold),#8A6D1A);
    box-shadow:0 0 0 6px rgba(212,175,55,.2),0 12px 32px rgba(212,175,55,.4);
    animation:scMedalPop .6s cubic-bezier(.17,.89,.32,1.28);
}
.scan-scope .sc-medal i{font-size:2.8rem;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.2)}
@keyframes scMedalPop{0%{transform:scale(0) rotate(-20deg)}60%{transform:scale(1.15) rotate(5deg)}100%{transform:scale(1) rotate(0)}}
.scan-scope .sc-sparkles{position:absolute;inset:0;pointer-events:none;overflow:hidden}
.scan-scope .sc-sparkles span{position:absolute;width:5px;height:5px;border-radius:50%;background:var(--sc-gold);opacity:0;animation:scSpark 1.6s ease-in-out infinite}
.scan-scope .sc-sparkles span:nth-child(1){top:10%;left:12%;animation-delay:0s}
.scan-scope .sc-sparkles span:nth-child(2){top:15%;right:14%;animation-delay:.2s}
.scan-scope .sc-sparkles span:nth-child(3){top:40%;left:5%;animation-delay:.35s}
.scan-scope .sc-sparkles span:nth-child(4){top:35%;right:6%;animation-delay:.5s}
.scan-scope .sc-sparkles span:nth-child(5){bottom:18%;left:16%;animation-delay:.65s}
.scan-scope .sc-sparkles span:nth-child(6){bottom:22%;right:18%;animation-delay:.8s}
@keyframes scSpark{0%,100%{transform:scale(0);opacity:0}50%{transform:scale(1);opacity:1}}
.scan-scope .sc-badge-info{
    display:flex;align-items:center;gap:8px;padding:10px 14px;
    background:var(--sc-bg);border-radius:12px;margin-top:12px;
    font-size:.78rem;color:var(--sc-muted);justify-content:center;
}
.scan-scope .sc-badge-info i{color:var(--sc-green);font-size:.95rem}
.scan-scope canvas{display:none}

/* ═══ TOAST ═══ */
.sc-toast{
    position:fixed;top:16px;left:50%;transform:translateX(-50%) translateY(-120%);
    z-index:10000;width:calc(100% - 32px);max-width:420px;
    transition:transform .45s cubic-bezier(.17,.89,.32,1.28);
}
.sc-toast.show{transform:translateX(-50%) translateY(0)}
.sc-toast-card{
    display:flex;align-items:center;gap:12px;padding:14px 16px;
    background:var(--sc-surface);border-radius:16px;
    box-shadow:0 12px 48px rgba(0,0,0,.2);border-left:4px solid var(--sc-green);
}
.sc-toast-icon{font-size:1.6rem;color:var(--sc-green);flex-shrink:0;animation:scToastPop .4s cubic-bezier(.17,.89,.32,1.28)}
@keyframes scToastPop{0%{transform:scale(0)}60%{transform:scale(1.2)}100%{transform:scale(1)}}
.sc-toast-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:1px}
.sc-toast-body strong{font-size:.86rem;color:var(--sc-text)}
.sc-toast-body span{font-size:.76rem;color:var(--sc-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sc-toast-link{
    width:36px;height:36px;border-radius:10px;background:var(--sc-bg);
    color:var(--sc-green);display:flex;align-items:center;justify-content:center;
    flex-shrink:0;transition:all .2s;
}
.sc-toast-link:hover{background:var(--sc-green);color:#fff}
[dir="rtl"] .sc-toast-link .mdi-arrow-right{transform:scaleX(-1)}

/* ═══ RESPONSIVE ═══ */
@media (max-width:380px){
    .scan-scope .sc-page{padding:0 12px 80px}
    .scan-scope .sc-hero{padding:12px 12px 22px;margin:0 -12px 20px;border-radius:0 0 24px 24px}
    .scan-scope .sc-ctrl{padding:16px 14px}
}
</style>

<div class="scan-scope">
<div class="sc-page">

<!-- ═══ HERO ═══ -->
<div class="sc-hero">
    <div class="sc-header">
        <a href="<?= $backUrl ?>" class="sc-back" aria-label="<?= $isAr ? 'عودة' : 'Retour' ?>"><i class="mdi mdi-arrow-left"></i></a>
        <span class="sc-title"><?= $isAr ? 'مسح رمز QR' : 'Scanner un QR Code' ?></span>
        <div class="sc-header-w"></div>
    </div>

    <div class="sc-net" id="networkInfo">
        <i class="mdi mdi-lan"></i>
        <span id="networkUrl"><?= e(url('')) ?></span>
        <button type="button" class="sc-net-copy" id="networkCopyBtn" title="<?= $isAr ? 'نسخ' : 'Copier' ?>"><i class="mdi mdi-content-copy"></i></button>
    </div>

    <!-- Camera viewport — idle state -->
    <div class="sc-cam-box" id="scanCameraFrame">
        <div class="sc-cam-idle">
            <div class="sc-cam-ring">
                <div class="sc-cam-ring-inner"><i class="mdi mdi-qrcode-scan"></i></div>
            </div>
            <p class="sc-cam-hint"><?= $isAr ? 'امسح رمز QR لتسجيل حضورك' : 'Scannez un code QR pour enregistrer votre participation' ?></p>
            <p class="sc-cam-https-hint" id="httpsHint"></p>
            <button class="sc-cam-go" id="btnStartCamera" type="button">
                <i class="mdi mdi-camera"></i>
                <span><?= $isAr ? 'تفعيل الكاميرا' : 'Activer la caméra' ?></span>
            </button>
        </div>
    </div>

    <!-- Camera viewport — active state -->
    <div class="sc-cam-box" id="scanCameraActive" style="display:none">
        <div class="sc-cam-live">
            <video id="scanVideo" playsinline muted style="width:100%;height:100%;object-fit:cover"></video>
            <canvas id="scanCanvas" style="display:none"></canvas>
        </div>
        <div class="sc-overlay">
            <div class="sc-frame">
                <div class="sc-corner tl"></div>
                <div class="sc-corner tr"></div>
                <div class="sc-corner bl"></div>
                <div class="sc-corner br"></div>
                <div class="sc-scanline"></div>
                <div class="sc-frame-label">
                    <i class="mdi mdi-qrcode-scan"></i>
                    <span><?= $isAr ? 'ضع الرمز داخل الإطار' : 'Placez le QR code dans le cadre' ?></span>
                </div>
            </div>
        </div>
        <button class="sc-stop" id="btnStopCamera" type="button" aria-label="<?= $isAr ? 'إيقاف' : 'Stop' ?>"><i class="mdi mdi-close"></i></button>
    </div>
</div>

<!-- ═══ CONTROLS ═══ -->
<div class="sc-ctrl">
    <button class="sc-ctrl-primary" id="btnStartCameraMain" type="button">
        <i class="mdi mdi-camera"></i>
        <span><?= $isAr ? 'تفعيل الكاميرا' : 'Activer la caméra' ?></span>
    </button>

    <div class="sc-sep"><span><?= $isAr ? 'أو' : 'ou' ?></span></div>

    <div class="sc-alt-row">
        <label for="scanImageInput" class="sc-alt-btn">
            <i class="mdi mdi-image-multiple-outline"></i>
            <span><?= $isAr ? 'مسح من صورة' : 'Depuis une image' ?></span>
        </label>
        <input type="file" id="scanImageInput" accept="image/*" style="display:none">
    </div>

    <div class="sc-manual">
        <label class="sc-manual-lbl" for="manualToken"><?= $isAr ? 'أو أدخل الرمز يدوياً' : 'Ou collez le code QR' ?></label>
        <div class="sc-manual-row">
            <input type="text" id="manualToken" class="sc-manual-inp" placeholder="ab12cd34-ef56-…" autocomplete="off" inputmode="text">
            <button class="sc-manual-go" type="button" id="btnManualToken" aria-label="<?= $isAr ? 'تأكيد' : 'Valider' ?>">
                <i class="mdi mdi-arrow-right-bold"></i>
            </button>
        </div>
    </div>
</div>

<!-- ═══ OFFLINE BANNER ═══ -->
<div class="sc-offline" id="offlineBanner" role="status" aria-live="polite">
    <i class="mdi mdi-cloud-off-outline"></i>
    <span id="offlineBannerText"></span>
    <button type="button" id="offlineSyncBtn" class="sc-offline-sync">
        <i class="mdi mdi-cloud-sync-outline"></i> <?= $isAr ? 'مزامنة' : 'Sync' ?>
    </button>
</div>

<!-- ═══ EVENTS ═══ -->
<?php if (!empty($evenements)): ?>
<div class="sc-events">
    <div class="sc-events-head">
        <div class="sc-events-title"><i class="mdi mdi-qrcode"></i> <?= $isAr ? 'أحدث رموز QR' : 'Événements à portée de scan' ?></div>
        <span class="sc-events-count"><?= count($evenements) ?></span>
    </div>
    <?php foreach ($evenements as $ev):
        $evDate = (new DateTimeImmutable((string)($ev['date_evenement'] ?? 'now')));
        $evDesc = (string)($ev['description'] ?? $ev['adresse'] ?? '');
        $evName = mb_strlen($evDesc) > 42 ? mb_substr($evDesc, 0, 42) . '…' : $evDesc;
        $evTime = $ev['heure'] ?? '';
        $tokenVal = e((string)($ev['token_qr'] ?? ''));
        if (empty($tokenVal)) continue;
    ?>
        <div class="sc-ev" data-token="<?= $tokenVal ?>">
            <div class="sc-ev-date">
                <span class="sc-ev-day"><?= e($evDate->format('d')) ?></span>
                <span class="sc-ev-month"><?= e($evDate->format('M')) ?></span>
            </div>
            <div class="sc-ev-body">
                <h4 class="sc-ev-name"><?= e($evName) ?></h4>
                <div class="sc-ev-row">
                    <?php if ($evTime): ?>
                        <span class="sc-ev-time"><i class="mdi mdi-clock-outline"></i> <?= e(substr($evTime, 0, 5)) ?></span>
                    <?php endif; ?>
                    <span class="sc-ev-badge badge-<?= e(statut_badge_class((string)($ev['statut'] ?? ''))) ?>"><?= e(statut_label((string)($ev['statut'] ?? ''))) ?></span>
                </div>
            </div>
            <button class="sc-ev-action" type="button" data-token="<?= $tokenVal ?>" aria-label="<?= $isAr ? 'مسح' : 'Scanner' ?>">
                <i class="mdi mdi-qrcode-scan"></i>
            </button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div>

<!-- ═══ LOADING ═══ -->
<div class="sc-load" id="scanLoading">
    <div class="sc-load-ring"></div>
    <div class="sc-load-text" id="scanLoadingText"><?= $isAr ? 'جاري المعالجة...' : 'Traitement en cours...' ?></div>
</div>

<!-- ═══ TOAST ═══ -->
<div class="sc-toast" id="scanSuccessToast" role="status" aria-live="polite">
    <div class="sc-toast-card">
        <div class="sc-toast-icon"><i class="mdi mdi-check-circle"></i></div>
        <div class="sc-toast-body">
            <strong id="toastTitle"></strong>
            <span id="toastMsg"></span>
        </div>
        <a href="<?= $participationsUrl ?>" class="sc-toast-link" title="<?= $isAr ? 'مشاركاتي' : 'Mes participations' ?>">
            <i class="mdi mdi-arrow-right"></i>
        </a>
    </div>
</div>

<!-- ═══ POPUP ERROR ═══ -->
<div class="sc-popup" id="scanErrorPopup" role="alert" aria-live="assertive">
    <div class="sc-popup-card">
        <div class="sc-popup-icon err"><i class="mdi mdi-alert-octagon-outline"></i></div>
        <h3 class="sc-popup-title" id="scanErrorTitle"></h3>
        <p class="sc-popup-msg" id="scanErrorMessage"></p>
        <div class="sc-popup-btns">
            <button type="button" class="sc-popup-btn pri" id="scanErrorRetry"><i class="mdi mdi-refresh"></i> <?= $isAr ? 'إعادة المحاولة' : 'Réessayer' ?></button>
            <button type="button" class="sc-popup-btn sec" id="scanErrorClose"><?= $isAr ? 'إغلاق' : 'Fermer' ?></button>
        </div>
    </div>
</div>

<!-- ═══ POPUP BADGE ═══ -->
<div class="sc-popup" id="scanBadgePopup" role="dialog" aria-modal="true" aria-live="polite">
    <div class="sc-popup-card" style="position:relative;overflow:hidden">
        <div class="sc-sparkles"><span></span><span></span><span></span><span></span><span></span><span></span></div>
        <div class="sc-medal" id="badgeMedal"><i class="mdi mdi-medal-outline"></i></div>
        <h3 class="sc-popup-title" style="color:var(--sc-green)"><?= $isAr ? 'Badge جديد!' : 'Badge débloqué !' ?></h3>
        <p class="sc-popup-msg" id="badgeName" style="font-weight:700;color:var(--sc-gold);font-size:1.05rem"></p>
        <p class="sc-popup-msg" id="badgeDesc" style="font-size:.82rem"></p>
        <div class="sc-badge-info" id="badgeEventInfo" style="display:none">
            <i class="mdi mdi-calendar-check"></i>
            <span id="badgeEventText"></span>
        </div>
        <canvas id="badgeShareCanvas" width="600" height="315"></canvas>
        <div class="sc-popup-btns" style="margin-top:16px">
            <button type="button" class="sc-popup-btn gold" id="badgeShareBtn"><i class="mdi mdi-share-variant"></i> <?= $isAr ? 'مشاركة' : 'Partager' ?></button>
            <button type="button" class="sc-popup-btn pri" id="badgeDownloadBtn"><i class="mdi mdi-download"></i> <?= $isAr ? 'تحميل' : 'Télécharger' ?></button>
            <button type="button" class="sc-popup-btn sec" id="badgeClose"><?= $isAr ? 'إغلاق' : 'Fermer' ?></button>
        </div>
    </div>
</div>

</div>

<script src="<?= asset('assets/vendor/zxing/index.min.js') ?>"></script>
<script>
(function () {
    'use strict';

    var I18N = <?= json_encode([
        'scan_error'           => $isAr ? 'خطأ في المسح' : 'Erreur de scan',
        'camera_unsupported'   => $isAr ? 'الكاميرا غير مدعومة في هذا الجهاز' : "La caméra n'est pas supportée.",
        'camera_http'          => $isAr ? 'الكاميرا تتطلب HTTPS. أضف الشهادة SSL أو استخدم الإدخال اليدوي أدناه.' : 'La caméra nécessite HTTPS. Ajoutez un certificat SSL ou utilisez la saisie manuelle ci-dessous.',
        'camera_denied'        => $isAr ? 'تم رفض إذن الكاميرا' : 'Accès caméra refusé',
        'decoder_unavailable'  => $isAr ? 'ماسح الرمز غير متاح' : 'Décodeur QR indisponible',
        'qr_not_recognized'    => $isAr ? 'الرمز غير معروف' : 'QR non reconnu',
        'no_qr_detected'       => $isAr ? 'لم يتم التعرف على أي رمز' : 'Aucun QR détecté',
        'enter_code'           => $isAr ? 'الرجاء إدخال رمز QR' : 'Veuillez entrer un code QR.',
        'offline_scan'         => $isAr ? 'بدون اتصال — سيتم المزامنة لاحقاً' : 'Hors connexion — le scan sera synchronisé.',
        'scans_pending'        => $isAr ? 'مسح في الانتظار' : 'scan en attente',
        'scans_pending_pl'     => $isAr ? 'مسح في الانتظار' : 'scans en attente',
        'processing'           => $isAr ? 'جاري المعالجة...' : 'Traitement en cours...',
        'participation_ok'     => $isAr ? 'تم تسجيل مشاركتك !' : 'Participation enregistrée !',
        'thanks'               => $isAr ? 'شكراً لمشاركتك' : 'Merci pour votre participation',
        'stop_camera'          => $isAr ? 'إيقاف' : 'Arrêter',
        'start_camera'         => $isAr ? 'تفعيل الكاميرا' : 'Activer la caméra',
        'already_participated' => $isAr ? 'لقد شاركت في هذا الحدث مسبقاً.' : 'Vous avez déjà participé à cet événement.',
        'event_full'           => $isAr ? 'تم الوصول للحد الأقصى من المشاركين.' : 'La capacité maximale est atteinte.',
        'expired'              => $isAr ? 'انتهت صلاحية هذا الرمز.' : 'Ce code QR n\'est plus valide.',
        'event_expired'        => $isAr ? 'انتهت صلاحية الرمز بعد انتهاء الحدث.' : 'Ce code QR a expiré après la fin de l\'événement.',
        'event_closed'         => $isAr ? 'هذا الحدث لم يعد مفتوحاً للتسجيل.' : 'Cet événement n\'est plus ouvert.',
        'unknown_error'        => $isAr ? 'خطأ غير معروف' : 'Erreur inconnue',
        'try_again'            => $isAr ? 'حدث خطأ. حاول مرة أخرى.' : 'Erreur. Veuillez réessayer.',
    ], JSON_UNESCAPED_UNICODE) ?>;

    var isAr = <?= $isAr ? 'true' : 'false' ?>;
    var CSRF = <?= json_encode(csrf_token()) ?>;
    var NETWORK_URL = <?= json_encode(url('')) ?>;
    var VALIDATE_URL = <?= json_encode(url('api/qrcode/validate')) ?>;

    var videoEl = document.getElementById('scanVideo');
    var canvasEl = document.getElementById('scanCanvas');
    var btnStartEls = [document.getElementById('btnStartCamera'), document.getElementById('btnStartCameraMain')].filter(Boolean);
    var cameraFrame = document.getElementById('scanCameraFrame');
    var cameraActive = document.getElementById('scanCameraActive');
    var btnStop = document.getElementById('btnStopCamera');
    var errorPopup = document.getElementById('scanErrorPopup');
    var scanInput = document.getElementById('scanImageInput');
    var loadOverlay = document.getElementById('scanLoading');
    var loadText = document.getElementById('scanLoadingText');
    var stream = null, zxing = null, processing = false, camOn = false;

    function t(k) { return I18N[k] || k; }
    function vibrate(p) { try { if ('vibrate' in navigator) navigator.vibrate(p); } catch(e){} }

    /* ── Offline queue ── */
    var QK = 'wh_scan_queue';
    var offBanner = document.getElementById('offlineBanner');
    var offText = document.getElementById('offlineBannerText');
    var offSync = document.getElementById('offlineSyncBtn');
    function readQ() { try { return JSON.parse(localStorage.getItem(QK)||'[]'); } catch(e){ return []; } }
    function writeQ(q) { try { localStorage.setItem(QK, JSON.stringify(q)); } catch(e){} updBanner(); }
    function updBanner() {
        var n = readQ().length;
        if (offBanner) { offBanner.style.display = n > 0 ? 'flex' : 'none'; if (offText) offText.textContent = n + ' ' + (n > 1 ? t('scans_pending_pl') : t('scans_pending')); }
    }
    function qScan(tok) { var q = readQ(); q.push({token:tok,ts:Date.now()}); writeQ(q); }
    function syncQ() {
        var q = readQ(); if (!q.length) { updBanner(); return; }
        if (offSync) offSync.disabled = true;
        var pending = q.slice(), kept = [], synced = 0;
        (function proc(i) {
            if (i >= pending.length) { writeQ(kept); if (offSync) offSync.disabled = false; if (synced > 0) showSuccess(null, 0); updBanner(); return; }
            fetch(VALIDATE_URL, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},body:'token='+encodeURIComponent(pending[i].token)+'&_token='+encodeURIComponent(CSRF)})
            .then(function(r){return r.json().then(function(d){return{ok:r.ok,d:d};});})
            .then(function(res){if(res.ok&&res.d&&res.d.success)synced++;else kept.push(pending[i]);proc(i+1);})
            .catch(function(){kept.push(pending[i]);proc(i+1);});
        })(0);
    }

    /* ── Network copy ── */
    var copyBtn = document.getElementById('networkCopyBtn');
    if (copyBtn) copyBtn.addEventListener('click', function () {
        var u = NETWORK_URL;
        var fn = navigator.clipboard && navigator.clipboard.writeText ? function(){return navigator.clipboard.writeText(u);} : function(){var inp=document.createElement('input');inp.value=u;document.body.appendChild(inp);inp.select();document.execCommand('copy');document.body.removeChild(inp);return Promise.resolve();};
        fn().then(function(){copyBtn.classList.add('copied');copyBtn.innerHTML='<i class="mdi mdi-check"></i>';setTimeout(function(){copyBtn.classList.remove('copied');copyBtn.innerHTML='<i class="mdi mdi-content-copy"></i>';},1500);});
    });

    /* ── Loading ── */
    function showLoad(m) { if(loadText) loadText.textContent=m||t('processing'); if(loadOverlay) loadOverlay.classList.add('show'); }
    function hideLoad() { if(loadOverlay) loadOverlay.classList.remove('show'); }

    /* ── Success toast ── */
    var toast = document.getElementById('scanSuccessToast');
    var toastT = document.getElementById('toastTitle');
    var toastM = document.getElementById('toastMsg');
    var toastTimer = null;
    function showSuccess(ev, pts) {
        var d = [];
        if (ev && ev.adresse) d.push(ev.adresse);
        if (ev && ev.date_evenement) { try { d.push(new Date(ev.date_evenement).toLocaleDateString(isAr?'ar-DZ':'fr-FR')); } catch(e){} }
        if (pts) d.push('+' + pts + ' pts');
        toastT.textContent = t('participation_ok');
        toastM.textContent = d.join(' \u00B7 ') || t('thanks');
        if (toast) { toast.classList.add('show'); vibrate([50,30,50]); if(toastTimer) clearTimeout(toastTimer); toastTimer = setTimeout(function(){toast.classList.remove('show');}, 4500); }
    }

    /* ── Error popup ── */
    function showError(msg, key) {
        var m = document.getElementById('scanErrorMessage');
        var h = document.getElementById('scanErrorTitle');
        if(m) m.textContent = msg;
        if(h) h.textContent = t(key || 'scan_error');
        if(errorPopup) { errorPopup.classList.add('show'); vibrate([30]); }
    }
    function mapErr(s) {
        if (!s) return t('unknown_error');
        if (/d[eé]j[àa]\s+particip[ée]/i.test(s) || /already participated/i.test(s)) return t('already_participated');
        if (/capacit[ée]\s+maximale|maximum/i.test(s)) return t('event_full');
        if (/expir[ée]/i.test(s) || /no longer valid/i.test(s)) return t('expired');
        if (/plus ouvert|no longer open/i.test(s)) return t('event_closed');
        if (/invalide|invalid/i.test(s)) return t('unknown_error');
        return s;
    }

    /* ── Badge popup ── */
    var badgePopup = document.getElementById('scanBadgePopup');
    var badgeCanvas = document.getElementById('badgeShareCanvas');
    function drawBadge(b) {
        if (!badgeCanvas) return;
        var c = badgeCanvas.getContext('2d'), W = 600, H = 315;
        var g = c.createLinearGradient(0,0,W,H); g.addColorStop(0,'#0F2B22'); g.addColorStop(.55,'#1A4D3E'); g.addColorStop(1,'#2E6E5C');
        c.fillStyle = g; c.fillRect(0,0,W,H);
        c.fillStyle = 'rgba(212,175,55,.12)'; c.beginPath(); c.arc(W-70,60,120,0,Math.PI*2); c.fill();
        c.beginPath(); c.arc(60,H-60,90,0,Math.PI*2); c.fill();
        c.fillStyle = '#D4AF37'; c.beginPath(); c.arc(W/2,118,58,0,Math.PI*2); c.fill();
        c.fillStyle = '#0F2B22'; c.font = 'bold 46px serif'; c.textAlign = 'center'; c.fillText('\u2605',W/2,137);
        c.fillStyle = '#D4AF37'; c.font = 'bold 15px sans-serif'; c.fillText('WILAYA HARMONIA',W/2,30);
        c.fillStyle = '#fff'; c.font = 'bold 26px sans-serif'; c.fillText(isAr?'\u0628\u0627\u062f\u062c \u062c\u062f\u064a\u062f!':'Badge d\u00e9bloqu\u00e9 !',W/2,208);
        c.fillStyle = '#f5d78e'; c.font = 'bold 22px sans-serif'; c.fillText(String(b.nom||''),W/2,242);
        c.fillStyle = 'rgba(255,255,255,.85)'; c.font = '15px sans-serif'; c.fillText(String(b.description||'').slice(0,64),W/2,272);
        c.fillStyle = 'rgba(255,255,255,.55)'; c.font = '12px sans-serif'; c.fillText(new Date().toLocaleDateString(isAr?'ar-DZ':'fr-FR'),W/2,300);
    }
    function badgeBlob(cb) { drawBadge({nom:document.getElementById('badgeName').textContent,description:document.getElementById('badgeDesc').textContent}); badgeCanvas.toBlob(cb,'image/png'); }
    function showBadgePopup(badge, ev) {
        if (!badgePopup) return;
        document.getElementById('badgeName').textContent = badge.nom || '';
        document.getElementById('badgeDesc').textContent = badge.description || '';
        var m = document.getElementById('badgeMedal');
        if (badge.couleur && m) m.style.background = 'linear-gradient(135deg,' + badge.couleur + ',#B8932C)';
        var ei = document.getElementById('badgeEventInfo');
        var et = document.getElementById('badgeEventText');
        if (ev && ei && et) { var p=[]; if(ev.adresse)p.push(ev.adresse); if(ev.date_evenement){try{p.push(new Date(ev.date_evenement).toLocaleDateString(isAr?'ar-DZ':'fr-FR'));}catch(e){}} if(p.length){et.textContent=p.join(' \u2014 ');ei.style.display='flex';}else{ei.style.display='none';} }
        badgePopup.classList.add('show');
        vibrate([40,60,40,60,80]);
    }

    /* ── Submit token ── */
    function submitToken(token) {
        if (!token || processing) return;
        processing = true;
        showLoad(t('processing'));
        fetch(VALIDATE_URL, {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},
            body: 'token=' + encodeURIComponent(token) + '&_token=' + encodeURIComponent(CSRF)
        })
        .then(function(r){return r.json();})
        .then(function(d){
            processing = false; hideLoad();
            if (d.success) { stopCam(); if(d.new_badge) showBadgePopup(d.new_badge, d.event||null); else showSuccess(d.event||null, d.points_gagnes||0); }
            else { showError(mapErr(d.error), d.expired ? 'expired' : 'scan_error'); }
        })
        .catch(function(){ processing=false; hideLoad(); qScan(token); vibrate([30]); showError(t('offline_scan'),'scan_error'); });
    }

    function extractToken(text) {
        if (!text) return '';
        var m = text.match(/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i);
        return m ? m[0] : text.replace(/[^\w-]/g, '');
    }
    var useNative = 'BarcodeDetector' in window;

    /* ── Camera ── */
    var isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1' || location.hostname === '::1';
    var httpsHint = document.getElementById('httpsHint');
    if (!isSecure && httpsHint) { httpsHint.textContent = t('camera_http'); httpsHint.style.display = 'block'; }
    function startCam() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (!isSecure) { showError(t('camera_http'),'scan_error'); }
            else { showError(t('camera_unsupported'),'scan_error'); }
            return;
        }
        setCamOn(true);
        // facingMode ideal pour fallback desktop
        var constraints = {video:{facingMode:{ideal:'environment'}, width:{ideal:1280}, height:{ideal:720}}};
        navigator.mediaDevices.getUserMedia(constraints)
        .then(function(s){
            stream=s;
            // s'assurer que la piste est active
            s.getVideoTracks().forEach(function(t){ t.enabled=true; });
            cameraFrame.style.display='none';
            cameraActive.style.display='flex';
            cameraActive.classList.add('sc-cam-active');
            cameraActive.style.flexDirection='column';
            videoEl.srcObject=stream;
            videoEl.muted=true;
            videoEl.playsInline=true;
            videoEl.setAttribute('playsinline','');
            videoEl.setAttribute('autoplay','');
            // forcer affichage même si metadata lente
            videoEl.style.display='block';
            videoEl.style.width='100%';
            videoEl.style.height='100%';
            return videoEl.play().catch(function(e){
                // autoplay bloqué → attendre interaction
                console.warn('play blocked', e);
                return new Promise(function(res){ videoEl.addEventListener('loadedmetadata', function(){ videoEl.play().then(res).catch(function(){}); }, {once:true}); setTimeout(res, 500); });
            });
        })
        .then(function(){
            // vérifier flux noir → attendre metadata + vérifier dimensions
            if(videoEl.videoWidth===0){
                return new Promise(function(res){
                    var done=false;
                    function finish(){ if(done) return; done=true; res(); }
                    videoEl.addEventListener('loadedmetadata', finish, {once:true});
                    videoEl.addEventListener('canplay', finish, {once:true});
                    setTimeout(finish, 1200);
                });
            }
        })
        .then(function(){
            // si toujours noir après 1.2s → fallback
            if(videoEl.videoWidth===0 || videoEl.videoHeight===0){
                console.warn('video still black', videoEl.videoWidth, videoEl.videoHeight, stream.getVideoTracks()[0]?.getSettings());
                // ne pas bloquer, essayer quand même le décodeur — certains devices mettent du temps
                setTimeout(function(){
                    if(videoEl.videoWidth===0) showError((isAr?'الكاميرا سوداء — جرب "من صورة" أو أعد السماح':'Caméra noire — essayez "Depuis une image" ou ré-autorisez')+' (videoWidth 0)','scan_error');
                }, 800);
            }
            if(useNative) runNative(); else if(window.ZXing) runZxing(); else showError(t('decoder_unavailable'),'scan_error');
        })
        .catch(function(e){
            var name=(e&&e.name?e.name:'');
            // retry sans facingMode si Overconstrained
            if(name==='OverconstrainedError'){
                console.warn('retry without facingMode');
                return navigator.mediaDevices.getUserMedia({video:true}).then(function(s){
                    stream=s; s.getVideoTracks().forEach(function(t){t.enabled=true;});
                    cameraFrame.style.display='none'; cameraActive.style.display='flex';
                    cameraActive.classList.add('sc-cam-active');
                    videoEl.srcObject=stream; videoEl.muted=true; videoEl.playsInline=true;
                    return videoEl.play().then(function(){ if(useNative) runNative(); else if(window.ZXing) runZxing(); });
                }).catch(function(e2){
                    setCamOn(false); cameraFrame.style.display='flex'; cameraActive.style.display='none';
                    cameraActive.classList.remove('sc-cam-active');
                    showError((isAr?'الكاميرا لا تدعم — استخدم صورة':'Caméra non supportée — utilisez image')+' ('+e2.name+')','scan_error');
                });
            }
            setCamOn(false);
            cameraFrame.style.display='flex';
            cameraActive.style.display='none';
            var msg=t('camera_denied');
            if(name==='NotAllowedError') msg = (isAr?'الكاميرا محجوبة — اسمح في إعدادات المتصفح وجرب https':'Caméra bloquée — autorisez dans les paramètres et utilisez https') + ' ('+name+')';
            else if(name==='NotFoundError') msg = (isAr?'لا توجد كاميرا':'Aucune caméra trouvée')+' ('+name+')';
            else if(name==='NotReadableError') msg = (isAr?'الكاميرا مشغولة — أغلق تطبيق آخر':'Caméra occupée — fermez autre app')+' ('+name+')';
            else if(name==='AbortError' || name==='SecurityError') msg = t('camera_http')+' ('+name+')';
            if(!isSecure) msg += ' — ' + t('camera_http');
            showError(msg,'scan_error');
            console.error('getUserMedia', e);
        });
    }
    function runNative() {
        var det = new BarcodeDetector({formats:['qr_code']});
        var iv = setInterval(function(){
            if(!stream){clearInterval(iv);return;}
            det.detect(videoEl).then(function(c){if(c&&c.length>0){clearInterval(iv);var tk=extractToken(c[0].rawValue);if(tk.length>3)submitToken(tk);else showError(t('qr_not_recognized'),'scan_error');}}).catch(function(){});
        },400);
    }
    function runZxing() {
        try{ zxing=new ZXing.BrowserMultiFormatReader(); zxing.decodeFromVideoDevice(undefined,videoEl,function(e,r){if(e&&e.name!=='NotFoundException')return;if(r){var tk=extractToken(r.getText());if(tk.length>3)submitToken(tk);else showError(t('qr_not_recognized'),'scan_error');}}); }
        catch(e){ showError(t('decoder_unavailable'),'scan_error'); }
    }
    function stopCam() {
        if(stream){stream.getTracks().forEach(function(tr){tr.stop();});stream=null;}
        if(zxing&&zxing.reset){try{zxing.reset();}catch(e){}zxing=null;}
        if(videoEl.srcObject) videoEl.srcObject=null;
        cameraActive.style.display='none'; cameraActive.classList.remove('sc-cam-active'); cameraFrame.style.display='flex';
        camOn=false; setCamOn(false);
    }
    function setCamOn(on) {
        camOn=on;
        btnStartEls.forEach(function(b){
            b.innerHTML=on?'<i class="mdi mdi-close"></i><span>'+t('stop_camera')+'</span>':'<i class="mdi mdi-camera"></i><span>'+t('start_camera')+'</span>';
            b.onclick=on?stopCam:startCam;
        });
    }
    function decodeImage(f) {
        showLoad(t('processing'));
        var imgUrl = URL.createObjectURL(f);
        var img = new Image();
        img.onload = function () {
            var p;
            if (useNative) {
                p = new BarcodeDetector({formats:['qr_code']}).detect(img).then(function(r) {
                    URL.revokeObjectURL(imgUrl);
                    return r;
                });
            } else if (window.ZXing) {
                try {
                    var reader = new ZXing.BrowserMultiFormatReader();
                    p = reader.decodeFromImage(null, imgUrl).then(function(r) {
                        URL.revokeObjectURL(imgUrl);
                        return r;
                    });
                } catch (e) {
                    URL.revokeObjectURL(imgUrl);
                    p = Promise.reject(e);
                }
            } else {
                URL.revokeObjectURL(imgUrl);
                p = Promise.reject();
            }
            p.then(function (r) {
                hideLoad();
                var raw = useNative ? (r && r[0] ? r[0].rawValue : '') : (r && r.getText ? r.getText() : '');
                var tk = extractToken(raw);
                if (tk.length > 3) submitToken(tk);
                else showError(t('no_qr_detected'), 'scan_error');
            })
            .catch(function () { hideLoad(); showError(t('no_qr_detected'), 'scan_error'); });
        };
        img.onerror = function () { URL.revokeObjectURL(imgUrl); hideLoad(); showError(t('no_qr_detected'), 'scan_error'); };
        img.src = imgUrl;
    }

    /* ── Event bindings ── */
    btnStartEls.forEach(function(b){b.onclick=startCam;});
    if(btnStop) btnStop.addEventListener('click',stopCam);
    document.querySelectorAll('.sc-ev-action,.sc-ev').forEach(function(el){
        el.addEventListener('click',function(e){e.stopPropagation();var tk=el.getAttribute('data-token')||(el.closest&&el.closest('.sc-ev')?el.closest('.sc-ev').getAttribute('data-token'):'');if(tk)submitToken(tk);});
    });
    document.getElementById('btnManualToken').addEventListener('click',function(){var tk=document.getElementById('manualToken').value.trim();if(!tk){showError(t('enter_code'),'scan_error');return;}submitToken(extractToken(tk));});
    document.getElementById('manualToken').addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();document.getElementById('btnManualToken').click();}});
    if(scanInput) scanInput.addEventListener('change',function(e){if(e.target.files&&e.target.files[0])decodeImage(e.target.files[0]);e.target.value='';});

    document.getElementById('scanErrorClose').addEventListener('click',function(){errorPopup.classList.remove('show');});
    document.getElementById('scanErrorRetry').addEventListener('click',function(){errorPopup.classList.remove('show');startCam();});
    document.getElementById('badgeClose').addEventListener('click',function(){badgePopup.classList.remove('show');});
    document.getElementById('badgeShareBtn').addEventListener('click',function(){
        badgeBlob(function(bl){if(!bl)return;var f=new File([bl],'badge-wh.png',{type:'image/png'});var nd={title:'Wilaya Harmonia',text:document.getElementById('badgeName').textContent,files:[f]};if(navigator.canShare&&navigator.canShare(nd))navigator.share(nd).catch(function(){});else{var a=document.createElement('a');a.href=URL.createObjectURL(bl);a.download='badge-wilaya-harmonia.png';a.click();}});
    });
    document.getElementById('badgeDownloadBtn').addEventListener('click',function(){
        badgeBlob(function(bl){if(!bl)return;var a=document.createElement('a');a.href=URL.createObjectURL(bl);a.download='badge-wilaya-harmonia.png';a.click();});
    });

    document.addEventListener('keydown',function(e){if(e.key==='Escape'){errorPopup.classList.remove('show');badgePopup.classList.remove('show');toast.classList.remove('show');stopCam();}});

    updBanner();
    if(offSync) offSync.addEventListener('click',syncQ);
    window.addEventListener('online',function(){if(readQ().length>0)syncQ();});
})();
</script>
