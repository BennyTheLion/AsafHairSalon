<?php
session_start();
require_once "config.php";

if (!function_exists('logAction')) { function logAction($a,$d=''){} }
if (!function_exists('clientLog')) { function clientLog($a,$d=''){} }

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$isAdmin = $_SESSION['role'] === 'admin';

$conn = getDbConnection();
if (!$conn) die("DB connection failed");

// ===== ALL POST HANDLERS (must run before any HTML output) =====

// Inline text update (AJAX)
if ($isAdmin && isset($_POST['update'])) {
    $conn->query("UPDATE {$_POST['table']} SET {$_POST['column']}='".$conn->real_escape_string($_POST['value'])."' WHERE id=".intval($_POST['id']));
    exit('OK');
}

// Service image upload (AJAX)
if ($isAdmin && isset($_POST['upd_svc_img']) && isset($_FILES['svc_img'])) {
    $id = intval($_POST['upd_svc_img']);
    $up = __DIR__ . '/uploads/services/';
    if (!is_dir($up)) mkdir($up, 0755, true);
    $ex = strtolower(pathinfo($_FILES['svc_img']['name'], PATHINFO_EXTENSION));
    if (!in_array($ex, ['jpg','jpeg','png','webp'])) { http_response_code(400); exit('bad'); }
    $r = $conn->query("SELECT image_url FROM services WHERE id=$id");
    if (($rw = $r->fetch_assoc()) && !empty($rw['image_url'])) {
        $p = __DIR__ . '/' . ltrim($rw['image_url'], '/');
        if (file_exists($p)) @unlink($p);
    }
    $nn = 'svc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ex;
    move_uploaded_file($_FILES['svc_img']['tmp_name'], $up . $nn);
    $conn->query("UPDATE services SET image_url='uploads/services/$nn' WHERE id=$id");
    exit('uploads/services/' . $nn);
}

// Add category
if (isset($_POST['add_category']) && $isAdmin) {
    $nh = $conn->real_escape_string($_POST['name_he']);
    $ne = $conn->real_escape_string($_POST['name_en']);
    $so = intval($_POST['sort_order']);
    $conn->query("INSERT INTO categories (name_he, name_en, sort_order) VALUES ('$nh','$ne',$so)");
    logAction('Added category', $nh);
    header("Location: admin-panel.php?page=categories"); exit();
}

// Add service
if (isset($_POST['add_service']) && $isAdmin) {
    $t = $conn->real_escape_string($_POST['title']);
    $cid = intval($_POST['category_id']);
    $bp = floatval($_POST['base_price']);
    $mf = floatval($_POST['materials_fee']);
    $d = intval($_POST['duration']);
    $sd = $conn->real_escape_string($_POST['short_description']);
    $de = $conn->real_escape_string($_POST['description']);
    $img = '';
    if (!empty($_FILES['service_image']['name'])) {
        $up = __DIR__ . '/uploads/services/';
        if (!is_dir($up)) mkdir($up, 0755, true);
        $ex = strtolower(pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ex, ['jpg','jpeg','png','webp'])) {
            $nn = 'svc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ex;
            move_uploaded_file($_FILES['service_image']['tmp_name'], $up . $nn);
            $img = 'uploads/services/' . $nn;
        }
    }
    $conn->query("INSERT INTO services (title,category_id,base_price,materials_fee,duration,short_description,description,image_url) VALUES ('$t',$cid,$bp,$mf,$d,'$sd','$de','$img')");
    logAction('Added service', $t);
    header("Location: admin-panel.php?page=services"); exit();
}

// Delete category
if ($isAdmin && isset($_GET['del_cat'])) {
    $conn->query("DELETE FROM categories WHERE id=" . intval($_GET['del_cat']));
    logAction('Deleted category', "ID: " . intval($_GET['del_cat']));
    header("Location: admin-panel.php?page=categories"); exit();
}

// Delete service
if ($isAdmin && isset($_GET['del_svc'])) {
    $conn->query("DELETE FROM services WHERE id=" . intval($_GET['del_svc']));
    logAction('Deleted service', "ID: " . intval($_GET['del_svc']));
    header("Location: admin-panel.php?page=services"); exit();
}

// ===== PAGE SELECTION =====
$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard','appointments','categories','services','before-after'];
if (!in_array($page, $allowed)) $page = 'dashboard';
?>
<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>פאנל ניהול</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f4f4;}
/* Sidebar — desktop only */
.sidebar{width:210px;background:#2c2c2c;color:#fff;position:fixed;top:0;right:0;bottom:0;padding:15px 0;z-index:100;overflow-y:auto;}
.sidebar h3{padding:0 15px 15px;color:#c8a97e;font-size:16px;border-bottom:1px solid #444;margin-bottom:8px;}
.sidebar a{display:block;padding:12px 15px;color:#bbb;text-decoration:none;font-size:14px;border-right:3px solid transparent;transition:.2s;}
.sidebar a:hover,.sidebar a.active{color:#fff;background:#3a3a3a;border-right-color:#c8a97e;}
/* Hamburger + mobile menu */
.hamburger{display:none;position:fixed;top:12px;right:12px;z-index:300;background:#c8a97e;color:#fff;border:none;width:42px;height:42px;border-radius:8px;font-size:22px;cursor:pointer;}
.mobile-menu{display:none;position:fixed;top:60px;right:10px;left:10px;z-index:250;background:#2c2c2c;border-radius:10px;padding:8px 0;box-shadow:0 8px 30px rgba(0,0,0,.4);}
.mobile-menu a{display:block;padding:14px 18px;color:#bbb;text-decoration:none;font-size:15px;border-right:3px solid transparent;}
.mobile-menu a.active{color:#fff;background:#3a3a3a;border-right-color:#c8a97e;}
.mobile-menu .logout{color:#e74c3c;}
.mobile-menu.open{display:block;}
.main{margin-right:210px;padding:20px;max-width:calc(100% - 210px);}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;background:#fff;padding:12px 15px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.05);font-size:14px;}
.card{background:#fff;border-radius:12px;padding:20px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow-x:auto;}
.card h2{color:#444;margin-bottom:12px;font-size:18px;}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;max-width:100%;}
table{width:100%;border-collapse:collapse;margin-top:8px;min-width:480px;}
th,td{border:1px solid #ddd;padding:7px;font-size:13px;white-space:nowrap;}
th{background:#c8a97e;color:#fff;}
td[contenteditable="true"]{background:#fefbd8;}
input,select,textarea{padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;margin:3px 0;width:100%;}
button,.btn{padding:8px 14px;border:none;border-radius:6px;background:#c8a97e;color:#fff;cursor:pointer;font-size:13px;margin:3px;text-decoration:none;display:inline-block;}
button:hover,.btn:hover{background:#8b7355;}
.btn-sm{padding:3px 8px;font-size:11px;}
.btn-del{background:#e74c3c;}.btn-del:hover{background:#c0392b;}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;color:#fff;}
.badge.cfm{background:#2ecc71;}.badge.cmp{background:#3498db;}.badge.nos{background:#e74c3c;}
.appt-cal th{background:#c8a97e;color:#fff;padding:6px;text-align:center;font-size:12px;}
.appt-cal td{vertical-align:top;height:65px;padding:3px;border:1px solid #ddd;font-size:10px;}
.appt-cal .today{background:#fef9f0;}.appt-cal .empty{background:#f9f9f9;}
/* Mobile */
@media(max-width:768px){
 .sidebar{display:none;}
 .hamburger{display:block;}
 .main{margin-right:0;max-width:100%;padding:70px 10px 10px;}
 .topbar{flex-direction:column;gap:5px;}
 .card{padding:12px;}
 table{min-width:520px;}
 th,td{padding:5px;}
}
</style>
</head>
<body>

<!-- Hamburger — mobile -->
<button class="hamburger" onclick="document.querySelector('.mobile-menu').classList.toggle('open')">☰</button>

<!-- Mobile dropdown menu -->
<div class="mobile-menu">
    <a href="?page=dashboard" class="<?=$page=='dashboard'?'active':''?>">🏠 לוח בקרה</a>
    <a href="?page=appointments" class="<?=$page=='appointments'?'active':''?>">📅 יומן תורים</a>
    <a href="?page=categories" class="<?=$page=='categories'?'active':''?>">📂 קטגוריות</a>
    <a href="?page=services" class="<?=$page=='services'?'active':''?>">💇 שירותים</a>
    <a href="?page=before-after" class="<?=$page=='before-after'?'active':''?>">🔄 לפני/אחרי</a>
    <a href="logout.php" class="logout">🚪 יציאה</a>
</div>

<!-- Sidebar — desktop -->
<div class="sidebar">
    <h3>ניהול</h3>
    <a href="?page=dashboard" class="<?=$page=='dashboard'?'active':''?>">🏠 לוח בקרה</a>
    <a href="?page=appointments" class="<?=$page=='appointments'?'active':''?>">📅 יומן תורים</a>
    <a href="?page=categories" class="<?=$page=='categories'?'active':''?>">📂 קטגוריות</a>
    <a href="?page=services" class="<?=$page=='services'?'active':''?>">💇 שירותים</a>
    <a href="?page=before-after" class="<?=$page=='before-after'?'active':''?>">🔄 לפני / אחרי</a>
    <a href="logout.php" style="border-top:1px solid #444;margin-top:10px;">🚪 התנתק</a>
</div>

<div class="main">
<div class="topbar">
    <div>שלום <strong><?=htmlspecialchars($_SESSION['full_name'])?></strong> (<?=$_SESSION['role']?>)</div>
</div>
<?php include "admin_sections/$page.php"; ?>
</div>

<script>
function upd(t,c,i,v){fetch("admin-panel.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"update=1&table="+t+"&column="+c+"&id="+i+"&value="+encodeURIComponent(v)})}
function upimg(i,inp){var f=inp.files[0];if(!f)return;var d=new FormData();d.append("upd_svc_img",i);d.append("svc_img",f);fetch("admin-panel.php",{method:"POST",body:d}).then(r=>r.text()).then(u=>{if(u.indexOf("uploads/")==0)location.reload()})}
</script>

</body>
</html>
