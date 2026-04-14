<?php
// appointments.php - Manage Appointments
include 'config.php';

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM appointments WHERE id=$id");
    header("Location: appointments.php?msg=deleted");
    exit();
}

// UPDATE STATUS
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $status = in_array($_GET['status'], ['scheduled','completed','cancelled']) ? $_GET['status'] : 'scheduled';
    $conn->query("UPDATE appointments SET status='$status' WHERE id=$id");
    header("Location: appointments.php?msg=updated");
    exit();
}

// ADD
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)$_POST['patient_id'];
    $doctor_id  = (int)$_POST['doctor_id'];
    $date       = $conn->real_escape_string($_POST['appointment_date']);
    $time       = $conn->real_escape_string($_POST['appointment_time']);
    $reason     = $conn->real_escape_string(trim($_POST['reason']));

    $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason)
            VALUES ($patient_id, $doctor_id, '$date', '$time', '$reason')";

    if (!$conn->query($sql)) $error = $conn->error;
    else { header("Location: appointments.php?msg=booked"); exit(); }
}

if (isset($_GET['msg'])) $success = htmlspecialchars($_GET['msg']);

// Fetch dropdowns
$all_patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name");
$all_doctors  = $conn->query("SELECT id, full_name, specialization FROM doctors WHERE status='active' ORDER BY full_name");

// Fetch appointments with filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where  = '';
if ($filter === 'today') $where = "WHERE a.appointment_date = CURDATE()";
elseif ($filter === 'scheduled') $where = "WHERE a.status = 'scheduled'";
elseif ($filter === 'completed') $where = "WHERE a.status = 'completed'";

$appointments = $conn->query("
    SELECT a.*, p.full_name AS patient_name, d.full_name AS doctor_name, d.specialization
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    $where
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DoctorHub | Appointments</title>
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
.sidebar { width:240px; min-height:100vh; background:var(--surface); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; }
.logo { padding:28px 24px 20px; border-bottom:1px solid var(--border); }
.logo-text { font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--teal); }
.logo-sub { font-size:11px; color:var(--muted); margin-top:2px; letter-spacing:1px; text-transform:uppercase; }
.nav { padding:20px 12px; flex:1; }
.nav-label { font-size:10px; color:var(--muted); letter-spacing:1.5px; text-transform:uppercase; padding:8px 12px 4px; }
.nav a { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; color:var(--muted); text-decoration:none; font-size:14px; font-weight:500; margin-bottom:2px; transition:all 0.2s; }
.nav a:hover, .nav a.active { background:var(--teal-dim); color:var(--teal); }
.nav a.active { font-weight:600; }
.icon { font-size:16px; width:20px; text-align:center; }
.sidebar-footer { padding:16px; border-top:1px solid var(--border); font-size:12px; color:var(--muted); text-align:center; }
.main { margin-left:240px; flex:1; padding:32px; }
.topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; }
.page-title { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; }
.page-title span { color:var(--teal); }
.form-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:24px; margin-bottom:28px; }
.form-card h3 { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; margin-bottom:18px; color:var(--teal); }
.form-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.8px; }
.form-group input, .form-group select, .form-group textarea {
    background:var(--surface2); border:1px solid var(--border); border-radius:8px;
    padding:10px 14px; color:var(--text); font-size:14px; outline:none; transition:0.2s;
    font-family:'DM Sans',sans-serif;
}
.form-group textarea { resize:vertical; min-height:60px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--teal); box-shadow:0 0 0 3px var(--teal-dim); }
.form-group select option { background:var(--surface2); }
.form-actions { margin-top:18px; }
.btn { padding:10px 22px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s; border:none; font-family:'DM Sans',sans-serif; }
.btn-teal { background:var(--teal); color:#0a0f1e; }
.btn-teal:hover { background:#00bfa6; }

/* FILTER TABS */
.filter-tabs { display:flex; gap:8px; margin-bottom:16px; }
.filter-tab { padding:8px 18px; border-radius:20px; font-size:13px; font-weight:600; text-decoration:none; color:var(--muted); background:var(--surface); border:1px solid var(--border); transition:0.2s; }
.filter-tab:hover, .filter-tab.active { background:var(--teal-dim); color:var(--teal); border-color:rgba(0,212,184,0.3); }

.table-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
table { width:100%; border-collapse:collapse; }
th { padding:12px 16px; text-align:left; font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:1px; font-weight:600; background:var(--surface2); }
td { padding:13px 16px; font-size:14px; border-top:1px solid var(--border); }
tr:hover td { background:rgba(255,255,255,0.02); }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-scheduled { background:rgba(245,158,11,0.15); color:var(--gold); }
.badge-completed { background:rgba(34,197,94,0.15); color:var(--success); }
.badge-cancelled { background:rgba(239,68,68,0.15); color:var(--danger); }
.action-btns { display:flex; gap:6px; flex-wrap:wrap; }
.btn-sm { padding:4px 10px; font-size:11px; border-radius:6px; text-decoration:none; display:inline-flex; align-items:center; transition:0.2s; }
.btn-done { background:rgba(34,197,94,0.1); color:var(--success); border:1px solid rgba(34,197,94,0.2); }
.btn-cancel { background:rgba(245,158,11,0.1); color:var(--gold); border:1px solid rgba(245,158,11,0.2); }
.btn-del { background:rgba(239,68,68,0.1); color:var(--danger); border:1px solid rgba(239,68,68,0.2); }
.alert { padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px; }
.alert-success { background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.2); color:var(--success); }
.empty { text-align:center; padding:40px; color:var(--muted); }
</style>
</head>
<body>
<aside class="sidebar">
    <div class="logo"><div class="logo-text">DoctorHub</div><div class="logo-sub">Hospital Management</div></div>
    <nav class="nav">
        <div class="nav-label">Main</div>
        <a href="index.php"><span class="icon">🏠</span> Dashboard</a>
        <a href="doctors.php"><span class="icon">👨‍⚕️</span> Doctors</a>
        <a href="patients.php"><span class="icon">🧑‍🤝‍🧑</span> Patients</a>
        <a href="appointments.php" class="active"><span class="icon">📅</span> Appointments</a>
    </nav>
    <div class="sidebar-footer">DoctorHub &nbsp;|&nbsp; PHP + MySQL</div>
</aside>

<main class="main">
    <div class="topbar">
        <div class="page-title">Manage <span>Appointments</span></div>
    </div>

    <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert" style="background:rgba(239,68,68,0.1);color:var(--danger);">❌ <?= $error ?></div><?php endif; ?>

    <div class="form-card">
        <h3>📋 Book New Appointment</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Patient</label>
                    <select name="patient_id" required>
                        <option value="">-- Select Patient --</option>
                        <?php $all_patients->data_seek(0); while ($p = $all_patients->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Doctor</label>
                    <select name="doctor_id" required>
                        <option value="">-- Select Doctor --</option>
                        <?php $all_doctors->data_seek(0); while ($d = $all_doctors->fetch_assoc()): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?> (<?= $d['specialization'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="appointment_date" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" name="appointment_time" required>
                </div>
                <div class="form-group full">
                    <label>Reason / Notes</label>
                    <textarea name="reason" placeholder="Describe the reason for appointment..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-teal">📅 Book Appointment</button>
            </div>
        </form>
    </div>

    <!-- FILTER TABS -->
    <div class="filter-tabs">
        <a href="appointments.php?filter=all"       class="filter-tab <?= $filter==='all' ? 'active' : '' ?>">All</a>
        <a href="appointments.php?filter=today"     class="filter-tab <?= $filter==='today' ? 'active' : '' ?>">Today</a>
        <a href="appointments.php?filter=scheduled" class="filter-tab <?= $filter==='scheduled' ? 'active' : '' ?>">Scheduled</a>
        <a href="appointments.php?filter=completed" class="filter-tab <?= $filter==='completed' ? 'active' : '' ?>">Completed</a>
    </div>

    <div class="table-card">
        <?php if ($appointments && $appointments->num_rows > 0): ?>
        <table>
            <thead><tr><th>#</th><th>Patient</th><th>Doctor</th><th>Specialization</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $i=1; while ($a = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($a['patient_name']) ?></td>
                    <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($a['specialization']) ?></td>
                    <td><?= date('d M Y', strtotime($a['appointment_date'])) ?></td>
                    <td><?= date('h:i A', strtotime($a['appointment_time'])) ?></td>
                    <td><?= htmlspecialchars($a['reason'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td>
                        <div class="action-btns">
                            <?php if ($a['status'] === 'scheduled'): ?>
                                <a href="appointments.php?status=completed&id=<?= $a['id'] ?>" class="btn-sm btn-done">✔ Done</a>
                                <a href="appointments.php?status=cancelled&id=<?= $a['id'] ?>" class="btn-sm btn-cancel"
                                   onclick="return confirm('Cancel this appointment?')">✕ Cancel</a>
                            <?php endif; ?>
                            <a href="appointments.php?delete=<?= $a['id'] ?>" class="btn-sm btn-del"
                               onclick="return confirm('Delete this record?')">🗑</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?><div class="empty">No appointments found.</div><?php endif; ?>
    </div>
</main>
</body>
</html>