<?php
$dsn = 'mysql:host=localhost;dbname=データベース名;charset=utf8';
$user = 'ユーザ名';
$password = 'パスワード';

$pdo = new PDO($dsn, $user, $password);

session_start();

// 管理者以外は拒否
if (empty($_SESSION['admin'])) {
    header('Location: index.php?page=admin_login');
    exit;
}

// ファイル名
$filename = 'shift_data_' . date('Ymd_His') . '.csv';

// UTF-8 with BOM をつけて Excel で文字化けしにくくする
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . $filename);
header('Content-Transfer-Encoding: binary');

$output = fopen('php://output', 'w');

// UTF-8 BOM
fwrite($output, "\xEF\xBB\xBF");

// ヘッダ行（ダッシュボードと同じ順序・表記）
fputcsv($output, [
    'ユーザー名', 
    '日付', 
    '時間帯', 
    '希望度', 
    '理由', 
    '公開'
]);

// データ取得
$stmt = $pdo->query("
    SELECT u.name AS user_name, s.date, s.time_slot, s.strength, s.reason, s.is_public
    FROM shifts s
    JOIN users u ON s.user_id = u.id
    ORDER BY u.name, s.date
");

foreach ($stmt as $row) {
    fputcsv($output, [
        $row['user_name'],
        date('Y-m-d', strtotime($row['date'])),
        $row['time_slot'],
        $row['strength'],
        $row['reason'],
        $row['is_public'] ? '公開' : '非公開'
    ]);
}

fclose($output);
exit;
