<?php
session_start();

if (empty($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('管理者のみアクセス可能');
}

header('Content-Type: application/json');

$dsn = 'mysql:host=localhost;dbname=データベース名;charset=utf8';
$user = 'ユーザ名';
$password = 'パスワード';
$pdo = new PDO($dsn, $user, $password);

$events = [];

$stmt = $pdo->query("
    SELECT s.*, u.name AS user_name 
    FROM shifts s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.date
");

foreach ($stmt as $row) {
    switch ($row['strength']) {
        case '◎': $color = 'green'; break;
        case '○': $color = 'blue'; break;
        case '△': $color = 'orange'; break;
        case '×': $color = 'gray'; break;
        default: $color = 'red';
    }

    $events[] = [
        'title' => "{$row['user_name']} - {$row['time_slot']}：{$row['strength']}",
        'start' => $row['date'],
        'allDay' => true,
        'color' => $color,
        'reason' => $row['reason']
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
