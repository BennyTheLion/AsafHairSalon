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

// -------------------------
// HANDLE CRUD ACTIONS
// -------------------------

// --- ADD CATEGORY ---
if (isset($_POST['add_category']) && $isAdmin) {
    $name_he = $conn->real_escape_string($_POST['name_he']);
    $name_en = $conn->real_escape_string($_POST['name_en']);
    $sort_order = intval($_POST['sort_order']);
    $conn->query("INSERT INTO categories (name_he, name_en, sort_order) VALUES ('$name_he','$name_en',$sort_order)");
}

// --- ADD SERVICE ---
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
}

// --- DELETE ---
if ($isAdmin && isset($_GET['delete_category'])) {
    $id = intval($_GET['delete_category']);
    $conn->query("DELETE FROM categories WHERE id=$id");
}

if ($isAdmin && isset($_GET['delete_service'])) {
    $id = intval($_GET['delete_service']);
    $conn->query("DELETE FROM services WHERE id=$id");
}

// --- SERVICE IMAGE UPLOAD (AJAX) ---
if ($isAdmin && isset($_POST['upload_service_image']) && isset($_FILES['service_image'])) {
    $id = intval($_POST['upload_service_image']);
    $file = $_FILES['service_image'];
    
    $uploadDir = __DIR__ . '/uploads/services/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp']) || $file['size'] > 10*1024*1024) {
        http_response_code(400);
        exit('Invalid file');
    }

    // Delete old image
    $res = $conn->query("SELECT image_url FROM services WHERE id=$id");
    if (($row = $res->fetch_assoc()) && !empty($row['image_url'])) {
        $oldPath = __DIR__ . '/' . ltrim($row['image_url'], '/');
        if (file_exists($oldPath)) @unlink($oldPath);
    }

    $newName = 'svc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $uploadDir . $newName);
    $newUrl = 'uploads/services/' . $newName;

    $stmt = $conn->prepare("UPDATE services SET image_url=? WHERE id=?");
    $stmt->bind_param("si", $newUrl, $id);
    $stmt->execute();
    exit($newUrl);
}

// --- INLINE UPDATE (AJAX) ---
if ($isAdmin && isset($_POST['update'])) {
    $table = $_POST['table'];
    $column = $_POST['column'];
    $id = intval($_POST['id']);
    $value = $_POST['value'];

    $stmt = $conn->prepare("UPDATE $table SET $column=? WHERE id=?");
    $stmt->bind_param("si", $value, $id);
    $stmt->execute();
    exit('OK');
}

// --- FETCH DATA ---
$categories = $conn->query("SELECT * FROM categories ORDER BY sort_order ASC");
$services = $conn->query("
    SELECT s.*, c.name_he AS category_name
    FROM services s
    LEFT JOIN categories c ON s.category_id = c.id
    ORDER BY s.id DESC
");

?>
<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
<meta charset="UTF-8">
<title>פאנל ניהול - סלון</title>
<style>
body { font-family: Arial; background:#f4f4f4; padding:20px; }
.container { max-width:1200px; margin:auto; background:white; padding:25px; border-radius:10px; box-shadow:0 0 15px #ccc; }
h1,h2{color:#444;}
table {width:100%; border-collapse:collapse; margin-top:15px;}
th, td {border:1px solid #ddd; padding:10px;}
th {background:#c8a97e; color:white;}
input, select, textarea {padding:5px; margin:3px; width:100%;}
button {padding:5px 10px; border:none; border-radius:5px; background:#c8a97e; color:white; cursor:pointer;}
button:hover {background:#b89266;}
a {color:red;text-decoration:none;}
.topbar {margin-bottom:20px;padding:10px;background:white;display:flex;justify-content:space-between;border-radius:8px;}
td[contenteditable="true"] {background:#fefbd8;}
</style>
</head>
<body>

<div class="container">
<div class="topbar">
    <div>שלום, <?= htmlspecialchars($_SESSION['full_name']); ?> (<?= $_SESSION['role'] ?>)</div>
    <a href="logout.php">התנתק</a>
</div>

<h1>פאנל ניהול - קטגוריות ושירותים</h1>

<h2>קטגוריות</h2>
<?php if($isAdmin): ?>
<form method="POST">
    <input type="text" name="name_he" placeholder="שם בעברית" required>
    <input type="text" name="name_en" placeholder="שם באנגלית">
    <input type="number" name="sort_order" placeholder="סדר" value="0">
    <button type="submit" name="add_category">הוסף קטגוריה</button>
</form>
<?php endif; ?>

<table>
<tr>
    <th>ID</th>
    <th>שם בעברית</th>
    <th>שם באנגלית</th>
    <th>סדר</th>
    <?php if($isAdmin) echo "<th>פעולות</th>"; ?>
</tr>
<?php while($c = $categories->fetch_assoc()): ?>
<tr>
    <td><?= $c['id'] ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateField('categories','name_he',<?= $c['id'] ?>,this.innerText)"><?= htmlspecialchars($c['name_he']) ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateField('categories','name_en',<?= $c['id'] ?>,this.innerText)"><?= htmlspecialchars($c['name_en']) ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateField('categories','sort_order',<?= $c['id'] ?>,this.innerText)"><?= $c['sort_order'] ?></td>
    <?php if($isAdmin): ?>
    <td><a href="?delete_category=<?= $c['id'] ?>" onclick="return confirm('למחוק קטגוריה?')">❌ מחק</a></td>
    <?php endif; ?>
</tr>
<?php endwhile; ?>
</table>

<h2>שירותים</h2>
<?php if($isAdmin): ?>
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="שם השירות" required>
    <input type="number" name="duration" placeholder="משך בדקות" required>
    <input type="number" name="base_price" placeholder="מחיר בסיס" step="0.1" required>
    <input type="number" name="materials_fee" placeholder="תוספת חומרים" step="0.1" value="0">
    <input type="text" name="short_description" placeholder="תיאור קצר">
    <textarea name="description" placeholder="תיאור מלא"></textarea>
    <label style="font-size:0.85rem;">תמונת שירות:</label>
    <input type="file" name="service_image" accept="image/*" style="padding:3px;">
    <select name="category_id" required>
        <option value="">בחר קטגוריה</option>
        <?php
        $cats2 = $conn->query("SELECT * FROM categories ORDER BY sort_order ASC");
        while($ct = $cats2->fetch_assoc()):
        ?>
        <option value="<?= $ct['id'] ?>"><?= htmlspecialchars($ct['name_he']) ?></option>
        <?php endwhile; ?>
    </select>
    <button type="submit" name="add_service">הוסף שירות</button>
</form>
<?php endif; ?>

<table>
<tr>
    <th>ID</th>
    <th>תמונה</th>
    <th>שם השירות</th>
    <th>תיאור קצר</th>
    <th>תיאור מלא</th>
    <th>משך בדקות</th>
    <th>מחיר בסיס</th>
    <th>חומרים</th>
    <th>קטגוריה</th>
    <?php if($isAdmin) echo "<th>פעולות</th>"; ?>
</tr>
<?php while($s = $services->fetch_assoc()): ?>
<tr>
    <td><?= $s['id'] ?></td>
    <td style="text-align:center;">
        <?php if (!empty($s['image_url'])): ?>
        <img src="<?= htmlspecialchars($s['image_url']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
        <?php else: ?>
        <span style="color:#ccc;font-size:0.8rem;">—</span>
        <?php endif; ?>
        <?php if($isAdmin): ?>
        <br><input type="file" accept="image/*" onchange="uploadServiceImage(<?= $s['id'] ?>,this)" style="width:60px;font-size:0.65rem;">
        <?php endif; ?>
    </td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateField('services','title',<?= $s['id'] ?>,this.innerText)"><?= htmlspecialchars($s['title']) ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateField('services','short_description',<?= $s['id'] ?>,this.innerText)"><?= htmlspecialchars($s['short_description']) ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateField('services','description',<?= $s['id'] ?>,this.innerText)"><?= htmlspecialchars($s['description']) ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateField('services','duration',<?= $s['id'] ?>,this.innerText)"><?= $s['duration'] ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateField('services','base_price',<?= $s['id'] ?>,this.innerText)"><?= $s['base_price'] ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateField('services','materials_fee',<?= $s['id'] ?>,this.innerText)"><?= $s['materials_fee'] ?></td>
    <td><?= htmlspecialchars($s['category_name']) ?></td>
    <?php if($isAdmin): ?>
    <td><a href="?delete_service=<?= $s['id'] ?>" onclick="return confirm('למחוק שירות?')">❌ מחק</a></td>
    <?php endif; ?>
</tr>
<?php endwhile; ?>
</table>


<h2>לפני / אחרי</h2>
<?php if($isAdmin): ?>
<form id="baAddForm" enctype="multipart/form-data" style="margin-bottom:15px;">
    <input type="text" name="title_he" placeholder="כותרת" required>
    <input type="number" name="sort_order" placeholder="סדר" value="0" style="width:80px;">
    <label style="font-size:0.85rem;">תמונת לפני:</label>
    <input type="file" name="before_image" accept="image/*" required>
    <label style="font-size:0.85rem;">תמונת אחרי:</label>
    <input type="file" name="after_image" accept="image/*" required>
    <button type="submit" name="add_before_after">הוסף</button>
</form>
<div id="baMsg" style="color:green;margin-bottom:10px;"></div>
<?php endif; ?>

<table>
<tr>
    <th>ID</th>
    <th>תמונה לפני</th>
    <th>תמונה אחרי</th>
    <th>כותרת</th>
    <th>סדר</th>
    <th>פעיל</th>
    <?php if($isAdmin) echo "<th>פעולות</th>"; ?>
</tr>
<?php
$baItems = $conn->query("SELECT * FROM before_after ORDER BY sort_order ASC, id DESC");
while($ba = $baItems->fetch_assoc()):
?>
<tr id="baRow<?= $ba['id'] ?>">
    <td><?= $ba['id'] ?></td>
    <td>
        <img src="<?= htmlspecialchars($ba['before_image']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
        <?php if($isAdmin): ?>
        <br><input type="file" accept="image/*" onchange="updateImage(<?= $ba['id'] ?>,'before',this)" style="width:60px;font-size:0.7rem;">
        <?php endif; ?>
    </td>
    <td>
        <img src="<?= htmlspecialchars($ba['after_image']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
        <?php if($isAdmin): ?>
        <br><input type="file" accept="image/*" onchange="updateImage(<?= $ba['id'] ?>,'after',this)" style="width:60px;font-size:0.7rem;">
        <?php endif; ?>
    </td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateBAField(<?= $ba['id'] ?>,'title_he',this.innerText)"><?= htmlspecialchars($ba['title_he']) ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateBAField(<?= $ba['id'] ?>,'sort_order',this.innerText)"><?= $ba['sort_order'] ?></td>
    <td contenteditable="<?= $isAdmin?'true':'false' ?>" onblur="updateBAField(<?= $ba['id'] ?>,'is_active',this.innerText)"><?= $ba['is_active'] ?></td>
    <?php if($isAdmin): ?>
    <td><a href="#" onclick="deleteBA(<?= $ba['id'] ?>);return false;" style="color:red;">❌ מחק</a></td>
    <?php endif; ?>
</tr>
<?php endwhile; ?>
</table>

</div>

<script>
function updateField(table, column, id, value){
    fetch("", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`update=1&table=${table}&column=${column}&id=${id}&value=${encodeURIComponent(value)}`
    }).then(r=>r.text()).then(console.log);
}

function uploadServiceImage(id, input) {
    const file = input.files[0];
    if(!file) return;
    const fd = new FormData();
    fd.append('upload_service_image', id);
    fd.append('service_image', file);
    fetch("", { method:"POST", body:fd })
    .then(r=>r.text())
    .then(url => {
        if(url.startsWith('uploads/')) location.reload();
        else alert('שגיאה בהעלאת התמונה');
    });
}

// --- Before/After AJAX ---

function updateBAField(id, column, value) {
    fetch("upload-before-after.php", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`action=update&id=${id}&column=${column}&value=${encodeURIComponent(value)}`
    }).then(r=>r.json()).then(d=>{ if(!d.success) alert(d.error); });
}

function deleteBA(id) {
    if(!confirm('למחוק את הפריט?')) return;
    fetch("upload-before-after.php", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`action=delete&id=${id}`
    }).then(r=>r.json()).then(d=>{
        if(d.success) {
            const row = document.getElementById('baRow'+id);
            if(row) row.remove();
        } else { alert(d.error); }
    });
}

function updateImage(id, type, input) {
    const file = input.files[0];
    if(!file) return;
    const fd = new FormData();
    fd.append('action', 'update_image');
    fd.append('id', id);
    fd.append('image_type', type);
    fd.append(type+'_image', file);
    fetch("upload-before-after.php", { method:"POST", body:fd })
    .then(r=>r.json())
    .then(d=>{
        if(d.success) location.reload();
        else alert(d.error);
    });
}

document.getElementById('baAddForm').addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'add');
    fetch("upload-before-after.php", { method:"POST", body:fd })
    .then(r=>r.json())
    .then(d=>{
        const msg = document.getElementById('baMsg');
        if(d.success) {
            msg.textContent = d.message;
            location.reload();
        } else {
            msg.style.color = 'red';
            msg.textContent = d.error;
        }
    });
});
</script>

</body>
</html>
