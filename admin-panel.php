<?php
session_start();
require_once "config.php";

// Stubs for old config files
if (!function_exists('logAction')) { function logAction($a,$d=''){} }
if (!function_exists('clientLog')) { function clientLog($a,$d=''){} }

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$isAdmin = $_SESSION['role'] === 'admin';
$conn = getDbConnection();
if (!$conn) die("DB connection failed");

// Handle CRUD
if (isset($_POST['add_category']) && $isAdmin) {
    $conn->query("INSERT INTO categories (name_he, name_en, sort_order) VALUES ('".$conn->real_escape_string($_POST['name_he'])."','".$conn->real_escape_string($_POST['name_en'])."',".intval($_POST['sort_order']).")");
}
if (isset($_POST['add_service']) && $isAdmin) {
    $t=$conn->real_escape_string($_POST['title']); $cid=intval($_POST['category_id']); $bp=floatval($_POST['base_price']); $mf=floatval($_POST['materials_fee']); $d=intval($_POST['duration']); $sd=$conn->real_escape_string($_POST['short_description']); $de=$conn->real_escape_string($_POST['description']);
    $img='';
    if(!empty($_FILES['service_image']['name'])){
        $up=__DIR__.'/uploads/services/'; if(!is_dir($up))mkdir($up,0755,true);
        $ex=strtolower(pathinfo($_FILES['service_image']['name'],PATHINFO_EXTENSION));
        if(in_array($ex,['jpg','jpeg','png','webp'])){
            $nn='svc_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ex;
            move_uploaded_file($_FILES['service_image']['tmp_name'],$up.$nn);
            $img='uploads/services/'.$nn;
        }
    }
    $conn->query("INSERT INTO services (title,category_id,base_price,materials_fee,duration,short_description,description,image_url) VALUES ('$t',$cid,$bp,$mf,$d,'$sd','$de','$img')");
}
if($isAdmin&&isset($_GET['del_cat'])){ $conn->query("DELETE FROM categories WHERE id=".intval($_GET['del_cat'])); }
if($isAdmin&&isset($_GET['del_svc'])){ $conn->query("DELETE FROM services WHERE id=".intval($_GET['del_svc'])); }

// Service image upload via AJAX
if($isAdmin&&isset($_POST['upd_svc_img'])&&isset($_FILES['svc_img'])){
    $id=intval($_POST['upd_svc_img']); $up=__DIR__.'/uploads/services/'; if(!is_dir($up))mkdir($up,0755,true);
    $ex=strtolower(pathinfo($_FILES['svc_img']['name'],PATHINFO_EXTENSION));
    if(!in_array($ex,['jpg','jpeg','png','webp'])){ http_response_code(400);exit('bad'); }
    $r=$conn->query("SELECT image_url FROM services WHERE id=$id");
    if(($rw=$r->fetch_assoc())&&!empty($rw['image_url'])){ $p=__DIR__.'/'.ltrim($rw['image_url'],'/'); if(file_exists($p))@unlink($p); }
    $nn='svc_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ex;
    move_uploaded_file($_FILES['svc_img']['tmp_name'],$up.$nn);
    $conn->query("UPDATE services SET image_url='uploads/services/$nn' WHERE id=$id");
    exit('uploads/services/'.$nn);
}

// Inline update
if($isAdmin&&isset($_POST['update'])){
    $conn->query("UPDATE {$_POST['table']} SET {$_POST['column']}='".$conn->real_escape_string($_POST['value'])."' WHERE id=".intval($_POST['id']));
    exit('OK');
}

// Fetch data
$cats = $conn->query("SELECT * FROM categories ORDER BY sort_order ASC");
$svcs = $conn->query("SELECT s.*, c.name_he AS cat_name FROM services s LEFT JOIN categories c ON s.category_id=c.id ORDER BY s.id DESC");
$cm = isset($_GET['month'])?intval($_GET['month']):intval(date('m'));
$cy = isset($_GET['year'])?intval($_GET['year']):intval(date('Y'));
$ar = $conn->query("SELECT * FROM appointments WHERE status!='cancelled' AND MONTH(appointment_date)=$cm AND YEAR(appointment_date)=$cy ORDER BY appointment_date,start_time");
$appts=[]; if($ar) while($a=$ar->fetch_assoc()) $appts[$a['appointment_date']][]=$a;
$ba = $conn->query("SELECT * FROM before_after ORDER BY sort_order ASC, id DESC");
?>
<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>פאנל ניהול</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f4f4;padding:20px;}
.container{max-width:1200px;margin:auto;}
h2{color:#444;margin:15px 0;font-size:20px;}
.card{background:#fff;border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #ddd;padding:8px;font-size:13px;}
th{background:#c8a97e;color:#fff;}
td[contenteditable="true"]{background:#fefbd8;}
input,select,textarea{padding:8px;border:1px solid #ddd;border-radius:6px;font-size:14px;margin:3px 0;}
button,.btn{padding:8px 16px;border:none;border-radius:6px;background:#c8a97e;color:#fff;cursor:pointer;font-size:14px;margin:3px;}
button:hover{background:#8b7355;}
.btn-sm{padding:3px 10px;font-size:11px;}
.btn-del{background:#e74c3c;}.btn-del:hover{background:#c0392b;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;background:#fff;padding:15px 20px;border-radius:12px;}
.appt-cal{width:100%;border-collapse:collapse;table-layout:fixed;}
.appt-cal th{background:#c8a97e;color:#fff;padding:6px;text-align:center;font-size:12px;}
.appt-cal td{vertical-align:top;height:70px;padding:3px;border:1px solid #ddd;font-size:10px;}
.appt-cal .today{background:#fef9f0;}
.appt-cal .empty{background:#f9f9f9;}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;color:#fff;}
.badge.cfm{background:#2ecc71;}.badge.cmp{background:#3498db;}.badge.nos{background:#e74c3c;}
@media(max-width:768px){body{padding:10px;}table{font-size:11px;}th,td{padding:4px;}}
</style>
</head>
<body>
<div class="container">
<div class="topbar">
    <div>שלום <strong><?=htmlspecialchars($_SESSION['full_name'])?></strong> (<?=$_SESSION['role']?>)</div>
    <a href="logout.php" class="btn btn-del">התנתק</a>
</div>

<!-- DASHBOARD -->
<div class="card"><h2>לוח בקרה</h2>
<?php
$ta=$conn->query("SELECT COUNT(*) as c FROM appointments"); $totalApts=$ta?$ta->fetch_assoc()['c']:0;
$ts=$conn->query("SELECT COUNT(*) as c FROM services"); $totalSvcs=$ts?$ts->fetch_assoc()['c']:0;
$td=$conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date=CURDATE() AND status!='cancelled'"); $todayApts=$td?$td->fetch_assoc()['c']:0;
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
    <div class="card" style="text-align:center;background:linear-gradient(135deg,#c8a97e,#8b7355);color:#fff;"><h1><?=$totalApts?></h1>סה"כ תורים</div>
    <div class="card" style="text-align:center;background:linear-gradient(135deg,#2ecc71,#27ae60);color:#fff;"><h1><?=$todayApts?></h1>תורים להיום</div>
    <div class="card" style="text-align:center;background:linear-gradient(135deg,#3498db,#2980b9);color:#fff;"><h1><?=$totalSvcs?></h1>שירותים</div>
</div></div>

<!-- APPOINTMENTS -->
<div class="card"><h2>יומן תורים</h2>
<?php
$mh=['ינואר','פברואר','מרץ','אפריל','מאי','יוני','יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר'];
$dim=cal_days_in_month(CAL_GREGORIAN,$cm,$cy);
$fd=date('w',strtotime("$cy-$cm-01"));
$pm=$cm==1?12:$cm-1; $py=$cm==1?$cy-1:$cy;
$nm=$cm==12?1:$cm+1; $ny=$cm==12?$cy+1:$cy;
?>
<div style="display:flex;justify-content:space-between;margin-bottom:10px;">
    <a href="?month=<?=$pm?>&year=<?=$py?>" class="btn">&lt; חודש קודם</a>
    <strong><?=$mh[$cm-1]?> <?=$cy?></strong>
    <a href="?month=<?=$nm?>&year=<?=$ny?>" class="btn">חודש הבא &gt;</a>
</div>
<table class="appt-cal">
<tr><?php foreach(['א','ב','ג','ד','ה','ו','ש'] as $hd) echo "<th>$hd</th>"; ?></tr><tr>
<?php
$dc=1; for($i=0;$i<$fd;$i++){echo "<td class=empty></td>";$dc++;}
for($d=1;$d<=$dim;$d++){
    $ds=sprintf('%04d-%02d-%02d',$cy,$cm,$d);
    $cls=date('Y-m-d')===$ds?'today':'';
    echo "<td class=\"$cls\"><b>$d</b>";
    if(isset($appts[$ds]))foreach($appts[$ds] as $a){
        $sc=$a['status']==='confirmed'?'cfm':($a['status']==='completed'?'cmp':'nos');
        echo "<div class=\"badge $sc\" style=\"display:block;margin:1px 0;text-align:right;\">".htmlspecialchars(substr($a['start_time'],0,5)).' '.htmlspecialchars($a['customer_name'])."</div>";
    }
    echo "</td>";
    if($dc%7==0)echo"</tr><tr>"; $dc++;
}
while($dc%7!=1){echo"<td class=empty></td>";$dc++;}
?></tr></table></div>

<!-- CATEGORIES -->
<div class="card"><h2>קטגוריות</h2>
<?php if($isAdmin): ?><form method="POST"><input type="text" name="name_he" placeholder="שם בעברית" required> <input type="text" name="name_en" placeholder="שם באנגלית"> <input type="number" name="sort_order" value="0" style="width:70px"> <button type="submit" name="add_category">הוסף</button></form><?php endif; ?>
<table><tr><th>ID</th><th>שם בעברית</th><th>שם באנגלית</th><th>סדר</th><?php if($isAdmin)echo"<th></th>";?></tr>
<?php if($cats)while($c=$cats->fetch_assoc()):?>
<tr><td><?=$c['id']?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('categories','name_he',<?=$c['id']?>,this.innerText)"><?=htmlspecialchars($c['name_he'])?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('categories','name_en',<?=$c['id']?>,this.innerText)"><?=htmlspecialchars($c['name_en'])?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('categories','sort_order',<?=$c['id']?>,this.innerText)"><?=$c['sort_order']?></td>
<?php if($isAdmin):?><td><a href="?del_cat=<?=$c['id']?>" onclick="return confirm('למחוק?')" class="btn btn-sm btn-del">מחק</a></td><?php endif;?>
</tr><?php endwhile; ?></table></div>

<!-- SERVICES -->
<div class="card"><h2>שירותים</h2>
<?php if($isAdmin): ?>
<form method="POST" enctype="multipart/form-data">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
<input type="text" name="title" placeholder="שם" required>
<input type="number" name="duration" placeholder="דקות" required>
<input type="number" name="base_price" placeholder="מחיר" step="0.1" required>
<input type="number" name="materials_fee" placeholder="חומרים" step="0.1" value="0">
<input type="text" name="short_description" placeholder="תיאור קצר">
<textarea name="description" placeholder="תיאור מלא" rows="2"></textarea>
<div><label>תמונה</label><input type="file" name="service_image" accept="image/*"></div>
<select name="category_id" required><option value="">בחר קטגוריה</option>
<?php $c2=$conn->query("SELECT * FROM categories ORDER BY sort_order ASC"); if($c2)while($ct=$c2->fetch_assoc()):?>
<option value="<?=$ct['id']?>"><?=htmlspecialchars($ct['name_he'])?></option>
<?php endwhile; ?></select>
</div>
<button type="submit" name="add_service">הוסף שירות</button>
</form><?php endif; ?>
<table><tr><th>ID</th><th>תמונה</th><th>שם</th><th>תיאור</th><th>דקות</th><th>מחיר</th><th>קטגוריה</th><?php if($isAdmin)echo"<th></th>";?></tr>
<?php if($svcs)while($s=$svcs->fetch_assoc()):?>
<tr><td><?=$s['id']?></td>
<td style="text-align:center"><?php if(!empty($s['image_url'])):?><img src="<?=htmlspecialchars($s['image_url'])?>" style="width:35px;height:35px;object-fit:cover;border-radius:4px;"><br><?php endif;?>
<?php if($isAdmin):?><input type="file" accept="image/*" onchange="upimg(<?=$s['id']?>,this)" style="width:50px;font-size:10px;"><?php endif;?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('services','title',<?=$s['id']?>,this.innerText)"><?=htmlspecialchars($s['title'])?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('services','short_description',<?=$s['id']?>,this.innerText)"><?=htmlspecialchars($s['short_description'])?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('services','duration',<?=$s['id']?>,this.innerText)"><?=$s['duration']?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('services','base_price',<?=$s['id']?>,this.innerText)"><?=$s['base_price']?></td>
<td><?=htmlspecialchars($s['cat_name'])?></td>
<?php if($isAdmin):?><td><a href="?del_svc=<?=$s['id']?>" onclick="return confirm('למחוק?')" class="btn btn-sm btn-del">מחק</a></td><?php endif;?>
</tr><?php endwhile; ?></table></div>

<!-- BEFORE/AFTER -->
<div class="card"><h2>לפני / אחרי</h2>
<?php if($isAdmin): ?><form id="baForm" enctype="multipart/form-data" style="display:flex;gap:6px;flex-wrap:wrap;align-items:end;">
<input type="text" name="title_he" placeholder="כותרת" required style="flex:1;min-width:100px;">
<input type="number" name="sort_order" value="0" style="width:60px;">
<div><label style="font-size:11px;">לפני</label><input type="file" name="before_image" accept="image/*" required></div>
<div><label style="font-size:11px;">אחרי</label><input type="file" name="after_image" accept="image/*" required></div>
<button type="submit">הוסף</button></form><div id="baMsg" style="color:green;margin:5px 0;"></div><?php endif; ?>
<table><tr><th>ID</th><th>לפני</th><th>אחרי</th><th>כותרת</th><th>סדר</th><th>פעיל</th><?php if($isAdmin)echo"<th></th>";?></tr>
<?php if($ba)while($b=$ba->fetch_assoc()):?>
<tr id="baRow<?=$b['id']?>"><td><?=$b['id']?></td>
<td><img src="<?=htmlspecialchars($b['before_image'])?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;"><?php if($isAdmin):?><br><input type="file" accept="image/*" onchange="baimg(<?=$b['id']?>,'before',this)" style="width:45px;font-size:10px;"><?php endif;?></td>
<td><img src="<?=htmlspecialchars($b['after_image'])?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;"><?php if($isAdmin):?><br><input type="file" accept="image/*" onchange="baimg(<?=$b['id']?>,'after',this)" style="width:45px;font-size:10px;"><?php endif;?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="baupd(<?=$b['id']?>,'title_he',this.innerText)"><?=htmlspecialchars($b['title_he'])?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="baupd(<?=$b['id']?>,'sort_order',this.innerText)"><?=$b['sort_order']?></td>
<td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="baupd(<?=$b['id']?>,'is_active',this.innerText)"><?=$b['is_active']?></td>
<?php if($isAdmin):?><td><a href="#" onclick="badel(<?=$b['id']?>);return false" class="btn btn-sm btn-del">מחק</a></td><?php endif;?>
</tr><?php endwhile; ?></table></div>

</div>

<script>
function upd(t,c,i,v){fetch("",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"update=1&table="+t+"&column="+c+"&id="+i+"&value="+encodeURIComponent(v)})}
function upimg(i,inp){var f=inp.files[0];if(!f)return;var d=new FormData();d.append("upd_svc_img",i);d.append("svc_img",f);fetch("",{method:"POST",body:d}).then(r=>r.text()).then(u=>{if(u.startsWith("uploads/"))location.reload()})}
function baupd(i,c,v){fetch("upload-before-after.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=update&id="+i+"&column="+c+"&value="+encodeURIComponent(v)}).then(r=>r.json()).then(d=>{if(!d.success)alert(d.error)})}
function badel(i){if(!confirm("למחוק?"))return;fetch("upload-before-after.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=delete&id="+i}).then(r=>r.json()).then(d=>{if(d.success)document.getElementById("baRow"+i).remove()})}
function baimg(i,t,inp){var f=inp.files[0];if(!f)return;var d=new FormData();d.append("action","update_image");d.append("id",i);d.append("image_type",t);d.append(t+"_image",f);fetch("upload-before-after.php",{method:"POST",body:d}).then(r=>r.json()).then(d=>{if(d.success)location.reload()})}
document.getElementById("baForm")&&document.getElementById("baForm").addEventListener("submit",function(e){e.preventDefault();var d=new FormData(this);d.append("action","add");fetch("upload-before-after.php",{method:"POST",body:d}).then(r=>r.json()).then(d=>{var m=document.getElementById("baMsg");if(d.success){m.textContent=d.message;location.reload()}else{m.style.color="red";m.textContent=d.error}})});
</script>
</body></html>
