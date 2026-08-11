<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Monitoring - {{ config('app.name', 'Kresek.in') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; background: #f6f8fb; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        .owner-shell { min-height: 100vh; display: flex; }
        .owner-main { min-width: 0; flex: 1; }
        .owner-content { display: grid; gap: 22px; padding: 26px 32px 42px; }
        .owner-toolbar, .owner-card { border: 1px solid #e0e6ef; border-radius: 16px; background: #fff; box-shadow: 0 10px 28px rgba(23,32,51,.05); }
        .owner-toolbar { display: flex; flex-wrap: wrap; align-items: end; gap: 14px; padding: 18px; }
        .owner-field { min-width: 170px; display: grid; gap: 7px; }
        .owner-field--search { flex: 1; min-width: 220px; }
        .owner-field label { color: #59657a; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .owner-field select, .owner-field input { width: 100%; min-height: 42px; border: 1px solid #d5dde9; border-radius: 10px; background: #fff; padding: 0 12px; color: #172033; font: inherit; }
        .owner-live { margin-left: auto; min-width: 190px; display: grid; gap: 4px; text-align: right; }
        .owner-live strong { color: #05845f; }
        .owner-live[data-state="error"] strong { color: #c52525; }
        .owner-live[data-state="loading"] strong { color: #0b6aa7; }
        .owner-live small { color: #68758a; }
        .owner-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
        .owner-metric { padding: 20px; }
        .owner-metric span { color: #667286; font-size: 14px; font-weight: 700; }
        .owner-metric strong { display: block; margin-top: 10px; font-size: clamp(25px, 3vw, 34px); }
        .owner-statuses { display: grid; grid-template-columns: repeat(7, minmax(120px, 1fr)); gap: 10px; overflow-x: auto; padding: 16px; }
        .owner-status { border-radius: 12px; background: #f3f7fa; padding: 14px; }
        .owner-status span { display: block; min-height: 34px; color: #607086; font-size: 12px; font-weight: 800; }
        .owner-status strong { font-size: 24px; }
        .owner-section-head { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 18px 20px; border-bottom: 1px solid #e7ebf1; }
        .owner-section-head h2 { margin: 0; font-size: 19px; }
        .owner-table-wrap { overflow-x: auto; }
        .owner-table { width: 100%; border-collapse: collapse; min-width: 820px; }
        .owner-table th, .owner-table td { padding: 14px 18px; border-bottom: 1px solid #edf0f4; text-align: left; vertical-align: top; }
        .owner-table th { color: #667286; background: #fafbfd; font-size: 12px; text-transform: uppercase; }
        .owner-table td { font-size: 14px; }
        .owner-table .money { color: #06845f; font-weight: 900; }
        .owner-list { display: flex; flex-wrap: wrap; gap: 5px; }
        .owner-chip { border-radius: 999px; color: #176375; background: #e7f8fa; padding: 4px 8px; font-size: 12px; font-weight: 800; }
        .owner-badge { display: inline-block; border-radius: 999px; background: #edf2f8; padding: 6px 10px; font-size: 12px; font-weight: 900; }
        .owner-empty { padding: 30px !important; color: #758095; text-align: center !important; }
        .owner-pagination { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 14px 18px; }
        .owner-pagination button { min-height: 36px; border: 1px solid #d5dde9; border-radius: 9px; background: #fff; padding: 0 13px; cursor: pointer; }
        .owner-pagination button:disabled { opacity: .45; cursor: default; }
        .owner-error { display: none; border: 1px solid #ffc9c9; border-radius: 12px; color: #9f1d1d; background: #fff0f0; padding: 14px 16px; }
        .owner-error.is-visible { display: block; }
        @media (max-width: 1180px) { .owner-metrics { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 860px) { .owner-shell { display: block; } .owner-content { padding: 18px; } }
        @media (max-width: 620px) { .owner-metrics { grid-template-columns: 1fr; } .owner-live { width: 100%; margin-left: 0; text-align: left; } }
    </style>
</head>
<body>
<div class="owner-shell">
    <x-dashboard.sidebar role="owner" active="monitoring" />
    <main class="owner-main">
        <x-dashboard.header title="Online Monitoring" panel-label="Owner Panel" user-name="Owner" />
        <div class="owner-content">
            <section class="owner-toolbar" aria-label="Filter monitoring">
                <div class="owner-field"><label for="owner-branch">Cabang</label><select id="owner-branch"><option value="">Semua Cabang</option></select></div>
                <div class="owner-field"><label for="owner-store">Toko</label><select id="owner-store"><option value="">Semua Toko</option></select></div>
                <div class="owner-field"><label for="owner-date">Tanggal</label><input id="owner-date" type="date" value="{{ now('Asia/Jakarta')->toDateString() }}"></div>
                <div class="owner-live" id="owner-live" data-state="loading"><strong>Menghubungkan...</strong><small>Belum ada pembaruan</small></div>
            </section>
            <div class="owner-error" id="owner-error" role="alert"></div>
            <section class="owner-metrics" aria-label="Ringkasan penjualan">
                <article class="owner-card owner-metric"><span>Omzet</span><strong id="metric-sales">Rp 0</strong></article>
                <article class="owner-card owner-metric"><span>Order Selesai</span><strong id="metric-orders">0</strong></article>
                <article class="owner-card owner-metric"><span>Item Terjual</span><strong id="metric-items">0</strong></article>
                <article class="owner-card owner-metric"><span>Toko Aktif</span><strong id="metric-stores">0</strong></article>
            </section>
            <section class="owner-card"><div class="owner-section-head"><h2>Status Order</h2></div><div class="owner-statuses" id="owner-statuses"></div></section>
            <section class="owner-card">
                <div class="owner-section-head"><h2>Performa Toko</h2></div>
                <div class="owner-table-wrap"><table class="owner-table"><thead><tr><th>Toko</th><th>Cabang</th><th>Omzet</th><th>Order</th><th>Item</th><th>Order Terakhir</th></tr></thead><tbody id="store-rows"></tbody></table></div>
                <div class="owner-pagination"><button id="stores-prev">Sebelumnya</button><span id="stores-page">Halaman 1</span><button id="stores-next">Berikutnya</button></div>
            </section>
            <section class="owner-card">
                <div class="owner-section-head"><h2>Order Terbaru</h2></div>
                <div class="owner-toolbar" style="border:0;box-shadow:none;border-radius:0">
                    <div class="owner-field"><label for="owner-status">Status</label><select id="owner-status"><option value="">Semua Status</option></select></div>
                    <div class="owner-field owner-field--search"><label for="owner-search">Nomor Order</label><input id="owner-search" type="search" placeholder="Cari nomor order"></div>
                </div>
                <div class="owner-table-wrap"><table class="owner-table"><thead><tr><th>Nomor</th><th>Cabang</th><th>Toko</th><th>Waktu</th><th>Nominal Scope</th><th>Status</th></tr></thead><tbody id="order-rows"></tbody></table></div>
                <div class="owner-pagination"><button id="orders-prev">Sebelumnya</button><span id="orders-page">Halaman 1</span><button id="orders-next">Berikutnya</button></div>
            </section>
        </div>
    </main>
</div>
<script>
(() => {
    const token = localStorage.getItem('kresekin_token');
    const role = localStorage.getItem('kresekin_user_role');
    if (!token || role !== 'owner') { window.location.replace('{{ url('/') }}'); return; }

    const state = { storesPage: 1, ordersPage: 1, storesLast: 1, ordersLast: 1, timer: null, controller: null, loading: false, refreshId: 0, stores: [] };
    const el = (id) => document.getElementById(id);
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
    const chips = (items) => `<div class="owner-list">${items.map(item => `<span class="owner-chip">${escapeHtml(item.name)}</span>`).join('')}</div>`;

    function params(extra = {}) {
        const values = { seller_id: el('owner-branch').value, store_id: el('owner-store').value, date: el('owner-date').value, ...extra };
        const query = new URLSearchParams();
        Object.entries(values).forEach(([key, value]) => { if (value !== '' && value != null) query.set(key, value); });
        return query.toString();
    }

    async function request(path, query, signal) {
        const response = await fetch(`${path}?${query}`, { headers: { Accept: 'application/json', Authorization: `Bearer ${token}` }, signal });
        const payload = await response.json().catch(() => ({}));
        if (response.status === 401 || response.status === 403) {
            localStorage.removeItem('kresekin_token'); localStorage.removeItem('kresekin_token_type'); localStorage.removeItem('kresekin_user_role');
            window.location.replace('{{ url('/') }}'); throw new Error('Sesi berakhir.');
        }
        if (!response.ok) throw new Error(payload.message || 'Data monitoring gagal dimuat.');
        return payload;
    }

    function setConnection(stateName, label, detail) { const live = el('owner-live'); live.dataset.state = stateName; live.querySelector('strong').textContent = label; live.querySelector('small').textContent = detail; }
    function renderEmpty(target, colspan, message) { target.innerHTML = `<tr><td class="owner-empty" colspan="${colspan}">${message}</td></tr>`; }

    function syncOptions(summary) {
        const scope = summary.data.scope;
        const selectedBranch = el('owner-branch').value;
        el('owner-branch').innerHTML = '<option value="">Semua Cabang</option>' + scope.branches.map(branch => `<option value="${branch.id}">${escapeHtml(branch.name)}</option>`).join('');
        el('owner-branch').value = selectedBranch;
        state.stores = scope.stores;
        const selectedStore = el('owner-store').value;
        el('owner-store').innerHTML = '<option value="">Semua Toko</option>' + state.stores.map(store => `<option value="${store.id}">${escapeHtml(store.name)}</option>`).join('');
        el('owner-store').value = state.stores.some(store => store.id === selectedStore) ? selectedStore : '';
    }

    function renderSummary(payload) {
        const data = payload.data;
        syncOptions(payload);
        el('metric-sales').textContent = data.summary.sales_amount_label;
        el('metric-orders').textContent = data.summary.order_count.toLocaleString('id-ID');
        el('metric-items').textContent = data.summary.item_quantity.toLocaleString('id-ID');
        el('metric-stores').textContent = data.summary.active_store_count.toLocaleString('id-ID');
        el('owner-statuses').innerHTML = data.order_status_counts.map(status => `<article class="owner-status"><span>${escapeHtml(status.status_label)}</span><strong>${status.count.toLocaleString('id-ID')}</strong></article>`).join('');
        const statusSelect = el('owner-status');
        if (statusSelect.options.length === 1) statusSelect.innerHTML += data.order_status_counts.map(status => `<option value="${status.status_code}">${escapeHtml(status.status_label)}</option>`).join('');
        return data.generated_at;
    }

    function renderStores(payload) {
        const rows = el('store-rows');
        if (!payload.data.length) renderEmpty(rows, 6, 'Belum ada toko dalam scope ini.');
        else rows.innerHTML = payload.data.map(store => `<tr><td><strong>${escapeHtml(store.store_name)}</strong></td><td>${escapeHtml(store.branch_name)}</td><td class="money">${escapeHtml(store.sales_amount_label)}</td><td>${store.order_count}</td><td>${store.item_quantity}</td><td>${escapeHtml(store.last_order_at_label || '-')}</td></tr>`).join('');
        state.storesLast = payload.meta.last_page; el('stores-page').textContent = `Halaman ${payload.meta.current_page} dari ${payload.meta.last_page}`; el('stores-prev').disabled = state.storesPage <= 1; el('stores-next').disabled = state.storesPage >= state.storesLast;
    }

    function renderOrders(payload) {
        const rows = el('order-rows');
        if (!payload.data.length) renderEmpty(rows, 6, 'Tidak ada order yang sesuai filter.');
        else rows.innerHTML = payload.data.map(order => `<tr><td><strong>${escapeHtml(order.order_number)}</strong></td><td>${chips(order.branches)}</td><td>${chips(order.stores)}</td><td>${escapeHtml(order.transaction_at_label)}</td><td class="money">${escapeHtml(order.amount_label)}</td><td><span class="owner-badge">${escapeHtml(order.status_label)}</span></td></tr>`).join('');
        state.ordersLast = payload.meta.last_page; el('orders-page').textContent = `Halaman ${payload.meta.current_page} dari ${payload.meta.last_page}`; el('orders-prev').disabled = state.ordersPage <= 1; el('orders-next').disabled = state.ordersPage >= state.ordersLast;
    }

    async function refresh() {
        if (state.loading || document.hidden) return;
        const refreshId = ++state.refreshId;
        state.loading = true; state.controller?.abort(); state.controller = new AbortController();
        setConnection('loading', 'Menghubungkan ulang', 'Memperbarui data...'); el('owner-error').classList.remove('is-visible');
        try {
            const common = params();
            const [summary, stores, orders] = await Promise.all([
                request('/api/owner/online-monitoring/summary', common, state.controller.signal),
                request('/api/owner/online-monitoring/stores', params({ page: state.storesPage, per_page: 25, sort: 'sales_amount', direction: 'desc' }), state.controller.signal),
                request('/api/owner/online-monitoring/orders', params({ page: state.ordersPage, per_page: 25, status: el('owner-status').value, search: el('owner-search').value.trim() }), state.controller.signal),
            ]);
            const generatedAt = renderSummary(summary); renderStores(stores); renderOrders(orders);
            setConnection('live', 'Live', `Terakhir diperbarui ${new Date(generatedAt).toLocaleTimeString('id-ID')}`);
            clearTimeout(state.timer); state.timer = setTimeout(refresh, (summary.data.refresh_after_seconds || 10) * 1000 + Math.random() * 2000);
        } catch (error) {
            if (error.name !== 'AbortError') { el('owner-error').textContent = error.message; el('owner-error').classList.add('is-visible'); setConnection('error', 'Gagal memperbarui', 'Mencoba kembali dalam 10 detik'); clearTimeout(state.timer); state.timer = setTimeout(refresh, 10000); }
        } finally { if (refreshId === state.refreshId) state.loading = false; }
    }

    function filtersChanged() { state.storesPage = 1; state.ordersPage = 1; clearTimeout(state.timer); state.controller?.abort(); state.loading = false; refresh(); }
    el('owner-branch').addEventListener('change', () => { el('owner-store').value = ''; filtersChanged(); });
    ['owner-store','owner-date','owner-status'].forEach(id => el(id).addEventListener('change', filtersChanged));
    let searchTimer; el('owner-search').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(filtersChanged, 350); });
    el('stores-prev').addEventListener('click', () => { if (state.storesPage > 1) { state.storesPage--; refresh(); } });
    el('stores-next').addEventListener('click', () => { if (state.storesPage < state.storesLast) { state.storesPage++; refresh(); } });
    el('orders-prev').addEventListener('click', () => { if (state.ordersPage > 1) { state.ordersPage--; refresh(); } });
    el('orders-next').addEventListener('click', () => { if (state.ordersPage < state.ordersLast) { state.ordersPage++; refresh(); } });
    document.addEventListener('visibilitychange', () => { clearTimeout(state.timer); if (document.hidden) { state.refreshId++; state.controller?.abort(); state.loading = false; setConnection('loading', 'Dijeda', 'Tab tidak aktif'); } else refresh(); });
    refresh();
})();
</script>
</body>
</html>
