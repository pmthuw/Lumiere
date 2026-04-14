const { getPool } = require("../db");

async function findByEmail(email) {
  const pool = getPool();
  const [rows] = await pool.query(
    "SELECT * FROM users WHERE email = ? LIMIT 1",
    [email],
  );
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

  const [result] = await pool.query(
    `
    INSERT INTO users (full_name, email, password_hash, role)
    VALUES (?, ?, ?, 'customer')
    `,
    [fullName, email, password],
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

  const ok = user.password_hash === password;
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
