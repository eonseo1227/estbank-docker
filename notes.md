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
