<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

$pdo = new PDO(
    'mysql:host=localhost;dbname=データベース名;charset=utf8',
    'ユーザ名',
    'パスワード'
);

$page = $_GET['page'] ?? 'home';

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// === 管理者希望 編集用データ ===
$edit_request = null;
if ($page === 'admin_dashboard' && isset($_GET['edit_request'])) {
    $stmt = $pdo->prepare("SELECT * FROM admin_requests WHERE id=?");
    $stmt->execute([$_GET['edit_request']]);
    $edit_request = $stmt->fetch();
}

// === 管理者希望 登録/更新 ===
if ($page === 'admin_dashboard' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $comment = $_POST['comment'] ?? '';

    if (!empty($_POST['update_request'])) {
        $id = $_POST['id'] ?? '';
        $stmt = $pdo->prepare("UPDATE admin_requests SET date=?, comment=? WHERE id=?");
        $stmt->execute([$date, $comment, $id]);
    } elseif (!empty($_POST['new_request'])) {
        $stmt = $pdo->prepare("INSERT INTO admin_requests (date, comment) VALUES (?, ?)");
        $stmt->execute([$date, $comment]);
    }
    header("Location: ?page=admin_dashboard");
    exit;
}

// === 管理者希望 削除 ===
if ($page === 'admin_dashboard' && isset($_GET['delete_request'])) {
    $stmt = $pdo->prepare("DELETE FROM admin_requests WHERE id=?");
    $stmt->execute([$_GET['delete_request']]);
    header("Location: ?page=admin_dashboard");
    exit;
}

// === ユーザー登録 ===
if ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $pass = $_POST['pass'] ?? '';
    if ($name && $pass) {
        $stmt = $pdo->prepare("INSERT INTO users (name, password) VALUES (?, ?)");
        $stmt->execute([$name, password_hash($pass, PASSWORD_DEFAULT)]);
        echo '✅ 登録完了 <a href="?page=login">ログイン</a>';
        exit;
    }
}

// === ユーザーログイン ===
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $pass = $_POST['pass'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name=?");
    $stmt->execute([$name]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: ?page=mypage');
        exit;
    } else {
        echo '⚠ ログイン失敗';
    }
}

// === 管理者ログイン ===
if ($page === 'admin_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['pass'] === '12347') {
        $_SESSION['admin'] = true;
        header('Location: ?page=admin_dashboard');
        exit;
    } else {
        echo '⚠ 管理者パスワード誤り';
    }
}

// === ログアウト ===
if ($page === 'logout') {
    session_destroy();
    header('Location: ?page=home');
    exit;
}

// === シフト登録/編集 ===
if ($page === 'mypage' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['user_id'])) {
        header("Location: ?page=login");
        exit;
    }
    $date = $_POST['date'];
    $time = $_POST['time_slot'];
    $strength = $_POST['strength'];
    $reason = $_POST['reason'];
    $public = isset($_POST['public']) ? 1 : 0;

    if (!empty($_POST['shift_id'])) {
        $stmt = $pdo->prepare("UPDATE shifts SET date=?,time_slot=?,strength=?,reason=?,is_public=? WHERE id=? AND user_id=?");
        $stmt->execute([$date, $time, $strength, $reason, $public, $_POST['shift_id'], $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO shifts (user_id,date,time_slot,strength,reason,is_public) VALUES(?,?,?,?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $date, $time, $strength, $reason, $public]);
    }
}

// === シフト削除 ===
if ($page === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM shifts WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    header("Location: ?page=mypage");
    exit;
}

// === 編集用データ（シフト） ===
$edit_shift = null;
if ($page === 'mypage' && isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['edit'], $_SESSION['user_id']]);
    $edit_shift = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>らくらくシフト管理</title>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/locales-all.min.js"></script>
<style>
table { border-collapse:collapse; width:100%; margin:10px 0; }
th,td { border:1px solid #ccc; padding:5px; text-align:center; }
.reason-cell { text-align:left; min-width:300px; white-space:pre-wrap; }
textarea { resize:none; width:500px; height:80px; }
a.btn {
    display:inline-block; margin:10px; padding:10px 20px;
    text-decoration:none; border-radius:5px; font-weight:bold; color:white;
}
.btn-register { background-color:#4CAF50; }
.btn-login { background-color:#2196F3; }
.btn-admin { background-color:#f44336; }
</style>
</head>
<body>

<?php if ($page === 'home'): ?>
<h1>らくらくシフト管理</h1>
<div style="text-align:center; margin-top:30px;">
<a href="?page=register" class="btn btn-register">新規登録</a>
<a href="?page=login" class="btn btn-login">ログイン</a>
<a href="?page=admin_login" class="btn btn-admin">管理者ログイン</a>
</div>

<?php elseif ($page === 'register'): ?>
<h2>新規登録</h2>
<form method="post">
名前：<input name="name"><br>
パスワード：<input type="password" name="pass"><br>
<button>登録</button>
</form>

<?php elseif ($page === 'login'): ?>
<h2>ログイン</h2>
<form method="post">
名前：<input name="name"><br>
パスワード：<input type="password" name="pass"><br>
<button>ログイン</button>
</form>

<?php elseif ($page === 'mypage'): ?>
<?php if (empty($_SESSION['user_id'])) { header("Location: ?page=login"); exit; } ?>
<h2><?php echo h($_SESSION['user_name']); ?> さんのマイページ</h2>

<h3>管理者の希望</h3>
<table>
<tr><th>日付</th><th>コメント</th></tr>
<?php
foreach ($pdo->query("SELECT * FROM admin_requests ORDER BY date") as $row) {
    echo "<tr><td>".h($row['date'])."</td><td>".nl2br(h($row['comment']))."</td></tr>";
}
?>

</table>
<h3>希望シフト登録</h3>

<div style="display: flex; gap: 40px; align-items: flex-start;">
  <!-- 左：フォーム -->
  <form method="post">
  <input type="hidden" name="shift_id" value="<?=h($edit_shift['id'] ?? '')?>">

  <?php
  $date_value = '';
  if (!empty($edit_shift['date'])) {
      $date_value = date('Y-m-d', strtotime($edit_shift['date']));
  }
  ?>

  日付:
  <input type="date" name="date" value="<?=h($date_value)?>"><br>

  時間帯:
  <select name="time_slot">
  <option value="昼" <?= (isset($edit_shift['time_slot']) && $edit_shift['time_slot']==='昼') ? 'selected' : '' ?>>昼</option>
  <option value="夜" <?= (isset($edit_shift['time_slot']) && $edit_shift['time_slot']==='夜') ? 'selected' : '' ?>>夜</option>
  <option value="通し" <?= (isset($edit_shift['time_slot']) && $edit_shift['time_slot']==='通し') ? 'selected' : '' ?>>通し</option>
  </select><br>

  希望度:
  <select name="strength">
  <option value="◎" <?= (isset($edit_shift['strength']) && $edit_shift['strength']==='◎') ? 'selected' : '' ?>>◎</option>
  <option value="○" <?= (isset($edit_shift['strength']) && $edit_shift['strength']==='○') ? 'selected' : '' ?>>○</option>
  <option value="△" <?= (isset($edit_shift['strength']) && $edit_shift['strength']==='△') ? 'selected' : '' ?>>△</option>
  <option value="×" <?= (isset($edit_shift['strength']) && $edit_shift['strength']==='×') ? 'selected' : '' ?>>×</option>
  </select><br>

  理由:<br>
  <textarea name="reason"><?=h($edit_shift['reason'] ?? '')?></textarea><br>

  公開:
  <input type="checkbox" name="public" <?= (!empty($edit_shift) && $edit_shift['is_public']) ? 'checked' : '' ?>><br>

  <button>保存</button>
  </form>

  <!-- 右：希望度の指標 -->
  <div style="max-width: 200px; font-size: 14px; line-height: 1.5;">
    <strong>希望度の指標</strong><br><br>
    ◎ … 必ず入りたい<br>
    ○ … できれば入りたい<br>
    △ … 人が足りなければ入る<br>
    × … 入れない<br>
  </div>
</div>




<h3>自分のシフト履歴</h3>
<table>
<tr><th>日付</th><th>時間帯</th><th>希望度</th><th>理由</th><th>公開</th><th>操作</th></tr>
<?php
$stmt = $pdo->prepare("SELECT * FROM shifts WHERE user_id=? ORDER BY date");
$stmt->execute([$_SESSION['user_id']]);
foreach ($stmt as $row) {
    $stmt=$pdo->prepare("SELECT * FROM shifts WHERE user_id=? ORDER BY date");
$stmt->execute([$_SESSION['user_id']]);
    $date_only = date('Y-m-d', strtotime($row['date']));
    echo "<tr><td>".h($date_only)."</td><td>".h($row['time_slot'])."</td><td>".h($row['strength'])."</td><td class='reason-cell'>".nl2br(h($row['reason']))."</td><td>".($row['is_public']?'公開':'非公開')."</td>
    <td><a href='?page=mypage&edit={$row['id']}'>編集</a>|<a href='?page=delete&id={$row['id']}'>削除</a></td></tr>";
}

?>
</table>

<hr>
<div id="calendar" style="margin-bottom:50px;"></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
const el = document.getElementById('calendar');
const cal = new FullCalendar.Calendar(el, {
initialView: 'dayGridMonth',
locale: 'ja',
height: 1200,
events: 'calendar_data.php',
eventClick: function(info) {
alert(info.event.title + "\n\n理由:" + (info.event.extendedProps.reason || ''));
}});
cal.render();
});
</script>
<div style="text-align:center; margin-top:30px;">
<a href="?page=logout" style="display:inline-block; padding:10px 20px; background:#f44336; color:white; text-decoration:none; border-radius:5px;">ログアウト</a>
</div>

<?php elseif ($page === 'admin_login'): ?>
<h2>管理者ログイン</h2>
<form method="post">
パスワード：<input type="password" name="pass">
<button>ログイン</button>
</form>

<?php elseif ($page === 'admin_dashboard'): ?>
<?php if (empty($_SESSION['admin'])) { header("Location: ?page=admin_login"); exit; } ?>
<h1>管理者ページ</h1>

<h3>管理者の希望登録</h3>
<form method="post" action="?page=admin_dashboard">
<input type="hidden" name="<?php echo isset($edit_request) ? 'update_request' : 'new_request'; ?>" value="1">
<?php if (isset($edit_request)): ?>
<input type="hidden" name="id" value="<?php echo h($edit_request['id']); ?>">
<?php endif; ?>
日付: <input type="date" name="date" required value="<?php echo h($edit_request['date'] ?? ''); ?>"><br>
コメント:<br><textarea name="comment" style="width:400px; height:80px;"><?php echo h($edit_request['comment'] ?? ''); ?></textarea><br>
<button>保存</button>
</form>

<h3>管理者希望一覧</h3>
<table>
<tr><th>日付</th><th>コメント</th><th>操作</th></tr>
<?php
foreach ($pdo->query("SELECT * FROM admin_requests ORDER BY date") as $row) {
  echo "<tr>
  <td>".h($row['date'])."</td>
  <td>".nl2br(h($row['comment']))."</td>
  <td>
    <a href='?page=admin_dashboard&edit_request={$row['id']}'>編集</a> |
    <a href='?page=admin_dashboard&delete_request={$row['id']}'>削除</a>
  </td></tr>";
}
?>
</table>

<h3>全ユーザーのシフト</h3>
<table>
<tr><th>ユーザー</th><th>日付</th><th>時間帯</th><th>希望度</th><th>理由</th><th>公開</th></tr>
<?php
foreach ($pdo->query("SELECT u.name,s.* FROM shifts s JOIN users u ON u.id=s.user_id ORDER BY u.name,s.date") as $r) {
  $date_only = date('Y-m-d', strtotime($r['date']));
  echo "<tr>
  <td>".h($r['name'])."</td>
  <td>".h($date_only)."</td>
  <td>".h($r['time_slot'])."</td>
  <td>".h($r['strength'])."</td>
  <td class='reason-cell'>".nl2br(h($r['reason']))."</td>
  <td>".($r['is_public']?'公開':'非公開')."</td></tr>";
}
?>
</table>

<!-- カレンダー -->
<h3>カレンダー</h3>
<!-- 希望度の色分け指標 -->
<div style="margin-bottom:20px;">
  <strong>希望度の色分け指標</strong><br><br>
  <div style="display:flex; flex-wrap:wrap; gap:20px;">
    <div><span style="display:inline-block; width:20px; height:20px; background-color:#4CAF50; vertical-align:middle; margin-right:5px;"></span>◎ … 必ず入りたい</div>
    <div><span style="display:inline-block; width:20px; height:20px; background-color:#2196F3; vertical-align:middle; margin-right:5px;"></span>○ … できれば入りたい</div>
    <div><span style="display:inline-block; width:20px; height:20px; background-color:#FFC107; vertical-align:middle; margin-right:5px;"></span>△ … 人が足りなければ入る</div>
    <div><span style="display:inline-block; width:20px; height:20px; background-color:#F44336; vertical-align:middle; margin-right:5px;"></span>× … 入れない</div>
  </div>
</div>

<div id="calendar"></div>

<script>
document.addEventListener('DOMContentLoaded',function(){
const el = document.getElementById('calendar');
const cal = new FullCalendar.Calendar(el, {
initialView: 'dayGridMonth',
locale: 'ja',
height: 1200,
events: 'calendar_data_admin.php',
eventClick: function(info) {
    alert(info.event.title + "\n\n理由: " + (info.event.extendedProps.reason || ''));
}});
cal.render();
});
</script>
<div style="text-align:center; margin-top:30px; margin-bottom:50px;">
  <a href="?page=logout" style="display:inline-block; padding:10px 20px; background:#f44336; color:white; text-decoration:none; border-radius:5px;">ログアウト</a>
</div>


<?php endif; ?>
</body>
</html>
