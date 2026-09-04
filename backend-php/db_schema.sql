-- Academic AI Dashboard — schema (SQLite for demo; portable to MySQL/Postgres)

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,       -- password_hash() with PASSWORD_BCRYPT
    role TEXT NOT NULL CHECK (role IN ('admin', 'faculty', 'coordinator')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS faculty (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    department TEXT NOT NULL,
    designation TEXT,
    email TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    roll_no TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    batch TEXT NOT NULL,
    department TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    department TEXT NOT NULL,
    faculty_id INTEGER REFERENCES faculty(id)
);

CREATE TABLE IF NOT EXISTS schedule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL REFERENCES courses(id),
    day_of_week TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    room TEXT
);

-- Raw attendance log. The Django AI service reads aggregates of
-- this table (via the PHP API, not direct DB access) to compute
-- risk scores, keeping the two services decoupled.
CREATE TABLE IF NOT EXISTS attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL REFERENCES students(id),
    course_id INTEGER NOT NULL REFERENCES courses(id),
    date TEXT NOT NULL,
    present INTEGER NOT NULL CHECK (present IN (0, 1)),
    UNIQUE(student_id, course_id, date)
);

-- Demo user is seeded by setup.php at install time, using PHP's
-- password_hash() rather than a hardcoded hash in this file.
