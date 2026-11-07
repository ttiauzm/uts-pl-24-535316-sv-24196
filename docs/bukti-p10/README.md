# Bukti P10

Simpan screenshot dengan nama berikut agar otomatis muncul di `docs/laporan-p10.md`.

| File | Isi screenshot |
|---|---|
| `01-compose-ps-healthy.png` | `docker compose ps` saat semua service sehat |
| `02-health-healthy.png` | `curl -i http://localhost:8000/health` saat sehat (HTTP 200) |
| `03-log-structured.png` | `docker compose logs` berisi log JSON terstruktur |
| `04-compose-ps-unhealthy.png` | `docker compose ps -a` setelah `docker compose stop db` |
| `05-health-unhealthy.png` | `curl -i http://localhost:8000/health` saat db mati (HTTP 503) |
| `06-log-db-failure.png` | log `health.check_failed` / `database.operation_failed` |
| `07-compose-ps-recovered.png` | `docker compose ps` setelah `docker compose start db` |
| `08-health-recovered.png` | `/health` kembali HTTP 200 |
| `09-log-rotation.png` | output `docker inspect` yang menunjukkan `max-size` dan `max-file` |

Screenshot boleh berupa terminal atau browser. Pastikan teks status/log terbaca.
