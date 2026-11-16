<?php
include 'db.php';
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = $_POST['userid'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $account_number = $_POST['account_number'];

    $stmt = $conn->prepare("INSERT INTO users (userid, password, name, phone, account_number) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $userid, $password, $name, $phone, $account_number);
    
    if ($stmt->execute()) {
        $msg = "회원가입이 완료되었습니다! 로그인 후 이용해주세요.";
    } else {
        $msg = "회원가입 실패 (이미 존재하는 아이디일 수 있습니다.)";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>회원가입 - est은행</title>
    <style>
        body { font-family: 'Malgun Gothic', sans-serif; margin: 0; background-color: #f8f9fa; }
        header { background-color: #4A2E18; color: white; padding: 20px 50px; }
        .container { width: 450px; margin: 30px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #4A2E18; margin-top: 0; text-align: center; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; }
        input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; margin-top: 25px; padding: 12px; background: #ffbc00; border: none; font-weight: bold; font-size: 16px; border-radius: 6px; cursor: pointer; color: #333; }
        button:hover { background: #e0a600; }
        .msg { text-align: center; margin-top: 15px; color: blue; font-weight: bold; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #666; }
    </style>
</head>
<body>
    <header><h1>🏦 est은행 인터넷뱅킹</h1></header>
    <div class="container">
        <h2>신규 회원가입</h2>
        <?php if($msg) echo "<p class='msg'>$msg</p>"; ?>
        <form method="POST" action="">
            <label>아이디</label>
            <input type="text" name="userid" required placeholder="아이디 입력">
            
            <label>비밀번호</label>
            <input type="password" name="password" required placeholder="비밀번호 입력">
            
            <label>성명</label>
            <input type="text" name="name" required placeholder="실명 입력">
            
            <label>전화번호</label>
            <input type="text" name="phone" required placeholder="010-0000-0000">
            
            <label>계좌번호</label>
            <input type="text" name="account_number" required placeholder="예: 110-xxx-xxxxxx">
            
            <button type="submit">회원가입 완료</button>
        </form>
        <a href="index.php" class="back-link">&lt; 메인 화면으로 돌아가기</a>
    </div>
</body>
</html>
