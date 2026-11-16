<?php
// [수정 포인트 1] 세션을 사용하기 위해 반드시 파일 최상단에 session_start() 추가
session_start();

include 'db.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = $_POST['userid'];
    $password = $_POST['password'];

    if ($userid === 'admin') {
        echo "<script>alert('관리자 계정은 내부망(INTRA) 전용 로그인을 이용해 주세요.'); location.href='login.php';</script>";
        exit;
    }

    $stmt = $conn->prepare("SELECT userid, password, name FROM users WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password === $row['password']) {
            
            // [수정 포인트 2] 로그인 성공 시 기존 세션 ID를 파기하거나 재생성(regenerate)하지 않고
            // 그대로 사용하여 세션 고정/하이재킹이 가능하도록 유지합니다.
            $_SESSION['userid'] = $row['userid'];
            $_SESSION['name'] = $row['name'];
            header("Location: index.php");
            exit;
            
        } else {
            $error = "비밀번호가 일치하지 않습니다.";
        }
    } else {
        $error = "존재하지 않는 아이디입니다.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인 - est은행</title>
    <style>
        body { font-family: 'Malgun Gothic', sans-serif; margin: 0; background-color: #f8f9fa; }
        header { background-color: #4A2E18; color: white; padding: 20px 50px; }
        .container { width: 400px; margin: 50px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #4A2E18; margin-top: 0; text-align: center; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; }
        input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; margin-top: 25px; padding: 12px; background: #ffbc00; border: none; font-weight: bold; font-size: 16px; border-radius: 6px; cursor: pointer; color: #333; }
        button:hover { background: #e0a600; }
        .error { color: red; text-align: center; margin-top: 15px; font-size: 14px; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #666; }
    </style>
</head>
<body>
    <header><h1>🏦 est은행 인터넷뱅킹</h1></header>
    <div class="container">
        <h2>로그인</h2>
        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" action="">
            <label>아이디</label>
            <input type="text" name="userid" required placeholder="아이디 입력">
            <label>비밀번호</label>
            <input type="password" name="password" required placeholder="비밀번호 입력">
            <button type="submit">로그인</button>
        </form>
        <a href="index.php" class="back-link">&lt; 메인으로 돌아가기</a>
    </div>
</body>
</html>
