# Checklist bukti UTS

Gunakan daftar ini sebelum mengekspor laporan ke PDF.

## Lokal

- [ ] `cd backend && php artisan test` → 20 passed
- [ ] `cd frontend && npm ci && npm run build` → sukses
- [ ] `docker compose up -d --build` → tiga service sehat
- [ ] `docker compose ps` → `db`, `backend`, `frontend` Up (healthy)
- [ ] Login `test@example.com` / `password` di <http://localhost:8080>
- [ ] Tambah, ubah, hapus film dari browser
- [ ] `docker compose down` lalu `docker compose up -d` → data tetap ada
- [ ] `curl -i http://localhost:8000/up` → 200, header FrankenPHP
- [ ] `docker compose logs --tail=80 backend` → log akses FrankenPHP

## GitHub

- [ ] Repository publik `uts-pl-24-535316-sv-24196`
- [ ] Branch `main`, `dev`, `feature/letterbox`
- [ ] PR fitur → `dev` dan `dev` → `main` berstatus merged
- [ ] Ruleset `protect-main` dan `protect-dev` aktif
- [ ] Push langsung ke `main` ditolak
- [ ] Actions di `main`: tiga job hijau
- [ ] Actions di `dev`/PR: dua job hijau, deploy skipped
- [ ] Secrets: `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`
- [ ] Packages: image backend dan frontend dengan tag SHA

## Postman

- [ ] Import `postman/letterbox-api.postman_collection.json`
- [ ] Login menyimpan token
- [ ] Index, store (201), show, update, delete berhasil
- [ ] Tanpa token → 401
- [ ] Data tidak valid → 422

## P10 — logging, health, healthcheck

- [ ] `docker compose ps` → `backend` **healthy**
- [ ] `curl -i http://localhost:8000/health` → HTTP 200, `database: ok`, `cache: ok`
- [ ] `docker compose logs --tail=100 backend` → log JSON dengan `event`, `actor_id`, `action`, `reason`
- [ ] `docker compose stop db` → tunggu retries → `backend` **unhealthy**
- [ ] `curl -i http://localhost:8000/health` saat db mati → HTTP 503, `failed_components: ["database"]`
- [ ] `docker compose logs` saat db mati → `health.check_failed`, `component: database`
- [ ] `docker compose start db` → `backend` kembali **healthy**
- [ ] `docker inspect` log config → `max-size=10m`, `max-file=3`
