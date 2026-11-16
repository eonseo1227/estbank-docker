<?php
include 'db.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = $_POST['userid'];
    $password = $_POST['password'];

    // 인트라망에서도 오직 admin 계정만 허용
    if ($userid !== 'admin') {
        $error = "관리자 전용 계정만 로그인할 수 있습니다.";
    } else {
        $stmt = $conn->prepare("SELECT userid, password, name FROM users WHERE userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if ($password === $row['password']) {
                $_SESSION['userid'] = $row['userid'];
                $_SESSION['name'] = $row['name'];
                header("Location: admin_index.php");
                exit;
            } else {
                $error = "비밀번호가 일치하지 않습니다.";
            }
        } else {
            $error = "존재하지 않는 관리자 계정입니다.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>관리자 로그인 - est은행 INTRA</title>
    <style>
        body { font-family: 'Malgun Gothic', sans-serif; margin: 0; background-color: #2c3e50; }
        header { background-color: #1a252f; color: white; padding: 20px 50px; }
        .container { width: 400px; margin: 80px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        h2 { color: #2c3e50; margin-top: 0; text-align: center; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; }
        input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; margin-top: 25px; padding: 12px; background: #e74c3c; border: none; font-weight: bold; font-size: 16px; border-radius: 6px; cursor: pointer; color: white; }
        button:hover { background: #c0392b; }
        .error { color: red; text-align: center; margin-top: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <header><h1>🔒 est은행 INTRA (관리자 구역)</h1></header>
    <div class="container">
        <h2>관리자 인증</h2>
        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" action="">
            <label>관리자 아이디</label>
            <input type="text" name="userid" required placeholder="admin 입력">
            <label>비밀번호</label>
            <input type="password" name="password" required placeholder="비밀번호 입력">
            <button type="submit">관리자 로그인</button>
        </form>
    </div>
</body>
</html>
