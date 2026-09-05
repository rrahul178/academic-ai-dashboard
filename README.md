# Academic AI Dashboard

A full-stack portfolio project built for the **Academic AI Program Lead** role at STS Group Bangladesh. It matches the job posting's tech stack directly: a PHP backend, a JavaScript frontend, and a Python/Django microservice for AI-based analytics.

## Live Demo

- **Dashboard:** https://rrahul178.github.io/academic-ai-dashboard/
- **Login:** `admin@pahmc.edu.bd` / `admin123`
- **Source code:** https://github.com/rrahul178/academic-ai-dashboard

## What It Does

The dashboard shows students, faculty, class schedules, and an AI-generated **attendance risk score** for each student — flagging who may need academic support, with a plain-language reason (not a black-box number).

## Architecture

```
frontend/          → HTML/CSS/JS dashboard (Chart.js)
backend-php/        → PHP REST API — auth, students, faculty, schedule, attendance
ai-service-django/  → Django microservice — attendance risk scoring
```

The PHP backend calls the Django service internally (server-to-server) to get each student's risk score — this is the "integrate backend services" pattern the job posting asks for.

## Security Basics Covered

- Passwords hashed with bcrypt (never stored in plain text)
- JWT-based login sessions (signed, time-limited)
- All database queries use prepared statements (SQL-injection safe)
- A shared secret key protects the internal PHP → Django connection

## Running It Locally

```bash
# PHP backend
cd backend-php
php setup.php && php seed_demo_data.php
php -S localhost:8080

# Django AI service
cd ai-service-django
pip install -r requirements.txt
python manage.py migrate
python manage.py runserver 8000

# Frontend
Open frontend/index.html in a browser
```

## Why It's Built This Way

- **Three languages, not one** — matches exactly what the job posting asks for (PHP, JavaScript, Python/Django), and shows real API-to-API integration between two backend services.
- **Simple, explainable AI model** — a rule-based risk score instead of a black-box ML model, because academic staff need to trust *why* a student was flagged, not just see a number. The code is structured so a real ML model could replace this logic later without changing anything else.
- **What's next for production** — multiple user roles, an audit log for attendance changes, and swapping SQLite for MySQL/Postgres.
