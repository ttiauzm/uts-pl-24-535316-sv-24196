# P11 — Pemantauan dengan Prometheus, Grafana, cAdvisor, dan node-exporter

**Nama:** Mutia Umniati Z  
**NIM:** 24/535316/SV/24196  
**Kelas:** PL5AA  
**Mata kuliah:** Konstruksi dan Evolusi Perangkat Lunak  
**Repository:** <https://github.com/ttiauzm/uts-pl-24-535316-sv-24196>

> **Catatan:** ganti semua `[TEMPEL ...]` dan pastikan gambar di `docs/bukti-p11/` sudah terisi
> sebelum diekspor ke PDF. Jangan mengumpulkan laporan yang masih berisi placeholder.

## 1. Ringkasan

| Kebutuhan | Implementasi |
|---|---|
| Empat layanan pemantau dalam satu compose | `prometheus`, `grafana`, `cadvisor`, `node-exporter` di `docker-compose.yml` |
| Versi image dikunci | `prom/prometheus:v3.5.0`, `grafana/grafana:12.2.0`, `gcr.io/cadvisor/cadvisor:v0.52.1`, `prom/node-exporter:v1.9.1` |
| Volume data | `prometheus_data` (`/prometheus`) dan `grafana_data` (`/var/lib/grafana`) |
| Metrik aplikasi | Caddy/FrankenPHP pada `backend:9180/metrics` (tidak dipublikasikan ke host) |
| Dashboard | `monitoring/grafana/dashboards/letterbox-p11.json` (4 panel, judul berupa pertanyaan) |
| Alarm | `monitoring/prometheus/alerts.yml` — `LayananMati` (`for: 1m`) |
| Keamanan | password admin Grafana dibaca dari `.env`, bukan `admin/admin`; port monitoring hanya di `127.0.0.1` |

## 2. Susunan layanan berjalan

[TEMPEL keterangan singkat]

![docker compose ps](bukti-p11/01-compose-ps.png)

## 3. Halaman /targets — seluruh target UP

Lima target (`prometheus`, `cadvisor`, `node-exporter`, `grafana`, `backend`) berstatus **UP**.

![Prometheus targets](bukti-p11/02-targets-up.png)

## 4. Dashboard Grafana

| Panel (pertanyaan) | PromQL | Satuan sumbu |
|---|---|---|
| Container mana yang paling banyak memakai CPU? | `sum by (name) (rate(container_cpu_usage_seconds_total{name!=""}[5m])) * 100` | persen (% dari 1 core) |
| Container mana yang paling boros memori? | `container_memory_working_set_bytes{name!=""}` | byte (IEC) |
| Berapa permintaan per detik yang sedang dilayani backend? | `sum(rate(caddy_http_requests_total{job="backend", server="api"}[1m]))` | permintaan/detik (req/s) |
| Apakah semua layanan sedang hidup? | `up` | status (1 = hidup, 0 = mati) |

Counter dibungkus `rate(...)` supaya grafik menunjukkan laju, bukan garis lurus yang selalu naik.

![Dashboard lengkap](bukti-p11/03-dashboard-normal.png)

[TEMPEL keterangan: apa yang terlihat pada tiap panel]

## 5. Aturan alarm

```yaml
[TEMPEL isi monitoring/prometheus/alerts.yml]
```

- `expr: up == 0` — target gagal di-scrape.
- `for: 1m` — mencegah alarm palsu akibat restart singkat.
- `tindakan_pertama` — langkah pertama penerima: cek `docker compose ps`/`logs`, lalu nyalakan ulang.

Kondisi normal:

![Alerts inactive](bukti-p11/04-alerts-inactive.png)

## 6. Bukti alarm berbunyi

Perintah: `docker compose stop backend`, lalu tunggu sampai lebih dari 1 menit.

![Alerts firing](bukti-p11/06-alerts-firing.png)

[TEMPEL keterangan: waktu mulai, waktu FIRING, isi anotasi]

![Dashboard saat backend mati](bukti-p11/07-dashboard-backend-mati.png)

Pemulihan: `docker compose start backend` → alarm kembali Inactive.

## 7. Keamanan dasar: password admin Grafana

Password admin diisi lewat variabel `GRAFANA_ADMIN_PASSWORD` di `.env` (tidak di-commit, ada di
`.gitignore`). Bukti: login `admin/admin` ditolak, password baru diterima.

![Bukti password](bukti-p11/08-grafana-password.png)

**Mengapa penting?** `admin/admin` adalah kredensial bawaan yang diketahui semua orang dan pertama
kali dicoba penyerang. Grafana memegang akses ke seluruh data pemantauan (nama container, beban
server, struktur layanan) dan bisa mengubah dashboard, data source, dan alarm. Siapa pun yang
berhasil masuk bisa melihat peta infrastruktur dan mematikan pemantauan sebelum menyerang.

## 8. Berkas yang di-commit

```text
docker-compose.yml
backend/docker/Caddyfile
monitoring/prometheus/prometheus.yml
monitoring/prometheus/alerts.yml
monitoring/grafana/provisioning/{datasources,dashboards}/*.yml
monitoring/grafana/dashboards/letterbox-p11.json
scripts/traffic.sh
.env.example            # tanpa nilai rahasia
```

Nama berkas laporan: `P11_24-535316_Mutia-Umniati-Z.pdf`
