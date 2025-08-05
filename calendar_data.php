<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('ログインが必要です');
}

header('Content-Type: application/json');

$dsn = 'mysql:host=localhost;dbname=データベース名;charset=utf8';
$user = 'ユーザ名';
$password = 'パスワード';
$pdo = new PDO($dsn, $user, $password);

$events = [];

// 自分のシフト
$stmt = $pdo->prepare("
    SELECT * FROM shifts 
    WHERE user_id = ?
    ORDER BY date
");
$stmt->execute([$_SESSION['user_id']]);
foreach ($stmt as $row) {
    $events[] = [
        'title' => "自分 - {$row['time_slot']}：{$row['strength']}",
        'start' => $row['date'],
        'allDay' => true,
        'color' => 'blue',
        'reason' => $row['reason']
    ];
}

// 他人の公開シフト
$stmt = $pdo->prepare("
    SELECT s.*, u.name AS user_name 
    FROM shifts s
    JOIN users u ON s.user_id = u.id
    WHERE s.is_public = 1 AND s.user_id != ?
    ORDER BY s.date
");
$stmt->execute([$_SESSION['user_id']]);
foreach ($stmt as $row) {
    $events[] = [
        'title' => "{$row['user_name']} - {$row['time_slot']}：{$row['strength']}",
        'start' => $row['date'],
        'allDay' => true,
        'color' => 'red',
        'reason' => $row['reason']
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
