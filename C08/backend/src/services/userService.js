const bcrypt = require("bcryptjs");
const { getPool } = require("../db");

async function findByEmail(email) {
  const pool = getPool();
  const [rows] = await pool.query("SELECT * FROM users WHERE email = ? LIMIT 1", [email]);
  return rows[0] || null;
}

async function registerUser({ fullName, email, password }) {
  const pool = getPool();

  const existing = await findByEmail(email);
  if (existing) {
    const err = new Error("Email already exists");
    err.statusCode = 409;
    err.publicMessage = "Email already exists";
    throw err;
  }

  const passwordHash = await bcrypt.hash(password, 12);
  const [result] = await pool.query(
    `
    INSERT INTO users (full_name, email, password_hash, role)
    VALUES (?, ?, ?, 'customer')
    `,
    [fullName, email, passwordHash]
  );

  return { id: result.insertId };
}

async function loginUser({ email, password }) {
  const user = await findByEmail(email);
  if (!user) {
    const err = new Error("Invalid credentials");
    err.statusCode = 401;
    err.publicMessage = "Invalid credentials";
    throw err;
  }

  const ok = await bcrypt.compare(password, user.password_hash);
  if (!ok) {
    const err = new Error("Invalid credentials");
    err.statusCode = 401;
    err.publicMessage = "Invalid credentials";
    throw err;
  }

  return {
    id: user.id,
    email: user.email,
    role: user.role,
    fullName: user.full_name,
  };
}

module.exports = {
  registerUser,
  loginUser,
};

