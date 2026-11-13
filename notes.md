3차  프로젝트
1. 첫 트러블 슈팅
## 11/13 docker1 인터넷 안 됨
- 증상: ping google.com → "알 수 없는 이름", 8.8.8.8도 안 됨
- 확인: VM의 IP·게이트웨이·DNS는 정상 (192.168.159.50 / gw .2 / dns 8.8.8.8)
- 원인: Windows의 VMware DHCP Service가 "사용 안 함", VMnet8 설정 꼬임
- 해결: 서비스 시작 유형을 자동으로, Virtual Network Editor에서 VMnet8 설정 다시 적용 → 정상
