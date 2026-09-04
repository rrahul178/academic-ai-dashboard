/**
 * Thin fetch wrapper for the PHP backend.
 * Adjust API_BASE if the PHP backend isn't served at the same
 * host, e.g. 'http://localhost:8080/backend-php'.
 */
const API_BASE = 'http://localhost:8080/backend-php';

async function request(path, { method = 'GET', body, auth = false } = {}) {
  const headers = { 'Content-Type': 'application/json' };
  if (auth) {
    const token = localStorage.getItem('aad_token');
    if (!token) throw new Error('Not signed in');
    headers['Authorization'] = `Bearer ${token}`;
  }
  const res = await fetch(`${API_BASE}${path}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.error || `Request failed (${res.status})`);
  }
  return data;
}

const api = {
  login: (email, password) => request('/auth.php', { method: 'POST', body: { email, password } }),
  listStudents: (batch) => request(`/api/students.php${batch ? `?batch=${encodeURIComponent(batch)}` : ''}`),
  createStudent: (student) => request('/api/students.php', { method: 'POST', body: student, auth: true }),
  listFaculty: () => request('/api/faculty.php'),
  listSchedule: () => request('/api/schedule.php'),
  recordAttendance: (entry) => request('/api/attendance.php', { method: 'POST', body: entry, auth: true }),
  studentRisk: (studentId) => request(`/api/attendance.php?student_id=${studentId}&risk=1`),
};
