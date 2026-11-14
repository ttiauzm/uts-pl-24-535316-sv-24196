#!/usr/bin/env bash
# Membangkitkan trafik ke backend agar panel "permintaan per detik" terisi.
# Pakai: bash scripts/traffic.sh [jumlah_detik]   (default 120)
DURASI="${1:-120}"
END=$((SECONDS + DURASI))
echo "Mengirim trafik ke http://localhost:8000 selama ${DURASI} detik..."
while [ "$SECONDS" -lt "$END" ]; do
  curl -s -o /dev/null http://localhost:8000/health
  curl -s -o /dev/null -X POST http://localhost:8000/api/login \
    -H 'Content-Type: application/json' \
    -d '{"email":"test@example.com","password":"password"}'
  sleep 0.2
done
echo "Selesai."
