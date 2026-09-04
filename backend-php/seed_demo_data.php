<?php
/**
 * Run once after setup.php: `php seed_demo_data.php`
 * Populates faculty, students, courses, schedule, and a few
 * weeks of attendance so the dashboard has something to show.
 */
require __DIR__ . '/config.php';
$pdo = get_db();

$faculty = [
    ['Dr. Nasrin Sultana', 'Anatomy', 'Associate Professor', 'nasrin@pahmc.edu.bd'],
    ['Dr. Kamrul Hasan', 'Physiology', 'Professor', 'kamrul@pahmc.edu.bd'],
    ['Dr. Farhana Yasmin', 'Community Medicine', 'Assistant Professor', 'farhana@pahmc.edu.bd'],
];
$facultyIds = [];
foreach ($faculty as [$name, $dept, $desig, $email]) {
    $pdo->prepare('INSERT INTO faculty (name, department, designation, email) VALUES (?, ?, ?, ?)')
        ->execute([$name, $dept, $desig, $email]);
    $facultyIds[] = (int) $pdo->lastInsertId();
}

$courses = [
    ['Human Anatomy I', 'Anatomy', $facultyIds[0]],
    ['Physiology Fundamentals', 'Physiology', $facultyIds[1]],
    ['Community Medicine Practicum', 'Community Medicine', $facultyIds[2]],
];
$courseIds = [];
foreach ($courses as [$title, $dept, $facId]) {
    $pdo->prepare('INSERT INTO courses (title, department, faculty_id) VALUES (?, ?, ?)')
        ->execute([$title, $dept, $facId]);
    $courseIds[] = (int) $pdo->lastInsertId();
}

$scheduleRows = [
    [$courseIds[0], 'Sunday', '09:00', '10:30', 'Room 101'],
    [$courseIds[1], 'Monday', '11:00', '12:30', 'Room 204'],
    [$courseIds[2], 'Wednesday', '14:00', '15:30', 'Room 305'],
    [$courseIds[0], 'Tuesday', '09:00', '10:30', 'Room 101'],
];
foreach ($scheduleRows as [$cid, $day, $start, $end, $room]) {
    $pdo->prepare('INSERT INTO schedule (course_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)')
        ->execute([$cid, $day, $start, $end, $room]);
}

$students = [
    ['PAHMC-24-001', 'Tania Rahman', '2024', 'MBBS'],
    ['PAHMC-24-002', 'Imran Kabir', '2024', 'MBBS'],
    ['PAHMC-24-003', 'Sadia Islam', '2024', 'MBBS'],
    ['PAHMC-23-011', 'Rifat Hossain', '2023', 'MBBS'],
];
$studentIds = [];
foreach ($students as [$roll, $name, $batch, $dept]) {
    $pdo->prepare('INSERT INTO students (roll_no, name, batch, department) VALUES (?, ?, ?, ?)')
        ->execute([$roll, $name, $batch, $dept]);
    $studentIds[] = (int) $pdo->lastInsertId();
}

// Attendance pattern deliberately varied so the risk engine has
// something interesting to score: one strong, one borderline,
// one weak, one with too little data.
$patterns = [
    array_fill(0, 16, 1),                                   // near-perfect
    array_merge(array_fill(0, 10, 1), array_fill(0, 6, 0)),  // borderline ~62%
    array_merge(array_fill(0, 5, 1), array_fill(0, 11, 0)),  // weak ~31%
    [1, 1],                                                  // insufficient data
];
foreach ($studentIds as $i => $sid) {
    $day = strtotime('-16 days');
    foreach ($patterns[$i] as $present) {
        $courseId = $courseIds[$i % count($courseIds)];
        $date = date('Y-m-d', $day);
        $pdo->prepare('INSERT OR REPLACE INTO attendance (student_id, course_id, date, present) VALUES (?, ?, ?, ?)')
            ->execute([$sid, $courseId, $date, $present]);
        $day = strtotime('+1 day', $day);
    }
}

echo "Seeded " . count($faculty) . " faculty, " . count($courses) . " courses, "
    . count($scheduleRows) . " schedule rows, " . count($students) . " students with attendance.\n";
