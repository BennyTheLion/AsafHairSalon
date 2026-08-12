<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$isAdmin = $_SESSION['role'] === 'admin';
$conn = getDbConnection();
if (!$conn) die("DB connection failed");

// ---------- HANDLE CRUD ACTIONS ----------

if (isset($_POST['add_category']) && $isAdmin) {
    $name_he = $conn->real_escape_string($_POST['name_he']);
    $name_en = $conn->real_escape_string($_POST['name_en']);
    $sort_order = intval($_POST['sort_order']);
    $conn->query("INSERT INTO categories (name_he, name_en, sort_order) VALUES ('$name_he','$name_en',$sort_order)");
    logAction('Added category', $name_he);
}

if (isset($_POST['add_service']) && $isAdmin) {
    $title = $conn->real_escape_string($_POST['title']);
    $category_id = intval($_POST['category_id']);
    $base_price = floatval($_POST['base_price']);
    $materials_fee = floatval($_POST['materials_fee']);
    $duration = intval($_POST['duration']);
    $short_description = $conn->real_escape_string($_POST['short_description']);
    $description = $conn->real_escape_string($_POST['description']);
    $image_url = '';
    if (!empty($_FILES['service_image']['name'])) {
        $uploadDir = __DIR__ . '/uploads/services/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['service_image']['size'] <= 10*1024*1024) {
            $newName = 'svc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['service_image']['tmp_name'], $uploadDir . $newName);
            $image_url = 'uploads/services/' . $newName;
        }
    }
    $conn->query("INSERT INTO services (title, category_id, base_price, materials_fee, duration, short_description, description, image_url) 
                 VALUES ('$title', $category_id, $base_price, $materials_fee, $duration, '$short_description', '$description', '$image_url')");
    logAction('Added service', $title);
}

if ($isAdmin && isset($_GET['delete_category'])) {
    $id = intval($_GET['delete_category']);
    $conn->query("DELETE FROM categories WHERE id=$id");
    logAction('Deleted category', "ID: $id");
}
if ($isAdmin && isset($_GET['delete_service'])) {
    $id = intval($_GET['delete_service']);
    $conn->query("DELETE FROM services WHERE id=$id");
    logAction('Deleted service', "ID: $id");
}

if ($isAdmin && isset($_POST['upload_service_image']) && isset($_FILES['service_image'])) {
    $id = intval($_POST['upload_service_image']);
    $uploadDir = __DIR__ . '/uploads/services/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp']) || $_FILES['service_image']['size'] > 10*1024*1024) { http_response_code(400); exit('Invalid'); }
    $res = $conn->query("SELECT image_url FROM services WHERE id=$id");
    if (($row = $res->fetch_assoc()) && !empty($row['image_url'])) { $p = __DIR__ . '/' . ltrim($row['image_url'], '/'); if (file_exists($p)) @unlink($p); }
    $newName = 'svc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    move_uploaded_file($_FILES['service_image']['tmp_name'], $uploadDir . $newName);
    $stmt = $conn->prepare("UPDATE services SET image_url=? WHERE id=?");
    $stmt->bind_param("si", $n = 'uploads/services/' . $newName, $id);
    $stmt->execute();
    exit($n);
}

if ($isAdmin && isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE {$_POST['table']} SET {$_POST['column']}=? WHERE id=?");
    $stmt->bind_param("si", $_POST['value'], $id = intval($_POST['id']));
    $stmt->execute();
    exit('OK');
}

// ---------- FETCH DATA ----------
$categories = $conn->query("SELECT * FROM categories ORDER BY sort_order ASC");
$services = $conn->query("SELECT s.*, c.name_he AS category_name FROM services s LEFT JOIN categories c ON s.category_id = c.id ORDER BY s.id DESC");

$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$currentYear  = isset($_GET['year'])  ? intval($_GET['year'])  : intval(date('Y'));
$aptRes = $conn->query("SELECT * FROM appointments WHERE status != 'cancelled' AND MONTH(appointment_date)=$currentMonth AND YEAR(appointment_date)=$currentYear ORDER BY appointment_date, start_time");
$appointments = [];
while ($a = $aptRes->fetch_assoc()) { $appointments[$a['appointment_date']][] = $a; }
?>
<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>פאנל ניהול</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f4f4;display:flex;min-height:100vh;}
.sidebar{width:240px;background:#2c2c2c;color:#fff;padding:20px 0;position:fixed;top:0;right:0;bottom:0;overflow-y:auto;z-index:100;}
.sidebar h3{padding:0 20px 20px;color:#c8a97e;font-size:18px;border-bottom:1px solid #444;margin-bottom:10px;}
.sidebar a{display:block;padding:14px 20px;color:#bbb;text-decoration:none;font-size:15px;transition:.2s;border-right:3px solid transparent;}
.sidebar a:hover,.sidebar a.active{color:#fff;background:#3a3a3a;border-right-color:#c8a97e;}
.sidebar a .icon{margin-left:10px;font-size:16px;}
.main{margin-right:240px;flex:1;padding:25px;max-width:calc(100% - 240px);}
.tab{display:none;}
.tab.active{display:block;}
.card{background:#fff;border-radius:12px;padding:24px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.card h2{color:#444;margin-bottom:15px;font-size:20px;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #ddd;padding:10px 8px;font-size:14px;}
th{background:#c8a97e;color:#fff;text-align:center;}
td[contenteditable="true"]{background:#fefbd8;}
input,select,textarea{padding:8px;margin:4px 0;border:1px solid #ddd;border-radius:6px;width:100%;font-size:14px;}
button,.btn{padding:8px 16px;border:none;border-radius:6px;background:#c8a97e;color:#fff;cursor:pointer;font-size:14px;}
button:hover,.btn:hover{background:#8b7355;}
.btn-sm{padding:4px 10px;font-size:12px;}
.btn-danger{background:#e74c3c;}
.btn-danger:hover{background:#c0392b;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;background:#fff;padding:15px 20px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;color:#fff;font-weight:bold;}
.badge.confirmed{background:#2ecc71;}
.badge.completed{background:#3498db;}
.badge.no_show{background:#e74c3c;}
.appt-cal{width:100%;border-collapse:collapse;table-layout:fixed;}
.appt-cal th{background:#c8a97e;color:#fff;padding:8px;text-align:center;font-size:13px;}
.appt-cal td{vertical-align:top;height:85px;padding:4px;border:1px solid #ddd;font-size:11px;}
.appt-cal .today{background:#fef9f0;}
.appt-cal .today b{color:#c8a97e;font-size:14px;}
.appt-cal .empty{background:#f9f9f9;}
.cal-nav{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
.chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:15px;}
.chip{padding:8px 16px;border:2px solid #ddd;border-radius:20px;cursor:pointer;font-size:13px;background:#fff;transition:.2s;}
.chip:hover,.chip.active{background:#c8a97e;color:#fff;border-color:#c8a97e;}
.hamburger{display:none;position:fixed;top:15px;right:15px;z-index:200;background:#c8a97e;color:#fff;border:none;width:40px;height:40px;border-radius:8px;font-size:22px;cursor:pointer;}
@media(max-width:900px){
 .sidebar{width:60px;transition:width 0.3s;}
 .sidebar.open{width:220px;}
 .sidebar a span{display:none;}
 .sidebar.open a span{display:inline;}
 .sidebar h3{font-size:0;padding:10px;}
 .sidebar.open h3{font-size:18px;}
 .sidebar h3:after{content:'☰';font-size:22px;}
 .sidebar.open h3:after{content:'';}
 .main{margin-right:60px;max-width:calc(100% - 60px);}
 .hamburger{display:block;}
}
@media(max-width:500px){
 .main{padding:15px 10px;margin-right:0;max-width:100%;}
 .sidebar{width:0;overflow:hidden;}
 .sidebar.open{width:220px;}
 .topbar{flex-direction:column;gap:8px;font-size:14px;}
 table{font-size:12px;}
 th,td{padding:6px 4px;}
}
</head>
<body>

<button class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

<div class="sidebar">
    <h3>ניהול</h3>
    <a href="#" class="active" onclick="showTab('dashboard',this)"><span class="icon">🏠</span> <span>לוח בקרה</span></a>
    <a href="#" onclick="showTab('appointments',this)"><span class="icon">📅</span> <span>יומן תורים</span></a>
    <a href="#" onclick="showTab('categories',this)"><span class="icon">📂</span> <span>קטגוריות</span></a>
    <a href="#" onclick="showTab('services',this)"><span class="icon">💇</span> <span>שירותים</span></a>
    <a href="#" onclick="showTab('beforeafter',this)"><span class="icon">🔄</span> <span>לפני / אחרי</span></a>
    <a href="#" onclick="showTab('logs',this)"><span class="icon">📋</span> <span>יומן פעולות</span></a>
    <a href="logout.php" style="margin-top:auto;border-top:1px solid #444;padding-top:14px;"><span class="icon">🚪</span> <span>התנתק</span></a>
</div>

<div class="main">

<div class="topbar">
    <div>שלום, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong> (<?= $_SESSION['role'] ?>)</div>
</div>

<!-- DASHBOARD -->
<div class="tab active" id="tab-dashboard">
    <div class="card"><h2>לוח בקרה</h2>
    <?php
    $totalApts = $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'];
    $totalServices = $conn->query("SELECT COUNT(*) as c FROM services")->fetch_assoc()['c'];
    $todayApts = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date = CURDATE() AND status != 'cancelled'")->fetch_assoc()['c'];
    ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;margin-top:15px;">
        <div class="card" style="text-align:center;background:linear-gradient(135deg,#c8a97e,#8b7355);color:#fff;"><h1><?=$totalApts?></h1><p>סה"כ תורים</p></div>
        <div class="card" style="text-align:center;background:linear-gradient(135deg,#2ecc71,#27ae60);color:#fff;"><h1><?=$todayApts?></h1><p>תורים להיום</p></div>
        <div class="card" style="text-align:center;background:linear-gradient(135deg,#3498db,#2980b9);color:#fff;"><h1><?=$totalServices?></h1><p>שירותים במערכת</p></div>
    </div>
    </div>
</div>

<!-- APPOINTMENTS -->
<div class="tab" id="tab-appointments">
    <div class="card"><h2>יומן תורים</h2>
    <?php
    $months_he = ['ינואר','פברואר','מרץ','אפריל','מאי','יוני','יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר'];
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
    $firstDay = date('w', strtotime("$currentYear-$currentMonth-01"));
    $prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
    $prevYear  = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
    $nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
    $nextYear  = $currentMonth == 12 ? $currentYear + 1 : $currentYear;
    ?>
    <div class="cal-nav">
        <a href="?tab=appointments&month=<?=$prevMonth?>&year=<?=$prevYear?>#cal" class="btn">&lt; חודש קודם</a>
        <strong><?=$months_he[$currentMonth-1]?> <?=$currentYear?></strong>
        <a href="?tab=appointments&month=<?=$nextMonth?>&year=<?=$nextYear?>#cal" class="btn">חודש הבא &gt;</a>
    </div>
    <table class="appt-cal" id="cal">
    <tr><?php foreach(['א','ב','ג','ד','ה','ו','ש'] as $hd) echo "<th>$hd</th>"; ?></tr><tr>
    <?php
    $dc = 1;
    for ($i=0;$i<$firstDay;$i++){ echo "<td class=\"empty\"></td>"; $dc++; }
    for ($d=1;$d<=$daysInMonth;$d++){
        $ds = sprintf('%04d-%02d-%02d',$currentYear,$currentMonth,$d);
        $has = isset($appointments[$ds]);
        $cls = date('Y-m-d')===$ds?'today':'';
        echo "<td class=\"$cls\"><b>$d</b>";
        if($has) foreach($appointments[$ds] as $a){
            $sc = $a['status']==='confirmed'?'confirmed':($a['status']==='completed'?'completed':'no_show');
            echo "<div class=\"badge $sc\" style=\"display:block;margin:1px 0;font-size:10px;text-align:right;padding:2px 4px;\">".htmlspecialchars(substr($a['start_time'],0,5)).' '.htmlspecialchars($a['customer_name'])."</div>";
        }
        echo "</td>";
        if($dc%7==0) echo "</tr><tr>";
        $dc++;
    }
    while($dc%7!=1){ echo "<td class=\"empty\"></td>"; $dc++; }
    ?></tr></table></div>
</div>

<!-- CATEGORIES -->
<div class="tab" id="tab-categories">
    <div class="card"><h2>קטגוריות</h2>
    <?php if($isAdmin): ?>
    <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;">
        <input type="text" name="name_he" placeholder="שם בעברית" required style="flex:1;min-width:150px;">
        <input type="text" name="name_en" placeholder="שם באנגלית" style="flex:1;min-width:150px;">
        <input type="number" name="sort_order" placeholder="סדר" value="0" style="width:80px;">
        <button type="submit" name="add_category">הוסף</button>
    </form>
    <?php endif; ?>
    <table>
    <tr><th>ID</th><th>שם בעברית</th><th>שם באנגלית</th><th>סדר</th><?php if($isAdmin) echo "<th>פעולות</th>"; ?></tr>
    <?php while($c = $categories->fetch_assoc()): ?>
    <tr>
        <td><?=$c['id']?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateField('categories','name_he',<?=$c['id']?>,this.innerText)"><?=htmlspecialchars($c['name_he'])?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateField('categories','name_en',<?=$c['id']?>,this.innerText)"><?=htmlspecialchars($c['name_en'])?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateField('categories','sort_order',<?=$c['id']?>,this.innerText)"><?=$c['sort_order']?></td>
        <?php if($isAdmin): ?><td><a href="?delete_category=<?=$c['id']?>" onclick="return confirm('למחוק?')" class="btn btn-sm btn-danger">מחק</a></td><?php endif; ?>
    </tr>
    <?php endwhile; ?>
    </table></div>
</div>

<!-- SERVICES -->
<div class="tab" id="tab-services">
    <div class="card"><h2>שירותים</h2>
    <?php if($isAdmin): ?>
    <form method="POST" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <input type="text" name="title" placeholder="שם השירות" required>
        <input type="number" name="duration" placeholder="משך בדקות" required>
        <input type="number" name="base_price" placeholder="מחיר בסיס" step="0.1" required>
        <input type="number" name="materials_fee" placeholder="תוספת חומרים" step="0.1" value="0">
        <input type="text" name="short_description" placeholder="תיאור קצר">
        <textarea name="description" placeholder="תיאור מלא" rows="2"></textarea>
        <div><label style="font-size:12px;">תמונה</label><input type="file" name="service_image" accept="image/*"></div>
        <select name="category_id" required>
            <option value="">בחר קטגוריה</option>
            <?php $cats2 = $conn->query("SELECT * FROM categories ORDER BY sort_order ASC");
            while($ct = $cats2->fetch_assoc()): ?>
            <option value="<?=$ct['id']?>"><?=htmlspecialchars($ct['name_he'])?></option>
            <?php endwhile; ?>
        </select>
        </div>
        <button type="submit" name="add_service" style="margin-top:8px;">הוסף שירות</button>
    </form>
    <?php endif; ?>
    <table>
    <tr><th>ID</th><th>תמונה</th><th>שם</th><th>תיאור</th><th>משך</th><th>מחיר</th><th>חומרים</th><th>קטגוריה</th><?php if($isAdmin) echo "<th>פעולות</th>"; ?></tr>
    <?php while($s = $services->fetch_assoc()): ?>
    <tr>
        <td><?=$s['id']?></td>
        <td style="text-align:center">
            <?php if(!empty($s['image_url'])): ?><img src="<?=htmlspecialchars($s['image_url'])?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;"><br><?php else: ?><span style="color:#ccc">—</span><br><?php endif; ?>
            <?php if($isAdmin): ?><input type="file" accept="image/*" onchange="uploadServiceImage(<?=$s['id']?>,this)" style="width:50px;font-size:10px;"><?php endif; ?>
        </td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateField('services','title',<?=$s['id']?>,this.innerText)"><?=htmlspecialchars($s['title'])?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateField('services','short_description',<?=$s['id']?>,this.innerText)"><?=htmlspecialchars($s['short_description'])?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateField('services','duration',<?=$s['id']?>,this.innerText)"><?=$s['duration']?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateField('services','base_price',<?=$s['id']?>,this.innerText)"><?=$s['base_price']?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateField('services','materials_fee',<?=$s['id']?>,this.innerText)"><?=$s['materials_fee']?></td>
        <td><?=htmlspecialchars($s['category_name'])?></td>
        <?php if($isAdmin): ?><td><a href="?delete_service=<?=$s['id']?>" onclick="return confirm('למחוק?')" class="btn btn-sm btn-danger">מחק</a></td><?php endif; ?>
    </tr>
    <?php endwhile; ?>
    </table></div>
</div>

<!-- BEFORE/AFTER -->
<div class="tab" id="tab-beforeafter">
    <div class="card"><h2>לפני / אחרי</h2>
    <?php if($isAdmin): ?>
    <form id="baAddForm" enctype="multipart/form-data" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
        <input type="text" name="title_he" placeholder="כותרת" required style="flex:1;min-width:120px;">
        <input type="number" name="sort_order" placeholder="סדר" value="0" style="width:70px;">
        <div><label style="font-size:11px;">לפני</label><input type="file" name="before_image" accept="image/*" required></div>
        <div><label style="font-size:11px;">אחרי</label><input type="file" name="after_image" accept="image/*" required></div>
        <button type="submit">הוסף</button>
    </form>
    <div id="baMsg" style="color:green;margin:5px 0;"></div>
    <?php endif; ?>
    <table>
    <tr><th>ID</th><th>לפני</th><th>אחרי</th><th>כותרת</th><th>סדר</th><th>פעיל</th><?php if($isAdmin) echo "<th>פעולות</th>"; ?></tr>
    <?php
    $baItems = $conn->query("SELECT * FROM before_after ORDER BY sort_order ASC, id DESC");
    while($ba = $baItems->fetch_assoc()): ?>
    <tr id="baRow<?=$ba['id']?>">
        <td><?=$ba['id']?></td>
        <td><img src="<?=htmlspecialchars($ba['before_image'])?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px;"><?php if($isAdmin): ?><br><input type="file" accept="image/*" onchange="updateBAImage(<?=$ba['id']?>,'before',this)" style="width:50px;font-size:10px;"><?php endif; ?></td>
        <td><img src="<?=htmlspecialchars($ba['after_image'])?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px;"><?php if($isAdmin): ?><br><input type="file" accept="image/*" onchange="updateBAImage(<?=$ba['id']?>,'after',this)" style="width:50px;font-size:10px;"><?php endif; ?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateBAField(<?=$ba['id']?>,'title_he',this.innerText)"><?=htmlspecialchars($ba['title_he'])?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateBAField(<?=$ba['id']?>,'sort_order',this.innerText)"><?=$ba['sort_order']?></td>
        <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="updateBAField(<?=$ba['id']?>,'is_active',this.innerText)"><?=$ba['is_active']?></td>
        <?php if($isAdmin): ?><td><a href="#" onclick="deleteBA(<?=$ba['id']?>);return false;" class="btn btn-sm btn-danger">מחק</a></td><?php endif; ?>
    </tr>
    <?php endwhile; ?>
    </table></div>
</div>

<!-- LOGS -->
<div class="tab" id="tab-logs">
    <div class="card"><h2>יומן פעולות</h2>
    <table>
    <tr><th>תאריך</th><th>משתמש</th><th>פעולה</th><th>פרטים</th><th>IP</th></tr>
    <?php
    $logs = $conn->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 200");
    while($l = $logs->fetch_assoc()):
    ?>
    <tr>
        <td style="white-space:nowrap;font-size:12px;"><?= htmlspecialchars($l['created_at']) ?></td>
        <td><?= htmlspecialchars($l['username']) ?></td>
        <td><?= htmlspecialchars($l['action']) ?></td>
        <td style="font-size:12px;"><?= htmlspecialchars($l['details']) ?></td>
        <td style="font-size:11px;direction:ltr;text-align:left;"><?= htmlspecialchars($l['ip']) ?></td>
    </tr>
    <?php endwhile; ?>
    </table></div>
</div>

</div><!-- .main -->

<script>
// Tab switching
function showTab(name, link) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    if (link) link.classList.add('active');
    // Update URL hash
    history.replaceState(null, '', '?tab=' + name + window.location.search.replace(/[?&]tab=[^&]*/, '').replace(/^&/, '?'));
}

// Restore active tab from URL
(function() {
    var params = new URLSearchParams(window.location.search);
    var tab = params.get('tab') || 'dashboard';
    var link = document.querySelector('.sidebar a[onclick*="' + tab + '"]');
    showTab(tab, link);
})();

// Existing functions
function updateField(table, column, id, value) {
    fetch("", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:`update=1&table=${table}&column=${column}&id=${id}&value=${encodeURIComponent(value)}` }).then(r=>r.text());
}

function uploadServiceImage(id, input) {
    var f = input.files[0]; if(!f) return;
    var fd = new FormData(); fd.append('upload_service_image', id); fd.append('service_image', f);
    fetch("", { method:"POST", body:fd }).then(r=>r.text()).then(url => { if(url.startsWith('uploads/')) location.reload(); else alert('שגיאה'); });
}

function updateBAField(id, column, value) {
    fetch("upload-before-after.php", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:`action=update&id=${id}&column=${column}&value=${encodeURIComponent(value)}` }).then(r=>r.json()).then(d=>{ if(!d.success) alert(d.error); });
}

function deleteBA(id) {
    if(!confirm('למחוק?')) return;
    fetch("upload-before-after.php", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:`action=delete&id=${id}` }).then(r=>r.json()).then(d=>{ if(d.success) document.getElementById('baRow'+id).remove(); else alert(d.error); });
}

function updateBAImage(id, type, input) {
    var f = input.files[0]; if(!f) return;
    var fd = new FormData(); fd.append('action','update_image'); fd.append('id',id); fd.append('image_type',type); fd.append(type+'_image',f);
    fetch("upload-before-after.php", { method:"POST", body:fd }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.error); });
}

document.getElementById('baAddForm') && document.getElementById('baAddForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this); fd.append('action','add');
    fetch("upload-before-after.php", { method:"POST", body:fd }).then(r=>r.json()).then(d=>{
        var m = document.getElementById('baMsg');
        if(d.success) { m.textContent = d.message; location.reload(); }
        else { m.style.color='red'; m.textContent = d.error; }
    });
});
</script>
</body>
</html>
