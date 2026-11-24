#!/bin/sh
# fw 방화벽 규칙 (docs/matrix.md 기준)

# 1. 초기화: 기존 FORWARD 규칙을 지우고, 기본 정책을 차단으로
iptables -F FORWARD
iptables -P FORWARD DROP

# 2. 이미 허용된 연결의 응답과 관련 패킷은 통과
iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

# 3. 매트릭스 1번: 외부(outside) -> dmz-lb 80
iptables -A FORWARD -s 192.168.156.0/24 -d 192.168.155.10 -p tcp --dport 80 -j ACCEPT

# 4. 매트릭스 5번: dmz 웹 서버 두 대 -> intra-db 3306
iptables -A FORWARD -s 192.168.155.200 -d 192.168.153.103 -p tcp --dport 3306 -j ACCEPT
iptables -A FORWARD -s 192.168.155.201 -d 192.168.153.103 -p tcp --dport 3306 -j ACCEPT

# 5. 그 밖의 모든 것은 기본 정책(DROP)으로 차단됨

echo "fw rules applied"
iptables -L FORWARD -n --line-numbers
