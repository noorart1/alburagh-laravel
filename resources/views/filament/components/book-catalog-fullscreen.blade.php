@php
$isBookCatalog = request()->is('admin/book-catalogs*');
$isBookCatalogEdit = request()->is('admin/book-catalogs/*/edit');
$isBookCatalogIndex = request()->is('admin/book-catalogs');
@endphp
@if ($isBookCatalog)
<style>
/* Full width فقط داخل فضای واقعی پنل.
مهم: وقتی Sidebar باز است، عرض .fi-main-ctn را 100% نمی‌کنیم
چون Filament خودش فضای Sidebar را محاسبه می‌کند. */
.fi-main {
    max-width: none !important;
    padding-inline: 12px !important;
    padding-block: 8px !important; /* Added for compact spacing */
}

.fi-page,
.fi-page-content {
    width: 100% !important;
    max-width: none !important;
    padding-block: 0 !important;
    gap: 8px !important;
}

.fi-main-ctn {
    max-width: none !important;
}

.fi-ta-table {
    width: max-content !important;
    min-width: 100% !important;
    table-layout: auto !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    margin-block: 0 !important;
}

.fi-ta-table th,
.fi-ta-table td {
    white-space: nowrap !important;
    vertical-align: middle !important;
}

.fi-ta-table th:nth-child(2),
.fi-ta-table td:nth-child(2) {
    width: 64px !important;
    min-width: 64px !important;
    max-width: 64px !important;
}

/* =========================================================
   COMPACT PAGE SPACING
   Reduces default Filament paddings, margins, and gaps 
   for a tighter, more compact layout.
========================================================= */
.fi-section,
.fi-ta-content-ctn {
    margin-block: 0 !important;
    padding-block: 4px !important;
}

.fi-header {
    padding-block: 4px !important;
    margin-block: 0 !important;
}

/* Floating pinned block */
#bc-pin-wrap {
    position: fixed;
    z-index: 99999;
    display: none;
    border-bottom: 1px solid rgba(255,255,255,.10);
    box-shadow: 0 4px 14px rgba(0,0,0,.28);
    overflow: hidden;
}

#bc-pin-wrap.is-visible {
    display: block;
}

#bc-top-scroll {
    height: 17px;
    overflow-x: auto;
    overflow-y: hidden;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

#bc-top-scroll-inner {
    height: 1px;
}

#bc-top-scroll::-webkit-scrollbar {
    height: 12px;
}

#bc-top-scroll::-webkit-scrollbar-thumb {
    background: rgba(160,160,160,.75);
    border-radius: 10px;
}

#bc-top-scroll::-webkit-scrollbar-track {
    background: rgba(255,255,255,.05);
}

/* Bottom pinned scrollbar — always visible while the table overflows,
   regardless of vertical scroll position (independent of #bc-pin-wrap,
   which only shows once the header has scrolled out of view). */
#bc-bottom-scroll {
    position: fixed;
    bottom: 0;
    z-index: 99999;
    display: none;
    height: 17px;
    overflow-x: auto;
    overflow-y: hidden;
    border-top: 1px solid rgba(255,255,255,.10);
    box-shadow: 0 -4px 14px rgba(0,0,0,.22);
}

#bc-bottom-scroll.is-visible {
    display: block;
}

#bc-bottom-scroll-inner {
    height: 1px;
}

#bc-bottom-scroll::-webkit-scrollbar {
    height: 12px;
}

#bc-bottom-scroll::-webkit-scrollbar-thumb {
    background: rgba(160,160,160,.75);
    border-radius: 10px;
}

#bc-bottom-scroll::-webkit-scrollbar-track {
    background: rgba(255,255,255,.05);
}

#bc-head-viewport {
    width: 100%;
    overflow: hidden;
}

#bc-head-table {
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
    margin: 0;
}

#bc-head-table th {
    background: rgb(24 24 27) !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
    box-sizing: border-box !important;
}

/* =========================================================
THEME SAFE — بدون دست‌زدن به منطق Scroll
========================================================= */

/* Light */
html:not(.dark) #bc-pin-wrap,
html:not(.dark) #bc-top-scroll,
html:not(.dark) #bc-bottom-scroll,
html:not(.dark) #bc-head-table,
html:not(.dark) #bc-head-table th {
    background: #e7e9ec !important;
    color: #374151 !important;
    border-color: #d1d5db !important;
}

html:not(.dark) #book-catalog-sidebar-toggle,
html:not(.dark) .book-catalog-scroll-arrow,
html:not(.dark) #book-catalog-popover {
    background: #f4f5f6 !important;
    color: #374151 !important;
    border-color: #d1d5db !important;
}

html:not(.dark) #book-catalog-popover-close {
    color: #374151 !important;
}

/* Dark */
html.dark #bc-pin-wrap,
html.dark #bc-top-scroll,
html.dark #bc-bottom-scroll,
html.dark #bc-head-table,
html.dark #bc-head-table th {
    background: #34373c !important;
    color: #f0f1f2 !important;
    border-color: #484c52 !important;
}

html.dark #book-catalog-sidebar-toggle,
html.dark .book-catalog-scroll-arrow,
html.dark #book-catalog-popover {
    background: #2b2e33 !important;
    color: #e5e7eb !important;
    border-color: #484c52 !important;
}

html.dark #book-catalog-popover-close {
    color: #e5e7eb !important;
}

/* Eye comfort rows */
html:not(.dark) .fi-ta-table thead th {
    background: #e7e9ec !important;
    color: #374151 !important;
}

html:not(.dark) .fi-ta-table tbody tr:nth-child(odd) td {
    background: #fbfbfc !important;
}

html:not(.dark) .fi-ta-table tbody tr:nth-child(even) td {
    background: #f3f4f6 !important; 
}

html:not(.dark) .fi-ta-table tbody td {
    color: #3f4650 !important;
    border-bottom: 1px solid #e2e4e8 !important;
    border-inline-end: 1px solid #eceef1 !important;
}

html:not(.dark) .fi-ta-table tbody tr:hover td {
    background: #e9edf2 !important;
}

html.dark .fi-ta-table thead th {
    background: #34373c !important;
    color: #f0f1f2 !important;
}

html.dark .fi-ta-table tbody tr:nth-child(odd) td {
    background: #24262a !important;
}

html.dark .fi-ta-table tbody tr:nth-child(even) td {
    background: #2b2e33 !important;
}

html.dark .fi-ta-table tbody td {
    color: #e2e4e7 !important;
    border-bottom: 1px solid #393c42 !important;
    border-inline-end: 1px solid #34373c !important;
}

html.dark .fi-ta-table tbody tr:hover td {
    background: #353941 !important;
}

/* =========================================================
STABLE COLUMN LAYOUT
عرض ستون‌ها از خود Filament/PHP می‌آید، نه nth-child.
بنابراین Checkbox / Actions ترتیب ستون‌ها را خراب نمی‌کنند.
========================================================= */
.fi-ta-table {
    table-layout: auto !important;
}

.fi-ta-table th,
.fi-ta-table td {
    box-sizing: border-box !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
    padding-inline: 7px !important;
    padding-block: 4px !important;
}

/* Filament wraps cell content in its own span/div with its own vertical
   padding — override that too, otherwise padding-block above alone won't
   shrink the visible row height */
.fi-ta-table td > *,
.fi-ta-table td .fi-ta-text,
.fi-ta-table td [class*="fi-ta-"] {
    padding-block: 0 !important;
}

.fi-ta-table thead th {
    padding-block: 6px !important;
}

.fi-ta-table td {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

.fi-ta-table th {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

/* فقط محتوای متنی محدود شود؛ Flex داخلی Filament خراب نشود */
.fi-ta-table td .fi-ta-text,
.fi-ta-table td [class*="fi-ta-text"] {
    min-width: 0 !important;
    max-width: 100% !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

/* Header شناور نیز هیچ‌وقت وارد ستون بعدی نشود */
#bc-head-table {
    table-layout: fixed !important;
}

#bc-head-table th {
    box-sizing: border-box !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

/* Sidebar — sits inline in the topbar, to the left of the user menu,
   instead of floating mid-page. */
#book-catalog-sidebar-toggle {
    position: fixed;
    top: 12px;
    right: 64px;
    z-index: 100050;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(0,0,0,.12);
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
    font-size: 16px;
    line-height: 1;
}

#book-catalog-sidebar-toggle:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.18);
}

body.book-catalog-sidebar-collapsed .fi-sidebar {
    display: none !important;
}

/* فقط وقتی Sidebar را خودمان Collapse کرده‌ایم،
Main تمام عرض مرورگر را می‌گیرد. */
body.book-catalog-sidebar-collapsed .fi-main-ctn {
    margin-inline-start: 0 !important;
    width: 100% !important;
    max-width: none !important;
}

body.book-catalog-sidebar-collapsed .fi-main {
    width: 100% !important;
    max-width: none !important;
}

/* Side arrows */
.book-catalog-scroll-arrow {
    position: fixed;
    top: 52%;
    z-index: 9998;
    width: 42px;
    height: 58px;
    border: 1px solid rgba(0,0,0,.12);
    border-radius: 12px;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(0,0,0,.20);
    font-size: 27px;
    line-height: 1;
    user-select: none;
}

#book-catalog-scroll-left { left: 12px; }
#book-catalog-scroll-right { right: 12px; }

.book-catalog-scroll-arrow.is-visible {
    display: flex;
}

/* Click popover */
.book-catalog-click-text {
    cursor: pointer !important;
}

#book-catalog-popover {
    position: fixed;
    z-index: 200000;
    display: none;
    width: min(420px, calc(100vw - 28px));
    max-height: 260px;
    overflow: auto;
    padding: 14px 16px 16px;
    border: 1px solid rgba(128,128,128,.28);
    border-radius: 12px;
    box-shadow: 0 14px 36px rgba(0,0,0,.35);
}

#book-catalog-popover.is-open { display: block; }

#book-catalog-popover-header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

#book-catalog-popover-title { font-weight: 700; }

#book-catalog-popover-close {
    border: 0;
    background: transparent;
    cursor: pointer;
    font-size: 20px;
}

#book-catalog-popover-body {
    white-space: pre-wrap;
    line-height: 1.8;
}

/* =========================================================
   COMPACT EDIT PAGE
   فقط محیط ویرایش Book Catalog
   ========================================================= */
body:has(form#form) .fi-page {
    gap: 4px !important;
}

body:has(form#form) .fi-page-header,
body:has(form#form) .fi-header {
    margin: 0 !important;
    padding-block: 4px !important;
}

body:has(form#form) .fi-page-content {
    padding-top: 0 !important;
    margin-top: 0 !important;
    gap: 5px !important;
}

body:has(form#form) form#form {
    gap: 6px !important;
}

body:has(form#form) .fi-section {
    margin: 0 !important;
}

body:has(form#form) .fi-section-header {
    padding: 6px 10px !important;
    min-height: 0 !important;
}

body:has(form#form) .fi-section-content-ctn {
    padding: 0 !important;
}

body:has(form#form) .fi-section-content {
    padding: 8px 10px !important;
}

body:has(form#form) .fi-sc,
body:has(form#form) .fi-grid {
    gap: 7px !important;
    row-gap: 7px !important;
}

body:has(form#form) .fi-fo-field-wrp {
    gap: 2px !important;
    margin: 0 !important;
}

body:has(form#form) .fi-fo-field-wrp-label {
    margin-bottom: 1px !important;
}

body:has(form#form) .fi-input-wrp {
    min-height: 34px !important;
}

body:has(form#form) .fi-input-wrp input {
    min-height: 32px !important;
    padding-block: 4px !important;
}

body:has(form#form) textarea {
    padding-block: 5px !important;
}

/* Header actions: Save + Close + Delete one compact row */
body:has(form#form) .fi-header-actions,
body:has(form#form) .fi-page-header-actions {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    gap: 6px !important;
    margin: 0 !important;
    padding: 0 !important;
}

body:has(form#form) .fi-header-actions .fi-btn,
body:has(form#form) .fi-page-header-actions .fi-btn {
    min-height: 32px !important;
    padding-block: 5px !important;
    padding-inline: 10px !important;
}


@if ($isBookCatalogIndex)
/* =========================================================
   BOOK CATALOG INDEX — ZERO EMPTY TOP SPACE
   فقط صفحه فهرست کتاب‌ها
   ========================================================= */

/* فضای اصلی بالا کاملاً جمع شود */
.fi-main {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}

.fi-page {
    margin: 0 !important;
    padding: 0 !important;
    gap: 4px !important;
    row-gap: 4px !important;
}

/* Header صفحه در یک ردیف جمع‌وجور */
.fi-header,
.fi-page-header {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 8px !important;
    margin: 0 !important;
    padding: 4px 2px !important;
    min-height: 0 !important;
}

/* عنوان و container آن بدون فضای عمودی */
.fi-header-heading-ctn,
.fi-page-header-main-ctn,
.fi-page-header-main {
    margin: 0 !important;
    padding: 0 !important;
    gap: 2px !important;
    min-height: 0 !important;
}

/* Breadcrumb در فهرست لازم نیست و فضای عمودی می‌گیرد */
.fi-breadcrumbs {
    display: none !important;
}

/* عنوان الكتب جمع و جور */
.fi-header-heading,
.fi-page-heading {
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1.05 !important;
}

/* دکمه إضافة كتاب در همان ردیف */
.fi-header-actions,
.fi-page-header-actions,
.fi-header-actions-ctn {
    margin: 0 !important;
    padding: 0 !important;
    gap: 6px !important;
    align-self: center !important;
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    width: auto !important;
}

/* دکمه‌های Header کنار هم، نه زیر هم */
.fi-header-actions > *,
.fi-page-header-actions > *,
.fi-header-actions-ctn > * {
    width: auto !important;
    flex: 0 0 auto !important;
    margin: 0 !important;
}

/* محتوا بلافاصله زیر Header */
.fi-page-content {
    margin: 0 !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    gap: 0 !important;
    row-gap: 0 !important;
}

.fi-page-content > *,
.fi-page-content > div,
.fi-page > div {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}

/* کارت جدول بدون فاصله بالایی */
.fi-ta-ctn,
.fi-ta-main,
.fi-ta-content-ctn,
.fi-section {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}

/* Toolbar جدول جمع‌تر */
.fi-ta-header,
.fi-ta-header-ctn {
    margin: 0 !important;
    padding-top: 4px !important;
    padding-bottom: 4px !important;
    min-height: 0 !important;
}

/* اگر Filament روی header wrapper height/min-height گذاشته باشد */
.fi-header > *,
.fi-page-header > * {
    min-height: 0 !important;
    margin-block: 0 !important;
}

/* فاصله مصنوعی احتمالی بین Topbar و صفحه */
.fi-topbar + .fi-main-ctn,
.fi-topbar + * {
    margin-top: 0 !important;
}

/* خود نوار بالای پنل هم جمع‌تر شود تا جدول تقریباً بلافاصله بعد از
   نوار آدرس مرورگر شروع شود. */
.fi-topbar {
    min-height: 0 !important;
    padding-block: 4px !important;
}

.fi-topbar .fi-topbar-item-btn,
.fi-topbar .fi-icon-btn {
    padding: 4px !important;
}

.fi-topbar .fi-logo {
    height: 1.5rem !important;
}
@endif

</style>

<div id="bc-pin-wrap">
    <div id="bc-top-scroll">
        <div id="bc-top-scroll-inner"></div>
    </div>
    <div id="bc-head-viewport">
        <table id="bc-head-table"></table>
    </div>
</div>

<div id="bc-bottom-scroll">
    <div id="bc-bottom-scroll-inner"></div>
</div>

<div id="book-catalog-popover">
    <div id="book-catalog-popover-header">
        <div id="book-catalog-popover-title"></div>
        <button type="button" id="book-catalog-popover-close">×</button>
    </div>
    <div id="book-catalog-popover-body"></div>
</div>

<button type="button" id="book-catalog-sidebar-toggle">❯</button>
<button type="button" id="book-catalog-scroll-left" class="book-catalog-scroll-arrow">‹</button>
<button type="button" id="book-catalog-scroll-right" class="book-catalog-scroll-arrow">›</button>

<script>
(() => {
    const STORAGE_KEY = 'bookCatalogSidebarCollapsed';

    const pinWrap = document.getElementById('bc-pin-wrap');
    const topScroll = document.getElementById('bc-top-scroll');
    const topScrollInner = document.getElementById('bc-top-scroll-inner');
    const headTable = document.getElementById('bc-head-table');

    const bottomScroll = document.getElementById('bc-bottom-scroll');
    const bottomScrollInner = document.getElementById('bc-bottom-scroll-inner');

    const sidebarButton = document.getElementById('book-catalog-sidebar-toggle');
    const leftButton = document.getElementById('book-catalog-scroll-left');
    const rightButton = document.getElementById('book-catalog-scroll-right');

    const popover = document.getElementById('book-catalog-popover');
    const popoverTitle = document.getElementById('book-catalog-popover-title');
    const popoverBody = document.getElementById('book-catalog-popover-body');
    const popoverClose = document.getElementById('book-catalog-popover-close');

    if (!pinWrap || pinWrap.dataset.ready === '1') return;
    pinWrap.dataset.ready = '1';

    let table = null;
    let thead = null;
    let scrollHost = null;
    let syncing = false;

    function findScrollHost(el) {
        if (!el) return null;

        let node = el.parentElement;

        while (node && node !== document.body) {
            const style = getComputedStyle(node);
            const x = style.overflowX;

            if (
                node.scrollWidth > node.clientWidth + 2 &&
                (x === 'auto' || x === 'scroll' || x === 'overlay' || x === 'hidden')
            ) {
                return node;
            }

            node = node.parentElement;
        }

        // Filament 4 actual container on this installation:
        return el.closest('.fi-ta-content-ctn') || el.parentElement;
    }

    function discover() {
        table = document.querySelector('table.fi-ta-table');

        if (!table) {
            pinWrap.classList.remove('is-visible');
            bottomScroll.classList.remove('is-visible');
            return false;
        }

        thead = table.querySelector('thead');
        scrollHost = findScrollHost(table);

        if (!thead || !scrollHost) {
            pinWrap.classList.remove('is-visible');
            bottomScroll.classList.remove('is-visible');
            return false;
        }

        return true;
    }

    function getTopOffset() {
        const topbar = document.querySelector('.fi-topbar');

        if (!topbar) return 0;

        const rect = topbar.getBoundingClientRect();

        if (rect.bottom > 0 && rect.top <= 0) {
            return Math.round(rect.bottom);
        }

        return 0;
    }

    function rebuildHeader() {
        if (!discover()) return;

        const visibleViewport = table.closest('.fi-ta-content-ctn') || scrollHost;
        const hostRect = visibleViewport.getBoundingClientRect();

        pinWrap.style.left = `${Math.max(0, hostRect.left)}px`;
        pinWrap.style.width = `${Math.max(0, hostRect.width)}px`;
        pinWrap.style.top = `${getTopOffset()}px`;

        bottomScroll.style.left = `${Math.max(0, hostRect.left)}px`;
        bottomScroll.style.width = `${Math.max(0, hostRect.width)}px`;

        const clone = thead.cloneNode(true);

        // عرض واقعی هر TH را از جدول اصلی می‌گیریم.
        // این روش به تعداد/وجود Checkbox و Action وابسته نیست.
        const originalHeaders = [...thead.querySelectorAll('th')];
        const widths = originalHeaders.map((th) =>
            Math.max(1, Math.round(th.getBoundingClientRect().width))
        );

        const totalWidth = widths.reduce((sum, width) => sum + width, 0);

        headTable.innerHTML = '';

        const colgroup = document.createElement('colgroup');

        widths.forEach((width) => {
            const col = document.createElement('col');
            col.style.width = `${width}px`;
            col.style.minWidth = `${width}px`;
            col.style.maxWidth = `${width}px`;
            colgroup.appendChild(col);
        });

        headTable.appendChild(colgroup);
        headTable.appendChild(clone);

        headTable.style.width = `${totalWidth}px`;
        headTable.style.minWidth = `${totalWidth}px`;
        headTable.style.maxWidth = `${totalWidth}px`;
        topScrollInner.style.width = `${Math.max(table.scrollWidth, totalWidth)}px`;
        bottomScrollInner.style.width = `${Math.max(table.scrollWidth, totalWidth)}px`;

        const clonedHeaders = [...clone.querySelectorAll('th')];

        clonedHeaders.forEach((th, i) => {
            const width = widths[i] ?? 80;

            th.style.width = `${width}px`;
            th.style.minWidth = `${width}px`;
            th.style.maxWidth = `${width}px`;
            th.style.overflow = 'hidden';
            th.style.textOverflow = 'ellipsis';
            th.style.whiteSpace = 'nowrap';
        });

        bindScrollHost();
        syncFromTable();
        updateVisibility();
        updateArrows();
    }

    function bindScrollHost() {
        if (!scrollHost || scrollHost.dataset.bcBound === '1') return;

        scrollHost.dataset.bcBound = '1';

        scrollHost.addEventListener('scroll', () => {
            if (syncing) return;

            syncing = true;
            syncFromTable();
            requestAnimationFrame(() => syncing = false);
        }, { passive: true });
    }

    function syncFromTable() {
        if (!scrollHost) return;

        const x = scrollHost.scrollLeft;

        topScroll.scrollLeft = x;
        bottomScroll.scrollLeft = x;
        headTable.style.transform = `translateX(${-x}px)`;
    }

    topScroll.addEventListener('scroll', () => {
        if (!scrollHost || syncing) return;

        syncing = true;

        scrollHost.scrollLeft = topScroll.scrollLeft;
        bottomScroll.scrollLeft = topScroll.scrollLeft;
        headTable.style.transform = `translateX(${-topScroll.scrollLeft}px)`;

        requestAnimationFrame(() => syncing = false);
    }, { passive: true });

    bottomScroll.addEventListener('scroll', () => {
        if (!scrollHost || syncing) return;

        syncing = true;

        scrollHost.scrollLeft = bottomScroll.scrollLeft;
        topScroll.scrollLeft = bottomScroll.scrollLeft;
        headTable.style.transform = `translateX(${-bottomScroll.scrollLeft}px)`;

        requestAnimationFrame(() => syncing = false);
    }, { passive: true });

    function updateVisibility() {
        if (!table || !thead || !scrollHost) return;

        const offset = getTopOffset();
        const theadRect = thead.getBoundingClientRect();
        const tableRect = table.getBoundingClientRect();

        const shouldShow =
            theadRect.bottom <= offset &&
            tableRect.bottom > offset + 60;

        pinWrap.style.top = `${offset}px`;
        pinWrap.classList.toggle('is-visible', shouldShow);
    }

    function updateArrows() {
        if (!scrollHost) return;

        const overflow = scrollHost.scrollWidth > scrollHost.clientWidth + 4;

        leftButton?.classList.toggle('is-visible', overflow);
        rightButton?.classList.toggle('is-visible', overflow);

        // Always pinned at the bottom of the viewport whenever the table
        // overflows horizontally, independent of vertical scroll position.
        bottomScroll.classList.toggle('is-visible', overflow);
    }

    /* وقتی عرض Sidebar/Main تغییر می‌کند Header فوراً دوباره هم‌تراز می‌شود. */
    const layoutResizeObserver = new ResizeObserver(() => {
        requestAnimationFrame(() => {
            if (discover()) {
                rebuildHeader();
            }
        });
    });

    const observeLayoutTargets = () => {
        const mainCtn = document.querySelector('.fi-main-ctn');
        const sidebar = document.querySelector('.fi-sidebar');
        const tableViewport = document.querySelector('.fi-ta-content-ctn');

        [mainCtn, sidebar, tableViewport].forEach((el) => {
            if (el && el.dataset.bcResizeObserved !== '1') {
                el.dataset.bcResizeObserved = '1';
                layoutResizeObserver.observe(el);
            }
        });
    };

    observeLayoutTargets();

    document.addEventListener('scroll', updateVisibility, true);

    window.addEventListener('resize', () => {
        rebuildHeader();
    });

    const observer = new MutationObserver(() => {
        const oldTable = table;

        if (discover()) {
            if (table !== oldTable) {
                rebuildHeader();
            } else {
                updateVisibility();
            }
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    const layoutMutationObserver = new MutationObserver(() => {
        observeLayoutTargets();
    });

    layoutMutationObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });

    /* Sidebar */
    const isCollapsed = () =>
        document.body.classList.contains('book-catalog-sidebar-collapsed');

    const syncSidebarButton = () => {
        const collapsed = isCollapsed();

        sidebarButton.textContent = collapsed ? '❮' : '❯';
        sidebarButton.title = collapsed
            ? (document.documentElement.lang === 'ar' ? 'إظهار القائمة الجانبية' : 'Show sidebar')
            : (document.documentElement.lang === 'ar' ? 'تكبير العرض (بدون ملء الشاشة)' : 'Widen view (not fullscreen)');
    };

    const setSidebarCollapsed = (collapsed, persist = true) => {
        document.body.classList.toggle('book-catalog-sidebar-collapsed', collapsed);

        if (persist) {
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        }

        syncSidebarButton();

        setTimeout(rebuildHeader, 250);
    };

    sidebarButton.addEventListener('click', () => {
        setSidebarCollapsed(!isCollapsed());
    });

    setSidebarCollapsed(localStorage.getItem(STORAGE_KEY) === '1', false);

    /* Side arrows */
    function scrollTable(direction) {
        if (!scrollHost && !discover()) return;

        const amount = Math.max(420, Math.round(scrollHost.clientWidth * 0.72));

        scrollHost.scrollBy({
            left: direction * amount,
            behavior: 'smooth',
        });
    }

    leftButton?.addEventListener('click', () => scrollTable(-1));
    rightButton?.addEventListener('click', () => scrollTable(1));

    /* Popover */
    const hidePopover = () => popover?.classList.remove('is-open');

    function showPopover(target) {
        popoverTitle.textContent = target.getAttribute('data-heading') || '';
        popoverBody.textContent = target.getAttribute('data-full-text') || '—';

        popover.classList.add('is-open');

        const r = target.getBoundingClientRect();
        let top = r.bottom + 8;
        let left = Math.min(
            Math.max(14, r.left),
            window.innerWidth - popover.offsetWidth - 14
        );

        if (top + popover.offsetHeight > window.innerHeight - 14) {
            top = Math.max(14, r.top - popover.offsetHeight - 8);
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    }

    document.addEventListener('click', (e) => {
        const target = e.target.closest('.book-catalog-click-text');

        if (target) {
            e.preventDefault();
            e.stopPropagation();
            showPopover(target);
            return;
        }

        if (popover?.classList.contains('is-open') && !popover.contains(e.target)) {
            hidePopover();
        }
    });

    popoverClose?.addEventListener('click', hidePopover);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') hidePopover();
    });

    rebuildHeader();
})();
</script>
@endif