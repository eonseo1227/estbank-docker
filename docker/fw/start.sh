#!/bin/sh
# 1. 방화벽 규칙 적용
sh /rules.sh

# 2. 이전 실행이 남긴 pid 파일 제거 (없으면 그냥 넘어감)
rm -f /var/run/suricata.pid

# 3. Suricata 실행: eth 인터페이스를 모두 지정
mkdir -p /var/log/suricata
IFACES=""
for i in $(ls /sys/class/net/ | grep '^eth'); do
  IFACES="$IFACES -i $i"
done
suricata -S /suricata.rules $IFACES -l /var/log/suricata -D \
  --set vars.address-groups.HOME_NET="[192.168.155.0/24,192.168.153.0/24]"

echo "fw + suricata started on:$IFACES"

# 4. 계속 실행
exec sleep infinity
