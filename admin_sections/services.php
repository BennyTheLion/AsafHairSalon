<?php
// admin_sections/services.php
$svcs = $conn->query("SELECT s.*, c.name_he AS cat_name FROM services s LEFT JOIN categories c ON s.category_id=c.id ORDER BY s.id DESC");
?>
<div class="card"><h2>שירותים</h2>
<?php if($isAdmin): ?>
<form method="POST" action="admin-panel.php?page=services" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
        <input type="text" name="title" placeholder="שם השירות" required>
        <input type="number" name="duration" placeholder="דקות" required>
        <input type="number" name="base_price" placeholder="מחיר בסיס" step="0.1" required>
        <input type="number" name="materials_fee" placeholder="חומרים" step="0.1" value="0">
        <input type="text" name="short_description" placeholder="תיאור קצר">
        <textarea name="description" placeholder="תיאור מלא" rows="2"></textarea>
        <div><label style="font-size:11px;">תמונה</label><input type="file" name="service_image" accept="image/*"></div>
        <select name="category_id" required>
            <option value="">בחר קטגוריה</option>
            <?php $c2 = $conn->query("SELECT * FROM categories ORDER BY sort_order ASC");
            if ($c2) while ($ct = $c2->fetch_assoc()): ?>
            <option value="<?=$ct['id']?>"><?=htmlspecialchars($ct['name_he'])?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <button type="submit" name="add_service" style="margin-top:8px;">הוסף שירות</button>
</form>
<?php endif; ?>
<table>
<tr><th>ID</th><th>תמונה</th><th>שם</th><th>תיאור</th><th>דקות</th><th>מחיר</th><th>קטגוריה</th><?php if($isAdmin) echo "<th></th>"; ?></tr>
<?php if($svcs) while($s=$svcs->fetch_assoc()): ?>
<tr>
    <td><?=$s['id']?></td>
    <td style="text-align:center">
        <?php if(!empty($s['image_url'])): ?><img src="<?=htmlspecialchars($s['image_url'])?>" style="width:35px;height:35px;object-fit:cover;border-radius:4px;"><br><?php endif; ?>
        <?php if($isAdmin): ?><input type="file" accept="image/*" onchange="upimg(<?=$s['id']?>,this)" style="width:50px;font-size:10px;"><?php endif; ?>
    </td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('services','title',<?=$s['id']?>,this.innerText)"><?=htmlspecialchars($s['title'])?></td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('services','short_description',<?=$s['id']?>,this.innerText)"><?=htmlspecialchars($s['short_description'])?></td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('services','duration',<?=$s['id']?>,this.innerText)"><?=$s['duration']?></td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('services','base_price',<?=$s['id']?>,this.innerText)"><?=$s['base_price']?></td>
    <td><?=htmlspecialchars($s['cat_name'])?></td>
    <?php if($isAdmin): ?><td><a href="admin-panel.php?page=services&del_svc=<?=$s['id']?>" onclick="return confirm('למחוק?')" class="btn btn-sm btn-del">מחק</a></td><?php endif; ?>
</tr>
<?php endwhile; ?>
</table></div>
