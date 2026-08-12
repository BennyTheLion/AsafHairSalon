<?php
// admin_sections/dashboard.php
$ta = $conn->query("SELECT COUNT(*) as c FROM appointments");
$totalApts = $ta ? $ta->fetch_assoc()['c'] : 0;
$ts = $conn->query("SELECT COUNT(*) as c FROM services");
$totalSvcs = $ts ? $ts->fetch_assoc()['c'] : 0;
$td = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date=CURDATE() AND status!='cancelled'");
$todayApts = $td ? $td->fetch_assoc()['c'] : 0;
?>
<div class="card"><h2>לוח בקרה</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
    <div class="card" style="text-align:center;background:linear-gradient(135deg,#c8a97e,#8b7355);color:#fff;"><h1><?=$totalApts?></h1>סה"כ תורים</div>
    <div class="card" style="text-align:center;background:linear-gradient(135deg,#2ecc71,#27ae60);color:#fff;"><h1><?=$todayApts?></h1>תורים להיום</div>
    <div class="card" style="text-align:center;background:linear-gradient(135deg,#3498db,#2980b9);color:#fff;"><h1><?=$totalSvcs?></h1>שירותים</div>
</div></div>
