# Bukti P11

Simpan screenshot dengan nama berikut agar otomatis muncul di `docs/laporan-p11.md`.

| File | Isi screenshot |
|---|---|
| `01-compose-ps.png` | `docker compose ps` — 7 service berstatus Up (healthy) |
| `02-targets-up.png` | `http://localhost:9090/targets` — seluruh target UP |
| `03-dashboard-normal.png` | Dashboard Grafana lengkap (4 panel terisi) |
| `04-alerts-inactive.png` | `http://localhost:9090/alerts` — LayananMati berstatus Inactive (hijau) |
| `05-alerts-pending.png` | (opsional) LayananMati berstatus Pending setelah `docker compose stop backend` |
| `06-alerts-firing.png` | `http://localhost:9090/alerts` — LayananMati berstatus FIRING + anotasi |
| `07-dashboard-backend-mati.png` | Dashboard saat backend mati (panel status = MATI) |
| `08-grafana-password.png` | Bukti `admin/admin` ditolak (401) dan password baru diterima (200) |

Jangan tampilkan password asli di screenshot manapun.
