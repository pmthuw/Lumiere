const { getPool } = require("../db");

const PRICE_EXPR =
  "ROUND(COALESCE(avg_import_price, 0) * (1 + (COALESCE(profit_rate, 0) / 100)))";

class Product {
  static async findAll() {
    const pool = getPool();
    const [rows] = await pool.execute(
      `SELECT p.*, ${PRICE_EXPR} AS price FROM products p ORDER BY p.id`,
    );
    return rows;
  }

  static async findByCategory(category) {
    const pool = getPool();
    const [rows] = await pool.execute(
      `SELECT p.*, ${PRICE_EXPR} AS price FROM products p WHERE p.category = ? ORDER BY p.id`,
      [category],
    );
    return rows;
  }

  static async findById(id) {
    const pool = getPool();
    const [rows] = await pool.execute(
      `SELECT p.*, ${PRICE_EXPR} AS price FROM products p WHERE p.id = ?`,
      [id],
    );
    return rows[0];
  }

  static async search(query) {
    const pool = getPool();
    const searchTerm = `%${query}%`;
    const [rows] = await pool.execute(
      `SELECT p.*, ${PRICE_EXPR} AS price FROM products p WHERE p.name LIKE ? OR p.brand LIKE ? OR p.notes LIKE ? ORDER BY p.id`,
      [searchTerm, searchTerm, searchTerm],
    );
    return rows;
  }

  static async filterByPrice(min, max) {
    const pool = getPool();
    let query = `SELECT p.*, ${PRICE_EXPR} AS price FROM products p WHERE 1=1`;
    const params = [];

    if (min !== undefined && min !== "") {
      query += ` AND ${PRICE_EXPR} >= ?`;
      params.push(min);
    }

    if (max !== undefined && max !== "") {
      query += ` AND ${PRICE_EXPR} <= ?`;
      params.push(max);
    }

    query += " ORDER BY p.id";

    const [rows] = await pool.execute(query, params);
    return rows;
  }
}

module.exports = Product;
