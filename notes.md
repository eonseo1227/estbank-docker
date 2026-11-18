3차  프로젝트
1. 첫 트러블 슈팅
## 11/13 docker1 인터넷 안 됨
- 증상: ping google.com → "알 수 없는 이름", 8.8.8.8도 안 됨
- 확인: VM의 IP·게이트웨이·DNS는 정상 (192.168.159.50 / gw .2 / dns 8.8.8.8)
- 원인: Windows의 VMware DHCP Service가 "사용 안 함", VMnet8 설정 꼬임
- 해결: 서비스 시작 유형을 자동으로, Virtual Network Editor에서 VMnet8 설정 다시 적용 → 정상

## 11/16 (11/17 일정 앞당겨 수행)
### 한 일
- 볼륨: 이름 있는 볼륨(testvol)은 컨테이너를 지워도 데이터가 남음을 확인
- 바인드 마운트: VM 폴더를 컨테이너에 연결, SELinux 때문에 :z 옵션 필요
- 네트워크: 다른 네트워크끼리는 ping 불가, network connect로 두 네트워크에 붙이면 가능 (fw 컨테이너 원리)
- compose: docker-compose.yml로 nginx up/down
- 2차 est_bank 소스(web, admin)와 DB 덤프를 저장소에 정리 (shell.php, system_diag.php 제외)
- 통신 매트릭스 작성 (docs/matrix.md)

### 겪은 문제
- a1 이름 충돌: sleep을 slepp으로 오타 → Created 상태로 멈춤 → docker rm -f 후 재생성
- compose pull access denied: 이미지 이름 오타(ngnix) → nginx로 수정
- git commit 후 > 프롬프트에서 멈춤: 앞쪽 따옴표 누락 → Ctrl+C 후 다시 입력

## 11/16 intra-db (11/18 일정 앞당겨 수행)
- mariadb:10.11 + est_bank.sql 자동 실행, est_user는 .env로 생성, 한글 정상
- 겪은 문제: Access denied → 비밀번호 오타(-p123345678)
- 배운 점: db/init의 SQL은 볼륨이 비어 있을 때만 실행됨, down -v는 데이터까지 삭제
## 11/16 dmz-web1 (11/19 일정 앞당겨 수행)
- php:8.0-apache + mysqli, 운영용 php.ini 사용 (2차 Rocky 9 PHP 8.0과 버전 맞춤)
- db.php 호스트: 192.168.219.109 → intra-db (컨테이너 이름으로 접속)
- uploads는 볼륨으로 분리 (2차 NFS 대체), 소유자 www-data
- 확인: 회원가입, 로그인, 게시판 글쓰기·첨부, mypage 정상
- 겪은 문제: dmz-web1이 안 뜸: vi에서 docker-compose.yml 수정 후 저장이 안 됨 → 파일 다시 작성
- YAML 에러(L2.C9 mapping values are not allowed): cat <<'EOF' 명령을 vi 안에 붙여 넣어 명령어 줄과 EOF 줄이 파일에 들어감 → sed로 첫 줄·마지막 줄 삭제
- 배운 점
- 파일을 고친 뒤에는 cat으로 내용이 제대로 저장됐는지 먼저 확인하기
- YAML 에러는 표시된 줄보다 바로 윗줄이 원인인 경우가 많음
- cat -A를 쓰면 줄 끝($)이나 탭(^I)처럼 눈에 안 보이는 문자까지 확인할 수 있음
- cat > 파일 <<'EOF' 명령은 vi 안이 아니라 터미널 프롬프트에 바로 붙여 넣어야 함

## 11/17 dmz-web2, dmz-lb (11/20 일정 앞당겨 수행)
- web2는 web1과 같은 이미지·볼륨 (2차 WEB1 클론과 같은 역할)
- Nginx lb: upstream으로 web1·web2 라운드 로빈, 8080은 lb만 공개
- PHP 세션 저장 위치를 볼륨(sessions)으로 공유 → 서버가 바뀌어도 로그인 유지
- 확인: X-Upstream 헤더로 분산 확인, web1 중지 시 web2로 자동 전환, 첨부파일 양쪽 공유
- 겪은 문제:게시판 글은 등록되는데 web1·web2의 uploads 폴더가 비어 있음
  - 확인: DB의 board.filename이 NULL → 파일이 PHP까지 도착하지 않음 (권한 문제였다면 이름은 기록됨)
  - 원인: 첨부파일을 선택하지 않았거나, 운영용 php.ini의 업로드 한도(2MB)를 넘는 파일
  - 해결: 작은 test.txt로 다시 업로드 → (결과 적기)
-배운 점
- 증상이 같아도 DB에 남은 값으로 원인을 좁힐 수 있음 (filename NULL = 전송 문제 / 이름 있음 + 파일 없음 = 저장·권한 문제)
- 운영용 PHP 설정은 업로드 크기를 2MB로 제한하고, 넘으면 에러 없이 첨부만 빠짐
- 로드밸런서 뒤에서는 첨부파일(uploads)과 로그인 정보(sessions)를 볼륨으로 공유해야 서버가 바뀌어도 동작함
- Nginx는 시작할 때 upstream 이름을 찾기 때문에, web이 꺼진 상태에서 lb를 재시작하면 실패함

## 11/17 intra-web, 네트워크 분리 (11/21 일정 앞당겨 수행)
- 네트워크 3개: outside 192.168.156.0/24, dmz 155.0/24, intra 153.0/24 (2차 대역 유지)
- 고정 IP: lb 155.10, web1 155.200, web2 155.201, db 153.103, intra-web 153.110
- intra-web: 관리자 소스 + uploads 볼륨 공유, 확인용으로 8081 임시 공개 (2주차에 제한)
- web/db.php: intra-db → 192.168.153.103 (Docker DNS는 같은 네트워크 안에서만 이름을 찾음)
- admin/db.php: localhost → intra-db (같은 intra 망)
- 확인: dmz-web1에서 DB 접속 불가(망분리 정상), 관리자 웹은 DB 접속 가능
- 겪은 문제:- outside 네트워크 inspect 시 "network estbank-docker_outside not found"
  - 원인: compose는 서비스가 연결된 네트워크만 만듦. outside에 붙은 컨테이너가 아직 없어서 생성 안 됨 (정상)
- 예전 기본 네트워크 estbank-docker_default가 남아 있음
  - 원인: 네트워크 구조를 바꾸기 전 모든 컨테이너가 쓰던 기본 네트워크가 사용되지 않은 채 남음
  - 해결: docker network rm estbank-docker_default
-배운 점
- Docker DNS는 같은 네트워크 안에서만 컨테이너 이름을 찾음 → 망이 다르면 IP로 접속해야 함 (web/db.php를 다시 192.168.153.103으로)
- 망을 나누면 웹(dmz)에서 DB(intra)로 가는 길이 끊김 → 망분리가 제대로 된 증거, fw로 필요한 포트(3306)만 다시 열어야 함
- compose는 정의만 하고 쓰지 않은 네트워크는 만들지 않음
- 네트워크 구조를 바꿀 때는 down → up으로 다시 만들되, -v를 빼면 DB·첨부파일 볼륨은 그대로 남음
