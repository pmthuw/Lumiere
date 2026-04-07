const mysql = require("mysql2/promise");
const env = require("./config/env");

let pool;

function createPool() {
  return mysql.createPool({
    host: env.DB_HOST,
    port: env.DB_PORT,
    user: env.DB_USER,
    password: env.DB_PASSWORD,
    database: env.DB_NAME,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
  });
}

function getPool() {
  if (!pool) pool = createPool();
  return pool;
}

module.exports = { getPool };

