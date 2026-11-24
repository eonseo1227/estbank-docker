#!/bin/sh
# fw 방화벽 규칙 (docs/matrix.md 기준 + SYN Flood 완화)
iptables -F FORWARD
iptables -P FORWARD DROP

# 이미 맺어진 연결의 응답/관련 패킷은 통과 (상태 기반)
iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

# 외부 -> lb:80  SYN 속도 제한 (초당 25개 + 순간 50개까지 허용)
iptables -A FORWARD -s 192.168.156.0/24 -d 192.168.155.10 -p tcp --dport 80 --syn -m limit --limit 25/s --limit-burst 50 -j ACCEPT
# 제한을 넘는 SYN은 차단 (= SYN Flood 완화)
iptables -A FORWARD -s 192.168.156.0/24 -d 192.168.155.10 -p tcp --dport 80 --syn -j DROP

# dmz 웹 -> intra DB:3306
iptables -A FORWARD -s 192.168.155.200 -d 192.168.153.103 -p tcp --dport 3306 -j ACCEPT
iptables -A FORWARD -s 192.168.155.201 -d 192.168.153.103 -p tcp --dport 3306 -j ACCEPT

echo "fw rules applied"
iptables -L FORWARD -n --line-numbers
