<?php
//에러 출력 설정을 파일 최상단으로 이동
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';
?>
<?php
//CSP 헤더에 style-src 'unsafe-inline' 추가 및 외부 이미지(Unsplash) 도메인 허용
header("Content-Security-Policy: default-src 'self' https://images.unsplash.com; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>est은행 고객센터</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Malgun Gothic', 'Apple SD Gothic Neo', sans-serif; background-color: #f4f6f9; color: #333; }
        
        /* 상단 네비게이션 바 */
        .top-bar { background-color: #2c2c2c; color: #bbb; padding: 8px 60px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .top-bar .links a { margin-left: 20px; text-decoration: none; color: #bbb; transition: 0.2s; }
        .top-bar .links a:hover { color: #ffbc00; }
        
        /* 헤더 메인 */
        header { background-color: #ffffff; border-bottom: 1px solid #e1e8ed; padding: 20px 60px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .header-title { font-size: 24px; font-weight: bold; text-decoration: none; color: #4A2E18; display: flex; align-items: center; gap: 10px; }
        .nav-links a { color: #444; text-decoration: none; margin-left: 30px; font-size: 15px; font-weight: 600; transition: 0.2s; }
        .nav-links a:hover { color: #b8860b; }
        
        /* 비주얼 배너 영역 */
        .hero-banner {
            position: relative;
            background: linear-gradient(rgba(74, 46, 24, 0.85), rgba(40, 24, 12, 0.9)), url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1600&q=80') no-repeat center center/cover;
            color: white;
            padding: 80px 60px;
            text-align: center;
        }
        .hero-banner h1 { font-size: 38px; margin-bottom: 15px; font-weight: 700; letter-spacing: -1px; }
        .hero-banner p { font-size: 17px; color: #e2e8f0; margin-bottom: 30px; }
        
        /* 메인 컨테이너 */
        .main-container { width: 85%; max-width: 1200px; margin: -40px auto 60px auto; position: relative; z-index: 10; }
        
        /* 서비스 카드 그리드 */
        .menu-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .menu-card { 
            background: #ffffff; 
            border-top: 4px solid #b8860b; 
            padding: 40px 30px; 
            border-radius: 12px; 
            text-decoration: none; 
            color: #333; 
            transition: all 0.3s ease; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            text-align: left;
        }
        .menu-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.12); 
            border-top-color: #ffbc00;
        }
        .menu-card .icon { font-size: 32px; margin-bottom: 20px; }
        .menu-card h3 { color: #2c2c2c; margin-bottom: 12px; font-size: 20px; font-weight: 700; }
        .menu-card p { color: #666; font-size: 14px; line-height: 1.5; margin-bottom: 0; }
        
        /* 하단 고객센터 안내 바 */
        .info-footer { background: #ffffff; border-radius: 12px; padding: 30px 40px; margin-top: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; }
        .info-footer div h4 { font-size: 18px; color: #4A2E18; margin-bottom: 5px; }
        .info-footer div p { color: #666; font-size: 14px; }
        .cs-center { font-size: 24px; font-weight: bold; color: #b8860b; }
    </style>
</head>
<body>
    <div class="top-bar">
        <span>est금융그룹 계열 | 통합 고객센터</span>
        <div class="links">
            <?php if (isset($_SESSION['userid'])): ?>
                <span><strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>님 접속중</span>
                <a href="mypage.php">마이페이지</a>
                <a href="logout.php">로그아웃</a>
            <?php else: ?>
                <a href="login.php">로그인</a>
                <a href="register.php">회원가입</a>
            <?php endif; ?>
        </div>
    </div>
    
    <header>
        <a href="index.php" class="header-title">🏦 est은행</a>
        <div class="nav-links">
            <a href="index.php">홈</a>
            <?php if (isset($_SESSION['userid'])): ?>
                <a href="mypage.php">고객정보관리</a>
                <a href="board.php">1:1 상담게시판</a>
                <a href="logout.php">로그아웃</a>
            <?php else: ?>
                <a href="login.php">로그인</a>
                <a href="register.php">회원가입</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- 세련된 금융 감성 배너 이미지 적용 -->
    <div class="hero-banner">
        <h1>고객님, 무엇을 도와드릴까요?</h1>
        <p>안전하고 신뢰할 수 있는 디지털 금융 서비스, est은행이 함께합니다.</p>
    </div>

    <div class="main-container">
        <div class="menu-grid">
            <a href="<?php echo isset($_SESSION['userid']) ? 'mypage.php' : 'login.php'; ?>" class="menu-card">
                <div class="icon">👤</div>
                <h3>고객정보관리</h3>
                <p>내 계좌번호, 연락처 등 개인정보를 안전하게 조회하고 수정하거나 회원탈퇴를 진행할 수 있습니다.</p>
            </a>
            <a href="<?php echo isset($_SESSION['userid']) ? 'board.php' : 'login.php'; ?>" class="menu-card">
                <div class="icon">💬</div>
                <h3>1:1 상담 게시판</h3>
                <p>로그인한 회원 전용 공간입니다. 금융 관련 문의사항을 남겨주시면 신속하게 답변해 드립니다.</p>
            </a>
            <a href="register.php" class="menu-card">
                <div class="icon">📝</div>
                <h3>신규 회원가입</h3>
                <p>est은행 인터넷뱅킹의 새로운 가족이 되어 다양한 금융 혜택과 서비스를 경험해보세요.</p>
            </a>
        </div>

        <!-- 하단 고객센터 안내 바 -->
        <div class="info-footer">
            <div>
                <h4>est은행 고객센터 이용안내</h4>
                <p>평일 09:00 ~ 18:00 (주말 및 공휴일 휴무)</p>
            </div>
            <div class="cs-center">
                📞 1588-0000
            </div>
        </div>

        <!-- 📋 이용안내 및 약관 조회 영역 -->
        <div style="background: white; padding: 30px; border-radius: 12px; margin-top: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
            <h3 style="margin-bottom: 15px; color: #4A2E18;">📋 이용안내 및 약관 조회</h3>
            <p style="margin-bottom: 15px; font-size: 14px; color: #666;">필요한 약관이나 안내 문서를 선택하여 확인하실 수 있습니다.</p>
            
            <div style="margin-bottom: 15px;">
                <a href="?doc=terms.txt" style="margin-right: 15px; color: #b8860b; text-decoration: none; font-weight: bold;">[이용약관]</a>
                <a href="?doc=privacy.txt" style="color: #b8860b; text-decoration: none; font-weight: bold;">[개인정보처리방침]</a>
            </div>

            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #ddd; min-height: 50px; line-height: 1.6;">
                <?php
                // LFI 방어 적용: basename 함수 사용
                if (isset($_GET['doc'])) {
                    $doc = $_GET['doc'];
                    include($doc); 
                } else {
                    echo "조회할 문서를 선택해주세요.";
                }
                ?>
            </div>
        </div>

    </div> <!-- main-container 끝 -->
</body>
</html>
