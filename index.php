<?php
// index.php - Main Dashboard
include 'config.php';

// Fetch counts
$total_doctors      = $conn->query("SELECT COUNT(*) AS c FROM doctors WHERE status='active'")->fetch_assoc()['c'];
$total_patients     = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];
$today_appointments = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE appointment_date = CURDATE()")->fetch_assoc()['c'];
$completed          = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='completed'")->fetch_assoc()['c'];

// Fetch today's appointments with joins
$today_appts = $conn->query("
    SELECT a.*, p.full_name AS patient_name, d.full_name AS doctor_name, d.specialization
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DoctorHub | Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
    /* Backgrounds */
    --bg: #f0f4f8;           /* Cool light grey page bg */
    --surface: #ffffff;       /* Card/sidebar white */
    --surface2: #f7fafc;      /* Table header, input bg */

    /* Brand Colors */
    --teal: #0077b6;          /* Trust blue — primary action */
    --teal-dim: rgba(0, 119, 182, 0.08);  /* Hover highlight */
    --teal-glow: 0 0 20px rgba(0, 119, 182, 0.1);

    /* Accent */
    --gold: #f4a261;          /* Warm amber — warnings */

    /* Text */
    --text: #1a202c;          /* Near-black main text */
    --muted: #718096;         /* Grey secondary text */

    /* Borders */
    --border: rgba(0, 0, 0, 0.08);  /* Soft dividers */

    /* Status */
    --danger: #e53e3e;        /* Red — cancelled */
    --success: #2f855a;       /* Green — completed */
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; }

/* SIDEBAR */
.sidebar {
    width: 240px; min-height:100vh; background:var(--surface);
    border-right:1px solid var(--border); padding:0; display:flex; flex-direction:column;
    position:fixed; top:0; left:0; bottom:0; z-index:100;
}
.logo {
    padding: 28px 24px 20px;
    border-bottom: 1px solid var(--border);
}
.logo-text { font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--teal); letter-spacing:-0.5px; }
.logo-sub { font-size:11px; color:var(--muted); margin-top:2px; letter-spacing:1px; text-transform:uppercase; }

.nav { padding: 20px 12px; flex:1; }
.nav-label { font-size:10px; color:var(--muted); letter-spacing:1.5px; text-transform:uppercase; padding:8px 12px 4px; }
.nav a {
    display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px;
    color:var(--muted); text-decoration:none; font-size:14px; font-weight:500; margin-bottom:2px;
    transition:all 0.2s;
}
.nav a:hover { background:var(--teal-dim); color:var(--teal); }
.nav a.active { background:var(--teal-dim); color:var(--teal); font-weight:600; }
.nav a .icon { font-size:16px; width:20px; text-align:center; }

.sidebar-footer {
    padding:16px; border-top:1px solid var(--border); font-size:12px; color:var(--muted);
    text-align:center;
}

/* MAIN CONTENT */
.main { margin-left:240px; flex:1; padding:32px; }

.topbar {
    display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;
}
.page-title { font-family:'Syne',sans-serif; font-size:28px; font-weight:800; }
.page-title span { color:var(--teal); }
.date-badge {
    background:var(--surface); border:1px solid var(--border); padding:8px 16px;
    border-radius:20px; font-size:13px; color:var(--muted);
}

/* STAT CARDS */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:32px; }
.stat-card {
    background:var(--surface); border:1px solid var(--border); border-radius:16px;
    padding:20px; position:relative; overflow:hidden; transition:transform 0.2s;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:var(--teal-glow); }
.stat-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:2px;
    background:var(--accent, var(--teal));
}
.stat-card.gold { --accent: var(--gold); }
.stat-card.green { --accent: var(--success); }
.stat-card.red { --accent: var(--danger); }

.stat-icon { font-size:28px; margin-bottom:12px; }
.stat-number { font-family:'Syne',sans-serif; font-size:36px; font-weight:800; color:var(--text); }
.stat-label { font-size:13px; color:var(--muted); margin-top:4px; }

/* SECTION */
.section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.section-title { font-family:'Syne',sans-serif; font-size:18px; font-weight:700; }
.btn {
    padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;
    text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s;
    border:none;
}
.btn-teal { background:var(--teal); color:#0a0f1e; }
.btn-teal:hover { background:#00bfa6; }
.btn-outline { background:transparent; border:1px solid var(--border); color:var(--muted); }
.btn-outline:hover { border-color:var(--teal); color:var(--teal); }

/* TABLE */
.table-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
table { width:100%; border-collapse:collapse; }
thead tr { background:var(--surface2); }
th { padding:14px 16px; text-align:left; font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:1px; font-weight:600; }
td { padding:14px 16px; font-size:14px; border-top:1px solid var(--border); }
tr:hover td { background:rgba(255,255,255,0.02); }

.badge {
    display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;
}
.badge-scheduled { background:rgba(245,158,11,0.15); color:var(--gold); }
.badge-completed { background:rgba(34,197,94,0.15); color:var(--success); }
.badge-cancelled { background:rgba(239,68,68,0.15); color:var(--danger); }

.empty { text-align:center; padding:40px; color:var(--muted); font-size:14px; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <div class="logo-text">DoctorHub</div>
        <div class="logo-sub">Hospital Management</div>
    </div>
    <nav class="nav">
        <div class="nav-label">Main</div>
        <a href="index.php" class="active"><span class="icon">🏠</span> Dashboard</a>
        <a href="doctors.php"><span class="icon">👨‍⚕️</span> Doctors</a>
        <a href="patients.php"><span class="icon">🧑‍🤝‍🧑</span> Patients</a>
        <a href="appointments.php"><span class="icon">📅</span> Appointments</a>
    </nav>
    <div class="sidebar-footer">DoctorHub &nbsp;|&nbsp; PHP + MySQL</div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="topbar">
        <div class="page-title">Hello, <span>Ashutosh</span>👋</div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👨‍⚕️</div>
            <div class="stat-number"><?= $total_doctors ?></div>
            <div class="stat-label">Active Doctors</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon">🧑‍🤝‍🧑</div>
            <div class="stat-number"><?= $total_patients ?></div>
            <div class="stat-label">Registered Patients</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">📅</div>
            <div class="stat-number"><?= $today_appointments ?></div>
            <div class="stat-label">Today's Appointments</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">✅</div>
            <div class="stat-number"><?= $completed ?></div>
            <div class="stat-label">Completed Visits</div>
        </div>
    </div>

    <!-- TODAY'S APPOINTMENTS -->
    <div class="section-header">
        <div class="section-title">Today's Appointments</div>
        <a href="appointments.php" class="btn btn-teal">+ New Appointment</a>
    </div>
    <div class="table-card">
        <?php if ($today_appts && $today_appts->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Patient</th><th>Doctor</th>
                    <th>Specialization</th><th>Time</th><th>Reason</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php $i=1; while ($row = $today_appts->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                    <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($row['specialization']) ?></td>
                    <td><?= date('h:i A', strtotime($row['appointment_time'])) ?></td>
                    <td><?= htmlspecialchars($row['reason'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty">🗓️ No appointments scheduled for today.</div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>