# 통신 매트릭스

| 번호 | 출발지 | 목적지 | 포트 | 정책 | 이유 |
|---|---|---|---|---|---|
| 1 | kali (outside) | dmz-lb | 80 | 허용 | 외부 고객의 웹 접속 |
| 2 | kali (outside) | dmz-web1/2 | 전체 | 차단 | 웹은 lb를 거쳐서만 접속 |
| 3 | kali (outside) | intra 전체 | 전체 | 차단 | 내부망 보호 (2차 pfSense 정책과 동일) |
| 4 | dmz-lb | dmz-web1/2 | 80 | 허용 | 로드밸런싱 |
| 5 | dmz-web1/2 | intra-db | 3306 | 허용 | 웹 → DB 조회 |
| 6 | dmz-web1/2 | intra-db | 3306 외 | 차단 | 최소 권한 |
| 7 | dmz-web1/2 | intra-web, intra-elk | 전체 | 차단 | DMZ가 뚫려도 내부 확산 방지 |
| 8 | intra-web | intra-db | 3306 | 허용 | 관리자 웹 → DB |
| 9 | fw (Suricata 로그) | intra-elk | 9200 | 허용 | 로그 수집 |
