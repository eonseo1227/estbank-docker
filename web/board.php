<?php
include 'db.php';
if (!isset($_SESSION['userid'])) {
    echo "<script>alert('로그인 후 이용 가능합니다.'); location.href='login.php';</script>";
    exit;
}

$current_user = $_SESSION['userid'];
$current_name = $_SESSION['name'];
$is_admin = ($current_user === 'admin');
$upload_dir = 'uploads/';

// 1. 새 글 등록 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['write'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $filename = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_name = basename($_FILES['attachment']['name']);
        $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $file_name);
        move_uploaded_file($file_tmp, $upload_dir . $filename);
    }

    $stmt = $conn->prepare("INSERT INTO board (title, userid, writer, content, filename) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $current_user, $current_name, $content, $filename);
    $stmt->execute();
    $stmt->close();
    header("Location: board.php");
    exit;
}

// 2. 글 수정 및 첨부파일 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    $stmt_file = $conn->prepare("SELECT userid, filename FROM board WHERE id = ?");
    $stmt_file->bind_param("i", $id);
    $stmt_file->execute();
    $row_file = $stmt_file->get_result()->fetch_assoc();
    $stmt_file->close();

    if ($is_admin || ($row_file && $row_file['userid'] === $current_user)) {
        $filename = $row_file['filename'];

        if (isset($_POST['delete_file']) && $_POST['delete_file'] === 'yes') {
            if ($filename && file_exists($upload_dir . $filename)) {
                unlink($upload_dir . $filename);
            }
            $filename = null;
        }

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            if ($filename && file_exists($upload_dir . $filename)) {
                unlink($upload_dir . $filename);
            }
            $file_tmp = $_FILES['attachment']['tmp_name'];
            $file_name = basename($_FILES['attachment']['name']);
            $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $file_name);
            move_uploaded_file($file_tmp, $upload_dir . $filename);
        }

        $stmt = $conn->prepare("UPDATE board SET title = ?, content = ?, filename = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $content, $filename, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: board.php");
    exit;
}

// 3. 글 삭제 처리
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    $stmt_file = $conn->prepare("SELECT userid, filename FROM board WHERE id = ?");
    $stmt_file->bind_param("i", $delete_id);
    $stmt_file->execute();
    $row_file = $stmt_file->get_result()->fetch_assoc();
    $stmt_file->close();

    if ($is_admin || ($row_file && $row_file['userid'] === $current_user)) {
        if ($row_file['filename'] && file_exists($upload_dir . $row_file['filename'])) {
            unlink($upload_dir . $row_file['filename']);
        }
        $stmt = $conn->prepare("DELETE FROM board WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: board.php");
    exit;
}

// 수정 모드
$edit_row = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $query = "SELECT * FROM board WHERE id = " . $edit_id;
    $edit_row = $conn->query($query)->fetch_assoc();
}

// 선택된 게시글 상세보기 ID (제목 클릭 시)
$view_id = isset($_GET['view_id']) ? $_GET['view_id'] : null;

// [추가] Blind SQL Injection 실습용 서버 측 검색 처리 로직
$search_result_msg = "";
if (isset($_GET['search_keyword'])) {
    $s_key = $_GET['search_keyword'];
    
    // [취약점 포인트] 데이터의 내용을 보여주지 않고 참/거짓(존재 여부) 반응만 반환
    $query = "SELECT * FROM board WHERE title LIKE '%$s_key%'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $search_result_msg = "<span style='color:green;'>[True] 검색된 게시글이 존재합니다.</span>";
    } else {
        $search_result_msg = "<span style='color:red;'>[False] 검색된 게시글이 없습니다.</span>";
    }
}

$board_list = $conn->query("SELECT * FROM board ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>1:1 상담 게시판 - est은행</title>
    <style>
        body { font-family: 'Malgun Gothic', sans-serif; margin: 0; background-color: #f8f9fa; }
        header { background-color: #4A2E18; color: white; padding: 20px 50px; }
        .container { width: 85%; max-width: 900px; margin: 30px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #e1e8ed; padding: 12px; }
        th { background: #f4f6f9; color: #4A2E18; }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 10px; margin: 8px 0 20px 0; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .btn-group { display: flex; gap: 10px; }
        button { padding: 10px 20px; background: #4A2E18; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:hover { opacity: 0.9; }
        .btn-delete { background: #dc3545; padding: 5px 10px; font-size: 13px; text-decoration: none; color: white; border-radius: 4px; display: inline-block; }
        .btn-edit { background: #ffc107; padding: 5px 10px; font-size: 13px; text-decoration: none; color: #333; border-radius: 4px; display: inline-block; font-weight: bold; }
        .file-link { color: #0066cc; text-decoration: none; font-size: 13px; }
        .file-link:hover { text-decoration: underline; }
        .title-link { color: #333; text-decoration: none; font-weight: bold; cursor: pointer; }
        .title-link:hover { color: #0066cc; text-decoration: underline; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #666; }
        .content-box { background: #fafbfc; padding: 20px; border: 1px solid #e1e8ed; margin-top: 5px; border-radius: 6px; }
    </style>
</head>
<body>
    <header><h1>🏦 est은행 1:1 상담 게시판 (<?php echo $is_admin ? '관리자 모드' : '회원 전용'; ?>)</h1></header>
    <div class="container">
        <a href="index.php" class="back-link">&lt; 메인으로 돌아가기</a>
        
        <?php if ($edit_row): ?>
            <h3>문의글 수정하기</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>">
                <label>제목</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($edit_row['title']); ?>" required>
                <label>내용</label>
                <textarea name="content" rows="4" required><?php echo htmlspecialchars($edit_row['content']); ?></textarea>
                
                <label>첨부파일 관리</label>
                <?php if (!empty($edit_row['filename'])): ?>
                    <div style="background:#f8f9fa; padding:10px; border-radius:6px; margin-bottom:15px; border:1px solid #ddd;">
                        <p style="font-size:13px; margin:0 0 8px 0;">현재 첨부파일: <a href="uploads/<?php echo $edit_row['filename']; ?>" target="_blank" class="file-link"><?php echo htmlspecialchars($edit_row['filename']); ?></a></p>
                        <label style="font-weight:normal; font-size:13px; color:#dc3545; cursor:pointer;">
                            <input type="checkbox" name="delete_file" value="yes"> 기존 첨부파일 삭제하기
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="attachment">

                <div class="btn-group">
                    <button type="submit" name="update">수정 완료</button>
                    <a href="board.php" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px; text-align: center; font-weight: bold;">취소</a>
                </div>
            </form>
        <?php else: ?>
            <h3>새 문의 등록 (작성자: <?php echo htmlspecialchars($current_name); ?><?php echo $is_admin ? ' [관리자]' : ''; ?>)</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <label>제목</label>
                <input type="text" name="title" required placeholder="문의 제목">
                <label>내용</label>
                <textarea name="content" rows="4" required placeholder="문의 내용 작성"></textarea>
                <label>첨부파일</label>
                <input type="file" name="attachment">
                <button type="submit" name="write">등록하기</button>
            </form>
        <?php endif; ?>

        <hr style="margin: 40px 0; border:0; border-top:1px solid #eee;">

        <h3>상담 내역 목록</h3>
        
        <!-- 게시판 검색 영역 (Blind SQLi 실습용 서버 연동 폼) -->
        <div style="background:#f1f3f5; padding:15px; border-radius:8px; margin-bottom:20px;">
            <label style="font-weight:bold; font-size:14px;">게시판 내 검색 </label>
            <form method="GET" action="board.php" style="display:flex; gap:10px; margin-top:5px;">
                <input type="text" name="search_keyword" value="<?php echo isset($_GET['search_keyword']) ? htmlspecialchars($_GET['search_keyword']) : ''; ?>" placeholder="검색어를 입력하세요" style="margin:0;">
                <button type="submit" style="white-space:nowrap;">검색</button>
            </form>
            <div style="margin-top:10px; font-weight:bold;">
                <?php echo $search_result_msg; ?>
            </div>
        </div>

        <table>
            <tr>
                <th width="8%">번호</th>
                <th width="38%">제목</th>
                <th width="16%">작성자</th>
                <th width="22%">작성일</th>
                <th width="16%">관리</th>
            </tr>
            <?php while($row = $board_list->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <a href="board.php?view_id=<?php echo $row['id']; ?>" class="title-link">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($row['writer']); ?></td>
                    <td><?php echo $row['regdate']; ?></td>
                    <td>
                        <?php if ($is_admin || (isset($row['userid']) && $row['userid'] === $current_user)): ?>
                            <a href="board.php?edit_id=<?php echo $row['id']; ?>" class="btn-edit">수정</a>
                            <a href="board.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('정말 이 문의글을 삭제하시겠습니까?');">삭제</a>
                        <?php else: ?>
                            <span style="color:#aaa; font-size:12px;">권한 없음</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($view_id == $row['id']): ?>
                <tr>
                    <td colspan="5" style="background:#fafbfc; padding:20px;">
                        <div class="content-box">
                            <strong>[본문 내용]</strong><br><br>
                            <div><?php echo $row['content']; ?></div><br>
                            <?php if (!empty($row['filename'])): ?>
                                📎 <strong>첨부파일:</strong> <a href="uploads/<?php echo $row['filename']; ?>" target="_blank" class="file-link"><?php echo htmlspecialchars($row['filename']); ?></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
