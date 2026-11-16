<?php
include 'db.php';

// 관리자 세션 검증
if (!isset($_SESSION['userid']) || $_SESSION['userid'] !== 'admin') {
    echo "<script>alert('관리자 권한이 없습니다.'); location.href='admin_login.php';</script>";
    exit;
}

// [1] 관리자에 의한 게시글 수정 처리 (첨부파일 추가/변경/삭제 포함)
if (isset($_POST['update_board'])) {
    $board_id = intval($_POST['board_id']);
    $title = $_POST['title'];
    $content = $_POST['content'];
    $delete_file = isset($_POST['delete_file']) ? $_POST['delete_file'] : 'no';

    $upload_dir = '/var/www/html/uploads/';

    // 기존 파일 정보 조회
    $stmt = $conn->prepare("SELECT filename FROM board WHERE id = ?");
    $stmt->bind_param("i", $board_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row_file = $res->fetch_assoc();
    $existing_filename = $row_file['filename'];
    $stmt->close();

    $new_filename = $existing_filename;

    // 1) 사용자가 기존 파일 삭제를 체크했거나 새로운 파일을 올리는 경우 기존 파일 물리 삭제
    if ($delete_file === 'yes' || (isset($_FILES['new_file']) && $_FILES['new_file']['error'] === UPLOAD_ERR_OK)) {
        if (!empty($existing_filename) && file_exists($upload_dir . $existing_filename)) {
            unlink($upload_dir . $existing_filename);
        }
        $new_filename = '';
    }

    // 2) 새로운 파일 업로드 처리
    if (isset($_FILES['new_file']) && $_FILES['new_file']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['new_file']['tmp_name'];
        $original_name = basename($_FILES['new_file']['name']);
        // 파일명 중복 방지를 위한 타임스탬프 접두사
        $new_filename = time() . "_" . $original_name;
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        move_uploaded_file($tmp_name, $upload_dir . $new_filename);
    }

    // DB 업데이트 실행
    $update_stmt = $conn->prepare("UPDATE board SET title = ?, content = ?, filename = ? WHERE id = ?");
    $update_stmt->bind_param("sssi", $title, $content, $new_filename, $board_id);
    $update_stmt->execute();
    $update_stmt->close();

    echo "<script>alert('게시글과 첨부파일이 성공적으로 수정되었습니다.'); location.href='admin_index.php';</script>";
    exit;
}

// [2] 관리자에 의한 게시글 삭제 처리 (첨부파일 물리 삭제 포함)
if (isset($_GET['delete_board_id'])) {
    $board_id = intval($_GET['delete_board_id']);
    
    $stmt = $conn->prepare("SELECT filename FROM board WHERE id = ?");
    $stmt->bind_param("i", $board_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row_file = $res->fetch_assoc()) {
        $upload_dir = '/var/www/html/uploads/'; 
        if (!empty($row_file['filename']) && file_exists($upload_dir . $row_file['filename'])) {
            unlink($upload_dir . $row_file['filename']);
        }
    }
    $stmt->close();

    $del_stmt = $conn->prepare("DELETE FROM board WHERE id = ?");
    $del_stmt->bind_param("i", $board_id);
    $del_stmt->execute();
    $del_stmt->close();
    
    echo "<script>alert('게시글이 안전하게 삭제되었습니다.'); location.href='admin_index.php';</script>";
    exit;
}

// 데이터 조회
$users_result = $conn->query("SELECT id, userid, name, phone, account_number, regdate FROM users");
$board_result = $conn->query("SELECT id, title, userid, writer, content, filename, regdate FROM board ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>관리자 제어 센터 - INTRA</title>
    <style>
        body { font-family: 'Malgun Gothic', sans-serif; margin: 0; background-color: #f4f6f7; }
        header { background-color: #1a252f; color: white; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; }
        .container { width: 1300px; margin: 30px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-top: 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 14px; vertical-align: middle; }
        th { background-color: #2c3e50; color: white; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .del-btn { background: #e74c3c; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; display: inline-block; margin-top: 4px; }
        .del-btn:hover { background: #c0392b; }
        .edit-btn { background: #2980b9; color: white; padding: 6px 12px; border: none; border-radius: 4px; font-size: 13px; cursor: pointer; display: inline-block; }
        .edit-btn:hover { background: #1f618d; }
        
        .modal { display: none; position: fixed; z-index: 10; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 6% auto; padding: 30px; border-radius: 8px; width: 550px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .modal-content h3 { margin-top: 0; color: #2c3e50; }
        .modal-content input[type="text"], .modal-content textarea, .modal-content input[type="file"] { width: 100%; padding: 10px; margin-top: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
        .modal-content textarea { height: 130px; resize: vertical; }
        .modal-btns { text-align: right; }
        .save-btn { background: #27ae60; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .close-btn { background: #95a5a6; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; }
    </style>
    <script>
        function openEditModal(id, title, content, filename) {
            document.getElementById('edit_board_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_content').value = content;
            
            let fileInfoHtml = filename ? "현재 파일: <b>" + filename + "</b>" : "현재 첨부된 파일이 없습니다.";
            document.getElementById('current_file_info').innerHTML = fileInfoHtml;
            document.getElementById('delete_file_checkbox').checked = false;
            document.getElementById('new_file').value = "";
            
            document.getElementById('editModal').style.display = 'block';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</head>
<body>
    <header>
        <h1>🛡️ 관리자 제어 센터 (INTRA - 망분리 보안 환경)</h1>
        <a href="logout.php" class="logout-btn">로그아웃</a>
    </header>
    <div class="container">
        
        <h2>📋 가입된 사용자 목록 (DMZ 연동)</h2>
        <table>
            <tr>
                <th>번호</th>
                <th>아이디</th>
                <th>이름</th>
                <th>전화번호</th>
                <th>계좌번호</th>
                <th>가입일시</th>
            </tr>
            <?php while($row = $users_result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['userid']) ?></td>
                <td><?= $row['name'] ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['account_number']) ?></td>
                <td><?= $row['regdate'] ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <h2>💬 1:1 상담 게시판 통합 관리 및 제어</h2>
        <table>
            <tr>
                <th style="width: 6%;">글번호</th>
                <th style="width: 18%;">제목</th>
                <th style="width: 25%;">본문 내용</th>
                <th style="width: 10%;">작성자</th>
                <th style="width: 16%;">첨부파일 (풀네임)</th>
                <th style="width: 12%;">작성일시</th>
                <th style="width: 13%;">관리 제어</th>
            </tr>
            <?php while($board_row = $board_result->fetch_assoc()): ?>
            <tr>
                <td><?= $board_row['id'] ?></td>
                <td style="text-align: left; font-weight: bold;"><?= htmlspecialchars($board_row['title']) ?></td>
                <td style="text-align: left; font-size: 13px; color: #555;"><?= nl2br(htmlspecialchars($board_row['content'])) ?></td>
                <td><?= htmlspecialchars($board_row['writer']) ?><br><span style="font-size:11px; color:#888;">(<?= htmlspecialchars($board_row['userid']) ?>)</span></td>
                <td style="font-size: 13px; word-break: break-all;">
                    <?= !empty($board_row['filename']) ? htmlspecialchars($board_row['filename']) : '없음' ?>
                </td>
                <td style="font-size: 12px;"><?= $board_row['regdate'] ?></td>
                <td>
                    <button class="edit-btn" onclick="openEditModal(<?= $board_row['id'] ?>, '<?= htmlspecialchars(addslashes($board_row['title'])) ?>', '<?= htmlspecialchars(addslashes($board_row['content'])) ?>', '<?= htmlspecialchars(addslashes($board_row['filename'])) ?>')">수정</button>
                    <a href="admin_index.php?delete_board_id=<?= $board_row['id'] ?>" class="del-btn" onclick="return confirm('정말 이 게시글을 강제 삭제하시겠습니까?');">강제삭제</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

    </div>

    <!-- 게시글 및 첨부파일 수정 모달 팝업 -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>✏️ 1:1 상담 게시글 및 첨부파일 수정</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="board_id" id="edit_board_id">
                
                <label style="font-weight:bold; font-size:13px;">제목</label>
                <input type="text" name="title" id="edit_title" required>
                
                <label style="font-weight:bold; font-size:13px;">본문 내용</label>
                <textarea name="content" id="edit_content" required></textarea>
                
                <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #ddd;">
                    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:5px;">첨부파일 관리</label>
                    <p id="current_file_info" style="font-size: 13px; margin: 0 0 8px 0; color: #333;"></p>
                    
                    <div style="margin-bottom: 8px;">
                        <input type="checkbox" name="delete_file" value="yes" id="delete_file_checkbox">
                        <label for="delete_file_checkbox" style="font-size: 13px; color: #c0392b; display:inline; cursor:pointer;">현재 첨부파일 삭제하기</label>
                    </div>

                    <label style="font-size: 12px; color: #555; display:block; margin-top:5px;">새로운 파일로 교체(업로드)</label>
                    <input type="file" name="new_file" id="new_file" style="margin-bottom: 0;">
                </div>

                <div class="modal-btns">
                    <button type="button" class="close-btn" onclick="closeEditModal()">취소</button>
                    <button type="submit" name="update_board" class="save-btn">수정 완료</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
