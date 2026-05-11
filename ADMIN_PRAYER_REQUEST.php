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
    <title>Prayer Requests — Blessed Board</title>
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
            --purple: #8b5cf6;
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
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; }
        .page-header p  { font-size: 13px; color: var(--muted); margin-top: 3px; }

        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 22px; }

        /* ── Filters ── */
        .filters { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-wrap input {
            width: 100%; padding: 9px 12px 9px 36px;
            border: 1px solid var(--border); border-radius: 8px;
            font-size: 13px; font-family: inherit; background: var(--bg);
            transition: border-color .18s;
        }
        .search-wrap input:focus { outline: none; border-color: var(--blue); background: #fff; }
        .search-ico { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
        .filter-sel {
            padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px;
            background: var(--bg); color: var(--text); font-size: 13px;
            font-family: inherit; cursor: pointer;
            transition: border-color .18s;
        }
        .filter-sel:focus { outline: none; border-color: var(--blue); }
        .results-count { font-size: 12px; color: var(--muted); margin-left: auto; white-space: nowrap; }

        /* ── Table ── */
        .tbl-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: var(--bg); }
        th {
            text-align: left; padding: 10px 14px;
            font-size: 11px; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: .5px;
            border-bottom: 1px solid var(--border);
        }
        td { padding: 13px 14px; border-bottom: 1px solid var(--border); font-size: 13px; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: var(--bg); }

        .req-title { font-weight: 600; color: var(--text); }
        .req-desc  { font-size: 11.5px; color: var(--muted); margin-top: 2px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .tbl-date  { font-size: 12px; color: var(--muted); }

        /* ── Badges — status ── */
        .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
        .b-pending  { background: #fff7ed; color: #c2410c; }
        .b-progress { background: #fffbeb; color: #b45309; }
        .b-answered { background: #f0fdf4; color: #15803d; }

        /* ── Badges — category ── */
        .cat { display: inline-block; padding: 3px 9px; border-radius: 6px; font-size: 11.5px; font-weight: 600; }
        .cat-family   { background: #f3e8ff; color: #7c3aed; }
        .cat-health   { background: #fef2f2; color: #b91c1c; }
        .cat-career   { background: var(--blue-lt); color: #1d4ed8; }
        .cat-financial{ background: #f0fdf4; color: #166534; }
        .cat-spiritual{ background: #fff7ed; color: #c2410c; }
        .cat-other    { background: #f8fafc; color: #475569; }

        /* ── Action buttons ── */
        .actions { display: flex; gap: 6px; }
        .act-btn {
            width: 30px; height: 30px; border: 1px solid var(--border);
            border-radius: 7px; background: var(--white); color: var(--muted);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all .18s;
        }
        .act-btn:hover.edit-btn   { border-color: var(--blue); color: var(--blue); background: var(--blue-lt); }
        .act-btn:hover.del-btn    { border-color: var(--red);  color: var(--red);  background: #fef2f2; }

        /* ── Empty state ── */
        .empty { text-align: center; padding: 48px 20px; color: var(--muted); }
        .empty svg { margin-bottom: 12px; opacity: .3; }
        .empty p { font-size: 13px; }

        /* ── Modals ── */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 999; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-box { background: var(--white); border-radius: 14px; padding: 28px; max-width: 400px; width: calc(100% - 32px); box-shadow: 0 20px 60px rgba(0,0,0,.15); }
        .modal-box.center { text-align: center; }
        .modal-ttl { font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .modal-msg { font-size: 13px; color: var(--muted); margin-bottom: 22px; line-height: 1.6; }
        .modal-btns { display: flex; gap: 10px; justify-content: center; }
        .btn-primary { background: var(--navy); color: #fff; border: none; padding: 9px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-primary:hover { background: #0f1827; }
        .btn-ghost { background: var(--bg); color: var(--muted); border: 1px solid var(--border); padding: 9px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-ghost:hover { background: #e2e8f0; }
        .btn-danger { background: var(--red); color: #fff; border: none; padding: 9px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-danger:hover { background: #dc2626; }

        /* Edit modal form */
        .edit-form { display: grid; gap: 14px; margin-top: 4px; }
        .edit-field label { display: block; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
        .edit-field input, .edit-field select {
            width: 100%; padding: 9px 12px;
            border: 1px solid var(--border); border-radius: 8px;
            font-size: 13px; font-family: inherit;
            background: var(--bg); transition: border-color .18s;
        }
        .edit-field input:focus, .edit-field select:focus { outline: none; border-color: var(--blue); background: #fff; }
        .edit-field input[readonly] { color: var(--muted); cursor: default; }
        .edit-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 6px; }

        @media (max-width: 768px) { .sidebar { display: none; } .main { margin-left: 0; } .filters { flex-direction: column; } }
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
        <li><a href="ADMIN_PRAYER_REQUEST.php" class="active">
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

<!-- ── Main ── -->
<main class="main">
    <div class="page-header">
        <h1>Prayer Requests</h1>
        <p>Manage all submitted prayer requests</p>
    </div>

    <div class="card">
        <div class="filters">
            <div class="search-wrap">
                <svg class="search-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Search by title or description…">
            </div>
            <select class="filter-sel" id="categoryFilter">
                <option value="">All Categories</option>
                <option value="Family">Family</option>
                <option value="Health">Health</option>
                <option value="Career">Career</option>
                <option value="Financial">Financial</option>
                <option value="Spiritual Growth">Spiritual Growth</option>
                <option value="Other">Other</option>
            </select>
            <select class="filter-sel" id="statusFilter">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Answered">Answered</option>
            </select>
            <span class="results-count" id="resultsCount"></span>
        </div>

        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="requestsTbody"></tbody>
            </table>
        </div>
    </div>
</main>

<!-- Notification / confirm modal -->
<div id="notifModal" class="modal">
    <div class="modal-box center">
        <div class="modal-ttl" id="notifTitle"></div>
        <div class="modal-msg"  id="notifMsg"></div>
        <div class="modal-btns">
            <button class="btn-ghost"   id="notifCancel" style="display:none;">Cancel</button>
            <button class="btn-primary" id="notifOk">OK</button>
        </div>
    </div>
</div>

<!-- Edit modal -->
<div id="editModal" class="modal">
    <div class="modal-box">
        <div class="modal-ttl" style="margin-bottom:16px;">Edit Prayer Request</div>
        <form class="edit-form" id="editForm">
            <div class="edit-field">
                <label>Title</label>
                <input id="eTitle" type="text" readonly>
            </div>
            <div class="edit-field">
                <label>Category</label>
                <!-- FIX: all 6 categories included, field is read-only display -->
                <input id="eCategory" type="text" readonly>
            </div>
            <div class="edit-field">
                <label>Status</label>
                <select id="eStatus" required>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Answered">Answered</option>
                </select>
            </div>
            <div class="edit-field">
                <label>Submitted</label>
                <input id="eCreated" type="text" readonly>
            </div>
            <div class="edit-actions">
                <button class="btn-ghost" type="button" id="editCancel">Cancel</button>
                <button class="btn-primary" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Category helpers ──────────────────────────────────────────────────────────
// FIX: all 6 categories mapped; exact match instead of .includes()
const CAT_CLASS = {
    'family':         'cat-family',
    'health':         'cat-health',
    'career':         'cat-career',
    'financial':      'cat-financial',
    'spiritual growth': 'cat-spiritual',
    'other':          'cat-other',
};
function categoryClass(cat) {
    return CAT_CLASS[String(cat || '').toLowerCase()] || 'cat-other';
}

function statusClass(s) {
    const k = String(s || '').toLowerCase();
    if (k === 'pending')     return 'b-pending';
    if (k === 'in progress') return 'b-progress';
    if (k === 'answered')    return 'b-answered';
    return 'b-pending';
}

// ── Modal (notify + confirm) ──────────────────────────────────────────────────
let _resolve = null, _mode = 'notify';

function showNotif(title, msg) {
    _mode = 'notify';
    document.getElementById('notifTitle').textContent  = title;
    document.getElementById('notifMsg').textContent    = msg;
    document.getElementById('notifCancel').style.display = 'none';
    document.getElementById('notifOk').textContent    = 'OK';
    document.getElementById('notifModal').classList.add('show');
}

function confirmNotif(title, msg, okLabel = 'Confirm') {
    _mode = 'confirm';
    document.getElementById('notifTitle').textContent  = title;
    document.getElementById('notifMsg').textContent    = msg;
    document.getElementById('notifCancel').style.display = '';
    document.getElementById('notifOk').textContent    = okLabel;
    document.getElementById('notifModal').classList.add('show');
    return new Promise(res => { _resolve = res; });
}

function closeNotif() { document.getElementById('notifModal').classList.remove('show'); }

document.getElementById('notifOk').addEventListener('click', () => {
    if (_mode === 'confirm' && _resolve) { _resolve(true); _resolve = null; }
    closeNotif();
});
document.getElementById('notifCancel').addEventListener('click', () => {
    if (_resolve) { _resolve(false); _resolve = null; }
    closeNotif();
});
document.getElementById('notifModal').addEventListener('click', e => {
    if (e.target === document.getElementById('notifModal')) {
        if (_resolve) { _resolve(false); _resolve = null; }
        closeNotif();
    }
});

// ── Edit modal ────────────────────────────────────────────────────────────────
let _editRow = null, _editId = null;

function openEdit(row) {
    _editRow = row;
    _editId  = row.dataset.id;
    document.getElementById('eTitle').value    = row.dataset.title    || '';
    document.getElementById('eCategory').value = row.dataset.category || '';
    document.getElementById('eStatus').value   = row.dataset.status   || 'Pending';
    document.getElementById('eCreated').value  = row.dataset.created  || '';
    document.getElementById('editModal').classList.add('show');
}

function closeEdit() {
    document.getElementById('editModal').classList.remove('show');
    _editRow = null; _editId = null;
}

document.getElementById('editCancel').addEventListener('click', closeEdit);
document.getElementById('editModal').addEventListener('click', e => {
    if (e.target === document.getElementById('editModal')) closeEdit();
});

document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!_editId || !_editRow) return;
    const newStatus = document.getElementById('eStatus').value;

    const fd = new FormData();
    fd.append('id', _editId);
    fd.append('status', newStatus);

    const res  = await fetch('api.php?action=update_status', { method:'POST', body:fd });
    const json = await res.json();

    if (!res.ok || json.status !== 'success') {
        closeEdit();
        showNotif('Error', json.message || 'Failed to update status');
        return;
    }

    // Update DOM in-place
    const statusEl = _editRow.querySelector('.status-badge');
    if (statusEl) {
        statusEl.textContent = newStatus;
        statusEl.className   = 'badge status-badge ' + statusClass(newStatus);
    }
    _editRow.dataset.status = newStatus;
    closeEdit();
    showNotif('Updated', 'Status updated successfully.');
    updateResultsCount();
});

// ── Load requests ─────────────────────────────────────────────────────────────
let _allRows = [];

async function loadRequests() {
    const tbody = document.getElementById('requestsTbody');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:28px;color:var(--muted);">Loading…</td></tr>';

    const res  = await fetch('api.php?action=list_requests');
    const json = await res.json();

    if (!res.ok || json.status !== 'success') {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:28px;color:var(--red);">${json.message || 'Failed to load'}</td></tr>`;
        return;
    }

    const items = json.data || [];
    if (!items.length) {
        tbody.innerHTML = `<tr><td colspan="5">
            <div class="empty">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                <p>No prayer requests found.</p>
            </div>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(r => `
        <tr data-id="${r.id}"
            data-title="${String(r.title).replace(/"/g,'&quot;')}"
            data-category="${r.category}"
            data-status="${r.status}"
            data-created="${r.created}">
            <td>
                <div class="req-title" data-field="title">${r.title}</div>
                <div class="req-desc">${r.description}</div>
            </td>
            <td><span class="cat ${categoryClass(r.category)}" data-field="category">${r.category}</span></td>
            <td><span class="badge status-badge ${statusClass(r.status)}" data-field="status">${r.status}</span></td>
            <td class="tbl-date">${r.created}</td>
            <td>
                <div class="actions">
                    <button class="act-btn edit-btn" data-action="edit" title="Edit status">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="act-btn del-btn" data-action="delete" title="Delete request">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                </div>
            </td>
        </tr>`).join('');

    _allRows = Array.from(tbody.querySelectorAll('tr'));
    updateResultsCount();
}

// ── Filter — FIXED ────────────────────────────────────────────────────────────
// FIX 1: Uses dataset values for exact matching (no false substring hits)
// FIX 2: Category comparison is exact, case-insensitive
// FIX 3: "All Categories" / "All Statuses" use empty string sentinel (not text match)
function filterTable() {
    const search   = document.getElementById('searchInput').value.trim().toLowerCase();
    const catVal   = document.getElementById('categoryFilter').value.toLowerCase();  // empty = all
    const statVal  = document.getElementById('statusFilter').value.toLowerCase();    // empty = all

    let visible = 0;
    _allRows.forEach(row => {
        const title    = (row.querySelector('[data-field="title"]')?.textContent    || '').toLowerCase();
        const desc     = (row.querySelector('.req-desc')?.textContent               || '').toLowerCase();
        const category = (row.dataset.category || '').toLowerCase();
        const status   = (row.dataset.status   || '').toLowerCase();

        // FIX: exact match on category and status (not substring)
        const matchSearch = !search  || title.includes(search) || desc.includes(search);
        const matchCat    = !catVal  || category === catVal;
        const matchStat   = !statVal || status   === statVal;

        const show = matchSearch && matchCat && matchStat;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    updateResultsCount(visible);
}

function updateResultsCount(count) {
    const el    = document.getElementById('resultsCount');
    const total = _allRows.length;
    if (count === undefined) count = _allRows.filter(r => r.style.display !== 'none').length;
    el.textContent = count === total ? `${total} request${total !== 1 ? 's' : ''}` : `${count} of ${total}`;
}

document.getElementById('searchInput').addEventListener('input',  filterTable);
document.getElementById('categoryFilter').addEventListener('change', filterTable);
document.getElementById('statusFilter').addEventListener('change',   filterTable);

// ── Row actions (delegated) ───────────────────────────────────────────────────
document.getElementById('requestsTbody').addEventListener('click', async function(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const row = btn.closest('tr');
    if (!row) return;

    if (btn.dataset.action === 'edit') {
        openEdit(row);
        return;
    }

    if (btn.dataset.action === 'delete') {
        const ok = await confirmNotif(
            'Delete Request',
            'Are you sure you want to permanently delete this prayer request?',
            'Delete'
        );
        if (!ok) return;

        const fd = new FormData();
        fd.append('id', row.dataset.id);
        const res  = await fetch('api.php?action=delete_request', { method:'POST', body:fd });
        const json = await res.json();

        if (!res.ok || json.status !== 'success') {
            showNotif('Error', json.message || 'Failed to delete request');
            return;
        }

        _allRows = _allRows.filter(r => r !== row);
        row.remove();
        showNotif('Deleted', 'Prayer request removed successfully.');
        updateResultsCount();
    }
});

loadRequests();
</script>
</body>
</html>