<?php
// Customer-facing page: view / cancel / reschedule an appointment.
// Access is protected by id + cancel_token (sent in the booking email).
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email-helpers.php';

$conn = getDbConnection();
if (!$conn) die("שגיאת התחברות למסד הנתונים");

$id    = intval($_GET['id'] ?? $_POST['id'] ?? 0);
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$msg   = '';
$msgType = 'success';

function loadAppointment($conn, $id, $token) {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND cancel_token = ?");
    $stmt->bind_param("is", $id, $token);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r ? $r->fetch_assoc() : null;
}

$appt = ($id && $token) ? loadAppointment($conn, $id, $token) : null;

// ================== ACTIONS (POST) ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $appt) {

    $doCancel = isset($_POST['do_cancel']);
    $doReschedule = isset($_POST['do_reschedule']);

    if ($doCancel || $doReschedule) {
        if ($appt['status'] === 'cancelled') {
            $msg = 'התור כבר בוטל בעבר.';
            $msgType = 'error';
        } elseif ($appt['status'] === 'completed' || $appt['status'] === 'no_show') {
            $msg = 'לא ניתן לשנות תור זה (התור כבר התקיים).';
            $msgType = 'error';
        } elseif ($doCancel) {
            // ---------- CANCEL ----------
            $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND cancel_token = ?");
            $stmt->bind_param("is", $id, $token);
            $stmt->execute();
            clientLog('Customer cancelled appointment', $appt['customer_name'] . ' - ' . $appt['appointment_date'] . ' ' . $appt['start_time']);
            $msg = 'התור בוטל בהצלחה.';
            $msgType = 'success';

            $emailData = [
                'customer' => ['name' => $appt['customer_name'], 'phone' => $appt['customer_phone'], 'email' => $appt['customer_email']],
                'service'  => ['name' => $appt['service_name'], 'duration' => $appt['service_duration'], 'price' => $appt['service_price']],
                'date' => $appt['appointment_date'], 'startTime' => substr($appt['start_time'], 0, 5), 'endTime' => substr($appt['end_time'], 0, 5),
                'appointmentId' => $appt['id'], 'cancelToken' => $appt['cancel_token']
            ];
            sendAppointmentEmails('cancel', $emailData); // best-effort; helper never throws

            // clientLog() closes the shared connection — refresh with a new one.
            $conn = getDbConnection();
            $appt = loadAppointment($conn, $id, $token); // refresh
        } else {
            // ---------- RESCHEDULE ----------
            $newDate = trim($_POST['new_date'] ?? '');
            $newTime = trim($_POST['new_time'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) { $msg = 'תאריך לא תקין.'; $msgType = 'error'; }
            elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $newTime)) { $msg = 'שעה לא תקינה.'; $msgType = 'error'; }
            elseif ($newDate < date('Y-m-d')) { $msg = 'לא ניתן לקבוע תור בתאריך שעבר.'; $msgType = 'error'; }
            else {
                $newStart = $newTime;
                if (strlen($newStart) === 5) $newStart .= ':00';
                $dur = intval($appt['service_duration']);
                $startTs = strtotime("$newDate $newStart");
                $endTs = $startTs + $dur * 60;
                $newEnd = date('H:i:s', $endTs);

                // Overlap check (same rule as book-appointment.php, excluding self)
                $stmt = $conn->prepare("SELECT start_time, end_time FROM appointments WHERE appointment_date = ? AND status = 'confirmed' AND id <> ?");
                $stmt->bind_param("si", $newDate, $id);
                $stmt->execute();
                $res = $stmt->get_result();
                $conflict = false;
                while ($row = $res->fetch_assoc()) {
                    $exStart = strtotime("$newDate {$row['start_time']}");
                    $exEnd   = strtotime("$newDate {$row['end_time']}");
                    if ($startTs < $exEnd && $endTs > $exStart) { $conflict = true; break; }
                }

                if ($conflict) {
                    $msg = 'השעה המבוקשת כבר תפוסה. נסו שעה אחרת.';
                    $msgType = 'error';
                } else {
                    $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, start_time = ?, end_time = ? WHERE id = ? AND cancel_token = ?");
                    $stmt->bind_param("sssis", $newDate, $newStart, $newEnd, $id, $token);
                    if ($stmt->execute()) {
                        clientLog('Customer rescheduled appointment', $appt['customer_name'] . ' -> ' . $newDate . ' ' . $newStart);
                        $msg = 'התור שונה בהצלחה ל-' . $newDate . ' בשעה ' . substr($newStart, 0, 5) . '.';
                        $msgType = 'success';

                        $emailData = [
                            'customer' => ['name' => $appt['customer_name'], 'phone' => $appt['customer_phone'], 'email' => $appt['customer_email']],
                            'service'  => ['name' => $appt['service_name'], 'duration' => $appt['service_duration'], 'price' => $appt['service_price']],
                            'date' => $newDate, 'startTime' => substr($newStart, 0, 5), 'endTime' => substr($newEnd, 0, 5),
                            'appointmentId' => $appt['id'], 'cancelToken' => $appt['cancel_token']
                        ];
                        sendAppointmentEmails('reschedule', $emailData); // best-effort

                        // clientLog() closes the shared connection — refresh with a new one.
                        $conn = getDbConnection();
                        $appt = loadAppointment($conn, $id, $token); // refresh
                    } else {
                        // 1062 = duplicate slot_key (race) — show as taken
                        $msg = ($conn->errno === 1062)
                            ? 'השעה המבוקשת כבר תפוסה. נסו שעה אחרת.'
                            : 'לא ניתן היה לשנות את התור. נסו שוב.';
                        $msgType = 'error';
                    }
                }
            }
        }
    }
}
closeDbConnection();
?>
<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ניהול התור - אסף מספרה</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8f5f2;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:20px;}
.container{max-width:600px;width:100%;margin-top:40px;}
.card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 12px rgba(0,0,0,.08);margin-bottom:16px;}
h1{font-size:24px;color:#2c2c2c;margin-bottom:6px;}
h2{font-size:18px;color:#444;margin-bottom:12px;}
.sub{color:#8b7355;margin-bottom:20px;}
.row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0ece8;font-size:15px;}
.row b{color:#444;}
.row .val{color:#2c2c2c;}
.status-confirmed{color:#2ecc71;font-weight:bold;}
.status-cancelled{color:#e74c3c;font-weight:bold;}
.msg{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-weight:bold;}
.msg.success{background:#e8f8ef;color:#2e7d32;border:1px solid #b7e4c7;}
.msg.error{background:#fdecea;color:#c0392b;border:1px solid #f5c6cb;}
label{display:block;font-weight:bold;margin:12px 0 4px;color:#444;}
input{width:100%;padding:12px;border:2px solid #e8e4e0;border-radius:8px;font-size:16px;}
input:focus{outline:none;border-color:#c8a97e;}
.btn{display:inline-block;padding:12px 24px;border:none;border-radius:8px;cursor:pointer;font-size:16px;font-weight:bold;color:#fff;text-decoration:none;margin-top:14px;text-align:center;}
.btn-primary{background:#c8a97e;}
.btn-primary:hover{background:#8b7355;}
.btn-danger{background:#e74c3c;}
.btn-danger:hover{background:#c0392b;}
.btn-ghost{background:#eee;color:#555;}
.footer{text-align:center;color:#999;font-size:12px;margin-top:20px;}
.footer a{color:#8b7355;}
</style>
</head>
<body>
<div class="container">
    <div class="card" style="background:linear-gradient(135deg,#c8a97e,#8b7355);color:#fff;text-align:center;">
        <h1 style="color:#fff;">✂️ אסף - מספרה וסטיילינג</h1>
        <p>ניהול התור שלך</p>
    </div>

    <?php if (!$appt): ?>
        <div class="card">
            <div class="msg error">הקישור לא תקף או שהתור לא נמצא. אנא ודאו שהעתקתם את הקישור המלא מהאימייל.</div>
            <a class="btn btn-primary" href="<?=APP_URL?>">חזרה לאתר</a>
        </div>
    <?php else: ?>
        <?php if ($msg): ?><div class="msg <?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>

        <div class="card">
            <h2>פרטי התור</h2>
            <div class="row"><span><b>שם:</b></span><span class="val"><?=htmlspecialchars($appt['customer_name'])?></span></div>
            <div class="row"><span><b>שירות:</b></span><span class="val"><?=htmlspecialchars($appt['service_name'])?></span></div>
            <div class="row"><span><b>תאריך:</b></span><span class="val"><?=htmlspecialchars($appt['appointment_date'])?></span></div>
            <div class="row"><span><b>שעה:</b></span><span class="val"><?=htmlspecialchars(substr($appt['start_time'],0,5))?> - <?=htmlspecialchars(substr($appt['end_time'],0,5))?></span></div>
            <div class="row"><span><b>סטטוס:</b></span><span class="val <?=$appt['status']==='confirmed'?'status-confirmed':'status-cancelled'?>"><?= $appt['status']==='confirmed' ? 'מאושר' : ($appt['status']==='cancelled' ? 'בוטל' : htmlspecialchars($appt['status'])) ?></span></div>
        </div>

        <?php if ($appt['status'] === 'confirmed'): ?>
        <div class="card">
            <h2>שינוי מועד התור</h2>
            <form method="POST">
                <input type="hidden" name="id" value="<?=$appt['id']?>">
                <input type="hidden" name="token" value="<?=htmlspecialchars($appt['cancel_token'])?>">
                <label>תאריך חדש</label>
                <input type="date" name="new_date" min="<?=date('Y-m-d')?>" required>
                <label>שעה חדשה</label>
                <input type="time" name="new_time" step="1800" min="09:00" max="20:00" required>
                <button type="submit" name="do_reschedule" value="1" class="btn btn-primary">📅 עדכון המועד</button>
            </form>
        </div>

        <div class="card" style="border:1px solid #f5c6cb;">
            <h2>ביטול התור</h2>
            <form method="POST" onsubmit="return confirm('האם אתם בטוחים שברצונכם לבטל את התור?');">
                <input type="hidden" name="id" value="<?=$appt['id']?>">
                <input type="hidden" name="token" value="<?=htmlspecialchars($appt['cancel_token'])?>">
                <button type="submit" name="do_cancel" value="1" class="btn btn-danger">✖ ביטול התור</button>
            </form>
        </div>
        <?php else: ?>
        <div class="card">
            <a class="btn btn-primary" href="<?=APP_URL?>">קביעת תור חדש</a>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="footer">מספרת אסף בן נעים · <a href="<?=APP_URL?>">חזרה לאתר</a></div>
</div>
</body>
</html>
