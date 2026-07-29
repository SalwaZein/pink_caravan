# Pink Caravan — Project Guide

Breast-cancer screening platform for **Friends of Cancer Patients (FOCP) — Pink Caravan**.
Laravel 12 + Blade + Tailwind v4 + Alpine.js. Bilingual EN/AR with RTL.

---

## 1. Run locally (Windows)

PHP 8.3 and Composer are installed but **not on the default PATH** for existing shells — prepend them first (PowerShell):

```powershell
$env:Path = "C:\Users\salwa.zeineddin\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe;$env:LOCALAPPDATA\Composer\bin;" + $env:Path
```

Then, from the `pink-caravan` folder:

```powershell
php artisan migrate:fresh --seed   # create + seed the SQLite demo database
npm run build                      # build front-end assets
php artisan serve                  # http://127.0.0.1:8000
```

Run the tests: `php artisan test`

---

## 2. Demo logins (password: `password`)

| Role | Email | Can do |
|---|---|---|
| Super admin | `anish@focp.ae` | Dashboards, clinics, users & roles, audit, **service bookings** (review → approve → paid → completed) |
| Clinic admin | `mariam.s@focp.ae` | Clinic queue, register, **assign doctors** |
| Nurse | `s.nuaimi@focp.ae` | Register patients + fill record sheet |
| Doctor | `l.hassan@focp.ae` | Clinical breast exam, sign & submit |
| Mammographer | `n.khalid@focp.ae` | Upload + send the mammogram report |

Public pages (no login): `/` (hub), `/booking`, `/patient` (OTP portal), `/verify` (report verification).

---

## 3. End-to-end flow

1. **Nurse** registers a patient (Record sheet) — supports the **Read Emirates ID** button (auto-fill), manual PC number, signature pad, and consent → **Submit & assign**.
2. **Clinic admin** → **Assign doctors** → assign the record to a doctor in the same clinic.
3. **Doctor** opens the exam (Form 1 review + breast diagram pins + Normal/Abnormal result) → **Sign & submit**. Report is generated with a verification code.
4. **Mammographer** opens the completed record → uploads the mammogram report PDF → **Send report to patient** (notifies by email + SMS + WhatsApp; status → *Report Sent*).
5. **Patient** retrieves the report at `/patient` (mobile + one-time code) → views the bilingual document / downloads the PDF.
6. **Anyone** can confirm a report is authentic at `/verify` using the verification code + reference number (shows issuance-only metadata, no clinical data).

---

## 4. Key features & where they live

- **Registration form** — `resources/views/staff/nurse/record.blade.php`, `RecordController`.
- **Emirates ID reader** — `EmiratesIdController` (dev mock at `/tools/emirates-id/read`); front-end button auto-fills from a local reader bridge. See §6.
- **Doctor exam** — `resources/views/staff/doctor/exam.blade.php`, `ExamController`.
- **Mammographer workspace + report send** — `MammographerController`, `resources/views/staff/mammographer/*`.
- **CBE Report Document** (bilingual, print → PDF) — `resources/views/reports/document.blade.php`, `App\Support\ReportPresenter`. Stored PDF via mPDF: `App\Support\ReportService` + `reports/patient.blade.php`.
- **Report verification** — public `/verify`, `ReportController` (`verifyForm`/`verifyCheck`), `reports/verify.blade.php`.
- **Patient notifications** — `App\Support\PatientNotifier` + `App\Mail\ReportReadyMail`.
- **Service booking administration** — `ServiceBookingController`, `resources/views/super/{bookings,booking-show}.blade.php`. Reviews public booking requests through `new → approved → paid → completed` (or `rejected`), attaches a completion-report PDF, and audits each step. Four independent permissions (`review_bookings`, `approve_bookings`, `mark_bookings_paid`, `complete_bookings`) in `App\Support\Rbac`. Full spec: `Phase1a_Clinic_Management_Module.md` §8.
- **Bookings dashboard** — `ServiceBookingController@dashboard`, `App\Support\BookingDashboardService`, `resources/views/super/booking-dashboard.blade.php`. Pipeline KPIs (total / in-pipeline / completed / revenue / completion rate), a submitted→approved→paid→completed funnel, and by-status / by-service / by-emirate breakdowns with status + service + emirate + date filters. Gated by `review_bookings`.

---

## 5. Deployment (free demo — Render)

- Repo: **https://github.com/SalwaZein/pink_caravan** (private).
- Hosting: **Render** free web service, configured by **`render.yaml`** (Docker). `Dockerfile` builds PHP 8.3 + extensions + assets; `docker/entrypoint.sh` re-seeds fresh demo data on each boot.
- **Auto-deploy is on** — every `git push` to `main` triggers a new Render build:

```bash
git add -A && git commit -m "your message" && git push
```

- After the first deploy, set **`APP_URL`** (Render → service → Environment) to the exact URL Render assigned, so report/verification/notification links are correct.
- Free tier **sleeps after ~15 min idle** (first request then takes ~50s) and **re-seeds on wake** — data entered during a demo resets on sleep. Good for repeatable demos; do each walkthrough in one sitting.

> ⚠️ **Demo only.** The demo runs with `APP_DEBUG=true` on free shared hosting and re-seeds itself — use fake data. It is **not** suitable for real patient data (privacy/residency/compliance). For production, use proper hosting with a real MySQL DB, object storage for uploads, `APP_DEBUG=false`, and FOCP sign-off.

---

## 6. Going live — configuration checklist

All are stubbed/logged in the demo and switch on via environment variables (no code changes).

### Email (report-ready + future emails)
Set standard Laravel mail vars in `.env` once FOCP provides SMTP:
`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
Also ask FOCP for **SPF + DKIM (+ DMARC)** on the sending domain so mail isn't flagged as spam. (A transactional provider — SES/SendGrid/Mailgun — is recommended for campaign volume.)

### SMS + WhatsApp (report-ready push)
Configurable HTTP gateway — set per channel and `PatientNotifier` will POST `{to, from, body}`:
`SMS_GATEWAY_URL`, `SMS_GATEWAY_TOKEN`, `SMS_FROM`, `WHATSAPP_GATEWAY_URL`, `WHATSAPP_GATEWAY_TOKEN`, `WHATSAPP_FROM`.
When unset, messages are logged to `storage/logs/laravel.log`. For a specific provider (Twilio, Unifonic, WhatsApp Business API) a small adapter may be needed to match its payload.

### Emirates ID reader
`EMIRATES_ID_READER_URL` → the local reader bridge on the clinic device (official Emirates ID Toolkit / a small agent) that returns the normalised card JSON (see `EmiratesIdController` docblock for the contract). When unset, the built-in dev mock is used. The reader must run on the same machine as the browser (localhost); the fetch is client-side.

### Database & storage (production)
- Switch `DB_CONNECTION` to `mysql` and set `DB_*` (schema is MySQL-compatible).
- Point file storage (uploaded mammogram PDFs, generated reports, signatures) at persistent/object storage (e.g. S3-compatible like Cloudflare R2) instead of the local disk.

---

## 7. What FOCP needs to provide

- **SMTP** account (host/port/user/pass/encryption) + authorized From address, and SPF/DKIM/DMARC DNS — or a transactional email provider API key + verified domain.
- **SMS** and **WhatsApp** business gateway credentials (provider, sender ID, API endpoint/token).
- **Emirates ID** card readers + the government Emirates ID Toolkit/agent on each clinic device.
- **Production hosting** decision + a data-processing/residency agreement for real patient data.
- **Clinics/logins**: the mechanism supports per-clinic Clinic Admin + Doctor + Nurse + Mammographer logins for all locations; real accounts are created via Super Admin → Users & Clinics.

---

## 8. Notes / known stubs

- OTP delivery, SMS, and WhatsApp are **logged** until gateways are configured (email works via Laravel Mail; logs with `MAIL_MAILER=log`).
- The patient portal "Email me"/"WhatsApp" buttons on the report step are visual-only (separate from the mammographer's send push).
- The `/verify` "Scan QR" button is a visual affordance (no camera QR decoding yet).
- Arabic renders correctly in the report PDF via **mPDF** (dompdf can't shape Arabic); the dashboard CSV/PDF export still uses dompdf (English).
