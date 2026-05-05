# ExamCore v2.0 — Setup Guide

## Files
```
examcore-backend/
├── server.js               ← Node.js + Express API
├── package.json
├── examcore_database.sql   ← MySQL schema + seed data
├── README.md
└── public/
    └── index.html          ← Frontend (served by Express)
```

## Step 1 — MySQL Setup
```bash
mysql -u root -p < examcore_database.sql
```

## Step 2 — Generate Real Password Hashes
Run this ONE TIME in Node to get bcrypt hashes, then paste them into
the users table (or use POST /api/auth/register from the app):

```js
const bcrypt = require('bcrypt');
const pairs = [
  ['admin',   'admin123'],
  ['alice',   'alice123'],
  ['bob',     'bob123'],
  ['charlie', 'charlie123'],
];
pairs.forEach(async ([u, p]) => {
  const hash = await bcrypt.hash(p, 12);
  console.log(`UPDATE users SET password_hash='${hash}' WHERE username='${u}';`);
});
```
Copy the output SQL and run it in MySQL.

## Step 3 — Configure Database Connection
Edit the top of server.js:
```js
const pool = mysql.createPool({
  host    : 'localhost',
  user    : 'root',
  password: 'YOUR_MYSQL_PASSWORD',   // ← change this
  database: 'examcore',
});
```

## Step 4 — Install & Run
```bash
npm install
node server.js
```
Open http://localhost:3000 in your browser.

## API Endpoints
| Method | Endpoint              | Description               |
|--------|-----------------------|---------------------------|
| POST   | /api/auth/login       | Login                     |
| POST   | /api/auth/register    | Register new student      |
| GET    | /api/questions        | Fetch questions for exam  |
| POST   | /api/attempts         | Submit completed exam     |
| GET    | /api/attempts         | Get user's history        |
| GET    | /api/stats            | Get user's stats          |
| GET    | /api/leaderboard      | Get top scores            |

## Default Login Credentials
(after running the hash script above)
| Username | Password   | Role    |
|----------|------------|---------|
| admin    | admin123   | admin   |
| alice    | alice123   | student |
| bob      | bob123     | student |
| charlie  | charlie123 | student |
