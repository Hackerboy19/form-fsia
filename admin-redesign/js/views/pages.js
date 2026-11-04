/* Forms & Pages: edit the `more_pages` row behind each public form (SEO, social share, fee box). */
(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon;

  var CHECKED = ['meta_title', 'descritpion', 'meta_keyword', 'og_title', 'og_description', 'og_image', 'app_fee'];
  var LABELS = { meta_title: 'Page title', descritpion: 'Meta description', meta_keyword: 'Keywords', og_title: 'Share title', og_description: 'Share description', og_image: 'Share image', app_fee: 'Fee amount' };
  var listFilter = { q: '', group: '', attention: false };
  var listScroll = 0;

  function filled(m) { return CHECKED.filter(function (k) { return String(m[k] || '').trim() !== ''; }).length; }
  function missing(m) { return CHECKED.filter(function (k) { return String(m[k] || '').trim() === ''; }).map(function (k) { return LABELS[k]; }); }
  function same(a, b) { return F.data.metaKeys.every(function (k) { return String(a[k] || '') === String(b[k] || ''); }); }

  F.views.pages = {
    title: 'Forms & Pages',
    perm: 'pages.view',
    render: function (ctx) {
      var canEdit = F.can('pages.edit');
      var id = ctx.parts[1] ? +ctx.parts[1] : null;
      var form = id ? F.data.formsById[id] : null;
      var saved = form ? F.store.page(id) : null;
      var draft = saved ? Object.assign({}, saved) : null;
      var root = h('div', { class: 'view-pages' });
      var listHost = h('ul', { class: 'plist', 'aria-label': 'Pages' });
      var editorHost = h('div', { class: 'editor' });
      var split = h('div', { class: 'split' + (form ? ' has-sel' : '') });
      var objUrl = null;

      function dirty() { return !!draft && !same(draft, saved); }
      function go(href) {
        if (dirty()) {
          F.confirm({ title: 'Discard unsaved changes?', body: 'You edited ' + form.title + ' but did not save. Leaving now will lose those changes.', confirmText: 'Discard', danger: true }).then(function (ok) { if (ok) location.hash = href; });
        } else location.hash = href;
      }

      /* ---------- list ---------- */
      function renderList() {
        var q = listFilter.q.trim().toLowerCase();
        var items = F.data.forms.filter(function (x) {
          if (listFilter.group && x.group !== listFilter.group) return false;
          if (q && (x.title + ' ' + x.slug + ' ' + x.id).toLowerCase().indexOf(q) < 0) return false;
          if (listFilter.attention && filled(F.store.page(x.id)) === CHECKED.length) return false;
          return true;
        });
        F.fill(listHost, items.length ? items.map(function (x) {
          var m = F.store.page(x.id), n = filled(m), ok = n === CHECKED.length;
          return h('li', null, h('a', { class: 'pitem' + (x.id === id ? ' is-on' : ''), href: '#/pages/' + x.id, 'aria-current': x.id === id ? 'page' : null, onclick: function (e) { e.preventDefault(); if (x.id !== id) go('#/pages/' + x.id); } },
            h('span', { class: 'pitem-main' }, h('strong', null, x.title), h('span', { class: 'muted small' }, '/' + x.slug + (x.idKnown ? ' · ID ' + x.id : ' · page ID not known yet'))),
            h('span', { class: 'badge ' + (ok ? 'ok' : 'warn'), title: ok ? 'All SEO fields filled' : 'Missing: ' + missing(m).join(', ') }, ok ? icon('check', 13) : icon('alert', 13), n + '/' + CHECKED.length)));
        }) : h('li', { class: 'empty small' }, h('span', { class: 'muted' }, 'No pages match.')));
        count.textContent = items.length + ' of ' + F.data.forms.length + ' pages';
      }
      var count = h('span', { class: 'muted small' });
      var attentionTotal = F.data.forms.filter(function (x) { return filled(F.store.page(x.id)) < CHECKED.length; }).length;
      var listPane = h('aside', { class: 'list-pane' },
        h('div', { class: 'list-tools' },
          h('div', { class: 'search' }, icon('search', 16), h('input', { class: 'input has-ico', type: 'search', placeholder: 'Find a page…', 'aria-label': 'Find a page', 'data-search': '', value: listFilter.q, oninput: F.debounce(function (e) { listFilter.q = e.target.value; renderList(); }, 120) })),
          h('div', { class: 'chips', role: 'group', 'aria-label': 'Filter pages' },
            [{ id: '', label: 'All' }].concat(F.GROUPS).map(function (gr) {
              return h('button', { type: 'button', class: 'chip' + (listFilter.group === gr.id ? ' is-on' : ''), 'aria-pressed': listFilter.group === gr.id ? 'true' : 'false', onclick: function (e) { listFilter.group = gr.id; F.$$('.chips .chip', listPane).forEach(function (c) { var on = c === e.currentTarget; c.classList.toggle('is-on', on); c.setAttribute('aria-pressed', on); }); renderList(); } }, gr.short || gr.label);
            })),
          h('label', { class: 'check' }, h('input', { type: 'checkbox', checked: listFilter.attention, onchange: function (e) { listFilter.attention = e.target.checked; renderList(); } }), 'Needs attention', h('span', { class: 'tab-n' }, String(attentionTotal))),
          count),
        listHost);

      /* ---------- editor ---------- */
      function field(key, label, o) {
        o = o || {};
        var el = o.area ? h('textarea', { class: 'textarea', id: 'f-' + key, rows: o.rows || 3, value: draft[key] }) : h('input', { class: 'input', id: 'f-' + key, type: 'text', value: draft[key], placeholder: o.placeholder || '' });
        var counter = o.max ? h('span', { class: 'counter' }) : null;
        function upd() {
          draft[key] = el.value;
          if (counter) { var n = el.value.length; counter.textContent = n + ' / ' + o.max; counter.className = 'counter' + (n > o.max ? ' over' : n === 0 ? '' : ' ok'); }
          refresh();
        }
        el.addEventListener('input', upd);
        if (counter) { var n0 = el.value.length; counter.textContent = n0 + ' / ' + o.max; counter.className = 'counter' + (n0 > o.max ? ' over' : n0 === 0 ? '' : ' ok'); }
        return h('div', { class: 'field' },
          h('div', { class: 'label-row' }, h('label', { class: 'label', for: 'f-' + key }, label), counter),
          el, o.hint ? h('p', { class: 'hint' }, o.hint) : null);
      }

      var saveBtn, discardBtn, dirtyTag, previewHost = h('div', { class: 'preview' });

      function refresh() {
        var d = dirty();
        if (saveBtn) { saveBtn.disabled = !d; discardBtn.disabled = !d; dirtyTag.hidden = !d; }
        renderPreview();
      }

      function renderPreview() {
        if (!previewHost || !draft) return;
        var t = (draft.meta_title || '').trim() || form.title + ' | Forever Star India';
        var d = (draft.descritpion || '').trim();
        var ogT = (draft.og_title || '').trim() || t, ogD = (draft.og_description || '').trim() || d;
        var imgSrc = objUrl;
        var note = F.sanitize(draft.journey_note || '');
        var fee = (draft.app_fee || '').trim();
        F.fill(previewHost, 
          h('div', { class: 'card pv-card' }, h('h3', { class: 'pv-h' }, 'Google result'),
            h('div', { class: 'serp' }, h('div', { class: 'serp-url' }, 'www.fsia.in › ' + form.slug),
              h('div', { class: 'serp-title' }, t.length > 60 ? t.slice(0, 58) + '…' : t),
              h('div', { class: 'serp-desc' }, d ? (d.length > 160 ? d.slice(0, 157) + '…' : d) : h('em', null, 'No description — Google will pick text from the page.')))),
          h('div', { class: 'card pv-card' }, h('h3', { class: 'pv-h' }, 'WhatsApp / Facebook share'),
            h('div', { class: 'sharecard' },
              h('div', { class: 'share-img' }, imgSrc ? h('img', { src: imgSrc, alt: '' }) : h('span', { class: 'muted' }, icon('image', 26), draft.og_image ? draft.og_image : 'No image set')),
              h('div', { class: 'share-txt' }, h('span', { class: 'muted small' }, 'fsia.in'), h('strong', null, ogT), h('span', { class: 'muted small' }, ogD ? (ogD.length > 110 ? ogD.slice(0, 107) + '…' : ogD) : '')))),
          h('div', { class: 'card pv-card' }, h('h3', { class: 'pv-h' }, 'Fee box on the form'),
            h('div', { class: 'feebox' },
              h('div', { class: 'feebox-head' }, h('span', { class: 'feebox-i' }, 'i'), h('strong', null, (draft.fee_heading || 'Important').toUpperCase())),
              fee ? h('p', null, h('span', { class: 'feebox-price' }, fee), ' ', h('b', null, '— ' + (draft.fee_title || ''))) : h('p', { class: 'muted' }, 'No fee amount set — the fee line is hidden.'),
              draft.fee_subtitle ? h('p', null, draft.fee_subtitle) : null,
              note.textContent.trim() ? note : null)));
      }

      function save() {
        if (!canEdit) return;
        F.store.savePage(id, draft);
        F.audit.add('page', 'Edited the ' + form.title + ' page');
        saved = F.store.page(id); draft = Object.assign({}, saved);
        F.toast(form.title + ' saved. Preview only: stored in this browser, not on fsia.in.');
        renderEditor(); renderList();
      }
      function copyFee() {
        var grp = F.GROUPS.filter(function (g) { return g.id === form.group; })[0];
        var targets = F.data.forms.filter(function (x) { return x.group === form.group && x.id !== id; });
        F.confirm({ title: 'Copy fee box to ' + targets.length + ' pages?', body: 'Fee amount, title, heading, subtitle and journey note from ' + form.title + ' will overwrite the same fields on every other ' + grp.label + ' page.', confirmText: 'Copy to all' }).then(function (ok) {
          if (!ok) return;
          targets.forEach(function (x) {
            var m = F.store.page(x.id);
            ['app_fee', 'fee_title', 'fee_heading', 'fee_subtitle', 'journey_note'].forEach(function (k) { m[k] = draft[k]; });
            F.store.savePage(x.id, m);
          });
          F.audit.add('page', 'Copied the fee box of ' + form.title + ' to ' + targets.length + ' pages');
          F.toast('Fee box copied to ' + targets.length + ' pages'); renderList();
        });
      }

      function renderEditor() {
        if (!form) {
          F.fill(editorHost, h('div', { class: 'empty big' }, icon('file', 34), h('strong', null, 'Choose a page to edit'),
            h('span', { class: 'muted' }, 'Pick any form on the left to change its title, description, share image and fee box.'),
            attentionTotal ? h('button', { type: 'button', class: 'btn btn-sm', onclick: function () { listFilter.attention = true; renderAll(); } }, attentionTotal + ' pages need attention') : null));
          return;
        }
        draft = draft || Object.assign({}, saved);
        saveBtn = h('button', { type: 'button', class: 'btn btn-primary', disabled: !dirty(), onclick: save }, icon('save', 16), 'Save changes');
        discardBtn = h('button', { type: 'button', class: 'btn', disabled: !dirty(), onclick: function () { draft = Object.assign({}, saved); objUrl = null; renderEditor(); } }, icon('undo', 16), 'Discard');
        dirtyTag = h('span', { class: 'badge warn', hidden: !dirty() }, 'Unsaved changes');
        var file = h('input', { type: 'file', accept: 'image/*', class: 'sr-only', id: 'f-file', onchange: function (e) {
          var fl = e.target.files[0]; if (!fl) return;
          if (objUrl) URL.revokeObjectURL(objUrl);
          objUrl = URL.createObjectURL(fl); draft.og_image = fl.name; document.getElementById('f-og_image').value = fl.name; refresh();
        } });
        F.fill(editorHost, 
          h('div', { class: 'ed-head' },
            h('a', { class: 'back', href: '#/pages', onclick: function (e) { e.preventDefault(); go('#/pages'); } }, icon('left', 16), 'All pages'),
            h('div', { class: 'ed-title' }, h('h2', null, form.title), h('a', { class: 'link small', href: form.live, target: '_blank', rel: 'noopener noreferrer' }, 'fsia.in/' + form.slug, icon('link', 13)), dirtyTag),
            canEdit ? h('div', { class: 'ed-actions' }, discardBtn, saveBtn) : h('span', { class: 'badge plain' }, icon('lock', 13), 'View only')),
          h('div', { class: 'ed-grid' },
            h('div', { class: 'ed-form' },
              h('section', { class: 'card' }, h('div', { class: 'card-head' }, h('h2', null, 'Search listing'), h('span', { class: 'muted small' }, 'How the page appears on Google')),
                h('div', { class: 'card-body stackf' },
                  field('meta_title', 'Page title', { max: 60, hint: 'Left blank, the form falls back to its built-in title.' }),
                  field('descritpion', 'Meta description', { area: true, rows: 4, max: 160 }),
                  field('meta_keyword', 'Keywords', { hint: 'Separate with commas.' }))),
              h('section', { class: 'card' }, h('div', { class: 'card-head' }, h('h2', null, 'Social sharing'), h('span', { class: 'muted small' }, 'WhatsApp, Facebook, X')),
                h('div', { class: 'card-body stackf' },
                  field('og_title', 'Share title', { max: 70, hint: 'Defaults to the page title when empty.' }),
                  field('og_description', 'Share description', { area: true, rows: 2, max: 200 }),
                  h('div', { class: 'field' }, h('label', { class: 'label', for: 'f-og_image' }, 'Share image'),
                    h('div', { class: 'file-row' }, h('input', { class: 'input', id: 'f-og_image', type: 'text', value: draft.og_image, placeholder: 'file-name.jpg', oninput: function (e) { draft.og_image = e.target.value; objUrl = null; refresh(); } }),
                      file, h('label', { class: 'btn', for: 'f-file' }, icon('image', 16), 'Choose image')),
                    h('p', { class: 'hint' }, 'File name from the /uploads folder. Best size 1200×630.')))),
              h('section', { class: 'card' }, h('div', { class: 'card-head' }, h('h2', null, 'Fee box'), h('span', { class: 'muted small' }, 'Shown at the top of the form')),
                h('div', { class: 'card-body stackf' },
                  h('div', { class: 'row2' }, field('app_fee', 'Fee amount', { placeholder: '₹ 1,999', hint: 'Leave empty to hide the fee line.' }), field('fee_title', 'Fee label', { placeholder: 'Audition fee' })),
                  field('fee_heading', 'Box heading', { placeholder: 'Important' }),
                  field('fee_subtitle', 'Subtitle', { area: true, rows: 2 }),
                  field('journey_note', 'Journey note', { area: true, rows: 3, hint: 'Basic HTML allowed: <p>, <b>, <br>, <a>.' }),
                  h('div', null, h('button', { type: 'button', class: 'btn btn-sm', onclick: copyFee }, icon('copy', 15), 'Copy fee box to all ' + F.GROUPS.filter(function (g) { return g.id === form.group; })[0].label + ' pages'))))),
            previewHost));
        renderPreview();
        if (!canEdit) F.$$('.ed-form input, .ed-form textarea, .ed-form button, .ed-form label.btn', editorHost).forEach(function (el) { if (el.tagName === 'LABEL') el.hidden = true; else el.disabled = true; });
      }

      function renderAll() { renderList(); renderEditor(); }
      split.append(listPane, editorHost);
      root.append(h('div', { class: 'page-head' }, h('div', null, h('h1', { class: 'page-title' }, 'Forms & Pages'), h('p', { class: 'page-sub' }, 'Edit what visitors and search engines see for each registration form.'))), split);
      renderAll();
      var onKey = function (e) { if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's' && form) { e.preventDefault(); if (dirty()) save(); } };
      document.addEventListener('keydown', onKey);
      var onUnload = function (e) { if (dirty()) { e.preventDefault(); e.returnValue = ''; } };
      window.addEventListener('beforeunload', onUnload);
      return {
        el: root,
        afterMount: function () { listHost.scrollTop = listScroll; var on = listHost.querySelector('.is-on'); if (on && listScroll === 0) on.scrollIntoView({ block: 'nearest' }); },
        destroy: function () { listScroll = listHost.scrollTop; document.removeEventListener('keydown', onKey); window.removeEventListener('beforeunload', onUnload); if (objUrl) URL.revokeObjectURL(objUrl); }
      };
    }
  };
})();
