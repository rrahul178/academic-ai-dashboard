# Academic AI Dashboard

A portfolio demo built for the **Academic AI Program Lead** role at STS Group
Bangladesh. It's a small but complete slice of the stack the job posting asks
for: a PHP REST backend, a JavaScript frontend dashboard, and a separate
Python/Django microservice that does the "AI" part — attendance risk scoring
for students, with an explainable (not black-box) model.

## Why it's built this way

The job posting's actual technical asks were the brief:

- *"Develop and maintain backend applications using PHP, JavaScript, Python
  (Django)"* → three real, running services in those three languages, not a
  single-language stand-in.
- *"Integrate frontend applications/dashboard with RESTful APIs and backend
  services"* → the frontend calls the PHP API, and the **PHP API itself calls
  the Django API** server-to-server. That's the integration pattern the
  posting is describing — one backend service consuming another.
- *"Design, build, and maintain secure and scalable APIs"* → JWT auth with
  bcrypt password hashing, prepared statements everywhere (no SQL string
  concatenation), a shared-secret header between the two backend services,
  and role checks on destructive endpoints.
- AI use case framing → the risk model is a small, explainable weighted
  scorer with stated reasons and a recommended action, not a magic score.
  That's a deliberate choice: for a first version shown to academic staff,
  being able to say *why* a student was flagged matters more than a few
  points of accuracy, and it's easy to justify in an interview.

## Architecture

```
frontend/  (HTML/CSS/JS, Chart.js)
   |  fetch() with JWT bearer token
   v
backend-php/  (PHP, PDO/SQLite, hand-rolled JWT)
   |  students / faculty / schedule / attendance  — REST CRUD
   |  curl() server-to-server, X-Service-Key header
   v
ai-service-django/  (Django REST Framework)
      /api/risk-score/  — attendance-based risk scoring
      /api/health/      — liveness check
```

Each service is independently runnable and has its own database — they're
decoupled on purpose, the way you'd want separate teams or separate scaling
needs to be possible later.

## Running it locally

**1. PHP backend**
```bash
cd backend-php
php setup.php            # creates database.sqlite, seeds a demo admin user
php seed_demo_data.php   # adds sample students/faculty/schedule/attendance
php -S localhost:8080    # serves auth.php and api/*.php
```
Demo login: `admin@pahmc.edu.bd` / `admin123`

**2. Django AI service**
```bash
cd ai-service-django
python3 -m venv venv && source venv/bin/activate
pip install -r requirements.txt
python manage.py migrate
python manage.py runserver 8000
```

**3. Frontend**
Open `frontend/index.html` in a browser (or serve the folder with any static
file server). It's pre-filled with the demo credentials — sign in and it
loads students, schedule, and per-student risk scores from the two backends.

If you're only reviewing the code rather than running it, `risk_engine.py` is
dependency-free and the fastest way to see the model's logic:
```bash
python3 -c "from ai_service_django.analytics.risk_engine import compute_risk; print(compute_risk(0.55, 12))"
```

## Security notes (demo-scope, called out honestly)

- Passwords are bcrypt-hashed (`password_hash`/`password_verify`), never
  stored or compared in plaintext.
- JWTs are HMAC-signed and time-limited (8-hour expiry); `hash_equals()` is
  used for the signature check to avoid timing attacks.
- All SQL goes through PDO prepared statements.
- The Django service checks a shared-secret header rather than trusting any
  caller — appropriate for one trusted internal caller, not a substitute for
  full OAuth if this grew to more consumers.
- Secrets (`JWT_SECRET`, `SHARED_SERVICE_KEY`, `DJANGO_SECRET_KEY`) are read
  from environment variables with placeholder fallbacks *only* so the demo
  runs out of the box — these placeholders are called out in comments as
  things to replace before any real deployment, and CORS is wide open for
  the same reason.

## What to say about this in the interview

- Why three languages instead of one: it mirrors how the posting frames the
  role — PHP/JS for the day-to-day admin surface, Django for the AI/analytics
  piece — and shows the API-to-API integration skill directly rather than
  just each language in isolation.
- Why the risk model is rule-based, not ML: it's an intentional MVP choice —
  explainable, ships fast, and the API contract (`risk_score`, `risk_level`,
  `reasons`, `recommended_action`) is written so a trained model could
  replace `compute_risk()` later without changing anything upstream.
- What you'd add first for a real rollout: real user roles beyond one admin
  account, an audit log on attendance edits, and swapping SQLite for
  MySQL/Postgres (the PDO/Django ORM code doesn't need to change).
