// ============================================================
//  ExamCore — Express + MySQL Backend  (server.js)
//  Run:  node server.js
//  API base: http://localhost:3000/api
// ============================================================

const express  = require('express');
const mysql    = require('mysql2/promise');
const bcrypt   = require('bcrypt');
const cors     = require('cors');
const path     = require('path');

const app  = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

// ── serve the frontend from /public ─────────────────────────
app.use(express.static(path.join(__dirname, 'public')));

// ── MySQL connection pool ────────────────────────────────────
const pool = mysql.createPool({
  host     : process.env.DB_HOST     || 'localhost',
  user     : process.env.DB_USER     || 'root',
  password : process.env.DB_PASSWORD || 'your_password',   // ← change this
  database : process.env.DB_NAME     || 'examcore',
  waitForConnections: true,
  connectionLimit   : 10,
});

// ── helper ───────────────────────────────────────────────────
const ok  = (res, data)         => res.json({ success: true,  ...data });
const err = (res, msg, code=400) => res.status(code).json({ success: false, message: msg });

// ============================================================
//  AUTH
// ============================================================

// POST /api/auth/login
app.post('/api/auth/login', async (req, res) => {
  const { username, password } = req.body;
  if (!username || !password) return err(res, 'Username and password required.');

  const [rows] = await pool.query(
    'SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1',
    [username]
  );
  if (!rows.length) return err(res, 'Invalid credentials.', 401);

  const user = rows[0];
  const match = await bcrypt.compare(password, user.password_hash);
  if (!match) return err(res, 'Invalid credentials.', 401);

  // Return safe user object (no hash)
  ok(res, {
    user: {
      id          : user.id,
      username    : user.username,
      display_name: user.display_name,
      email       : user.email,
      role        : user.role,
    }
  });
});

// POST /api/auth/register  (optional self-registration)
app.post('/api/auth/register', async (req, res) => {
  const { username, password, display_name, email } = req.body;
  if (!username || !password) return err(res, 'Username and password required.');

  const hash = await bcrypt.hash(password, 12);
  try {
    const [result] = await pool.query(
      'INSERT INTO users (username, password_hash, display_name, email) VALUES (?,?,?,?)',
      [username, hash, display_name || username, email || null]
    );
    ok(res, { user_id: result.insertId });
  } catch (e) {
    if (e.code === 'ER_DUP_ENTRY') return err(res, 'Username already taken.');
    throw e;
  }
});

// ============================================================
//  QUESTIONS  (fetch for active exam)
// ============================================================

// GET /api/questions?exam_id=1
app.get('/api/questions', async (req, res) => {
  const exam_id = parseInt(req.query.exam_id) || 1;

  // Fetch exam config
  const [[exam]] = await pool.query('SELECT * FROM exams WHERE id = ?', [exam_id]);
  if (!exam) return err(res, 'Exam not found.', 404);

  // Fetch questions + options
  const [questions] = await pool.query(
    `SELECT q.id, q.question_text, q.category, q.difficulty
     FROM questions q
     WHERE q.is_active = 1
     ORDER BY q.id
     LIMIT ?`,
    [exam.total_questions]
  );

  const [options] = await pool.query(
    `SELECT o.question_id, o.option_key, o.option_text, o.is_correct
     FROM options o
     JOIN questions q ON q.id = o.question_id
     WHERE q.is_active = 1
     ORDER BY o.question_id, o.option_key`
  );

  // Attach options to questions
  const optMap = {};
  options.forEach(o => {
    if (!optMap[o.question_id]) optMap[o.question_id] = [];
    optMap[o.question_id].push(o);
  });

  const result = questions.map(q => ({
    id      : q.id,
    question: q.question_text,
    category: q.category,
    options : (optMap[q.id] || []).map(o => o.option_text),
    // answer index (0-3) derived from option_key A=0,B=1,C=2,D=3
    answer  : (optMap[q.id] || []).findIndex(o => o.is_correct === 1),
  }));

  ok(res, {
    exam: {
      id             : exam.id,
      title          : exam.title,
      total_questions: exam.total_questions,
      time_limit_sec : exam.time_limit_sec,
      pass_percentage: parseFloat(exam.pass_percentage),
    },
    questions: result,
  });
});

// ============================================================
//  EXAM ATTEMPTS
// ============================================================

// POST /api/attempts  — submit a completed exam
app.post('/api/attempts', async (req, res) => {
  const { user_id, exam_id = 1, answers, time_taken_sec } = req.body;
  // answers: array of { question_id, selected_index }  (selected_index 0-3, null=skipped)

  if (!user_id || !answers) return err(res, 'user_id and answers required.');

  // Fetch correct answers from DB
  const [opts] = await pool.query(
    `SELECT question_id,
            option_key,
            is_correct
     FROM options
     WHERE question_id IN (${answers.map(() => '?').join(',')})`,
    answers.map(a => a.question_id)
  );

  // Build correctness map: question_id → correct option index (0-3)
  const correctMap = {};
  const keys = ['A','B','C','D'];
  opts.forEach(o => {
    if (!correctMap[o.question_id]) correctMap[o.question_id] = { correct: null, all: [] };
    correctMap[o.question_id].all.push(o.option_key);
    if (o.is_correct) correctMap[o.question_id].correct = keys.indexOf(o.option_key);
  });

  let score = 0;
  const answerRows = answers.map(a => {
    const sel  = a.selected_index;
    const selKey = sel !== null && sel !== undefined ? keys[sel] : null;
    const isCorrect = sel !== null && sel !== undefined
      ? (sel === correctMap[a.question_id]?.correct ? 1 : 0)
      : 0;
    if (isCorrect) score++;
    return [null, a.question_id, selKey, isCorrect];
  });

  const total      = answers.length;
  const percentage = parseFloat(((score / total) * 100).toFixed(2));

  // Fetch pass threshold
  const [[exam]] = await pool.query('SELECT pass_percentage FROM exams WHERE id=?', [exam_id]);
  const status = percentage >= parseFloat(exam.pass_percentage) ? 'PASS' : 'FAIL';

  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();

    const [result] = await conn.query(
      `INSERT INTO exam_attempts
         (user_id, exam_id, score, total_questions, percentage, status, time_taken_sec, submitted_at)
       VALUES (?,?,?,?,?,?,?,NOW())`,
      [user_id, exam_id, score, total, percentage, status, time_taken_sec || null]
    );
    const attempt_id = result.insertId;

    // Insert per-question answers
    const rows = answerRows.map(r => [attempt_id, r[1], r[2], r[3]]);
    await conn.query(
      'INSERT INTO attempt_answers (attempt_id, question_id, selected_option, is_correct) VALUES ?',
      [rows]
    );

    await conn.commit();
    ok(res, { attempt_id, score, total, percentage, status });
  } catch(e) {
    await conn.rollback();
    throw e;
  } finally {
    conn.release();
  }
});

// GET /api/attempts?user_id=2  — history for a user
app.get('/api/attempts', async (req, res) => {
  const { user_id } = req.query;
  if (!user_id) return err(res, 'user_id required.');

  const [rows] = await pool.query(
    `SELECT id AS attempt_id, score, total_questions, percentage, status,
            time_taken_sec, started_at, submitted_at
     FROM exam_attempts
     WHERE user_id = ?
     ORDER BY submitted_at DESC`,
    [user_id]
  );
  ok(res, { attempts: rows });
});

// ============================================================
//  LEADERBOARD
// ============================================================

// GET /api/leaderboard?exam_id=1&limit=20
app.get('/api/leaderboard', async (req, res) => {
  const exam_id = parseInt(req.query.exam_id) || 1;
  const limit   = Math.min(parseInt(req.query.limit) || 20, 100);

  const [rows] = await pool.query(
    `SELECT
       ROW_NUMBER() OVER (ORDER BY l.best_percentage DESC, l.last_attempt_at ASC) AS \`rank\`,
       u.username, u.display_name,
       l.best_score, l.best_percentage, l.total_attempts, l.total_passed, l.last_attempt_at
     FROM leaderboard l
     JOIN users u ON u.id = l.user_id
     WHERE l.exam_id = ?
     ORDER BY l.best_percentage DESC
     LIMIT ?`,
    [exam_id, limit]
  );
  ok(res, { leaderboard: rows });
});

// ============================================================
//  STATS (profile page)
// ============================================================

// GET /api/stats?user_id=2
app.get('/api/stats', async (req, res) => {
  const { user_id } = req.query;
  if (!user_id) return err(res, 'user_id required.');

  const [[stats]] = await pool.query(
    `SELECT
       COUNT(*)                                       AS total_attempts,
       SUM(status = 'PASS')                           AS total_passed,
       MAX(percentage)                                AS best_percentage,
       MAX(score)                                     AS best_score
     FROM exam_attempts
     WHERE user_id = ?`,
    [user_id]
  );
  ok(res, { stats });
});

// ============================================================
//  CATCH-ALL — serve frontend for any unmatched route
// ============================================================
app.get('*', (_, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// ── Global error handler ─────────────────────────────────────
app.use((e, req, res, _next) => {
  console.error(e);
  res.status(500).json({ success: false, message: 'Internal server error.' });
});

app.listen(PORT, () => console.log(`ExamCore API running → http://localhost:${PORT}`));
