<?php
// admin_sections/before-after.php
$ba = $conn->query("SELECT * FROM before_after ORDER BY sort_order ASC, id DESC");
?>
<div class="card"><h2>לפני / אחרי</h2>
<?php if($isAdmin): ?>
<form id="baForm" enctype="multipart/form-data" style="display:flex;gap:6px;flex-wrap:wrap;align-items:end;">
    <input type="text" name="title_he" placeholder="כותרת" required style="flex:1;min-width:100px;">
    <input type="number" name="sort_order" value="0" style="width:60px;">
    <div><label style="font-size:11px;">לפני</label><input type="file" name="before_image" accept="image/*" required></div>
    <div><label style="font-size:11px;">אחרי</label><input type="file" name="after_image" accept="image/*" required></div>
    <button type="submit">הוסף</button>
</form>
<div id="baMsg" style="color:green;margin:5px 0;"></div>
<?php endif; ?>
<table>
<tr><th>ID</th><th>לפני</th><th>אחרי</th><th>כותרת</th><th>סדר</th><th>פעיל</th><?php if($isAdmin) echo "<th></th>"; ?></tr>
<?php if($ba) while($b=$ba->fetch_assoc()): ?>
<tr id="baRow<?=$b['id']?>">
    <td><?=$b['id']?></td>
    <td>
        <img src="<?=htmlspecialchars($b['before_image'])?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
        <?php if($isAdmin): ?><br><input type="file" accept="image/*" onchange="baimg(<?=$b['id']?>,'before',this)" style="width:45px;font-size:10px;"><?php endif; ?>
    </td>
    <td>
        <img src="<?=htmlspecialchars($b['after_image'])?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
        <?php if($isAdmin): ?><br><input type="file" accept="image/*" onchange="baimg(<?=$b['id']?>,'after',this)" style="width:45px;font-size:10px;"><?php endif; ?>
    </td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="baupd(<?=$b['id']?>,'title_he',this.innerText)"><?=htmlspecialchars($b['title_he'])?></td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="baupd(<?=$b['id']?>,'sort_order',this.innerText)"><?=$b['sort_order']?></td>
    <td contenteditable="<?=$isAdmin?'true':'false'?>" onblur="baupd(<?=$b['id']?>,'is_active',this.innerText)"><?=$b['is_active']?></td>
    <?php if($isAdmin): ?><td><a href="#" onclick="badel(<?=$b['id']?>);return false" class="btn btn-sm btn-del">מחק</a></td><?php endif; ?>
</tr>
<?php endwhile; ?>
</table></div>

<script>
function baupd(i,c,v){fetch("upload-before-after.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=update&id="+i+"&column="+c+"&value="+encodeURIComponent(v)}).then(r=>r.json()).then(d=>{if(!d.success)alert(d.error)})}
function badel(i){if(!confirm("למחוק?"))return;fetch("upload-before-after.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=delete&id="+i}).then(r=>r.json()).then(d=>{if(d.success)document.getElementById("baRow"+i).remove()})}
function baimg(i,t,inp){var f=inp.files[0];if(!f)return;var d=new FormData();d.append("action","update_image");d.append("id",i);d.append("image_type",t);d.append(t+"_image",f);fetch("upload-before-after.php",{method:"POST",body:d}).then(r=>r.json()).then(d=>{if(d.success)location.reload()})}
document.getElementById("baForm")&&document.getElementById("baForm").addEventListener("submit",function(e){e.preventDefault();var d=new FormData(this);d.append("action","add");fetch("upload-before-after.php",{method:"POST",body:d}).then(r=>r.json()).then(d=>{var m=document.getElementById("baMsg");if(d.success){m.textContent=d.message;location.reload()}else{m.style.color="red";m.textContent=d.error}})});
</script>
