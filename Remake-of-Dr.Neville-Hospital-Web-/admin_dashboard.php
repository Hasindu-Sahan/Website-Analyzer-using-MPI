<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit; }
$conn = new mysqli("localhost", "root", "", "hospital_db");

// --- 1. DOCTOR ACTIONS ---
if (isset($_POST['save_doctor'])) {
    $name = $_POST['doc_name']; $spec = $_POST['specialization']; $fee = $_POST['fee'];
    if (!empty($_POST['doc_id'])) {
        $id = $_POST['doc_id'];
        $conn->query("UPDATE doctors SET doctor_name='$name', specialization='$spec', fee='$fee' WHERE doctor_id=$id");
    } else {
        $conn->query("INSERT INTO doctors (doctor_name, specialization, fee) VALUES ('$name', '$spec', '$fee')");
    }
    header("Location: admin_dashboard.php?view=doctors");
}
if (isset($_GET['delete_doc'])) {
    $id = $_GET['delete_doc'];
    $conn->query("DELETE FROM doctors WHERE doctor_id = $id");
    header("Location: admin_dashboard.php?view=doctors");
}

// --- 2. PATIENT ACTIONS ---
if (isset($_POST['save_patient'])) {
    $name = $_POST['p_name']; $nic = $_POST['p_nic']; $contact = $_POST['p_contact']; $email = $_POST['p_email'];
    if (!empty($_POST['p_id'])) {
        $id = $_POST['p_id'];
        $conn->query("UPDATE patients SET patient_name='$name', nic='$nic', contact='$contact', email='$email' WHERE patient_id=$id");
    } else {
        $conn->query("INSERT INTO patients (patient_name, nic, contact, email) VALUES ('$name', '$nic', '$contact', '$email')");
    }
    header("Location: admin_dashboard.php?view=patients");
}
if (isset($_GET['delete_patient'])) {
    $id = intval($_GET['delete_patient']);
    
    // First, delete all appointments associated with this patient
    $conn->query("DELETE FROM appointments WHERE patient_id = $id");
    
    // Then, delete the patient
    $conn->query("DELETE FROM patients WHERE patient_id = $id");
    
    header("Location: admin_dashboard.php?view=patients");
    exit;
}

// --- 3. LAB REPORT ACTIONS ---
if (isset($_POST['save_report'])) {
    $p_id = $_POST['report_p_id']; 
    $r_name = $_POST['report_name']; 
    $r_date = $_POST['report_date'];
    
    // 1. Specify the directory
    $target_dir = "Laboratory Reports/";
    
    // 2. Create the directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_path = $_POST['existing_path']; 

    if (!empty($_FILES["report_file"]["name"])) {
        // 3. Clean the filename to remove spaces
        $file_name = time() . "_" . str_replace(' ', '_', basename($_FILES["report_file"]["name"]));
        $target_file = $target_dir . $file_name;

        // 4. Try to move the file
        if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
            $file_path = $target_file;
            echo "<script>alert('File successfully stored in " . $target_file . "');</script>";
        } else {
            echo "<script>alert('Error: Could not move file to folder. Check folder permissions.');</script>";
        }
    }
    if (!empty($_POST['report_id'])) {
        $rid = $_POST['report_id'];
        $conn->query("UPDATE lab_reports SET patient_id='$p_id', report_name='$r_name', test_date='$r_date', file_path='$file_path' WHERE report_id=$rid");
    } else {
        $conn->query("INSERT INTO lab_reports (patient_id, report_name, test_date, file_path) VALUES ('$p_id', '$r_name', '$r_date', '$file_path')");
    }
    header("Location: admin_dashboard.php?view=reports");
}
if (isset($_GET['delete_report'])) {
    $id = $_GET['delete_report'];
    $res = $conn->query("SELECT file_path FROM lab_reports WHERE report_id=$id");
    $row = $res->fetch_assoc();
    if (file_exists($row['file_path'])) unlink($row['file_path']); // Delete physical file
    $conn->query("DELETE FROM lab_reports WHERE report_id = $id");
    header("Location: admin_dashboard.php?view=reports");
}

// --- 4. NEWS ACTIONS ---
if (isset($_POST['save_news'])) {
    $title = $conn->real_escape_string($_POST['news_title']);
    $category = $_POST['news_category'];
    $date = $_POST['news_date'];
    $caption = $conn->real_escape_string($_POST['news_caption']);
    $details = $conn->real_escape_string($_POST['news_details']);
    
    $target_dir = "uploads/news/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $image_path = $_POST['existing_img'];
    if (!empty($_FILES["news_img"]["name"])) {
        $image_path = $target_dir . time() . "_" . basename($_FILES["news_img"]["name"]);
        move_uploaded_file($_FILES["news_img"]["tmp_name"], $image_path);
    }

    if (!empty($_POST['news_id'])) {
        $id = $_POST['news_id'];
        $conn->query("UPDATE hospital_news SET title='$title', category='$category', news_date='$date', image_path='$image_path', caption='$caption', full_details='$details' WHERE id=$id");
    } else {
        $conn->query("INSERT INTO hospital_news (title, category, news_date, image_path, caption, full_details) VALUES ('$title', '$category', '$date', '$image_path', '$caption', '$details')");
    }
    header("Location: admin_dashboard.php?view=news");
}

if (isset($_GET['delete_news'])) {
    $id = $_GET['delete_news'];
    $res = $conn->query("SELECT image_path FROM hospital_news WHERE id=$id");
    $row = $res->fetch_assoc();
    if (file_exists($row['image_path'])) unlink($row['image_path']);
    $conn->query("DELETE FROM hospital_news WHERE id = $id");
    header("Location: admin_dashboard.php?view=news");
}

// --- 5. FEEDBACK ACTIONS ---
if (isset($_GET['delete_feedback'])) {
    $id = $_GET['delete_feedback'];
    $conn->query("DELETE FROM contact_inquiries WHERE id = $id");
    header("Location: admin_dashboard.php?view=feedback");
    exit;
}

// --- 6. APPOINTMENT ACTIONS ---
if (isset($_POST['save_appointment'])) {
    $p_id = $_POST['app_p_id']; $d_id = $_POST['app_d_id']; 
    $date = $_POST['app_date']; $time = $_POST['app_time']; $fee = $_POST['app_fee'];
    if (!empty($_POST['app_id'])) {
        $id = $_POST['app_id'];
        $conn->query("UPDATE appointments SET patient_id='$p_id', doctor_id='$d_id', appointment_date='$date', time_slot='$time', fee='$fee' WHERE appointment_id=$id");
    } else {
        $conn->query("INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, fee, status) VALUES ('$p_id', '$d_id', '$date', '$time', '$fee', 'Confirmed')");
    }
    header("Location: admin_dashboard.php?view=appointments");
}
if (isset($_GET['cancel_app'])) {
    $id = $_GET['cancel_app'];
    $conn->query("UPDATE appointments SET status='Cancelled' WHERE appointment_id = $id");
    header("Location: admin_dashboard.php?view=appointments");
}

// --- 7. DOCTOR TIME SLOT ACTIONS ---
if (isset($_POST['add_timeslot'])) {
    $d_id = $_POST['slot_doc_id']; 
    $slot = $_POST['slot_time'];
    
    if (!empty($_POST['slot_id'])) {
        // Update existing slot
        $id = $_POST['slot_id'];
        $conn->query("UPDATE doctor_times SET doctor_id='$d_id', time_slot='$slot' WHERE time_id=$id");
    } else {
        // Insert new slot
        $conn->query("INSERT INTO doctor_times (doctor_id, time_slot) VALUES ('$d_id', '$slot')");
    }
    header("Location: admin_dashboard.php?view=timeslots");
}
if (isset($_GET['delete_slot'])) {
    $id = $_GET['delete_slot'];
    $conn->query("DELETE FROM doctor_times WHERE time_id = $id");
    header("Location: admin_dashboard.php?view=timeslots");
}

// --- Update Appointment to Completed ---
if (isset($_GET['complete_app'])) {
    $id = intval($_GET['complete_app']);
    $conn->query("UPDATE appointments SET status='Completed' WHERE appointment_id = $id");
    header("Location: admin_dashboard.php?view=appointments");
    exit;
}

// --- Update Appointment to Pending ---
if (isset($_GET['pending_app'])) {
    $id = intval($_GET['pending_app']);
    $conn->query("UPDATE appointments SET status='Pending' WHERE appointment_id = $id");
    header("Location: admin_dashboard.php?view=appointments");
    exit;
}

// --- RESET/REDO APPOINTMENT ACTION ---
if (isset($_GET['set_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = $_GET['set_status']; // Expecting 'Pending' or 'Completed'
    
    // Validate status to prevent SQL injection or database errors
    if (in_array($status, ['Pending', 'Completed'])) {
        $conn->query("UPDATE appointments SET status='$status' WHERE appointment_id = $id");
    }
    header("Location: admin_dashboard.php?view=appointments");
    exit;
}

if (isset($_POST['save_appointment'])) {
    // ... your existing variable extractions ...
    if (!empty($_POST['app_id'])) {
        // Update existing
        $conn->query("UPDATE appointments SET patient_id='$p_id', doctor_id='$d_id', appointment_date='$date', time_slot='$time', fee='$fee' WHERE appointment_id=$id");
    } else {
        // Insert new - Defaulting to Pending
        $conn->query("INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, fee, status) 
                      VALUES ('$p_id', '$d_id', '$date', '$time', '$fee', 'Pending')");
    }
    header("Location: admin_dashboard.php?view=appointments");
}

// --- DELETE APPOINTMENT ACTION ---
if (isset($_GET['delete_app'])) {
    $id = intval($_GET['delete_app']);
    // This permanently removes the record from the database
    $conn->query("DELETE FROM appointments WHERE appointment_id = $id");
    header("Location: admin_dashboard.php?view=appointments");
    exit;
}

// Determine which section to show
$view = isset($_GET['view']) ? $_GET['view'] : 'doctors';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NFTH Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root { --primary: #2ecc71; --dark: #2c3e50; --danger: #e74c3c; --blue: #3498db; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; height: 100vh; overflow: hidden;}
        
        /* Sidebar */
        .sidebar { width: 280px; background: var(--dark); color: white; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { color: var(--primary); text-align: center; margin-bottom: 30px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 15px; border-radius: 5px; margin-bottom: 10px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; }
        .sidebar a i { margin-right: 10px; width: 20px; }

        /* Content Area */
        .main-container { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        header { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .content { padding: 40px; }

        /* Components */
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th { background: #f8f9fa; color: #7f8c8d; text-align: left; padding: 12px; border-bottom: 2px solid #eee; }
        .data-table td { padding: 12px; border-bottom: 1px solid #eee; }
        
        form input, form select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px; width: 100%; box-sizing: border-box; }
        .btn { border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; text-decoration: none; font-weight: 600; font-size: 14px;}
        .btn-green { background: var(--primary); color: white; }
        .btn-blue { background: var(--blue); color: white; }
        .btn-red { background: var(--danger); color: white; }
        .action-btns { display: flex; gap: 5px; }

        /* Container for action buttons to keep them side-by-side */
        .actions-cell {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        /* Edit button style matching your other management sections */
        .btn-edit {
            background-color: #f59e0b; /* Amber/Yellow color used in your dashboard */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 14px;
        }

        .btn-edit:hover {
            background-color: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: white;
        }

        /* Ensure icons inside buttons are centered */
        .btn-edit i, .btn-delete i {
            margin: 0;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>NFTH Admin</h2>
        <a href="?view=doctors" class="<?= $view=='doctors'?'active':'' ?>"><i class="fas fa-user-md"></i> Doctor Management</a>
        <a href="?view=timeslots" class="<?= $view=='timeslots'?'active':'' ?>"><i class="fas fa-clock"></i> Time Slots Management</a>
        <a href="?view=patients" class="<?= $view=='patients'?'active':'' ?>"><i class="fas fa-user-injured"></i> Patient Management</a>
        <a href="?view=appointments" class="<?= $view=='appointments'?'active':'' ?>"><i class="fas fa-calendar-check"></i> Appointments Management</a>
        <a href="?view=reports" class="<?= $view=='reports'?'active':'' ?>"><i class="fas fa-flask"></i> Lab Reports Management</a>
        <a href="?view=news" class="<?= $view=='news'?'active':'' ?>"><i class="fas fa-newspaper"></i> News Management</a>
        <a href="?view=feedback" class="<?= $view=='feedback'?'active':'' ?>"><i class="fas fa-comments"></i> Feedback Management</a>
    </div>

    <div class="main-container">
        <header>
            <h3 style="color:#555">Hospital System / <?= ucfirst($view) ?></h3>
            <a href="javascript:void(0);" onclick="confirmLogout()" class="btn btn-red"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </header>

        <div class="content">

            <?php if($view == 'doctors'): ?>
                <div class="card">
                    <h3 id="doc-title">Add New Doctor</h3>
                    <form method="POST" id="doc-form">
                        <input type="hidden" name="doc_id" id="doc_id">
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr auto; gap:10px; align-items: start;">
                            <input type="text" name="doc_name" id="doc_name" placeholder="Full Name" required>
                            <input type="text" name="specialization" id="doc_spec" placeholder="Specialization" required>
                            <input type="number" name="fee" id="doc_fee" placeholder="Consultation Fee" required>
                            <button type="submit" name="save_doctor" class="btn btn-green">Save</button>
                        </div>
                    </form>
                </div>
                <div class="card">
                    <table class="data-table">
                        <thead><tr><th>ID</th><th>Name</th><th>Specialization</th><th>Fee</th><th>Actions</th></tr></thead>
                        <?php $data = $conn->query("SELECT * FROM doctors"); while($r = $data->fetch_assoc()): ?>
                        <tr><td>#<?= $r['doctor_id'] ?></td><td><?= $r['doctor_name'] ?></td><td><?= $r['specialization'] ?></td><td>LKR <?= number_format($r['fee'], 2) ?></td>
                        <td class="action-btns"><button class="btn btn-blue" onclick='editDoc(<?= json_encode($r) ?>)'>Edit</button><a href="?delete_doc=<?= $r['doctor_id'] ?>" class="btn btn-red" onclick="return confirm('Delete?')">Delete</a></td></tr>
                        <?php endwhile; ?>
                    </table>
                </div>

            <?php elseif($view == 'patients'): ?>
                <div class="card">
                    <h3 id="pat-title">Add New Patient</h3>
                    <form method="POST" id="pat-form">
                        <input type="hidden" name="p_id" id="p_id">
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap:10px;">
                            <input type="text" name="p_name" id="p_name" placeholder="Patient Name" required>
                            <input type="text" name="p_nic" id="p_nic" placeholder="NIC Number" required>
                            <input type="text" name="p_contact" id="p_contact" placeholder="Contact" required>
                            <input type="email" name="p_email" id="p_email" placeholder="Email">
                            <button type="submit" name="save_patient" class="btn btn-green">Save</button>
                        </div>
                    </form>
                </div>
                <div class="card">
                    <table class="data-table">
                        <thead><tr><th>ID</th><th>Name</th><th>NIC</th><th>Contact</th><th>Actions</th></tr></thead>
                        <?php $data = $conn->query("SELECT * FROM patients"); while($r = $data->fetch_assoc()): ?>
                        <tr><td>#<?= $r['patient_id'] ?></td><td><?= $r['patient_name'] ?></td><td><?= $r['nic'] ?></td><td><?= $r['contact'] ?></td>
                        <td class="action-btns"><button class="btn btn-blue" onclick='editPat(<?= json_encode($r) ?>)'>Edit</button><a href="?delete_patient=<?= $r['patient_id'] ?>" class="btn btn-red" onclick="return confirm('Delete?')">Delete</a></td></tr>
                        <?php endwhile; ?>
                    </table>
                </div>

            <?php elseif($view == 'appointments'): ?>
                <div class="card">
                    <h3 id="app-title">Manual Appointment Booking</h3>
                    <form method="POST" id="app-form">
                        <input type="hidden" name="app_id" id="app_id">
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto; gap:10px;">
                            <select name="app_p_id" id="app_p_id" required>
                                <option value="">Patient</option>
                                <?php $p_list = $conn->query("SELECT patient_id, patient_name FROM patients");
                                while($p = $p_list->fetch_assoc()) echo "<option value='".$p['patient_id']."'>".$p['patient_name']."</option>"; ?>
                            </select>
                            <select name="app_d_id" id="app_d_id" required>
                                <option value="">Doctor</option>
                                <?php $d_list = $conn->query("SELECT doctor_id, doctor_name FROM doctors");
                                while($d = $d_list->fetch_assoc()) echo "<option value='".$d['doctor_id']."'>".$d['doctor_name']."</option>"; ?>
                            </select>
                            <input type="date" name="app_date" id="app_date" required>
                            <input type="text" name="app_time" id="app_time" placeholder="e.g. 09:00 - 09:30" required>
                            <input type="number" name="app_fee" id="app_fee" placeholder="Fee" required>
                            <button type="submit" name="save_appointment" class="btn btn-green">Save</button>
                        </div>
                    </form>
                </div>
                <div class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $data = $conn->query("SELECT a.*, p.patient_name, d.doctor_name FROM appointments a 
                                                JOIN patients p ON a.patient_id=p.patient_id 
                                                JOIN doctors d ON a.doctor_id=d.doctor_id 
                                                ORDER BY a.appointment_date DESC"); 
                            
                            while($r = $data->fetch_assoc()): 
                                // Theme colors
                                $isCompleted = ($r['status'] == 'Completed');
                                $statusColor = $isCompleted ? 'var(--primary)' : '#f39c12'; // Green for Completed, Orange for Pending
                            ?>
                            <tr>
                                <td><?= $r['appointment_date'] ?></td>
                                <td><?= htmlspecialchars($r['patient_name']) ?></td>
                                <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                                <td><?= htmlspecialchars($r['time_slot']) ?></td>
                                <td>
                                    <span style="color:<?= $statusColor ?>; font-weight:bold;">
                                        <?= $isCompleted ? 'Completed' : 'Pending' ?>
                                    </span>
                                </td>
                                <<td class="action-btns">
                                    <?php if(!$isCompleted): ?>
                                        <a href="?set_status=Completed&id=<?= $r['appointment_id'] ?>&view=appointments" 
                                        class="btn btn-green" title="Mark as Completed">
                                        <i class="fas fa-check"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?set_status=Pending&id=<?= $r['appointment_id'] ?>&view=appointments" 
                                        class="btn" style="background:#607d8b; color:white;" title="Redo / Set to Pending">
                                        <i class="fas fa-undo"></i>
                                        </a>
                                    <?php endif; ?>

                                    <button class="btn btn-blue" onclick='editApp(<?= json_encode($r) ?>)' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <a href="?delete_app=<?= $r['appointment_id'] ?>&view=appointments" 
                                    class="btn btn-red" 
                                    onclick="return confirm('Are you sure? This will permanently remove the record from the database.')" 
                                    title="Delete Permanently">
                                    <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif($view == 'timeslots'): ?>
                <div class="card">
                    <h3 id="manage-timeslots-title">Manage Doctor Availability</h3>
                    <form method="POST">
                        <input type="hidden" name="slot_id" id="slot_id">
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap:10px;">
                            <select name="slot_doc_id" id="slot_doc_id" required>
                                <option value="">Select Doctor</option>
                                <?php $d_list = $conn->query("SELECT doctor_id, doctor_name FROM doctors");
                                while($d = $d_list->fetch_assoc()) echo "<option value='".$d['doctor_id']."'>".$d['doctor_name']."</option>"; ?>
                            </select>
                            <input type="text" name="slot_time" id="slot_time_input" placeholder="Time Slot (e.g. 14:00 - 14:10)" required>
                            <button type="submit" name="add_timeslot" class="btn btn-green">Save Slot</button>
                        </div>
                    </form>
                </div>
                <div class="card">
                    <table class="data-table">
                        <thead><tr><th>Doctor</th><th>Available Time Slot</th><th>Actions</th></tr></thead>
                        <?php $data = $conn->query("SELECT dt.*, d.doctor_name FROM doctor_times dt JOIN doctors d ON dt.doctor_id = d.doctor_id ORDER BY d.doctor_name ASC"); 
                        while($r = $data->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['doctor_name'] ?></td>
                            <td><?= $r['time_slot'] ?></td>
                            <td class="action-btns">
                                <button class="btn btn-blue" onclick='editSlot(<?= json_encode($r) ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <a href="?delete_slot=<?= $r['time_id'] ?>&view=timeslots" 
                                class="btn btn-red" 
                                onclick="return confirm('Remove this time slot?')">
                                <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>

            <?php elseif($view == 'reports'): ?>
                <div class="card">
                    <h3 id="rep-title">Upload Lab Report</h3>
                    <form method="POST" enctype="multipart/form-data" id="rep-form">
                        <input type="hidden" name="report_id" id="report_id">
                        <input type="hidden" name="existing_path" id="existing_path">
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap:10px;">
                            <select name="report_p_id" id="report_p_id" required>
                                <option value="">Select Patient</option>
                                <?php $p_list = $conn->query("SELECT patient_id, patient_name FROM patients");
                                while($p = $p_list->fetch_assoc()) echo "<option value='".$p['patient_id']."'>".$p['patient_name']."</option>"; ?>
                            </select>
                            <input type="text" name="report_name" id="report_name" placeholder="Report Name" required>
                            <input type="date" name="report_date" id="report_date" required>
                            <input type="file" name="report_file" accept=".pdf">
                            <button type="submit" name="save_report" class="btn btn-green">Upload</button>
                        </div>
                    </form>
                </div>
                <div class="card">
                    <table class="data-table">
                        <thead><tr><th>Patient</th><th>Report Name</th><th>Date</th><th>File</th><th>Actions</th></tr></thead>
                        <?php $data = $conn->query("SELECT lr.*, p.patient_name FROM lab_reports lr JOIN patients p ON lr.patient_id = p.patient_id"); 
                        while($r = $data->fetch_assoc()): ?>
                        <tr><td><?= $r['patient_name'] ?></td><td><?= $r['report_name'] ?></td><td><?= $r['test_date'] ?></td><td><a href="<?= $r['file_path'] ?>" target="_blank" style="color:var(--blue)">View PDF</a></td>
                        <td class="action-btns"><button class="btn btn-blue" onclick='editRep(<?= json_encode($r) ?>)'>Edit</button><a href="?delete_report=<?= $r['report_id'] ?>" class="btn btn-red" onclick="return confirm('Delete?')">Delete</a></td></tr>
                        <?php endwhile; ?>
                    </table>
                </div>

            <?php elseif($view == 'news'): ?>
                <div class="card">
                    <h3 id="news-title">Post New Article</h3>
                    <form method="POST" enctype="multipart/form-data" id="news-form">
                        <input type="hidden" name="news_id" id="news_id">
                        <input type="hidden" name="existing_img" id="existing_img">
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                            <input type="text" name="news_title" id="n_title" placeholder="Topic / Title" required>
                            <select name="news_category" id="n_cat" required>
                                <option value="facility">Facility Updates</option>
                                <option value="medical">Medical Advances</option>
                                <option value="awards">Awards & Recognition</option>
                                <option value="community">Community Outreach</option>
                            </select>
                            <input type="date" name="news_date" id="n_date" required>
                        </div>
                        <input type="text" name="news_caption" id="n_cap" placeholder="Short Caption" required>
                        <textarea name="news_details" id="n_det" placeholder="Full News Details" rows="5" required></textarea>
                        <div style="display:flex; gap:15px; align-items:center;">
                            <label>Feature Image: <input type="file" name="news_img" accept="image/*"></label>
                            <button type="submit" name="save_news" class="btn btn-green">Save Article</button>
                        </div>
                    </form>
                </div>
                <div class="card">
                    <table class="data-table">
                        <thead><tr><th>Date</th><th>Topic</th><th>Category</th><th>Actions</th></tr></thead>
                        <?php $data = $conn->query("SELECT * FROM hospital_news ORDER BY news_date DESC"); 
                        while($r = $data->fetch_assoc()): ?>
                        <tr><td><?= $r['news_date'] ?></td><td><strong><?= $r['title'] ?></strong></td><td style="text-transform: capitalize;"><?= $r['category'] ?></td>
                        <td class="action-btns"><button class="btn btn-blue" onclick='editNews(<?= json_encode($r) ?>)'>Edit</button><a href="?delete_news=<?= $r['id'] ?>" class="btn btn-red" onclick="return confirm('Delete?')">Delete</a></td></tr>
                        <?php endwhile; ?>
                    </table>
                </div>

            <?php elseif ($view == 'feedback'): ?>
                <div class="card"><h3>Feedback & Inquiries</h3></div>
                <div class="card">
                    <table class="data-table">
                        <thead><tr><th>ID</th><th>Patient</th><th>Email</th><th>Subject</th><th>Message</th><th>Actions</th></tr></thead>
                        <?php $feedback = $conn->query("SELECT * FROM contact_inquiries ORDER BY created_at DESC");
                        while($row = $feedback->fetch_assoc()): ?>
                        <tr><td>#<?= $row['id'] ?></td><td><?= htmlspecialchars($row['name']) ?></td><td><?= htmlspecialchars($row['email']) ?></td><td><?= htmlspecialchars($row['subject']) ?></td>
                        <td style="max-width: 200px; font-size: 0.85rem;"><?= htmlspecialchars($row['message']) ?></td>
                        <td><a href="?delete_feedback=<?= $row['id'] ?>&view=feedback" class="btn btn-red" onclick="return confirm('Delete feedback?')">Delete</a></td></tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmLogout() { if (confirm("Log out?")) window.location.href = "logout.php"; }   
        function editDoc(data) {
            document.getElementById('doc-title').innerText = "Edit Doctor Details";
            document.getElementById('doc_id').value = data.doctor_id;
            document.getElementById('doc_name').value = data.doctor_name;
            document.getElementById('doc_spec').value = data.specialization;
            document.getElementById('doc_fee').value = data.fee;
        }
        function editPat(data) {
            document.getElementById('pat-title').innerText = "Edit Patient Details";
            document.getElementById('p_id').value = data.patient_id;
            document.getElementById('p_name').value = data.patient_name;
            document.getElementById('p_nic').value = data.nic;
            document.getElementById('p_contact').value = data.contact;
            document.getElementById('p_email').value = data.email;
        }
        function editRep(data) {
            document.getElementById('rep-title').innerText = "Update Lab Report";
            document.getElementById('report_id').value = data.report_id;
            document.getElementById('report_p_id').value = data.patient_id;
            document.getElementById('report_name').value = data.report_name;
            document.getElementById('report_date').value = data.test_date;
            document.getElementById('existing_path').value = data.file_path;
        }
        function editNews(data) {
            document.getElementById('news-title').innerText = "Edit Article";
            document.getElementById('news_id').value = data.id;
            document.getElementById('existing_img').value = data.image_path;
            document.getElementById('n_title').value = data.title;
            document.getElementById('n_cat').value = data.category;
            document.getElementById('n_date').value = data.news_date;
            document.getElementById('n_cap').value = data.caption;
            document.getElementById('n_det').value = data.full_details;
        }

        function editApp(data) {
            document.getElementById('app-title').innerText = "Edit Appointment";
            document.getElementById('app_id').value = data.appointment_id;
            document.getElementById('app_p_id').value = data.patient_id;
            document.getElementById('app_d_id').value = data.doctor_id;
            document.getElementById('app_date').value = data.appointment_date;
            document.getElementById('app_time').value = data.time_slot;
            document.getElementById('app_fee').value = data.fee;
        }

        function editSlot(data) {
            document.querySelector('#manage-timeslots-title').innerText = "Edit Time Slot";
            document.getElementById('slot_id').value = data.time_id;
            document.getElementById('slot_doc_id').value = data.doctor_id;
            document.getElementById('slot_time_input').value = data.time_slot;
        }

    </script>
</body>
</html>