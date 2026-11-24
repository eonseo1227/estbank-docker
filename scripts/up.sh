#!/bin/bash
# 전체 기동: 컨테이너를 올리고 경로를 설정
docker compose up -d
echo "컨테이너 기동 대기 중..."
sleep 8
./scripts/routes.sh
echo "=== 완료. 현재 상태 ==="
docker compose ps --format "table {{.Name}}\t{{.Status}}"
