<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ADMIN_LOG_IN.php');
    exit();
}
$adminEmail = isset($_SESSION['admin_email']) ? (string)$_SESSION['admin_email'] : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — Blessed Board</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --navy:   #1a2332;
            --blue:   #3b82f6;
            --blue-lt:#eff6ff;
            --green:  #22c55e;
            --amber:  #f59e0b;
            --orange: #f97316;
            --red:    #ef4444;
            --text:   #1a2332;
            --muted:  #64748b;
            --border: #e2e8f0;
            --bg:     #f8fafc;
            --white:  #ffffff;
            --radius: 10px;
        }

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; left: 0; top: 0;
            width: 210px; height: 100vh;
            background: var(--navy);
            display: flex; flex-direction: column;
            padding: 28px 16px; z-index: 100;
        }
        .brand { display: flex; align-items: center; gap: 10px; padding: 0 8px; margin-bottom: 36px; }
        .brand-icon { width: 32px; height: 32px; background: var(--blue); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .brand-icon svg { color: #fff; }
        .brand-name { font-size: 15px; font-weight: 700; color: #fff; }
        .brand-sub  { font-size: 11px; color: #94a3b8; margin-top: 1px; }
        .nav { list-style: none; flex: 1; }
        .nav li { margin-bottom: 4px; }
        .nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 8px;
            color: #94a3b8; text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            transition: background .18s, color .18s;
        }
        .nav a:hover  { background: rgba(255,255,255,.07); color: #e2e8f0; }
        .nav a.active { background: rgba(59,130,246,.18);  color: var(--blue); }
        .sidebar-footer { border-top: 1px solid rgba(255,255,255,.1); padding-top: 16px; }
        .user-email { font-size: 11.5px; color: #64748b; word-break: break-all; margin-bottom: 10px; padding: 0 4px; }
        .logout-btn {
            display: flex; align-items: center; gap: 8px;
            width: 100%; padding: 9px 12px;
            background: transparent; border: 1px solid rgba(255,255,255,.12);
            border-radius: 8px; color: #94a3b8; font-size: 13px; font-weight: 500;
            cursor: pointer; text-decoration: none;
            transition: border-color .18s, color .18s;
        }
        .logout-btn:hover { border-color: var(--red); color: #fca5a5; }

        /* ── Main ── */
        .main { margin-left: 210px; padding: 30px; flex: 1; min-width: 0; }
        .page-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            margin-bottom: 28px; flex-wrap: wrap; gap: 16px;
        }
        .page-header h1 { font-size: 22px; font-weight: 700; }
        .page-header p  { font-size: 13px; color: var(--muted); margin-top: 3px; }
        .header-btns { display: flex; gap: 10px; flex-wrap: wrap; }
        .export-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 9px 16px; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer; border: none;
            transition: opacity .18s;
        }
        .export-btn:hover { opacity: .85; }
        .btn-summary { background: var(--bg); color: var(--text); border: 1px solid var(--border); }
        .btn-csv     { background: var(--navy); color: #fff; }

        /* ── Stat cards ── */
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
        .stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .stat-label { font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
        .stat-ico { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .si-blue   { background: var(--blue-lt); color: var(--blue); }
        .si-orange { background: #fff7ed;         color: var(--orange); }
        .si-amber  { background: #fffbeb;         color: var(--amber); }
        .si-green  { background: #f0fdf4;         color: var(--green); }
        .stat-value { font-size: 30px; font-weight: 700; line-height: 1; }
        .stat-pill { display: inline-block; margin-top: 8px; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
        .pill-blue   { background: var(--blue-lt); color: var(--blue); }
        .pill-orange { background: #fff7ed;         color: #c2410c; }
        .pill-amber  { background: #fffbeb;         color: #b45309; }
        .pill-green  { background: #f0fdf4;         color: #15803d; }

        /* ── Card ── */
        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 22px; margin-bottom: 20px; }
        .card-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .card-tag { font-size: 11px; font-weight: 400; color: var(--muted); margin-left: auto; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }

        /* ── Chart ── */
        .chart-wrap { position: relative; height: 280px; }
        .time-btns  { display: flex; gap: 6px; margin-bottom: 16px; }
        .time-btn {
            padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
            border: 1px solid var(--border); background: var(--bg); color: var(--muted);
            cursor: pointer; transition: all .18s;
        }
        .time-btn.active { background: var(--navy); color: #fff; border-color: var(--navy); }

        /* ── Tables ── */
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl th { text-align: left; padding: 9px 12px; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--border); background: var(--bg); }
        .tbl td { padding: 11px 12px; font-size: 13px; border-bottom: 1px solid var(--border); }
        .tbl tbody tr:last-child td { border-bottom: none; }
        .tbl tbody tr:hover td { background: var(--bg); }
        .num   { font-weight: 700; color: var(--blue); }
        .muted { color: var(--muted); font-size: 12px; }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
        .b-answered { background: #f0fdf4; color: #15803d; }

        /* ── Download history ── */
        .hist-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .hist-row:last-child { border-bottom: none; }
        .hist-type { font-size: 13px; font-weight: 600; }
        .hist-date { font-size: 11px; color: var(--muted); margin-top: 2px; }
        .hist-who  { font-size: 12px; color: var(--muted); background: var(--bg); padding: 3px 10px; border-radius: 6px; }

        /* ── Bar ── */
        .bar-wrap { display: flex; align-items: center; gap: 8px; margin-top: 5px; }
        .bar-bg   { flex: 1; height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 3px; background: var(--blue); }

        /* ── Progress ring ── */
        .kpi-row { display: flex; gap: 20px; margin-bottom: 16px; }
        .kpi { flex: 1; background: var(--bg); border-radius: 8px; padding: 14px; text-align: center; }
        .kpi-val  { font-size: 22px; font-weight: 700; color: var(--text); }
        .kpi-lbl  { font-size: 11px; color: var(--muted); margin-top: 2px; }

        /* ── Empty ── */
        .empty { text-align: center; padding: 32px; color: var(--muted); font-size: 13px; }

        /* ── Modal ── */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 999; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-box { background: var(--white); border-radius: 14px; padding: 28px; max-width: 380px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
        .modal-ttl { font-size: 17px; font-weight: 700; margin-bottom: 8px; }
        .modal-msg { font-size: 13px; color: var(--muted); margin-bottom: 20px; line-height: 1.6; }
        .modal-ok  { background: var(--navy); color: #fff; border: none; padding: 9px 24px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }

        .section-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .8px; margin: 28px 0 14px; }

        @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2,1fr); } .grid-2 { grid-template-columns: 1fr; } }
        @media (max-width: 768px)  { .sidebar { display: none; } .main { margin-left: 0; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<!-- ── Sidebar ── -->
<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="brand-name">Blessed Board</div>
            <div class="brand-sub">Prayer Tracker</div>
        </div>
    </div>
    <ul class="nav">
        <li><a href="ADMIN_DASHBOARD.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a></li>
        <li><a href="ADMIN_PRAYER_REQUEST.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1" fill="currentColor"/><circle cx="3" cy="12" r="1" fill="currentColor"/><circle cx="3" cy="18" r="1" fill="currentColor"/></svg>
            Requests
        </a></li>
        <li><a href="ADMIN_REPORTS.php" class="active">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
            Reports
        </a></li>
    </ul>
    <div class="sidebar-footer">
        <div class="user-email"><?php echo htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8'); ?></div>
        <a href="logout.php" class="logout-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</aside>

<!-- ── Main ── -->
<main class="main">
    <div class="page-header">
        <div>
            <h1>Reports</h1>
            <p>Summaries, trends, and data exports</p>
        </div>
        <div class="header-btns">
            <button class="export-btn btn-summary" id="btnSummary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Export PDF
            </button>
            <button class="export-btn btn-csv" id="btnCSV">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export CSV
            </button>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-label">Total</div>
                <div class="stat-ico si-blue"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></div>
            </div>
            <div class="stat-value" id="statTotal">—</div>
            <span class="stat-pill pill-blue">All time</span>
        </div>
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-label">Pending</div>
                <div class="stat-ico si-orange"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            </div>
            <div class="stat-value" id="statPending">—</div>
            <span class="stat-pill pill-orange">Current</span>
        </div>
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-label">In Progress</div>
                <div class="stat-ico si-amber"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></div>
            </div>
            <div class="stat-value" id="statProgress">—</div>
            <span class="stat-pill pill-amber">Current</span>
        </div>
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-label">Answered</div>
                <div class="stat-ico si-green"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
            </div>
            <div class="stat-value" id="statAnswered">—</div>
            <span class="stat-pill pill-green">Current</span>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid-2">
        <div class="card">
            <div class="card-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                Requests Per Day
            </div>
            <div class="time-btns">
                <button class="time-btn" data-days="1">Day</button>
                <button class="time-btn active" data-days="7">Week</button>
                <button class="time-btn" data-days="30">Month</button>
                <button class="time-btn" data-days="365">Year</button>
            </div>
            <div class="chart-wrap"><canvas id="dailyChart"></canvas></div>
        </div>
        <div class="card">
            <div class="card-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Status Distribution
            </div>
            <div class="chart-wrap"><canvas id="statusChart"></canvas></div>
        </div>
    </div>

    <!-- Download history -->
    <p class="section-label">Export History</p>
    <div class="card">
        <div class="card-title">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download History
        </div>
        <div id="historyList"><div class="empty">No exports yet.</div></div>
    </div>
</main>

<!-- Modal -->
<div id="modal" class="modal">
    <div class="modal-box">
        <div class="modal-ttl" id="mTtl">Notice</div>
        <div class="modal-msg"  id="mMsg"></div>
        <button class="modal-ok" onclick="closeModal()">OK</button>
    </div>
</div>

<script>
    function showModal(t, m) {
        document.getElementById('mTtl').textContent = t;
        document.getElementById('mMsg').textContent = m;
        document.getElementById('modal').classList.add('show');
    }
    function closeModal() { document.getElementById('modal').classList.remove('show'); }
    document.getElementById('modal').addEventListener('click', e => { if (e.target === document.getElementById('modal')) closeModal(); });

    function statusClass(s) {
        const k = String(s || '').toLowerCase();
        if (k === 'pending')     return 'b-pending';
        if (k === 'in progress') return 'b-progress';
        if (k === 'answered')    return 'b-answered';
        return 'b-pending';
    }

    async function api(url) {
        const r = await fetch(url);
        const j = await r.json();
        if (!r.ok || j.status !== 'success') throw new Error(j.message || 'Request failed');
        return j.data;
    }

    // ── Status counts ─────────────────────────────────────────
    const statusChart = new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Answered'],
            datasets: [{ data: [0,0,0], backgroundColor: ['#f97316','#f59e0b','#22c55e'], borderColor:'#fff', borderWidth: 3 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position:'bottom', labels: { usePointStyle:true, padding:16, font:{size:12} } } }
        }
    });

    async function loadStats() {
        const c = await api('api.php?action=get_status_counts');
        document.getElementById('statTotal').textContent    = c.Total || 0;
        document.getElementById('statPending').textContent  = c['Pending']     || 0;
        document.getElementById('statProgress').textContent = c['In Progress'] || 0;
        document.getElementById('statAnswered').textContent = c['Answered']    || 0;
        statusChart.data.datasets[0].data = [c['Pending']||0, c['In Progress']||0, c['Answered']||0];
        statusChart.update();
    }

    // ── Daily chart ───────────────────────────────────────────
    let dailyChart = null;
    async function loadDaily(days) {
        const rows = await api('api.php?action=get_daily_stats&days=' + days);
        if (dailyChart) dailyChart.destroy();
        dailyChart = new Chart(document.getElementById('dailyChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: rows.map(r => r.date),
                datasets: [{
                    label: 'Requests', data: rows.map(r => +r.count),
                    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.1)',
                    borderWidth: 2, fill: true, tension: .4,
                    pointRadius: 4, pointBackgroundColor: '#3b82f6', pointBorderColor: '#fff', pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f4f8' }, ticks: { font: { size: 11 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });
    }

    document.querySelectorAll('.time-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadDaily(+this.dataset.days);
        });
    });

    // ── Download history ──────────────────────────────────────
    async function loadHistory() {
        const list = document.getElementById('historyList');
        const rows = await api('api.php?action=get_downloads');
        if (!rows.length) { list.innerHTML = '<div class="empty">No exports yet.</div>'; return; }
        list.innerHTML = rows.map(r => `
            <div class="hist-row">
                <div>
                    <div class="hist-type">${r.action}</div>
                    <div class="hist-date">${r.action_date}</div>
                </div>
                <div class="hist-who"><?php echo htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>`).join('');
    }

    async function logDownload(type) {
        const fd = new FormData();
        fd.append('download_type', type);
        await fetch('api.php?action=log_download', { method:'POST', body:fd });
        loadHistory();
    }

    // ── Exports ───────────────────────────────────────────────
    function csvEscape(v) {
        const s = String(v ?? '');
        return /[",\n\r]/.test(s) ? `"${s.replace(/"/g,'""')}"` : s;
    }
    function downloadText(content, filename, mime) {
        const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob([content],{type:mime})), download: filename });
        document.body.appendChild(a); a.click(); a.remove();
    }

    document.getElementById('btnCSV').addEventListener('click', async () => {
        const rows = await api('api.php?action=list_requests');
        const csv  = [
            ['Title','Description','Category','Status','Created'],
            ...rows.map(r => [r.title, r.description, r.category, r.status, r.created])
        ].map(r => r.map(csvEscape).join(',')).join('\r\n');
        downloadText(csv, 'blessed-board-requests.csv', 'text/csv;charset=utf-8');
        await logDownload('Export to CSV');
        showModal('Exported', 'CSV file downloaded successfully.');
    });

    document.getElementById('btnSummary').addEventListener('click', async () => {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation:'portrait', unit:'mm', format:'a4' });

        pdf.setFontSize(18);
        pdf.text('Blessed Board — Prayer Request Summary', 105, 16, { align:'center' });
        pdf.setFontSize(10);
        pdf.text('Generated: ' + new Date().toLocaleDateString(), 105, 24, { align:'center' });

        let y = 34;
        const c = await api('api.php?action=get_status_counts');
        pdf.setFontSize(13); pdf.text('Overview', 15, y); y += 8;
        pdf.setFontSize(10);
        [['Total', c.Total||0],['Pending', c['Pending']||0],['In Progress', c['In Progress']||0],['Answered', c['Answered']||0]]
            .forEach(([label, val]) => { pdf.text(`${label}: ${val}`, 15, y); y += 7; });
        y += 6;

        const items = await api('api.php?action=list_requests');
        pdf.setFontSize(13); pdf.text('Prayer Requests', 15, y); y += 8;
        pdf.setFontSize(9);
        items.forEach(r => {
            if (y > 270) { pdf.addPage(); y = 15; }
            pdf.text(`${r.title}`, 15, y); y += 5;
            pdf.text(`${r.category} · ${r.status} · ${r.created}`, 15, y); y += 9;
        });

        pdf.save('blessed-board-summary.pdf');
        await logDownload('Export Summary');
        showModal('Exported', 'PDF summary downloaded successfully.');
    });

    // ── Boot ──────────────────────────────────────────────────
    async function boot() {
        try { await loadStats();        } catch(e) { showModal('Error', e.message); }
        try { await loadDaily(7);       } catch {}
        try { await loadHistory();      } catch {}
    }
    boot();
</script>
</body>
</html>