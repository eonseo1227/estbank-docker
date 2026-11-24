#!/bin/bash
# 통신 매트릭스 검증: 출발 컨테이너에서 목적지 IP:포트로 TCP 연결 시도
# (목적지에 실제 서비스가 뜬 포트로만 시험 → 연결=허용, 무응답=방화벽 차단)
check() {
  from=$1; ip=$2; port=$3; expect=$4; desc=$5
  if docker exec "$from" bash -c "timeout 3 bash -c '</dev/tcp/$ip/$port'" 2>/dev/null; then
    result="허용"
  else
    result="차단"
  fi
  if [ "$result" = "$expect" ]; then mark="OK  "; else mark="FAIL"; fi
  printf "%s | %-34s | %-9s -> %-16s:%-5s | 기대 %s / 결과 %s\n" \
         "$mark" "$desc" "$from" "$ip" "$port" "$expect" "$result"
}

echo "== 외부(outside)에서 들어오는 통신 =="
check kali     192.168.155.10  80   허용 "매트릭스1: 외부->lb:80 (고객 웹접속)"
check kali     192.168.155.200 80   차단 "매트릭스2: 외부->웹 직접 (lb 경유만)"
check kali     192.168.153.103 3306 차단 "매트릭스3: 외부->DB (내부망 보호)"
check kali     192.168.153.110 80   차단 "매트릭스3: 외부->관리자웹 (내부망 보호)"
echo "== DMZ에서 INTRA로 가는 통신 =="
check dmz-web1 192.168.153.103 3306 허용 "매트릭스5: 웹1->DB:3306"
check dmz-web2 192.168.153.103 3306 허용 "매트릭스5: 웹2->DB:3306"
check dmz-lb   192.168.153.103 3306 차단 "매트릭스6: lb->DB (웹서버만 허용)"
check dmz-web1 192.168.153.110 80   차단 "매트릭스7: 웹->관리자웹 (내부확산 차단)"
