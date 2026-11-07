# P10 — Logging Terstruktur, Endpoint `/health`, dan Healthcheck Docker Compose

**Nama:** Mutia Umniati Z  
**NIM:** 24/535316/SV/24196  
**Kelas:** PL5AA  
**Mata kuliah:** Konstruksi dan Evolusi Perangkat Lunak  
**Repository:** <https://github.com/ttiauzm/uts-pl-24-535316-sv-24196>

> **Catatan:** placeholder `[TEMPEL ...]` dan gambar di folder `docs/bukti-p10/`
> diisi dengan bukti asli setelah menjalankan perintah pada bagian Lampiran.
> Jangan mengumpulkan laporan yang masih berisi placeholder.

## 1. Ringkasan perubahan

| Kebutuhan | Implementasi |
|---|---|
| Log Laravel ke stdout | `LOG_CHANNEL=stdout`, channel `stdout` menulis ke `php://stdout` dengan `JsonFormatter` |
| Minimal tiga log terstruktur | login sukses/gagal, registrasi, simpan/ubah/hapus film, validasi gagal, query DB gagal, health check gagal |
| Endpoint `/health` | memeriksa database dengan `SELECT 1` dan cache dengan write/read/delete |
| Healthcheck Compose | `backend` memanggil `/health`; nilai `interval`, `timeout`, `retries`, `start_period` eksplisit |
| Pembatasan log | `logging.driver: json-file`, `max-size: "10m"`, `max-file: "3"` |

## 2. Log Laravel keluar ke stdout

Channel `stdout` pada `backend/config/logging.php`:

```php
'stdout' => [
    'driver' => 'monolog',
    'level' => env('LOG_LEVEL', 'info'),
    'handler' => StreamHandler::class,
    'handler_with' => [
        'stream' => 'php://stdout',
    ],
    'formatter' => env('LOG_STDOUT_FORMATTER', JsonFormatter::class),
    'processors' => [PsrLogMessageProcessor::class],
],
```

`backend/Dockerfile` dan `docker-compose.yml` memakai `LOG_CHANNEL=stdout` serta
`LOG_LEVEL=info`. Karena `JsonFormatter` dipakai, context log tampil sebagai JSON
di `docker compose logs backend`, bukan sebagai file `storage/logs/laravel.log`.

**Bukti log stdout:**

![Log Laravel di docker compose logs](bukti-p10/03-log-structured.png)

```text
[TEMPEL potongan `docker compose logs --tail=100 backend` yang memuat JSON log]
```

## 3. Log terstruktur dan alasan pemilihan alur

Setiap log memakai context `event`, `actor_id`, `action`, dan `reason`.
`actor_id` adalah ID numerik pengguna, bukan email/nama. Nilai input tidak dicatat.

| Event | Level | Siapa (`actor_id`) | Sedang apa (`action`) | Sebab (`reason`) | Alasan dipilih |
|---|---|---|---|---|---|
| `auth.login_succeeded` | info | ID user | login | kredensial valid | Memantau aktivitas autentikasi tanpa mencatat token/password |
| `auth.login_failed` | warning | ID user jika ada | login | kredensial tidak valid | Mendeteksi percobaan login gagal |
| `auth.user_registered` | info | ID admin/actor + ID user baru | register user | actor membuat akun | Jejak audit pembuatan akun |
| `film.created` | info | ID user | create film | data film valid | Membuktikan alur simpan data berhasil |
| `film.updated` | info | ID user | update film | perubahan valid | Melacak perubahan data penting |
| `film.deleted` | info | ID user | delete film | user meminta hapus | Jejak audit penghapusan |
| `request.validation_failed` | warning | ID user jika ada | method + path | payload tidak valid | Menjelaskan kegagalan input tanpa menyimpan nilainya |
| `database.operation_failed` | error | ID user jika ada | method + path | query database gagal | Membedakan gangguan DB dari kesalahan validasi |
| `health.check_failed` | error | `system/healthcheck` | cek database/cache | pesan exception yang sudah disanitasi | Menjelaskan komponen dan penyebab saat sehat/sakit |

Contoh pemanggilan log pada alur simpan film:

```php
Log::info('Film created', [
    'event' => 'film.created',
    'actor_id' => $request->user()?->id,
    'action' => 'create film',
    'film_id' => $film->id,
    'fields' => array_keys($validated),
    'reason' => 'valid film data submitted',
]);
```

**Bukti log terstruktur (login/CRUD/validasi):**

```text
[TEMPEL potongan log JSON yang menunjukkan minimal 3 event berbeda]
```

## 4. Endpoint `/health`

`GET http://localhost:8000/health` tidak hanya mengembalikan teks. Endpoint ini:

1. menjalankan `DB::select('SELECT 1')` untuk database;
2. menulis, membaca, lalu menghapus key probe pada cache.

Respons `200` jika database dan cache sehat. Respons `503` jika salah satu gagal,
dengan `checks` dan `failed_components` yang menyebut komponen bermasalah.

**Saat sehat (HTTP 200):**

![Health check saat sehat](bukti-p10/02-health-healthy.png)

```json
[TEMPEL output `curl -i http://localhost:8000/health` saat sehat]
```

**Saat database dimatikan (HTTP 503):**

![Health check saat database mati](bukti-p10/05-health-unhealthy.png)

```json
[TEMPEL output `curl -i http://localhost:8000/health` saat db dimatikan]
```

Penjelasan: saat database mati, `checks.database` bernilai `down` dan
`failed_components` berisi `["database"]`; cache tetap `ok` karena memakai driver
file. Healthcheck Compose memakai endpoint ini, sehingga backend ikut menjadi
`unhealthy`.

## 5. Healthcheck dan status Compose

Potongan `docker-compose.yml`:

```yaml
healthcheck:
  test: ["CMD-SHELL", "wget -qO- http://127.0.0.1:8000/health > /dev/null || exit 1"]
  interval: 10s
  timeout: 5s
  retries: 3
  start_period: 40s
```

**Status sehat:**

![docker compose ps saat sehat](bukti-p10/01-compose-ps-healthy.png)

```text
[TEMPEL output `docker compose ps` saat semua service sehat]
```

**Status setelah `docker compose stop db`:**

![docker compose ps saat db mati](bukti-p10/04-compose-ps-unhealthy.png)

```text
[TEMPEL output `docker compose ps -a` setelah db dihentikan]
```

Setelah database dihentikan, service `db` tampil `exited` (memang sengaja
dihentikan), sedangkan `backend` berubah menjadi `unhealthy` setelah tiga kali
healthcheck gagal. Log backend memuat `health.check_failed` dengan
`component: database` dan alasan koneksi.

**Log saat database mati:**

![Log kegagalan database](bukti-p10/06-log-db-failure.png)

```text
[TEMPEL potongan `docker compose logs --tail=100 backend` saat db mati]
```

**Status setelah `docker compose start db`:**

![docker compose ps setelah pulih](bukti-p10/07-compose-ps-recovered.png)

```text
[TEMPEL output `docker compose ps` setelah db dinyalakan kembali]
```

![Health check setelah pulih](bukti-p10/08-health-recovered.png)

```text
[TEMPEL output `/health` setelah pulih]
```

## 6. Pembatasan ukuran log

Semua service memakai konfigurasi berikut:

```yaml
logging:
  driver: json-file
  options:
    max-size: "10m"
    max-file: "3"
```

**Bukti konfigurasi aktif:**

![Konfigurasi log rotation](bukti-p10/09-log-rotation.png)

```text
[TEMPEL output `docker inspect` yang menunjukkan max-size=10m dan max-file=3]
```

## 7. Kesimpulan

- Log Laravel keluar ke stdout container dan dapat dibaca lewat
  `docker compose logs backend` dalam format JSON terstruktur.
- Alur autentikasi, CRUD film, validasi, kegagalan database, dan health check
  memiliki log dengan konteks siapa, sedang apa, dan sebabnya.
- `/health` benar-benar memeriksa database dan cache serta membedakan status
  `healthy`/`unhealthy` lewat HTTP 200/503.
- Healthcheck Compose bekerja dua arah: sehat saat dependensi tersedia dan
  `unhealthy` saat database sengaja dimatikan, lalu kembali sehat setelah
  database dinyalakan.
- Ukuran log dibatasi `10 MB` per file dengan maksimal `3` file.

## Lampiran — perintah pengambilan bukti

Jalankan dari folder repository di Ubuntu (bukan dari dalam container):

```bash
docker compose up -d --build
docker compose ps
docker compose exec backend printenv LOG_CHANNEL LOG_LEVEL
curl -i http://localhost:8000/health
docker compose logs --tail=100 backend
```

Ambil screenshot `01`, `02`, dan `03`.

```bash
# Hasilkan log terstruktur: login sukses, simpan film, lalu validasi gagal.
TOKEN=$(curl -fsS -X POST http://localhost:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@example.com","password":"password"}' \
  | python3 -c 'import json,sys; print(json.load(sys.stdin)["token"])')

curl -fsS -X POST http://localhost:8000/api/films \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"title":"P10 Structured Log","director":"Mutia","year":2026,"status":"ingin_ditonton","rating":5,"review":"Uji log P10."}' > /dev/null

curl -s -o /dev/null -w '%{http_code}\n' -X POST http://localhost:8000/api/films \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"title":"","director":"","year":1800,"status":"x","rating":9}'

docker compose logs --tail=120 backend | grep '"event"'
```

Ambil screenshot `03` (log JSON). Jangan menampilkan token atau password di laporan.

```bash
# Matikan database dengan sengaja
docker compose stop db

# Tunggu sekitar 40 detik (3 x interval 10 detik)
sleep 40
docker compose ps -a
curl -i http://localhost:8000/health
docker compose logs --tail=100 backend
```

Ambil screenshot `04`, `05`, dan `06`.

```bash
# Nyalakan lagi
docker compose start db

# Tunggu healthcheck berikutnya
sleep 20
docker compose ps
curl -i http://localhost:8000/health
```

Ambil screenshot `07` dan `08`.

```bash
# Bukti log rotation
docker inspect --format '{{json .HostConfig.LogConfig}}' $(docker compose ps -q backend)
```

Ambil screenshot `09`.

Ekspor laporan ini ke PDF dengan nama:

```text
P10_24-535316_Mutia-Umniati-Z.pdf
```
