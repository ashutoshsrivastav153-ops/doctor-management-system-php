<?php
// patients.php - Manage Patients
include 'config.php';

$success = $error = '';

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM patients WHERE id=$id");
    header("Location: patients.php?msg=deleted");
    exit();
}

// ADD / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $conn->real_escape_string(trim($_POST['full_name']));
    $email   = $conn->real_escape_string(trim($_POST['email']));
    $phone   = $conn->real_escape_string(trim($_POST['phone']));
    $dob     = $conn->real_escape_string($_POST['dob']);
    $gender  = $conn->real_escape_string($_POST['gender']);
    $address = $conn->real_escape_string(trim($_POST['address']));

    if (isset($_POST['edit_id']) && $_POST['edit_id'] > 0) {
        $id  = (int)$_POST['edit_id'];
        $sql = "UPDATE patients SET full_name='$name', email='$email', phone='$phone',
                dob='$dob', gender='$gender', address='$address' WHERE id=$id";
        $msg = 'Patient updated!';
    } else {
        $sql = "INSERT INTO patients (full_name, email, phone, dob, gender, address)
                VALUES ('$name','$email','$phone','$dob','$gender','$address')";
        $msg = 'Patient registered!';
    }

    if (!$conn->query($sql)) $error = $conn->error;
    else { header("Location: patients.php?msg=" . urlencode($msg)); exit(); }
}

if (isset($_GET['msg'])) $success = htmlspecialchars($_GET['msg']);

$patients = $conn->query("SELECT * FROM patients ORDER BY created_at DESC");

$edit_p = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_p = $conn->query("SELECT * FROM patients WHERE id=$eid")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DoctorHub | Patients</title>
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
/* Sidebar */
.sidebar { background: #6b46c1; }
/* Sidebar */
.sidebar { background: #1a7a4a; }
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
.form-group textarea { resize:vertical; min-height:70px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--teal); box-shadow:0 0 0 3px var(--teal-dim); }
.form-group select option { background:var(--surface2); }
.form-actions { margin-top:18px; display:flex; gap:10px; }
.btn { padding:10px 22px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s; border:none; font-family:'DM Sans',sans-serif; }
.btn-teal { background:var(--teal); color:#0a0f1e; }
.btn-teal:hover { background:#00bfa6; }
.btn-outline { background:transparent; border:1px solid var(--border); color:var(--muted); }
.table-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
.table-header { padding:16px 20px; border-bottom:1px solid var(--border); }
.table-header span { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; }
table { width:100%; border-collapse:collapse; }
th { padding:12px 16px; text-align:left; font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:1px; font-weight:600; background:var(--surface2); }
td { padding:13px 16px; font-size:14px; border-top:1px solid var(--border); }
tr:hover td { background:rgba(255,255,255,0.02); }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-male { background:rgba(0,212,184,0.12); color:var(--teal); }
.badge-female { background:rgba(245,158,11,0.12); color:var(--gold); }
.badge-other { background:rgba(100,116,139,0.2); color:var(--muted); }
.action-btns { display:flex; gap:8px; }
.btn-sm { padding:5px 12px; font-size:12px; border-radius:6px; }
.btn-edit { background:var(--teal-dim); color:var(--teal); border:1px solid rgba(0,212,184,0.2); cursor:pointer; text-decoration:none; }
.btn-del { background:rgba(239,68,68,0.1); color:var(--danger); border:1px solid rgba(239,68,68,0.2); cursor:pointer; text-decoration:none; }
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
        <a href="patients.php" class="active"><span class="icon">🧑‍🤝‍🧑</span> Patients</a>
        <a href="appointments.php"><span class="icon">📅</span> Appointments</a>
    </nav>
    <div class="sidebar-footer">DoctorHub &nbsp;|&nbsp; PHP + MySQL</div>
</aside>

<main class="main">
    <div class="topbar">
        <div class="page-title">Manage <span>Patients</span></div>
    </div>

    <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert" style="background:rgba(239,68,68,0.1);color:var(--danger);">❌ <?= $error ?></div><?php endif; ?>

    <div class="form-card">
        <h3><?= $edit_p ? '✏️ Edit Patient' : '➕ Register Patient' ?></h3>
        <form method="POST">
            <?php if ($edit_p): ?><input type="hidden" name="edit_id" value="<?= $edit_p['id'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required placeholder="Patient full name"
                        value="<?= htmlspecialchars($edit_p['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="patient@email.com"
                        value="<?= htmlspecialchars($edit_p['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="98XXXXXXXX"
                        value="<?= htmlspecialchars($edit_p['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?= $edit_p['dob'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="">-- Select --</option>
                        <option value="Male" <?= ($edit_p['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($edit_p['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($edit_p['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" placeholder="City, State"
                        value="<?= htmlspecialchars($edit_p['address'] ?? '') ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-teal"><?= $edit_p ? '💾 Update' : '➕ Register' ?></button>
                <?php if ($edit_p): ?><a href="patients.php" class="btn btn-outline">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="table-header"><span>All Patients</span></div>
        <?php if ($patients && $patients->num_rows > 0): ?>
        <table>
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>DOB</th><th>Gender</th><th>Address</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $i=1; while ($p = $patients->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                    <td><?= htmlspecialchars($p['email'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                    <td><?= $p['dob'] ? date('d M Y', strtotime($p['dob'])) : '—' ?></td>
                    <td><?php if($p['gender']): ?><span class="badge badge-<?= strtolower($p['gender']) ?>"><?= $p['gender'] ?></span><?php else: ?>—<?php endif; ?></td>
                    <td><?= htmlspecialchars($p['address'] ?? '—') ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="patients.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-edit">Edit</a>
                            <a href="patients.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-del"
                               onclick="return confirm('Delete this patient?')">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?><div class="empty">No patients found.</div><?php endif; ?>
    </div>
</main>
</body>
</html>