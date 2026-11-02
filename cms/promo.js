/*
 * FSIA website-wide event promotion.
 * Add once to the shared footer (e.g. footer1806.php), before </body>:
 *   <script src="/cms/promo.js" defer></script>
 * It asks the CMS which promotion belongs on this page (Events & Promotions in the
 * CMS admin decides: pages, dates, priority) and shows it at the bottom of the
 * screen: a full-width bar on computers, a floating card on phones, with a live
 * countdown to the start. Closing it shrinks it to a small pill in the corner (which
 * opens it again); the pill's own x hides it until the event is changed.
 * Nov 2026: colour themes, offer badge, WhatsApp button, several live events taking
 * turns (dots), an optional popup once a day, and views / clicks sent to the CMS.
 * Text is set as text, never as HTML.
 *
 * The CMS admin runs this same file for its previews (window.__fsiaPromoPreview
 * set first): it then only defines window.FSIAPromo.build and fetches nothing.
 */
(function () {
  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var IST = 330 * 60000;
  var DAY = 86400000;

  var CSS = [
    '.fsia-promo,.fsia-promo *,.fsia-promo-pill,.fsia-promo-pill *,.fsia-pop,.fsia-pop *{box-sizing:border-box;margin:0;padding:0;border:0;line-height:1.3;letter-spacing:normal;text-transform:none;text-align:left;font-style:normal}',
    '.fsia-promo,.fsia-promo-pill,.fsia-pop-card{--fp-bg:linear-gradient(100deg,#0A0F1C 0%,#111A2E 55%,#1C1A12 100%);--fp-text:#F6F2E8;--fp-title:#FFFFFF;--fp-muted:#C9CFDD;--fp-soft:#9AA3B5;--fp-eye:#E2C46B;--fp-gold:#D4AF37;--fp-line:rgba(212,175,55,.55);--fp-hair:#F3D27A;--fp-btn:linear-gradient(135deg,#F6DC8C 0%,#D4AF37 55%,#B98F1E 100%);--fp-ink:#0C1322;--fp-glow:rgba(212,175,55,.35);--fp-tile:rgba(255,255,255,.06);--fp-x:rgba(255,255,255,.08);--fp-x2:rgba(255,255,255,.18);--fp-face:#0A0F1C;--fp-pic:#1A2236}',
    '.fsia-t-royal{--fp-bg:linear-gradient(100deg,#2A0509 0%,#5B0E16 55%,#3A0A0F 100%);--fp-eye:#F3D27A;--fp-soft:#E7B7B7;--fp-muted:#F0D6D6;--fp-face:#2A0509;--fp-pic:#3A0A0F}',
    '.fsia-t-rose{--fp-bg:linear-gradient(100deg,#3B0A2A 0%,#7A1450 55%,#4A0D33 100%);--fp-eye:#FFC2DD;--fp-gold:#F48FB1;--fp-line:rgba(244,143,177,.6);--fp-hair:#FFC2DD;--fp-btn:linear-gradient(135deg,#FF9CC8 0%,#E0457F 55%,#B8135A 100%);--fp-ink:#FFFFFF;--fp-glow:rgba(224,69,127,.4);--fp-soft:#F1BCD5;--fp-muted:#F8DCEA;--fp-face:#3B0A2A;--fp-pic:#4A0D33}',
    '.fsia-t-emerald{--fp-bg:linear-gradient(100deg,#03241B 0%,#0B3D2E 55%,#1A2E12 100%);--fp-soft:#A9C9BC;--fp-muted:#D2E6DD;--fp-face:#03241B;--fp-pic:#0B3D2E}',
    '.fsia-t-ivory{--fp-bg:linear-gradient(100deg,#FFFDF7 0%,#FBF4E4 60%,#F4E7C8 100%);--fp-text:#1B2236;--fp-title:#0C1322;--fp-muted:#4A5468;--fp-soft:#6B7385;--fp-eye:#9A7414;--fp-gold:#B8901F;--fp-line:rgba(184,144,31,.6);--fp-btn:linear-gradient(135deg,#24365C 0%,#111A2E 60%,#0A0F1C 100%);--fp-ink:#F6DC8C;--fp-glow:rgba(12,19,34,.28);--fp-tile:rgba(12,19,34,.05);--fp-x:rgba(12,19,34,.07);--fp-x2:rgba(12,19,34,.14);--fp-face:#FFFDF7;--fp-pic:#EFE6D2}',
    '.fsia-promo{position:fixed;left:0;right:0;bottom:0;z-index:2147483000;color:var(--fp-text);font:500 14px/1.3 "Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;' +
      'background:var(--fp-bg);border-top:1px solid var(--fp-line);box-shadow:0 -12px 32px rgba(0,0,0,.28);' +
      'animation:fsia-promo-in .5s cubic-bezier(.2,.8,.2,1) both}',
    '.fsia-promo::before{content:"";position:absolute;left:0;right:0;top:-1px;height:2px;background:linear-gradient(90deg,transparent,var(--fp-hair) 20%,var(--fp-gold) 50%,var(--fp-hair) 80%,transparent)}',
    '.fsia-promo-in{display:flex;align-items:center;gap:18px;max-width:1240px;margin:0 auto;padding:12px 60px 12px 20px}',
    '.fsia-promo-pic{flex:none;width:136px;height:68px;border-radius:10px;object-fit:cover;border:1px solid var(--fp-line);background:var(--fp-pic)}',
    '.fsia-promo-txt{flex:1;min-width:0}',
    '.fsia-promo-eye{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--fp-eye);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-eye span{text-transform:uppercase;letter-spacing:inherit}',
    '.fsia-promo-dot{flex:none;width:7px;height:7px;border-radius:50%;background:#34D399;box-shadow:0 0 0 0 rgba(52,211,153,.6);animation:fsia-promo-pulse 1.8s infinite}',
    '.fsia-promo-title{margin-top:3px;font:700 19px/1.2 Cinzel,"Playfair Display",Georgia,serif;color:var(--fp-title);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-meta{display:flex;align-items:center;gap:14px;margin-top:5px;font-size:13px;color:var(--fp-muted);white-space:nowrap;overflow:hidden}',
    '.fsia-promo-meta span{display:inline-flex;align-items:center;gap:5px;min-width:0;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-meta svg{flex:none;width:14px;height:14px;color:var(--fp-gold)}',
    '.fsia-promo-sum{color:var(--fp-soft);overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-guests{flex:none;display:flex;align-items:center;gap:16px;padding-left:18px;border-left:1px solid var(--fp-x2)}',
    '.fsia-promo-g{display:flex;align-items:center;gap:10px;max-width:230px;min-width:0}',
    '.fsia-promo-face{flex:none;width:50px;height:50px;border-radius:50%;object-fit:cover;background:var(--fp-pic);box-shadow:0 0 0 2px var(--fp-face),0 0 0 4px var(--fp-gold)}',
    '.fsia-promo-face-x{display:flex;align-items:center;justify-content:center;color:var(--fp-gold);font-size:20px}',
    '.fsia-promo-role{font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--fp-eye);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-name{margin-top:2px;font-size:15px;font-weight:700;color:var(--fp-title);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-cta{flex:none;display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border-radius:999px;font-size:14px;font-weight:800;color:var(--fp-ink) !important;text-decoration:none !important;white-space:nowrap;' +
      'background:var(--fp-btn);box-shadow:0 6px 18px var(--fp-glow);transition:transform .15s,box-shadow .15s}',
    '.fsia-promo-cta{position:relative;overflow:hidden}',
    '.fsia-promo-cta::after{content:"";position:absolute;top:0;bottom:0;left:-60%;width:40%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.6),transparent);transform:skewX(-20deg);animation:fsia-promo-shine 5s ease-in-out 2s infinite}',
    '.fsia-promo-cta:hover{transform:translateY(-1px);box-shadow:0 10px 24px var(--fp-glow)}',
    '.fsia-promo-cta svg{width:16px;height:16px}',
    '.fsia-promo-x{position:absolute;right:14px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:var(--fp-x);color:var(--fp-text);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s}',
    '.fsia-promo-x:hover{background:var(--fp-x2)}',
    '.fsia-promo-x svg{width:14px;height:14px}',
    /* Countdown: tiles on wide screens, one line in the label elsewhere */
    '.fsia-promo-cd{flex:none;display:flex;gap:6px}',
    '.fsia-promo-cd div{min-width:48px;padding:7px 4px 6px;border-radius:10px;background:var(--fp-tile);border:1px solid var(--fp-line)}',
    '.fsia-promo-cd b{display:block;font:800 19px/1 "Plus Jakarta Sans",system-ui,sans-serif;color:var(--fp-title);text-align:center;font-variant-numeric:tabular-nums}',
    '.fsia-promo-cd i{display:block;margin-top:4px;font-size:9px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--fp-eye);text-align:center}',
    '.fsia-promo-cdi{display:none;font-variant-numeric:tabular-nums}',
    '.fsia-has-cd .fsia-promo-sum{display:none}',
    '@media (max-width:1300px){.fsia-promo:not(.fsia-pv) .fsia-promo-cd{display:none}.fsia-promo:not(.fsia-pv) .fsia-promo-cdi{display:inline}.fsia-promo:not(.fsia-pv) .fsia-promo-cd-on .fsia-promo-lbl{display:none}}',
    '.fsia-narrow .fsia-promo-cd,.fsia-m .fsia-promo-cd{display:none}',
    '.fsia-narrow .fsia-promo-cdi,.fsia-m .fsia-promo-cdi{display:inline}',
    '.fsia-narrow .fsia-promo-cd-on .fsia-promo-lbl,.fsia-m .fsia-promo-cd-on .fsia-promo-lbl{display:none}',
    /* Minimised: a small pill in the corner */
    '.fsia-promo-pill{position:fixed;right:16px;bottom:16px;z-index:2147483000;display:flex;align-items:center;gap:10px;max-width:min(360px,calc(100vw - 24px));padding:6px 8px 6px 6px;border-radius:999px;cursor:pointer;' +
      'background:var(--fp-bg);border:1px solid var(--fp-line);box-shadow:0 12px 32px rgba(0,0,0,.35);color:var(--fp-text);font:600 13px/1.25 "Plus Jakarta Sans",system-ui,sans-serif;animation:fsia-promo-pop .35s cubic-bezier(.2,.8,.2,1) both}',
    '.fsia-promo-pill img,.fsia-promo-pill .fsia-promo-pill-star{flex:none;width:36px;height:36px;border-radius:50%;object-fit:cover;box-shadow:0 0 0 2px var(--fp-gold)}',
    '.fsia-promo-pill-star{display:flex;align-items:center;justify-content:center;color:var(--fp-ink);background:var(--fp-btn)}',
    '.fsia-promo-pill-t{min-width:0;flex:1}',
    '.fsia-promo-pill-t b{display:block;font-weight:700;color:var(--fp-title);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-pill-t span{display:block;font-size:11px;color:var(--fp-eye);white-space:nowrap;font-variant-numeric:tabular-nums}',
    '.fsia-promo-pill-go{flex:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--fp-ink);background:var(--fp-btn)}',
    '.fsia-promo-pill-go svg{width:15px;height:15px}',
    '.fsia-promo-pill-x{flex:none;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--fp-x);color:var(--fp-muted);cursor:pointer}',
    '.fsia-promo-pill-x svg{width:11px;height:11px}',
    /* Phones: a floating card */
    '.fsia-promo.fsia-m{left:10px;right:10px;bottom:10px;border:1px solid var(--fp-line);border-radius:18px;box-shadow:0 14px 40px rgba(0,0,0,.4)}',
    '.fsia-promo.fsia-m::before{left:24px;right:24px}',
    '.fsia-m .fsia-promo-in{display:block;padding:12px}',
    '.fsia-m .fsia-promo-top{display:flex;align-items:center;gap:12px;padding-right:30px}',
    '.fsia-m .fsia-promo-pic{width:62px;height:62px;border-radius:12px}',
    '.fsia-m .fsia-promo-eye{font-size:10px;letter-spacing:.12em}',
    '.fsia-m .fsia-promo-title{font-size:16px;white-space:normal;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}',
    '.fsia-m .fsia-promo-meta{margin-top:4px;font-size:12px;gap:10px}',
    '.fsia-m .fsia-promo-bot{display:flex;align-items:center;gap:10px;margin-top:12px;padding-top:12px;border-top:1px solid var(--fp-x2)}',
    '.fsia-m .fsia-promo-guests{flex:1;min-width:0;padding:0;border:0;gap:10px}',
    '.fsia-m .fsia-promo-g{max-width:none}',
    '.fsia-m .fsia-promo-face{width:34px;height:34px;box-shadow:0 0 0 2px var(--fp-face),0 0 0 3px var(--fp-gold)}',
    '.fsia-m .fsia-promo-role{font-size:9.5px}',
    '.fsia-m .fsia-promo-name{font-size:13px}',
    '.fsia-m .fsia-promo-cta{padding:11px 18px;font-size:13px}',
    '.fsia-m .fsia-promo-cta.fsia-wide{flex:1;justify-content:center}',
    '.fsia-m .fsia-promo-x{top:10px;right:10px;transform:none;width:28px;height:28px}',
    /* Admin previews: in the flow, no animation */
    '.fsia-promo.fsia-pv{position:relative;z-index:auto;animation:none}',
    '.fsia-promo.fsia-pv.fsia-m{left:auto;right:auto;bottom:auto;margin:0 10px 10px}',
    '@keyframes fsia-promo-in{from{transform:translateY(110%);opacity:0}to{transform:none;opacity:1}}',
    '@keyframes fsia-promo-shine{0%{left:-60%}25%,100%{left:130%}}',
    '@keyframes fsia-promo-pop{from{transform:translateY(20px) scale(.9);opacity:0}to{transform:none;opacity:1}}',
    '@keyframes fsia-promo-pulse{70%{box-shadow:0 0 0 7px rgba(52,211,153,0)}100%{box-shadow:0 0 0 0 rgba(52,211,153,0)}}',
    '@media (max-width:1180px){.fsia-promo:not(.fsia-pv) .fsia-promo-sum{display:none}}',
    '.fsia-promo.fsia-narrow .fsia-promo-sum{display:none}',
    /* Offer badge */
    '.fsia-promo-badge{flex:none;position:relative;overflow:hidden;display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--fp-ink);background:var(--fp-btn);box-shadow:0 2px 10px var(--fp-glow);animation:fsia-promo-badge 2.4s ease-in-out infinite}',
    '.fsia-promo-badge::after{content:"";position:absolute;top:0;bottom:0;left:-70%;width:45%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.75),transparent);transform:skewX(-20deg);animation:fsia-promo-shine 3s ease-in-out 1s infinite}',
    '.fsia-promo-eye .fsia-promo-badge{margin-left:4px}',
    /* WhatsApp button */
    '.fsia-promo-wa{flex:none;display:inline-flex;align-items:center;justify-content:center;gap:7px;height:44px;padding:0 16px;border-radius:999px;font-size:13px;font-weight:800;color:#FFFFFF !important;text-decoration:none !important;white-space:nowrap;background:#25D366;box-shadow:0 6px 16px rgba(37,211,102,.32);transition:transform .15s}',
    '.fsia-promo-wa:hover{transform:translateY(-1px)}',
    '.fsia-promo-wa svg{width:20px;height:20px}',
    '.fsia-promo .fsia-promo-wa{width:44px;padding:0}',
    '.fsia-promo .fsia-promo-wa span{display:none}',
    '.fsia-m .fsia-promo-wa{width:42px;height:42px}',
    /* Several events taking turns: dots */
    '.fsia-promo-dots{position:absolute;left:50%;bottom:5px;transform:translateX(-50%);display:flex;gap:5px}',
    '.fsia-promo-dots button{width:6px;height:6px;border-radius:999px;background:var(--fp-x2);cursor:pointer;transition:width .25s,background .25s}',
    '.fsia-promo-dots button.on{width:16px;background:var(--fp-gold)}',
    '.fsia-m .fsia-promo-dots{bottom:4px}',
    '.fsia-m .fsia-promo-eye{flex-wrap:wrap;white-space:normal;row-gap:4px}',
    '.fsia-m .fsia-promo-eye .fsia-promo-badge{margin-left:0}',
    /* A bar with a guest has less room: countdown tiles become one line below 1440px */
    '@media (max-width:1440px){.fsia-promo.fsia-has-g:not(.fsia-pv) .fsia-promo-cd{display:none}.fsia-promo.fsia-has-g:not(.fsia-pv) .fsia-promo-cdi{display:inline}.fsia-promo.fsia-has-g:not(.fsia-pv) .fsia-promo-cd-on .fsia-promo-lbl{display:none}}',
    '.fsia-promo.fsia-swap{animation:fsia-promo-fade .45s ease both}',
    /* Popup: big banner in the middle of the screen */
    '.fsia-pop{position:fixed;inset:0;z-index:2147483001;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(5,8,16,.72);-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);animation:fsia-promo-fade .3s ease both}',
    '.fsia-pop-card{position:relative;width:100%;max-width:560px;max-height:calc(100vh - 32px);overflow:auto;border-radius:22px;background:var(--fp-bg);color:var(--fp-text);border:1px solid var(--fp-line);box-shadow:0 30px 80px rgba(0,0,0,.5);font:500 14px/1.35 "Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;animation:fsia-promo-pop .4s cubic-bezier(.2,.8,.2,1) both}',
    '.fsia-pop-img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;background:var(--fp-pic)}',
    '.fsia-pop-body{padding:20px 22px 22px}',
    '.fsia-pop-card .fsia-promo-eye{white-space:normal;flex-wrap:wrap}',
    '.fsia-pop-title{margin-top:6px;font:700 26px/1.15 Cinzel,"Playfair Display",Georgia,serif;color:var(--fp-title)}',
    '.fsia-pop-sum{margin-top:8px;font-size:14px;color:var(--fp-muted)}',
    '.fsia-pop-card .fsia-promo-meta{flex-wrap:wrap;white-space:normal;margin-top:10px}',
    '.fsia-pop-card .fsia-promo-cd{margin-top:14px}',
    '.fsia-pop-card .fsia-promo-cd div{flex:1}',
    '.fsia-pop-card .fsia-promo-guests{margin-top:14px;padding:12px 0 0;border-left:0;border-top:1px solid var(--fp-x2);flex-wrap:wrap}',
    '.fsia-pop-btns{display:flex;gap:10px;margin-top:18px}',
    '.fsia-pop-btns .fsia-promo-cta{flex:1;justify-content:center;padding:14px 22px;font-size:15px}',
    '.fsia-pop-later{display:block;margin:12px auto 0;background:none;color:var(--fp-soft);font-size:12.5px;font-weight:600;cursor:pointer;text-decoration:underline;text-align:center}',
    '.fsia-pop-card .fsia-promo-x{top:12px;right:12px;transform:none;background:rgba(0,0,0,.45);color:#FFFFFF}',
    '.fsia-pop.fsia-pv{position:relative;inset:auto;z-index:auto;padding:16px;border-radius:16px;animation:none}',
    '.fsia-pop.fsia-pv .fsia-pop-card{animation:none;max-height:none}',
    '.fsia-pop-m .fsia-pop-img{aspect-ratio:4/3}',
    '.fsia-pop-m .fsia-pop-title{font-size:21px}',
    '.fsia-pop-m .fsia-pop-body{padding:16px}',
    '@keyframes fsia-promo-fade{from{opacity:0}to{opacity:1}}',
    '@keyframes fsia-promo-badge{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}',
    '@media (prefers-reduced-motion:reduce){.fsia-promo,.fsia-promo-dot,.fsia-promo-pill,.fsia-promo-cta::after,.fsia-promo-badge,.fsia-promo-badge::after,.fsia-pop,.fsia-pop-card{animation:none}}',
  ].join('\n');

  var ICON = {
    date: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    place: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
    wa: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm5.8 14.03c-.24.68-1.42 1.3-1.95 1.35-.5.05-1.13.07-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.8-4.17-4.94-4.36-.14-.2-1.18-1.57-1.18-3 0-1.42.75-2.12 1.01-2.41.27-.29.58-.36.78-.36h.56c.18 0 .42-.07.66.5.24.58.83 2 .9 2.15.07.14.12.31.02.5-.1.2-.14.31-.29.48-.14.17-.3.38-.43.5-.14.15-.3.3-.13.6.17.28.75 1.24 1.6 2 1.1.98 2.03 1.28 2.32 1.43.29.14.46.12.63-.07.17-.2.72-.84.92-1.13.19-.29.38-.24.65-.14.26.1 1.68.79 1.97.94.29.14.48.21.55.33.07.12.07.7-.17 1.38z"/></svg>',
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
  };

  function day(s) { var d = String(s || '').slice(0, 10).split('-'); return d.length === 3 ? (+d[2]) + ' ' + MONTHS[+d[1] - 1] + ' ' + d[0] : ''; }
  // "26 Oct 2026", "26–28 Oct 2026", "30 Oct – 2 Nov 2026"
  function when(p) {
    var a = day(p.starts_at), b = day(p.ends_at);
    if (!b || a === b) return a;
    var x = a.split(' '), y = b.split(' ');
    if (x[2] === y[2] && x[1] === y[1]) return x[0] + '\u2013' + b;
    if (x[2] === y[2]) return x[0] + ' ' + x[1] + ' \u2013 ' + b;
    return a + ' \u2013 ' + b;
  }
  /** "2027-01-08 18:00:00" (India time) -> ms since epoch */
  function ist(s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(String(s || ''));
    return m ? Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]) - IST : null;
  }
  /** Line above the title: "Happening now", "Upcoming event · In 12 days", ... */
  function status(p, now) {
    var st = ist(p.starts_at), en = ist(p.ends_at);
    if (st === null) return { text: 'Upcoming event' };
    if (now >= st && (en === null || now <= en)) return { text: 'Happening now', live: true };
    var days = Math.floor((st + IST) / DAY) - Math.floor((now + IST) / DAY);
    var soon = days <= 0 ? 'Today' : days === 1 ? 'Tomorrow' : days <= 60 ? 'In ' + days + ' days' : '';
    return { text: soon ? 'Upcoming event \u00b7 ' + soon : 'Upcoming event' };
  }

  function pad2(n) { return (n < 10 ? '0' : '') + n; }
  /** Time until ms: {d, h, m, s} or null once it has passed. */
  function left(ms, now) {
    var t = Math.floor((ms - now) / 1000);
    if (t <= 0) return null;
    return { d: Math.floor(t / 86400), h: Math.floor(t / 3600) % 24, m: Math.floor(t / 60) % 60, s: t % 60 };
  }
  /** "17 days \u00b7 03:30:29" */
  function short(l) { return (l.d ? l.d + (l.d === 1 ? ' day' : ' days') + ' \u00b7 ' : '') + pad2(l.h) + ':' + pad2(l.m) + ':' + pad2(l.s); }

  var THEMES = ['gold', 'royal', 'rose', 'emerald', 'ivory'];
  function themeCls(p) { return p && p.theme && p.theme !== 'gold' && THEMES.indexOf(p.theme) > 0 ? ' fsia-t-' + p.theme : ''; }
  /** wa.me link with the message filled in, or '' when no number is set. */
  function waHref(p) {
    var n = String(p.whatsapp || '').replace(/\D+/g, '');
    if (n.length === 10) n = '91' + n;
    if (n.length < 11) return '';
    var msg = p.whatsapp_text || ('Hi FSIA, I want to know more about ' + (p.title || 'this event') + '.');
    return 'https://wa.me/' + n + '?text=' + encodeURIComponent(msg);
  }

  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text) e.textContent = text; return e; }
  function icon(name) { var s = el('span'); s.innerHTML = ICON[name]; return s.firstChild; }
  function addCss(doc) {
    if (doc.getElementById('fsia-promo-css')) return;
    var st = doc.createElement('style');
    st.id = 'fsia-promo-css';
    st.textContent = CSS;
    (doc.head || doc.documentElement).appendChild(st);
  }

  /** "Register now" button; opts.onCta(p) runs on click. */
  function ctaBtn(p, opts, extra) {
    if (!p.cta_url) return null;
    var a = el('a', 'fsia-promo-cta' + (extra || ''));
    a.href = p.cta_url;
    a.appendChild(el('span', '', p.cta_label || 'Register now'));
    a.appendChild(icon('arrow'));
    a.addEventListener('click', function (e) { if (opts.preview) e.preventDefault(); else if (opts.onCta) opts.onCta(p); });
    return a;
  }
  /** Green WhatsApp button; opts.onWa(p) runs on click. */
  function waBtn(p, opts) {
    var href = waHref(p);
    if (!href) return null;
    var a = el('a', 'fsia-promo-wa');
    a.href = href;
    a.target = '_blank';
    a.rel = 'noopener';
    a.setAttribute('aria-label', 'Ask on WhatsApp');
    a.appendChild(icon('wa'));
    a.appendChild(el('span', '', 'WhatsApp'));
    a.addEventListener('click', function (e) { if (opts.preview) e.preventDefault(); else if (opts.onWa) opts.onWa(p); });
    return a;
  }

  /**
   * Popup: a big card in the middle of the screen (banner, title, countdown, buttons).
   * opts: mobile, preview (in the page flow), asset, now, onClose, onCta, onWa.
   */
  function buildPopup(p, opts) {
    opts = opts || {};
    var asset = opts.asset || function (u) { return u; };
    var mobile = !!opts.mobile;
    addCss(document);
    var wrap = el('div', 'fsia-pop' + (mobile ? ' fsia-pop-m' : '') + (opts.preview ? ' fsia-pv' : ''));
    wrap.setAttribute('role', 'dialog');
    wrap.setAttribute('aria-modal', opts.preview ? 'false' : 'true');
    wrap.setAttribute('aria-label', 'Event: ' + (p.title || ''));
    var card = el('div', 'fsia-pop-card' + themeCls(p));
    wrap.appendChild(card);

    var banner = mobile ? (p.banner_mobile || p.banner_desktop) : (p.banner_desktop || p.banner_mobile);
    if (banner) {
      var img = el('img', 'fsia-pop-img');
      img.alt = '';
      img.src = asset(banner);
      img.onerror = function () { img.remove(); };
      card.appendChild(img);
    }
    var body = el('div', 'fsia-pop-body');
    card.appendChild(body);
    var now0 = opts.now || Date.now();
    var s = status(p, now0);
    var eye = el('div', 'fsia-promo-eye');
    if (s.live) eye.appendChild(el('span', 'fsia-promo-dot'));
    eye.appendChild(el('span', 'fsia-promo-lbl', s.text));
    if (p.badge) eye.appendChild(el('span', 'fsia-promo-badge', p.badge));
    body.appendChild(eye);
    body.appendChild(el('div', 'fsia-pop-title', p.title || 'Event title'));
    if (p.summary) body.appendChild(el('p', 'fsia-pop-sum', p.summary));
    var meta = el('div', 'fsia-promo-meta');
    var date = when(p);
    var place = [p.venue, p.city].filter(Boolean).join(', ');
    if (date) { var d = el('span'); d.appendChild(icon('date')); d.appendChild(el('span', '', date)); meta.appendChild(d); }
    if (place) { var pl = el('span'); pl.appendChild(icon('place')); pl.appendChild(el('span', '', place)); meta.appendChild(pl); }
    if (meta.firstChild) body.appendChild(meta);

    var startMs = ist(p.starts_at);
    var count = !s.live && startMs !== null && left(startMs, now0);
    if (count) {
      var tiles = el('div', 'fsia-promo-cd');
      tiles.setAttribute('aria-hidden', 'true');
      var cells = {};
      [['d', 'Days'], ['h', 'Hrs'], ['m', 'Min'], ['s', 'Sec']].forEach(function (u) {
        var c = el('div');
        cells[u[0]] = el('b', '', '00');
        c.appendChild(cells[u[0]]);
        c.appendChild(el('i', '', u[1]));
        tiles.appendChild(c);
      });
      var paint = function (l) { cells.d.textContent = String(l.d); cells.h.textContent = pad2(l.h); cells.m.textContent = pad2(l.m); cells.s.textContent = pad2(l.s); };
      paint(count);
      body.appendChild(tiles);
      var timer = setInterval(function () {
        if (!wrap.isConnected && wrap.dataset.started) { clearInterval(timer); return; }
        if (wrap.isConnected) wrap.dataset.started = '1';
        var l = left(startMs, Date.now());
        if (l) paint(l); else { clearInterval(timer); tiles.remove(); }
      }, 1000);
    }

    var guests = (p.guests || []).filter(function (g) { return g && g.name; });
    if (guests.length) {
      var gbox = el('div', 'fsia-promo-guests');
      guests.forEach(function (g) {
        var row = el('div', 'fsia-promo-g');
        var face;
        if (g.photo) {
          face = el('img', 'fsia-promo-face');
          face.alt = '';
          face.src = asset(g.photo);
          face.onerror = function () { face.replaceWith(el('div', 'fsia-promo-face fsia-promo-face-x', '\u2605')); };
        } else face = el('div', 'fsia-promo-face fsia-promo-face-x', '\u2605');
        row.appendChild(face);
        var who = el('div', '');
        who.style.minWidth = '0';
        if (g.role) who.appendChild(el('div', 'fsia-promo-role', g.role));
        who.appendChild(el('div', 'fsia-promo-name', g.name));
        row.appendChild(who);
        gbox.appendChild(row);
      });
      body.appendChild(gbox);
    }

    var btns = el('div', 'fsia-pop-btns');
    var cta = ctaBtn(p, opts, '');
    var wa = waBtn(p, opts);
    if (cta) btns.appendChild(cta);
    if (wa) btns.appendChild(wa);
    if (btns.firstChild) body.appendChild(btns);
    var later = el('button', 'fsia-pop-later', 'Maybe later');
    later.type = 'button';
    body.appendChild(later);

    var close = el('button', 'fsia-promo-x');
    close.type = 'button';
    close.setAttribute('aria-label', 'Close');
    close.appendChild(icon('close'));
    card.appendChild(close);
    var done = function () { if (!opts.preview && opts.onClose) opts.onClose(wrap); };
    close.onclick = done;
    later.onclick = done;
    if (!opts.preview) {
      wrap.addEventListener('click', function (e) { if (e.target === wrap) done(); });
      wrap.addEventListener('keydown', function (e) { if (e.key === 'Escape') done(); });
    }
    return wrap;
  }

  /**
   * The promotion element. opts: mobile (phone layout), preview (in the page flow,
   * close does nothing), onCta / onWa (button clicks), narrow (no summary or countdown tiles; previews under 1300px wide), asset (turns stored image paths into URLs), now (ms), onClose.
   */
  function build(p, opts) {
    opts = opts || {};
    var asset = opts.asset || function (u) { return u; };
    var mobile = !!opts.mobile;
    addCss(document);

    var bar = el('aside', 'fsia-promo' + themeCls(p) + (mobile ? ' fsia-m' : '') + (opts.preview ? ' fsia-pv' : '') + (opts.narrow ? ' fsia-narrow' : ''));
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Event: ' + (p.title || ''));
    var inner = el('div', 'fsia-promo-in');
    bar.appendChild(inner);

    var guests = (p.guests || []).filter(function (g) { return g && g.name; });
    var banner = mobile ? (p.banner_mobile || p.banner_desktop) : (p.banner_desktop || p.banner_mobile);
    var picSrc = banner || (mobile && guests[0] && guests[0].photo) || '';

    // Text block
    var txt = el('div', 'fsia-promo-txt');
    var now0 = opts.now || Date.now();
    var s = status(p, now0);
    var startMs = ist(p.starts_at);
    var count = !s.live && startMs !== null && left(startMs, now0);
    var eye = el('div', 'fsia-promo-eye' + (count ? ' fsia-promo-cd-on' : ''));
    if (s.live) eye.appendChild(el('span', 'fsia-promo-dot'));
    eye.appendChild(el('span', 'fsia-promo-lbl', count ? 'Upcoming event' : s.text));
    var cdi = count ? el('span', 'fsia-promo-cdi') : null;
    if (cdi) eye.appendChild(cdi);
    if (p.badge) eye.appendChild(el('span', 'fsia-promo-badge', p.badge));
    txt.appendChild(eye);
    txt.appendChild(el('div', 'fsia-promo-title', p.title || 'Event title'));
    var meta = el('div', 'fsia-promo-meta');
    var date = when(p);
    var place = mobile ? (p.city || p.venue) : [p.venue, p.city].filter(Boolean).join(', ');
    if (date) { var d = el('span'); d.appendChild(icon('date')); d.appendChild(el('span', '', date)); meta.appendChild(d); }
    if (place) { var pl = el('span'); pl.appendChild(icon('place')); pl.appendChild(el('span', '', place)); meta.appendChild(pl); }
    if (p.summary && !mobile) meta.appendChild(el('span', 'fsia-promo-sum', p.summary));
    if (meta.firstChild) txt.appendChild(meta);

    function pic(src, cls) {
      var img = el('img', cls);
      img.alt = '';
      img.loading = 'lazy';
      img.src = asset(src);
      img.onerror = function () { img.remove(); };
      return img;
    }

    // Guests (celebrity chief guest)
    var gbox = null;
    var shown = mobile ? guests.slice(0, 1) : guests;
    if (shown.length) {
      bar.className += ' fsia-has-g';
      gbox = el('div', 'fsia-promo-guests');
      shown.forEach(function (g) {
        var row = el('div', 'fsia-promo-g');
        if (g.photo) {
          var face = pic(g.photo, 'fsia-promo-face');
          face.onerror = function () { var x = el('div', 'fsia-promo-face fsia-promo-face-x', '\u2605'); face.replaceWith(x); };
          row.appendChild(face);
        } else {
          row.appendChild(el('div', 'fsia-promo-face fsia-promo-face-x', '\u2605'));
        }
        var who = el('div', '');
        who.style.minWidth = '0';
        if (g.role) who.appendChild(el('div', 'fsia-promo-role', g.role));
        who.appendChild(el('div', 'fsia-promo-name', g.name));
        row.appendChild(who);
        gbox.appendChild(row);
      });
    }

    // Countdown to the start, ticking every second while the bar is on the page.
    var tiles = null;
    if (count) {
      bar.className += ' fsia-has-cd';
      tiles = el('div', 'fsia-promo-cd');
      tiles.setAttribute('aria-hidden', 'true');
      var cells = {};
      [['d', 'Days'], ['h', 'Hrs'], ['m', 'Min'], ['s', 'Sec']].forEach(function (u) {
        var c = el('div');
        cells[u[0]] = el('b', '', '00');
        c.appendChild(cells[u[0]]);
        c.appendChild(el('i', '', u[1]));
        tiles.appendChild(c);
      });
      var paint = function (l) {
        cells.d.textContent = String(l.d); cells.h.textContent = pad2(l.h); cells.m.textContent = pad2(l.m); cells.s.textContent = pad2(l.s);
        cdi.textContent = 'Starts in ' + short(l);
      };
      paint(count);
      var timer = setInterval(function () {
        if (!bar.isConnected && bar.dataset.started) { clearInterval(timer); return; }
        if (bar.isConnected) bar.dataset.started = '1';
        var l = left(startMs, Date.now());
        if (l) { paint(l); return; }
        clearInterval(timer);   // the event has started
        tiles.remove();
        eye.className = 'fsia-promo-eye';
        eye.textContent = '';
        eye.appendChild(el('span', 'fsia-promo-dot'));
        eye.appendChild(el('span', 'fsia-promo-lbl', 'Happening now'));
        if (p.badge) eye.appendChild(el('span', 'fsia-promo-badge', p.badge));
      }, 1000);
    }

    var cta = ctaBtn(p, opts, mobile && !gbox ? ' fsia-wide' : '');
    var wa = waBtn(p, opts);

    if (mobile) {
      var top = el('div', 'fsia-promo-top');
      if (picSrc) top.appendChild(pic(picSrc, 'fsia-promo-pic'));
      top.appendChild(txt);
      inner.appendChild(top);
      if (gbox || cta || wa) {
        var bot = el('div', 'fsia-promo-bot');
        if (gbox) bot.appendChild(gbox);
        if (cta) bot.appendChild(cta);
        if (wa) bot.appendChild(wa);
        inner.appendChild(bot);
      }
    } else {
      if (banner) inner.appendChild(pic(banner, 'fsia-promo-pic'));
      inner.appendChild(txt);
      if (tiles) inner.appendChild(tiles);
      if (gbox) inner.appendChild(gbox);
      if (cta) inner.appendChild(cta);
      if (wa) inner.appendChild(wa);
    }

    var close = el('button', 'fsia-promo-x');
    close.type = 'button';
    close.setAttribute('aria-label', 'Close');
    close.appendChild(icon('close'));
    close.onclick = function () { if (opts.onClose) opts.onClose(bar); };
    bar.appendChild(close);
    return bar;
  }

  window.FSIAPromo = { build: build, buildPopup: buildPopup, themes: THEMES };
  if (window.__fsiaPromoPreview || window.__fsiaPromo) return;
  window.__fsiaPromo = true;

  // --- On the website --------------------------------------------------------
  var script = document.currentScript;
  var api = (script && script.getAttribute('data-api')) || '/cms/backend/api/promotions.php';
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }

  /** Minimised promotion: a pill in the corner that opens the bar again. */
  function pill(p, onOpen, onHide) {
    var guests = (p.guests || []).filter(function (g) { return g && g.name; });
    var b = el('div', 'fsia-promo-pill' + themeCls(p));
    b.id = 'fsia-promo';
    b.setAttribute('role', 'button');
    b.setAttribute('tabindex', '0');
    b.setAttribute('aria-label', 'Show event: ' + (p.title || ''));
    var face = (guests[0] && guests[0].photo) || p.banner_mobile || p.banner_desktop;
    if (face) {
      var img = el('img');
      img.alt = '';
      img.src = face;
      img.onerror = function () { img.replaceWith(el('span', 'fsia-promo-pill-star', '\u2605')); };
      b.appendChild(img);
    } else {
      b.appendChild(el('span', 'fsia-promo-pill-star', '\u2605'));
    }
    var t = el('div', 'fsia-promo-pill-t');
    t.appendChild(el('b', '', p.title || ''));
    var sub = el('span', '', when(p));
    t.appendChild(sub);
    b.appendChild(t);
    var go = el('span', 'fsia-promo-pill-go');
    go.appendChild(icon('arrow'));
    b.appendChild(go);
    var x = el('button', 'fsia-promo-pill-x');
    x.type = 'button';
    x.setAttribute('aria-label', 'Hide');
    x.appendChild(icon('close'));
    x.onclick = function (e) { e.stopPropagation(); onHide(b); };
    b.appendChild(x);
    var st = ist(p.starts_at);
    var tick = function () {
      var l = st !== null && left(st, Date.now());
      sub.textContent = l ? 'Starts in ' + short(l) : when(p);
    };
    tick();
    var timer = setInterval(function () { if (!b.isConnected) { clearInterval(timer); return; } tick(); }, 1000);
    b.onclick = function () { onOpen(b); };
    b.onkeydown = function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onOpen(b); } };
    return b;
  }

  /* --- Views and clicks (counted by the CMS: Events & Promotions shows them) --- */
  function today() { return new Date(Date.now() + IST).toISOString().slice(0, 10); }
  function track(kind, p) {
    var url = api + '?track=' + kind + '&id=' + encodeURIComponent(p.id);
    try {
      if (navigator.sendBeacon && navigator.sendBeacon(url, '')) return;
    } catch (e) { /* fall through */ }
    try { fetch(url, { method: 'POST', keepalive: true, credentials: 'omit' }); } catch (e) { /* ignore */ }
  }
  /** One view per visitor per event per day. */
  function seen(p) {
    var k = 'fsia-promo-seen-' + p.id;
    if (store(k) === today()) return;
    store(k, today());
    track('view', p);
  }
  var clicks = { onCta: function (p) { track('click', p); }, onWa: function (p) { track('wa', p); } };

  var ROTATE_MS = 7000;

  /** list = the promotions for this page, best first. Several take turns every 7 seconds. */
  function show(list) {
    var key = list.map(function (p) { return p.id + ':' + p.version; }).join('|');
    var hideKey = 'fsia-promo-closed';   // hidden for good (until an event changes)
    var minKey = 'fsia-promo-min';       // shown as the small pill
    if (store(hideKey) === key) return;
    var mq = window.matchMedia ? window.matchMedia('(max-width: 640px)') : null;
    var pad = document.body.style.paddingBottom;
    var cur = null;
    var mode = '';      // 'bar' | 'pill' | ''
    var idx = 0;
    var paused = false;
    var rot = null;

    function put(node) { if (cur) cur.replaceWith(node); else document.body.appendChild(node); cur = node; }
    function hide(node) { store(hideKey, key); node.remove(); cur = null; mode = ''; stopRot(); document.body.style.paddingBottom = pad; }
    function stopRot() { if (rot) clearInterval(rot); rot = null; }
    function startRot() {
      stopRot();
      if (list.length < 2) return;
      rot = setInterval(function () {
        if (paused || document.hidden || !cur) return;
        go((idx + 1) % list.length);
      }, ROTATE_MS);
    }
    function go(i) {
      idx = i;
      if (mode === 'bar') big(false, true);
      else if (mode === 'pill') small(true);
    }
    function dots(node) {
      if (list.length < 2) return;
      var d = el('div', 'fsia-promo-dots');
      list.forEach(function (x, i) {
        var b = el('button', i === idx ? 'on' : '');
        b.type = 'button';
        b.setAttribute('aria-label', 'Show event ' + (i + 1) + ' of ' + list.length + ': ' + (x.title || ''));
        b.onclick = function (e) { e.stopPropagation(); go(i); startRot(); };
        d.appendChild(b);
      });
      node.appendChild(d);
    }
    function hover(node) {
      node.addEventListener('mouseenter', function () { paused = true; });
      node.addEventListener('mouseleave', function () { paused = false; });
      node.addEventListener('focusin', function () { paused = true; });
      node.addEventListener('focusout', function () { paused = false; });
    }
    function small(swap) {
      store(minKey, key);
      mode = 'pill';
      document.body.style.paddingBottom = pad;
      var node = pill(list[idx], function () { store(minKey, ''); big(true); }, hide);
      if (swap) node.style.animation = 'fsia-promo-fade .45s ease both';
      hover(node);
      put(node);
      seen(list[idx]);
      startRot();
    }
    function big(again, swap) {
      var p = list[idx];
      var o = { mobile: !!(mq && mq.matches), onClose: function () { small(); }, onCta: clicks.onCta, onWa: clicks.onWa };
      var next = build(p, o);
      next.id = 'fsia-promo';
      if (swap) next.className += ' fsia-swap';
      else if (mode === 'bar') next.style.animation = 'none';
      dots(next);
      hover(next);
      put(next);
      mode = 'bar';
      seen(p);
      // Keep the page's own footer reachable above the bar / card.
      document.body.style.paddingBottom = (next.offsetHeight + (mq && mq.matches ? 20 : 0)) + 'px';
      var cta = again && next.querySelector('.fsia-promo-cta');
      if (cta) cta.focus({ preventScroll: true });
      if (!swap) startRot();
    }
    /** The popup, at most once a day per visitor (for the first event that has it on). */
    function popup(p, then) {
      var k = 'fsia-promo-pop-' + p.id;
      var stamp = today() + '|' + p.version;
      if (store(k) === stamp) return false;
      store(k, stamp);
      var back = document.activeElement;
      var node = buildPopup(p, {
        mobile: !!(mq && mq.matches), onCta: clicks.onCta, onWa: clicks.onWa,
        onClose: function (w) { w.remove(); document.documentElement.style.overflow = ov; if (back && back.focus) back.focus({ preventScroll: true }); then(); },
      });
      var ov = document.documentElement.style.overflow;
      document.documentElement.style.overflow = 'hidden';
      document.body.appendChild(node);
      seen(p);
      var x = node.querySelector('.fsia-promo-x');
      if (x) x.focus({ preventScroll: true });
      return true;
    }
    // Let the page settle before the promotion slides in.
    setTimeout(function () {
      var first = function () { if (store(minKey) === key) small(); else big(false); };
      var pop = null;
      for (var i = 0; i < list.length; i++) if (list[i].popup) { pop = list[i]; idx = i; break; }
      if (!(pop && popup(pop, first))) { idx = 0; first(); }
    }, 1200);
    if (mq) {
      var redo = function () { if (mode === 'bar') big(false); };
      if (mq.addEventListener) mq.addEventListener('change', redo); else if (mq.addListener) mq.addListener(redo);
    }
  }

  function start() {
    if (document.getElementById('fsia-promo')) return;
    fetch(api + '?path=' + encodeURIComponent(location.pathname), { credentials: 'omit' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d) return;
        var list = d.promotions && d.promotions.length ? d.promotions : d.promotion ? [d.promotion] : [];
        if (list.length) show(list);
      })
      .catch(function () {});
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
