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
    <title>Dashboard — Blessed Board</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; left: 0; top: 0;
            width: 210px; height: 100vh;
            background: var(--navy);
            display: flex; flex-direction: column;
            padding: 28px 16px;
            z-index: 100;
        }
        .brand { display: flex; align-items: center; gap: 10px; padding: 0 8px; margin-bottom: 36px; }
        .brand-icon {
            width: 32px; height: 32px; background: var(--blue);
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
        }
        .brand-icon svg { color: #fff; }
        .brand-name { font-size: 15px; font-weight: 700; color: #fff; }
        .brand-sub  { font-size: 11px; color: #94a3b8; margin-top: 1px; }

        .nav { list-style: none; flex: 1; }
        .nav li { margin-bottom: 4px; }
        .nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            transition: background .18s, color .18s;
        }
        .nav a:hover  { background: rgba(255,255,255,.07); color: #e2e8f0; }
        .nav a.active { background: rgba(59,130,246,.18);  color: var(--blue); }
        .nav a svg    { flex-shrink: 0; }

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

        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: var(--text); }
        .page-header p  { font-size: 13px; color: var(--muted); margin-top: 3px; }

        /* ── Stat cards ── */
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 28px; }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
        }
        .stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .stat-label { font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
        .stat-icon-wrap {
            width: 34px; height: 34px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .si-blue   { background: var(--blue-lt); color: var(--blue); }
        .si-amber  { background: #fffbeb;         color: var(--amber); }
        .si-orange { background: #fff7ed;         color: var(--orange); }
        .si-green  { background: #f0fdf4;         color: var(--green); }

        .stat-value { font-size: 30px; font-weight: 700; color: var(--text); line-height: 1; }
        .stat-pill  {
            display: inline-block; margin-top: 8px;
            font-size: 11px; font-weight: 600;
            padding: 3px 8px; border-radius: 20px;
        }
        .pill-blue   { background: var(--blue-lt); color: var(--blue); }
        .pill-amber  { background: #fffbeb;         color: #b45309; }
        .pill-orange { background: #fff7ed;         color: #c2410c; }
        .pill-green  { background: #f0fdf4;         color: #15803d; }

        /* ── Grid ── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-bottom: 20px; }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px;
        }
        .card-title {
            font-size: 14px; font-weight: 700; color: var(--text);
            margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px;
        }

        /* ── Recent activity ── */
        .activity-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 11px 0; border-bottom: 1px solid var(--border);
        }
        .activity-row:last-child { border-bottom: none; }
        .activity-title { font-size: 13px; font-weight: 600; color: var(--text); }
        .activity-date  { font-size: 11px; color: var(--muted); margin-top: 2px; }

        /* ── Category list ── */
        .cat-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid var(--border);
        }
        .cat-row:last-child { border-bottom: none; }
        .cat-name  { font-size: 13px; color: var(--text); }
        .cat-count { font-size: 13px; font-weight: 700; color: var(--blue); }

        /* ── Status badge ── */
        .badge {
            display: inline-block; padding: 3px 9px;
            border-radius: 20px; font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .3px;
        }
        .b-pending  { background: #fff7ed; color: #c2410c; }
        .b-progress { background: #fffbeb; color: #b45309; }
        .b-answered { background: #f0fdf4; color: #15803d; }

        /* ── Analytics table ── */
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl th {
            text-align: left; padding: 9px 12px;
            font-size: 11px; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: .5px;
            border-bottom: 1px solid var(--border); background: var(--bg);
        }
        .tbl td {
            padding: 11px 12px; font-size: 13px;
            border-bottom: 1px solid var(--border);
        }
        .tbl tbody tr:last-child td { border-bottom: none; }
        .tbl tbody tr:hover td { background: var(--bg); }

        .num { font-weight: 700; color: var(--blue); }
        .muted { color: var(--muted); font-size: 12px; }

        /* ── Progress bar ── */
        .bar-wrap { display: flex; align-items: center; gap: 8px; }
        .bar-bg   { flex: 1; height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 3px; background: var(--blue); }

        /* ── Top Submitters Table ── */
        .tbl {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
        }
        .tbl th {
            background: #f8fafc;
            color: #1a2b4a;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .tbl td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        .tbl tbody tr:last-child td {
            border-bottom: none;
        }
        .tbl tbody tr:hover {
            background: #f8fafc;
        }
        
        /* Table specific styling */
        .rank-cell {
            font-weight: 600;
            color: var(--text);
            text-align: center;
            font-size: 12px;
        }
        .name-cell {
            min-width: 200px;
        }
        .user-name {
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
            margin-bottom: 4px;
        }
        .user-contact {
            font-size: 12px;
            color: var(--text);
            margin-bottom: 6px;
        }
        .user-email {
            font-size: 12px;
            color: var(--muted);
        }
        .categories-cell {
            max-width: 250px;
        }
        .category-pill {
            display: inline-block;
            padding: 2px 8px;
            margin: 2px;
            background: rgba(59,130,246,.1);
            color: #1e40af;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .meta-cell {
            font-size: 11px;
            color: var(--muted);
        }
        .date-cell {
            font-size: 12px;
            color: var(--muted);
        }
        .stat-cell {
            font-weight: 700;
            color: var(--text);
            font-size: 14px;
            text-align: left;
        }
        .answered-cell {
            color: var(--green);
            font-weight: 500;
            text-align: left;
        }
        .admin-cell {
            color: var(--orange);
            font-weight: 500;
            text-align: left;
        }
        
        /* Alternating rows */
        .even-row {
            background: #ffffff;
        }
        .odd-row {
            background: #f8fafc;
        }

        /* ── Modal ── */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 999; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-box {
            background: var(--white); border-radius: 14px;
            padding: 28px; max-width: 380px; width: 90%; text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.15);
        }
        .modal-ico { font-size: 36px; margin-bottom: 12px; }
        .modal-ttl { font-size: 17px; font-weight: 700; margin-bottom: 8px; }
        .modal-msg { font-size: 13px; color: var(--muted); margin-bottom: 20px; line-height: 1.6; }
        .modal-ok  {
            background: var(--navy); color: #fff;
            border: none; padding: 9px 24px; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .modal-ok:hover { background: #0f1827; }

        /* ── Section label ── */
        .section-label {
            font-size: 11px; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: .8px;
            margin: 28px 0 14px;
        }

        @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2,1fr); } .grid-3 { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 768px)  { .stats-grid { grid-template-columns: 1fr; } .grid-2, .grid-3 { grid-template-columns: 1fr; } .sidebar { display: none; } .main { margin-left: 0; } }
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
        <li><a href="ADMIN_DASHBOARD.php" class="active">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a></li>
        <li><a href="ADMIN_PRAYER_REQUEST.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1" fill="currentColor"/><circle cx="3" cy="12" r="1" fill="currentColor"/><circle cx="3" cy="18" r="1" fill="currentColor"/></svg>
            Requests
        </a></li>
        <li><a href="ADMIN_REPORTS.php">
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

<!-- ── Main Content ── -->
<main class="main">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p id="currentDate"></p>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-label">Total</div>
                <div class="stat-icon-wrap si-blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                </div>
            </div>
            <div class="stat-value" id="statTotal">—</div>
            <span class="stat-pill pill-blue">All time</span>
        </div>
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-label">Pending</div>
                <div class="stat-icon-wrap si-orange">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="stat-value" id="statPending">—</div>
            <span class="stat-pill pill-orange" id="pctPending">0%</span>
        </div>
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-label">In Progress</div>
                <div class="stat-icon-wrap si-amber">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                </div>
            </div>
            <div class="stat-value" id="statProgress">—</div>
            <span class="stat-pill pill-amber" id="pctProgress">0%</span>
        </div>
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-label">Answered</div>
                <div class="stat-icon-wrap si-green">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
            </div>
            <div class="stat-value" id="statAnswered">—</div>
            <span class="stat-pill pill-green" id="pctAnswered">0%</span>
        </div>
    </div>

    <!-- Recent + Categories -->
    <div class="grid-2">
        <div class="card" id="recentCard">
            <div class="card-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Recent Activity
            </div>
        </div>
        <div class="card" id="categoryCard">
            <div class="card-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/></svg>
                Requests by Category
            </div>
        </div>
    </div>

    <!-- Top Submitters -->
    <p class="section-label">User Engagement</p>
    <div class="card" style="margin-bottom:20px;">
        <div class="card-title" style="position: sticky; top: 0; background: var(--white); z-index: 10; padding: 10px 22px; margin: -22px -22px 20px 0;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Top Submitters
        </div>
        
        <div style="overflow-x: auto;">
            <table class="tbl" id="topSubmittersTable">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align:center;">Rank</th>
                        <th style="width: 220px;">Full Name</th>
                        <th style="width: 150px;">Contact Number</th>
                        <th style="width: 240px;">Categories</th>
                        <th style="width: 120px; text-align:left;">Last Active</th>
                        <th style="width: 80px;  text-align:center;">Total</th>
                        <th style="width: 100px; text-align:center;">Answered</th>
                        <th style="width: 110px; text-align:center;">Admin Actions</th>
                    </tr>
                </thead>
                <tbody id="topSubmittersBody">
                    <tr><td colspan="8" class="muted" style="text-align:center;padding:20px;">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    </main>

<!-- Modal -->
<div id="modal" class="modal">
    <div class="modal-box">
        <div class="modal-ico" id="mIco"></div>
        <div class="modal-ttl" id="mTtl">Error</div>
        <div class="modal-msg" id="mMsg"></div>
        <button class="modal-ok" onclick="closeModal()">OK</button>
    </div>
</div>

<script>
    // ── Helpers ──────────────────────────────────────────────
    function showModal(t, m) {
        document.getElementById('mTtl').textContent = t;
        document.getElementById('mMsg').textContent = m;
        document.getElementById('modal').classList.add('show');
    }
    function closeModal() { document.getElementById('modal').classList.remove('show'); }
    document.getElementById('modal').addEventListener('click', e => { if (e.target === document.getElementById('modal')) closeModal(); });

    const options = { year:'numeric', month:'long', day:'numeric' };
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', options);

    function pct(n, t) { return t ? Math.round(n / t * 100) : 0; }

    function statusClass(s) {
        const k = String(s||'').toLowerCase();
        if (k.includes('pending'))  return 'b-pending';
        if (k.includes('progress')) return 'b-progress';
        if (k.includes('answered')) return 'b-answered';
        return 'b-pending';
    }

    async function api(endpoint) {
        const r = await fetch(endpoint);
        const j = await r.json();
        if (!r.ok || j.status !== 'success') throw new Error(j.message || 'Request failed');
        return j.data;
    }

    // ── Status counts ─────────────────────────────────────────
    async function loadCounts() {
        const c = await api('api.php?action=get_status_counts');
        const total = c.Total || 0;
        document.getElementById('statTotal').textContent   = total;
        document.getElementById('statPending').textContent = c['Pending']     || 0;
        document.getElementById('statProgress').textContent= c['In Progress'] || 0;
        document.getElementById('statAnswered').textContent= c['Answered']    || 0;
        document.getElementById('pctPending').textContent  = pct(c['Pending']     ||0, total) + '% of total';
        document.getElementById('pctProgress').textContent = pct(c['In Progress'] ||0, total) + '% of total';
        document.getElementById('pctAnswered').textContent = pct(c['Answered']    ||0, total) + '% of total';
    }

    // ── Recent activity ───────────────────────────────────────
    async function loadRecent() {
        const rows = await api('api.php?action=get_recent_requests&limit=5');
        const card  = document.getElementById('recentCard');
        const title = card.querySelector('.card-title');
        card.innerHTML = '';
        card.appendChild(title);
        if (!rows.length) {
            card.insertAdjacentHTML('beforeend','<p class="muted" style="text-align:center;padding:16px 0;">No requests yet.</p>');
            return;
        }
        rows.forEach(r => {
            card.insertAdjacentHTML('beforeend', `
                <div class="activity-row">
                    <div>
                        <div class="activity-title">${r.title}</div>
                        <div class="activity-date">${r.created}</div>
                    </div>
                    <span class="badge ${statusClass(r.status)}">${r.status}</span>
                </div>`);
        });
    }

    // ── Category counts ───────────────────────────────────────
    async function loadCatCounts() {
        const rows = await api('api.php?action=get_category_counts');
        const card  = document.getElementById('categoryCard');
        const title = card.querySelector('.card-title');
        card.innerHTML = '';
        card.appendChild(title);
        rows.forEach(r => {
            card.insertAdjacentHTML('beforeend', `
                <div class="cat-row">
                    <span class="cat-name">${r.category}</span>
                    <span class="cat-count">${r.count}</span>
                </div>`);
        });
    }

    // ── User engagement (Top Submitters Table) ───────────────────
    async function loadUserEngagement() {
        const tbody = document.getElementById('topSubmittersBody');
        const table = document.getElementById('topSubmittersTable');
        if (!tbody || !table) {
            console.error('Top submitters table elements not found');
            return;
        }
        
        try {
            const rows = await api('api.php?action=get_user_engagement');
            if (!rows || !rows.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="muted" style="text-align:center;padding:20px;">No users yet.</td></tr>';
                return;
            }
            
            tbody.innerHTML = rows.slice(0,10).map((r, index) => {
                // Safe null checks for all properties
                const fullName = r.full_name || 'Unknown';
                const email = r.email || '—';
                const contactNumber = r.contact_number || '—';
                const recentCategories = r.recent_categories || '—';
                const latestMonth = r.latest_month || '—';
                const totalRequests = r.total_requests_submitted || 0;
                const answeredRequests = r.answered_requests || 0;
                const adminActionsCount = r.admin_actions_count || 0;
                
                // Create category pills
                const categoryPills = recentCategories !== '—' 
                    ? recentCategories.split(', ').map(cat => 
                        `<span class="category-pill">${cat.trim()}</span>`
                      ).join(' ')
                    : '<span class="muted">—</span>';
                
                // Alternating row color
                const rowClass = index % 2 === 0 ? 'even-row' : 'odd-row';
                
                return `
                    <tr class="${rowClass}">
                        <td style="text-align:center;font-weight:600;font-size:13px;">${index + 1}</td>
                        <td class="name-cell">
                            <div class="user-name">${fullName}</div>
                            <div class="user-email">${email}</div>
                        </td>
                        <td class="contact-cell">${contactNumber}</td>
                        <td class="categories-cell">${categoryPills}</td>
                        <td class="date-cell">${latestMonth}</td>
                        <td style="text-align:center;font-weight:700;font-size:13px;color:var(--text);">${totalRequests}</td>
                        <td style="text-align:center;font-weight:500;font-size:13px;color:var(--green);">${answeredRequests}</td>
                        <td style="text-align:center;font-weight:500;font-size:13px;color:var(--orange);">${adminActionsCount}</td>
                    </tr>`;
            }).join('');
        } catch (error) {
            console.error('Error loading user engagement:', error);
            tbody.innerHTML = '<tr><td colspan="8" class="muted" style="text-align:center;padding:20px;">Error loading data.</td></tr>';
        }
    }

    
    
    // ── Boot ──────────────────────────────────────────────────
    async function boot() {
        try { await loadCounts();        } catch(e) { showModal('Error', e.message); }
        try { await loadRecent();        } catch {}
        try { await loadCatCounts();     } catch {}
        try { await loadUserEngagement();} catch(e) { console.warn('users',e); }
    }
    boot();
</script>
</body>
</html>