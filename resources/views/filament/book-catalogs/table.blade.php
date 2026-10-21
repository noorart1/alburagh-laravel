{{-- Plain JS table for ListBookCatalogs: rows come from fetchBooks() as JSON and
     are drawn with one innerHTML, so paging/sorting never morphs a big DOM. --}}
<div wire:ignore id="bk" dir="rtl">
    <div class="bk-bar">
        <input type="search" id="bk-search" placeholder="بحث…" autocomplete="off">
        <details class="bk-menu" data-menu="category"><summary>الصنف</summary><div></div></details>
        <details class="bk-menu" data-menu="publisher"><summary>الناشر</summary><div></div></details>
        <details class="bk-menu" data-menu="columns"><summary>الأعمدة</summary><div></div></details>
        <select id="bk-per-page" title="عدد الصفوف">
            <option>10</option><option>25</option><option>50</option><option>100</option>
        </select>
        <button type="button" id="bk-delete" hidden>حذف المحدد (<span></span>)</button>
        <span id="bk-total"></span>
    </div>

    <div class="bk-scroll">
        <table id="bk-table">
            <thead></thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="bk-pager">
        <button type="button" data-go="-1">‹ السابق</button>
        <span id="bk-page"></span>
        <button type="button" data-go="1">التالي ›</button>
    </div>
</div>

<style>
#bk { --bk-line: #e2e4e8; --bk-head: #e7e9ec; --bk-odd: #fbfbfc; --bk-even: #f3f4f6; --bk-text: #3f4650; --bk-input: #fff; font-size: .8125rem; color: var(--bk-text); }
html.dark #bk { --bk-line: #393c42; --bk-head: #34373c; --bk-odd: #24262a; --bk-even: #2b2e33; --bk-text: #e2e4e7; --bk-input: #18181b; }
#bk button, #bk select, #bk input[type=search] { padding: 4px 10px; border: 1px solid var(--bk-line); border-radius: .375rem; background: var(--bk-input); color: inherit; }
.bk-bar { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 6px; }
#bk-search { width: 260px; }
#bk-delete { border-color: #dc2626 !important; color: #dc2626 !important; }
#bk-total { margin-inline-start: auto; opacity: .75; }
.bk-menu { position: relative; }
.bk-menu summary { list-style: none; cursor: pointer; padding: 4px 10px; border: 1px solid var(--bk-line); border-radius: .375rem; background: var(--bk-input); }
.bk-menu summary::after { content: ' ▾'; }
.bk-menu.is-active summary { border-color: #f59e0b; }
.bk-menu > div { position: absolute; z-index: 30; top: calc(100% + 4px); inset-inline-start: 0; min-width: 200px; max-height: 60vh; overflow: auto; padding: 8px; border: 1px solid var(--bk-line); border-radius: .5rem; background: var(--bk-input); box-shadow: 0 8px 24px rgb(0 0 0 / .18); display: grid; gap: 4px; }
.bk-menu[data-menu=columns] > div { grid-template-columns: repeat(3, max-content); column-gap: 16px; }
.bk-menu label { display: flex; gap: 6px; align-items: center; white-space: nowrap; cursor: pointer; }
.bk-scroll { overflow: auto; max-height: calc(100vh - 210px); border: 1px solid var(--bk-line); border-radius: .5rem; }
#bk-table { border-collapse: separate; border-spacing: 0; width: max-content; min-width: 100%; transition: opacity .1s; }
#bk-table.is-loading { opacity: .5; }
#bk-table th, #bk-table td { padding: 4px 6px; border-bottom: 1px solid var(--bk-line); border-inline-end: 1px solid var(--bk-line); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: start; vertical-align: middle; }
#bk-table thead th { position: sticky; top: 0; z-index: 2; background: var(--bk-head); font-weight: 700; }
#bk-table th[data-sort] { cursor: pointer; user-select: none; }
#bk-table th.is-sorted::after { content: ' ▲'; font-size: .7em; }
#bk-table th.is-sorted.is-desc::after { content: ' ▼'; }
#bk-table tbody tr:nth-child(odd) td { background: var(--bk-odd); }
#bk-table tbody tr:nth-child(even) td { background: var(--bk-even); }
#bk-table td.n { text-align: center; font-variant-numeric: tabular-nums; }
#bk-table td.e { cursor: text; }
#bk-table td.is-editing { overflow: visible; white-space: normal; }
#bk-table td img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; display: block; margin: auto; }
#bk-table td.a a { color: #d97706; margin-inline-end: 8px; }
.bk-ed { min-width: 180px; }
.bk-ed input, .bk-ed select { width: 100%; padding: 2px 6px; border: 1px solid #f59e0b; border-radius: .375rem; background: var(--bk-input); color: inherit; }
.bk-ed.is-invalid input, .bk-ed.is-invalid select { border-color: #dc2626; box-shadow: 0 0 0 2px rgb(220 38 38 / .25); }
.bk-ed div { display: flex; gap: 4px; justify-content: flex-end; margin-top: 4px; }
.bk-ed button { padding: 1px 8px !important; font-size: .75rem; }
.bk-pager { display: flex; gap: 10px; align-items: center; justify-content: center; margin-top: 8px; }
.bk-pager button:disabled { opacity: .4; }
</style>

<script>
(() => {
    const root = document.getElementById('bk');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = '1';

    const COLS = @js($columns);
    const EDIT_URL = @js($editUrl);
    const state = @js($prefs);
    let rows = [];
    let selected = new Set();
    let lastPage = 1;

    const $ = (s) => root.querySelector(s);
    const table = $('#bk-table');
    const wire = () => Livewire.find(root.closest('[wire\\:id]').getAttribute('wire:id'));
    const esc = (v) => String(v ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
    const shown = () => Object.keys(COLS).filter((f) => !state.hidden.includes(f));
    const label = (f, v) => COLS[f].options ? (COLS[f].options[v] ?? v ?? '') : (v ?? '');
    const notify = (msg) => window.FilamentNotification && new FilamentNotification().title(msg).danger().send();

    // ---- toolbar ----
    const checkboxes = (menu, items, isChecked) => {
        root.querySelector(`[data-menu=${menu}] > div`).innerHTML = Object.entries(items)
            .map(([k, v]) => `<label><input type="checkbox" value="${esc(k)}"${isChecked(k) ? ' checked' : ''}> ${esc(v)}</label>`).join('');
    };
    const markMenus = () => ['category', 'publisher'].forEach((m) => root.querySelector(`[data-menu=${m}]`).classList.toggle('is-active', state[m].length > 0));
    checkboxes('category', COLS.category.options, (k) => state.category.includes(k));
    checkboxes('publisher', COLS.publisher.options, (k) => state.publisher.includes(k));
    checkboxes('columns', Object.fromEntries(Object.entries(COLS).map(([f, c]) => [f, c.label])), (k) => !state.hidden.includes(k));
    markMenus();

    root.querySelectorAll('.bk-menu').forEach((menu) => menu.addEventListener('change', () => {
        const values = [...menu.querySelectorAll('input:checked')].map((i) => i.value);
        const name = menu.dataset.menu;
        if (name === 'columns') {
            state.hidden = Object.keys(COLS).filter((f) => !values.includes(f));
            draw();
            load(); // also stores the choice
        } else {
            state[name] = values;
            state.page = 1;
            markMenus();
            load();
        }
    }));
    document.addEventListener('click', (e) => root.querySelectorAll('.bk-menu[open]').forEach((m) => m.contains(e.target) || m.removeAttribute('open')));

    const search = $('#bk-search');
    search.value = state.search;
    let searchTimer;
    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { state.search = search.value; state.page = 1; load(); }, 350);
    });

    const perPage = $('#bk-per-page');
    perPage.value = state.perPage;
    perPage.addEventListener('change', () => { state.perPage = +perPage.value; state.page = 1; load(); });

    root.querySelectorAll('[data-go]').forEach((b) => b.addEventListener('click', () => {
        state.page = Math.min(lastPage, Math.max(1, state.page + +b.dataset.go));
        load();
    }));

    $('#bk-delete').addEventListener('click', async () => {
        if (!confirm(`حذف ${selected.size} كتاب؟`)) return;
        await wire().deleteBooks([...selected]);
        selected.clear();
        load();
    });

    // ---- table ----
    const cell = (r, f) => {
        const c = COLS[f];
        if (c.type === 'image') return `<td>${r[f] ? `<img loading="lazy" src="${esc(r[f])}" alt="">` : ''}</td>`;
        const full = String(label(f, r[f]));
        const max = { series_name: 20, subject: 20 }[f] ?? 30; // max characters per column; full value stays in the tooltip
        const text = full.length > max ? full.slice(0, max) + '…' : full;
        return `<td class="e${c.type === 'number' ? ' n' : ''}" data-f="${f}" title="${esc(full)}">${esc(text)}</td>`;
    };

    function draw() {
        const fields = shown();
        table.querySelector('thead').innerHTML = '<tr><th><input type="checkbox" id="bk-all"></th>'
            + fields.map((f) => `<th${COLS[f].type === 'image' ? '' : ` data-sort="${f}"`} class="${state.sort === f ? 'is-sorted' + (state.dir === 'desc' ? ' is-desc' : '') : ''}" title="${esc(COLS[f].label)}">${esc(COLS[f].label)}</th>`).join('')
            + '<th></th></tr>';
        table.querySelector('tbody').innerHTML = rows.map((r) => `<tr data-id="${r.id}"><td><input type="checkbox"${selected.has(r.id) ? ' checked' : ''}></td>`
            + fields.map((f) => cell(r, f)).join('')
            + `<td class="a"><a href="${EDIT_URL.replace('__ID__', r.id)}">تعديل</a></td></tr>`).join('');
        $('#bk-all').checked = rows.length > 0 && rows.every((r) => selected.has(r.id));
        syncSelection();
    }

    const syncSelection = () => {
        $('#bk-delete').hidden = selected.size === 0;
        $('#bk-delete span').textContent = selected.size;
    };

    let loadSeq = 0;
    async function load() {
        const seq = ++loadSeq;
        table.classList.add('is-loading');
        const data = await wire().fetchBooks(state);
        if (seq !== loadSeq) return; // a newer request (e.g. fast typing) wins
        table.classList.remove('is-loading');
        show(data);
    }

    function show(data) {
        rows = data.rows;
        lastPage = data.lastPage;
        state.page = data.page;
        $('#bk-total').textContent = `${data.total} كتاب`;
        $('#bk-page').textContent = `${data.page} / ${data.lastPage}`;
        root.querySelector('[data-go="-1"]').disabled = data.page <= 1;
        root.querySelector('[data-go="1"]').disabled = data.page >= data.lastPage;
        draw();
    }

    table.querySelector('thead').addEventListener('click', (e) => {
        if (e.target.id === 'bk-all') {
            rows.forEach((r) => e.target.checked ? selected.add(r.id) : selected.delete(r.id));
            draw();
            return;
        }
        const f = e.target.closest('th[data-sort]')?.dataset.sort;
        if (!f) return;
        state.dir = state.sort === f && state.dir === 'asc' ? 'desc' : 'asc';
        state.sort = f;
        load();
    });

    // ---- click-to-edit: input + حفظ / الغاء ----
    table.querySelector('tbody').addEventListener('click', (e) => {
        const tr = e.target.closest('tr');
        if (!tr) return;
        const id = +tr.dataset.id;

        if (e.target.matches('input[type=checkbox]')) {
            e.target.checked ? selected.add(id) : selected.delete(id);
            syncSelection();
            return;
        }

        const td = e.target.closest('td.e');
        if (!td || td.classList.contains('is-editing')) return;
        openEditor(td, rows.find((r) => r.id === id), td.dataset.f);
    });

    function openEditor(td, row, f) {
        const c = COLS[f];
        const old = row[f] ?? '';
        td.classList.add('is-editing');
        td.innerHTML = `<div class="bk-ed">${c.options
            ? `<select>${f === 'publisher' ? '<option value="">—</option>' : ''}${Object.entries(c.options).map(([k, v]) => `<option value="${esc(k)}">${esc(v)}</option>`).join('')}</select>`
            : `<input type="${c.type === 'number' ? 'number' : 'text'}" step="any">`}<div><button type="button">حفظ</button><button type="button">الغاء</button></div></div>`;
        const box = td.firstChild;
        const input = box.querySelector('input, select');
        const [saveBtn, cancelBtn] = box.querySelectorAll('button');
        input.value = old;
        input.focus();
        input.select?.();

        const close = () => {
            td.classList.remove('is-editing');
            td.outerHTML = cell(row, f);
        };
        let busy = false;
        const save = async () => {
            if (busy) return;
            if (String(input.value) === String(old)) return close();
            busy = true;
            input.disabled = true;
            const result = await wire().saveCell(row.id, f, input.value);
            busy = false;
            input.disabled = false;
            if (result.error) {
                box.classList.add('is-invalid');
                notify(result.error);
                input.focus();
                return;
            }
            row[f] = result.value;
            close();
        };
        saveBtn.addEventListener('click', save);
        cancelBtn.addEventListener('click', close);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); save(); }
            if (e.key === 'Escape') { e.stopPropagation(); close(); }
        });
    }

    // Header actions (import, create…) re-render the page; refresh rows after them.
    document.addEventListener('livewire:init', () => Livewire.hook('commit', ({ component, commit, succeed }) => {
        if (component.id !== root.closest('[wire\\:id]').getAttribute('wire:id')) return;
        if ((commit.calls || []).some((c) => c.method === 'callMountedAction')) succeed(() => setTimeout(load));
    }));

    show(@js($initial));
})();
</script>

