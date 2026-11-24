#!/bin/bash
# 통신 매트릭스 검증: 출발 컨테이너에서 목적지 IP:포트로 TCP 연결을 시도
check() {
  from=$1; ip=$2; port=$3; expect=$4; desc=$5
  if docker exec "$from" bash -c "timeout 3 bash -c '</dev/tcp/$ip/$port'" 2>/dev/null; then
    result="허용"
  else
    result="차단"
  fi
  if [ "$result" = "$expect" ]; then mark="OK  "; else mark="FAIL"; fi
  echo "$mark | $desc | $from -> $ip:$port | 기대 $expect / 결과 $result"
}

check dmz-web1  192.168.153.103 3306 허용 "5. 웹1 -> DB"
check dmz-web2  192.168.153.103 3306 허용 "5. 웹2 -> DB"
check dmz-lb    192.168.153.103 3306 차단 "6. lb -> DB (웹 서버만 허용)"
check dmz-web1  192.168.153.110 80   차단 "7. 웹 -> 관리자 웹"
check intra-db  192.168.155.200 80   차단 "DB -> 웹 (내부에서 새 연결)"
check intra-web 192.168.155.200 80   차단 "관리자 웹 -> 웹 (내부에서 새 연결)"
