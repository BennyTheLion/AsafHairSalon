<?php
// admin_sections/categories.php
$cats = $conn->query("SELECT * FROM categories ORDER BY sort_order ASC");
?>
<div class="card"><h2>קטגוריות</h2>
<?php if($isAdmin): ?>
<form method="POST" action="admin-panel.php?page=categories" style="display:flex;gap:6px;flex-wrap:wrap;">
    <input type="text" name="name_he" placeholder="שם בעברית" required style="flex:1;min-width:120px;">
    <input type="text" name="name_en" placeholder="שם באנגלית" style="flex:1;min-width:120px;">
    <input type="number" name="sort_order" value="0" style="width:70px;">
    <button type="submit" name="add_category" style="margin:0;">הוסף</button>
</form>
<?php endif; ?>
<table>
<tr><th>ID</th><th>שם בעברית</th><th>שם באנגלית</th><th>סדר</th><?php if($isAdmin) echo "<th></th>"; ?></tr>
<?php if($cats) while($c=$cats->fetch_assoc()): ?>
<tr>
    <td><?=$c['id']?></td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('categories','name_he',<?=$c['id']?>,this.innerText)"><?=htmlspecialchars($c['name_he'])?></td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('categories','name_en',<?=$c['id']?>,this.innerText)"><?=htmlspecialchars($c['name_en'])?></td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="upd('categories','sort_order',<?=$c['id']?>,this.innerText)"><?=$c['sort_order']?></td>
    <?php if($isAdmin): ?><td><a href="admin-panel.php?page=categories&del_cat=<?=$c['id']?>" onclick="return confirm('למחוק?')" class="btn btn-sm btn-del">מחק</a></td><?php endif; ?>
</tr>
<?php endwhile; ?>
</table></div>
