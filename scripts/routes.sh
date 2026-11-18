#!/bin/bash
# 다른 망으로 가는 길을 fw로 지정 (컨테이너를 다시 띄울 때마다 실행)

set_route() {
  name=$1; net=$2; gw=$3
  pid=$(docker inspect -f '{{.State.Pid}}' "$name") || return
  nsenter -t "$pid" -n ip route replace "$net" via "$gw"
  echo "$name: $net -> $gw"
}

# dmz 컨테이너: intra, outside로 갈 땐 fw의 dmz 주소로
for c in dmz-lb dmz-web1 dmz-web2; do
  set_route $c 192.168.153.0/24 192.168.155.254
  set_route $c 192.168.156.0/24 192.168.155.254
done

# intra 컨테이너: dmz, outside로 갈 땐 fw의 intra 주소로
for c in intra-db intra-web; do
  set_route $c 192.168.155.0/24 192.168.153.254
  set_route $c 192.168.156.0/24 192.168.153.254
done
