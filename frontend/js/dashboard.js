/**
 * Dashboard bootstrap: guards the page behind login, then pulls
 * students/faculty/schedule from the PHP REST API and per-student
 * risk assessments from the Django AI service (via the PHP proxy
 * in attendance.php).
 */
(async function init() {
  const token = localStorage.getItem('aad_token');
  if (!token) {
    window.location.href = 'index.html';
    return;
  }
  const user = JSON.parse(localStorage.getItem('aad_user') || '{}');
  document.getElementById('user-name').textContent = user.name || 'User';
  document.getElementById('logout-link').addEventListener('click', (e) => {
    e.preventDefault();
    localStorage.removeItem('aad_token');
    localStorage.removeItem('aad_user');
    window.location.href = 'index.html';
  });

  await Promise.all([loadStudents(), loadFaculty(), loadSchedule()]);
  renderAttendanceChart();
})();

async function loadStudents() {
  try {
    const { students } = await api.listStudents();
    document.getElementById('stat-total-students').textContent = students.length;
    document.getElementById('student-count-hint').textContent = `${students.length} on record`;

    const rows = students.map(s =>
      `<tr><td>${escapeHtml(s.roll_no)}</td><td>${escapeHtml(s.name)}</td><td>${escapeHtml(s.batch)}</td><td>${escapeHtml(s.department)}</td></tr>`
    );
    document.getElementById('students-table-body').innerHTML = rows.join('') || '<tr><td colspan="4">No students yet — add one via the API.</td></tr>';

    await loadRiskTable(students);
  } catch (err) {
    document.getElementById('students-table-body').innerHTML =
      `<tr><td colspan="4">Couldn't load students: ${escapeHtml(err.message)}</td></tr>`;
  }
}

async function loadRiskTable(students) {
  const tbody = document.getElementById('risk-table-body');
  if (!students.length) {
    tbody.innerHTML = '<tr><td colspan="6">No students to assess yet.</td></tr>';
    return;
  }
  const results = await Promise.all(students.map(async (s) => {
    try {
      const r = await api.studentRisk(s.id);
      return { student: s, risk: r };
    } catch {
      return { student: s, risk: null };
    }
  }));

  let flagged = 0;
  const rows = results.map(({ student, risk }) => {
    const a = risk?.ai_assessment || {};
    const level = a.risk_level || 'insufficient_data';
    if (level === 'medium' || level === 'high') flagged += 1;
    const rate = risk?.attendance_rate != null ? `${Math.round(risk.attendance_rate * 100)}%` : '—';
    return `<tr>
      <td>${escapeHtml(student.roll_no)}</td>
      <td>${escapeHtml(student.name)}</td>
      <td>${escapeHtml(student.batch)}</td>
      <td>${rate}</td>
      <td><span class="badge ${level}">${level.replace('_', ' ')}</span></td>
      <td>${escapeHtml(a.recommended_action || '—')}</td>
    </tr>`;
  });
  tbody.innerHTML = rows.join('');
  document.getElementById('stat-at-risk').textContent = flagged;
}

async function loadFaculty() {
  try {
    const { faculty } = await api.listFaculty();
    document.getElementById('stat-total-faculty').textContent = faculty.length;
  } catch {
    document.getElementById('stat-total-faculty').textContent = '—';
  }
}

async function loadSchedule() {
  try {
    const { schedule } = await api.listSchedule();
    document.getElementById('stat-classes-week').textContent = schedule.length;
    const rows = schedule.map(s =>
      `<tr><td>${escapeHtml(s.course_title)}</td><td>${escapeHtml(s.day_of_week)}</td><td>${escapeHtml(s.start_time)}–${escapeHtml(s.end_time)}</td><td>${escapeHtml(s.room || '—')}</td></tr>`
    );
    document.getElementById('schedule-table-body').innerHTML = rows.join('') || '<tr><td colspan="4">No classes scheduled yet.</td></tr>';
  } catch (err) {
    document.getElementById('schedule-table-body').innerHTML =
      `<tr><td colspan="4">Couldn't load schedule: ${escapeHtml(err.message)}</td></tr>`;
  }
}

function renderAttendanceChart() {
  const ctx = document.getElementById('attendance-chart');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      datasets: [{
        label: 'Class-wide attendance rate',
        data: [88, 84, 79, 81],
        borderColor: '#3E6B5C',
        backgroundColor: 'rgba(62,107,92,0.08)',
        fill: true,
        tension: 0.25,
      }],
    },
    options: {
      scales: { y: { min: 0, max: 100, ticks: { callback: (v) => `${v}%` } } },
      plugins: { legend: { display: false } },
    },
  });
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));
}
