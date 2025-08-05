<?php
session_start();
if (empty($_SESSION['user_id']) && empty($_SESSION['admin'])) {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($_SESSION['user_name'] ?? '管理者'); ?> さんのシフトカレンダー</title>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/locales-all.min.js"></script>
</head>
<body>
<h2>📅 <?php echo htmlspecialchars($_SESSION['user_name'] ?? '管理者'); ?> さんのシフトカレンダー</h2>

<p>
🔵 <strong>青：</strong>自分のシフト<br>
🔴 <strong>赤：</strong>他人のシフト
</p>

<div id="calendar"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'ja',
        events: 'calendar_data.php',
        eventClick: function(info) {
            alert(info.event.title + "\n\n理由: " + (info.event.extendedProps.reason || 'なし'));
        }
    });
    calendar.render();
});
</script>

<p><a href="index.php?page=mypage">← マイページに戻る</a></p>
</body>
</html>
