<?php
// admin_sections/appointments.php
$cm = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$cy = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$ar = $conn->query("SELECT * FROM appointments WHERE status!='cancelled' AND MONTH(appointment_date)=$cm AND YEAR(appointment_date)=$cy ORDER BY appointment_date,start_time");
$appts = [];
if ($ar) while ($a = $ar->fetch_assoc()) $appts[$a['appointment_date']][] = $a;

$mh = ['ינואר','פברואר','מרץ','אפריל','מאי','יוני','יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר'];
$dim = cal_days_in_month(CAL_GREGORIAN, $cm, $cy);
$fd = date('w', strtotime("$cy-$cm-01"));
$pm = $cm==1 ? 12 : $cm-1; $py = $cm==1 ? $cy-1 : $cy;
$nm = $cm==12 ? 1 : $cm+1; $ny = $cm==12 ? $cy+1 : $cy;
?>
<div class="card"><h2>יומן תורים</h2>
<div style="display:flex;justify-content:space-between;margin-bottom:10px;">
    <a href="?page=appointments&month=<?=$pm?>&year=<?=$py?>" class="btn">&lt; קודם</a>
    <strong><?=$mh[$cm-1]?> <?=$cy?></strong>
    <a href="?page=appointments&month=<?=$nm?>&year=<?=$ny?>" class="btn">הבא &gt;</a>
</div>
<table class="appt-cal" style="table-layout:fixed;">
<tr><?php foreach(['א','ב','ג','ד','ה','ו','ש'] as $hd) echo "<th>$hd</th>"; ?></tr><tr>
<?php
$dc = 1;
for ($i=0;$i<$fd;$i++){ echo "<td class=\"empty\"></td>"; $dc++; }
for ($d=1;$d<=$dim;$d++){
    $ds = sprintf('%04d-%02d-%02d',$cy,$cm,$d);
    $cls = date('Y-m-d')===$ds ? 'today' : '';
    echo "<td class=\"$cls\"><b>$d</b>";
    if (isset($appts[$ds])) foreach($appts[$ds] as $a){
        $sc = $a['status']==='confirmed'?'cfm':($a['status']==='completed'?'cmp':'nos');
        echo "<div class=\"badge $sc\" style=\"display:block;margin:1px 0;text-align:right;\">".htmlspecialchars(substr($a['start_time'],0,5)).' '.htmlspecialchars($a['customer_name'])."</div>";
    }
    echo "</td>";
    if ($dc%7==0) echo "</tr><tr>";
    $dc++;
}
while ($dc%7!=1){ echo "<td class=\"empty\"></td>"; $dc++; }
?>
</tr></table></div>
