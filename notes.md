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

## 11/18 fw 컨테이너 1: 라우팅 (11/23 일정 앞당겨 수행)

### 한 일
- fw 이미지: alpine:3.20 + iproute2, iptables, tcpdump (CMD sleep infinity로 계속 실행)
- fw를 outside·dmz·intra 세 망에 .254로 연결 (2차 pfSense 인터페이스 주소와 동일)
  - fw 인터페이스: eth? = outside(156.254), eth? = dmz(155.254), eth? = intra(153.254)
- fw에만 cap_add NET_ADMIN·NET_RAW 부여 (최소 권한), sysctl net.ipv4.ip_forward=1로 라우터 역할
- scripts/routes.sh: nsenter로 각 컨테이너 네트워크에 들어가 다른 망 경로를 fw로 지정
  - dmz 컨테이너: 153.0/24, 156.0/24 → 155.254
  - intra 컨테이너: 155.0/24, 156.0/24 → 153.254
- 확인
  - dmz-web1 → intra-db 3306 연결 성공 (fsockopen → resource)
  - 브라우저 8080에서 est_bank 다시 정상 동작
  - tcpdump: 패킷이 dmz 쪽 In → intra 쪽 Out으로 fw를 통과 (결과: )
  - fw 중지 시 웹→DB 끊김 → fw 외에 다른 길이 없음 (결과: )

### 겪은 문제
- VMware "This virtual machine appears to be in use"
  - 원인: VM이 켜진 채 VMware/PC가 종료되어 잠금 파일(.lck)이 남음
  - 해결: 다른 곳에서 실행 중이 아닌 것 확인 후 Take Ownership
- compose up 시 "did not find expected key (L3~L17)"
  - 원인: vi에 fw 블록을 붙여 넣으면서 들여쓰기가 어긋남
  - 해결: cat <<'EOF'로 compose 파일 전체 재작성, docker compose config --quiet로 문법 검사
- 경로 설정 후에도 웹→DB 실패 (bool(false))
  - 확인: intra-db의 ip route에 155.0/24 via 153.254가 없음 → 요청은 갔지만 응답이 돌아오지 못함
  - 원인 1: routes.sh 붙여 넣을 때 뒷부분(intra 반복문)이 잘려 저장됨 → 출력이 6줄뿐
  - 원인 2: 다시 만들 때 cat > ... <<'EOF' 명령 줄이 파일 첫 줄에 들어감 → "here-document" 경고
  - 해결: 파일 재작성 후 sed -i '1d'로 첫 줄 삭제, cat -n으로 21줄 확인, 재실행 시 10줄 출력

### 배운 점
- 라우팅은 갈 때와 올 때 양쪽 모두 필요함. 한쪽만 있으면 요청은 가도 응답이 사라짐
- 컨테이너가 재시작·재생성되면 routes.sh로 넣은 경로가 사라짐 → 항상 "docker compose up -d && ./scripts/routes.sh"
- 스크립트 출력 줄 수가 예상과 다르면 cat -n으로 파일 내용부터 확인
- compose 파일을 고치면 up 전에 docker compose config --quiet로 문법부터 검사
- cat <<'EOF' 블록은 vi 안이 아니라 [root@docker1 ...]# 프롬프트에 붙여 넣기
## 11/24 fw iptables 방화벽 규칙
### 한 일
- FORWARD 기본 정책 DROP + ESTABLISHED,RELATED 허용 (상태 기반, 2차 pfSense와 같은 방식)
- 허용 규칙: 외부→lb:80, web1·web2→db:3306 (매트릭스 1·5번), 나머지는 기본 정책으로 차단
- docker/fw/rules.sh 바인드 마운트, fw 시작 시 자동 적용
- scripts/test-matrix.sh 작성: bash /dev/tcp로 연결 시험
- 적용 전: 차단 대상 줄이 모두 허용됨(FAIL) → 적용 후: 전부 OK
### 겪은 문제
- sed로 compose에 command·volumes 삽입 → YAML 에러: 들여쓰기 어긋남 → 파일 전체 재작성
- docker.compose.yml(점) 옛날 파일이 따로 있었음 → 삭제
### 배운 점
- YAML은 줄 삽입보다 전체 재작성이 안전, up 전에 docker compose config --quiet로 검사

## 11/25 Kali 공격자 + Suricata IDS
### 한 일
- Kali 컨테이너를 outside(156.123)에 추가, nmap·hping3 설치, cap_add 세 개(NET_RAW·NET_ADMIN·NET_BIND_SERVICE)
- fw에 Suricata 설치, 탐지 규칙 3종(nmap 스캔·SYN Flood·SQL 인젝션)을 2차 규칙 기반으로 작성
- scripts/start.sh: 방화벽 규칙 적용 + Suricata 실행을 하나로
- nmap 스캔 → fast.log·eve.json에 LAB NMAP SYN Scan 탐지 확인
### 겪은 문제
- Kali 빌드 실패 1: iputils-ping 오타 + \ 뒤 공백으로 패키지명 깨짐 → RUN을 한 줄로
- Kali 빌드 실패 2: 미러 SSL 인증서 오류 → sources.list를 kali.download(http)로 고정
- nmap "Operation not permitted": nmap이 세 권한을 요구 → kali에 cap_add 세 개
- tail -f가 터미널을 붙잡아 다음 명령 안 됨 → Ctrl+C 후 -f 없이
- Suricata 안 뜸 1: -i any가 안 됨 → 실제 eth 인터페이스 지정
- Suricata 안 뜸 2: eth0@if 형태라 못 읽음 → /sys/class/net으로 깨끗한 이름
- Suricata 안 뜸 3: 낡은 pidfile 남아 abort → start.sh에서 rm -f 추가
### 배운 점
- 파일 권한(capability)이 걸린 프로그램은 요구 권한이 하나라도 빠지면 실행 자체가 안 됨
- 로그 안 쌓이면 1)트래픽이 닿는지 2)프로세스가 떴는지 3)로그 경로 순으로 확인

## 11/26 Elasticsearch + Kibana
### 한 일
- intra-elk(ES 8.15.3, 153.102), kibana(5601) 추가, 보안 비활성화(xpack.security.enabled=false)로 랩 단순화
- ES 메모리 512m 제한, 재부팅 대비 vm.max_map_count=262144 설정
- curl 9200 응답 확인(cluster GREEN), 브라우저 5601 Welcome 화면 확인
### 배운 점
- Elasticsearch는 vm.max_map_count를 미리 올려야 시작됨
- 2차에서 고생한 인증서·토큰을 랩에서는 보안 비활성화로 단순화(실무는 켜야 함)

## 11/27 Filebeat 로그 연결
### 한 일
- suricata-logs 볼륨으로 fw와 공유, filebeat(8.15.3)가 eve.json을 읽어 ES로 전송
- Kibana 데이터 뷰 suricata-* 생성, Discover에서 event_type:alert 조회
- 필드 추가(alert.signature, src_ip)로 표 정리
- Top values 분석: NMAP SYN Scan 53% / TCP SYN Flood 47%, 공격 출발지 192.168.156.123 100%
### 겪은 문제
- grep에 event type(공백)으로 쳐서 0 → event_type(밑줄)
- curl URL 앞 공백으로 400 에러 → 공백 제거
### 배운 점
- eve.json에는 alert 외에 flow·stats 등 여러 event_type이 섞임, 필요한 건 event_type:alert로 거름
- Suricata→Filebeat→Elasticsearch→Kibana 로그 파이프라인 전체 구성

## 11/29 2주차 점검
### 한 일
- scripts/up.sh 작성: docker compose up -d + routes.sh를 한 번에
- 전체 재기동·재부팅 테스트, restart: unless-stopped로 컨테이너 자동 복구 확인
- 재부팅 후에는 routes.sh 수동 실행 필요
- README 전체 구조로 갱신, .env.example 작성
### 배운 점
- 완성된 시스템은 명령 하나(up.sh)로 뜸, 단 재부팅 후 경로는 수동

## 11/30 통신 매트릭스 검증
### 한 일
- test-matrix.sh를 8줄로 보강(외부→내부 차단, DMZ→INTRA 제한 등)
- iptables -Z로 카운터 초기화 후 검증 → 8줄 모두 OK
- iptables -L -v로 규칙별 pkts + policy DROP 카운터 확인
- docs/results/matrix-verify.txt, fw-counters.txt 저장
### 배운 점
- 허용 통신은 각 규칙 통과, 차단은 policy DROP, 응답은 ESTABLISHED로 통과 (상태 기반 방화벽 증명)

## 12/1 공격 1·2 (포트 스캔 + SYN Flood)
### 한 일
- nmap 포트 스캔 → Suricata 탐지 캡처
- hping3 SYN Flood(-c 3000, 통제된 양) → 탐지 확인
- rules.sh에 SYN 속도 제한 규칙 추가(limit 25/s burst 50 ACCEPT + 초과 DROP)
- 차단 전: SYN 3000개 전부 통과 / 차단 후: 419개만 통과, 2581개 DROP
- curl 200 OK로 정상 접속은 유지됨 확인
### 배운 점
- 탐지(IDS)와 차단(방화벽)은 역할이 다름. Suricata는 탐지, iptables rate-limit은 완화
- 속도 제한은 공격만 거르고 정상 사용자는 통과

## 12/2 공격 3 (SQL 인젝션)
### 한 일
- board.php?edit_id= 취약점(입력을 쿼리에 그대로 연결) vs login.php(prepared statement) 비교
- Kali에서 curl 로그인으로 세션 획득
- UNION SELECT로 users 테이블 전체

