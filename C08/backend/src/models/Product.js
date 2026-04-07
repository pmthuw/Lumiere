const { getPool } = require("../db");

class Product {
  static async findAll() {
    const pool = getPool();
    const [rows] = await pool.execute("SELECT * FROM products ORDER BY id");
    return rows;
  }

  static async findByCategory(category) {
    const pool = getPool();
    const [rows] = await pool.execute(
      "SELECT * FROM products WHERE category = ? ORDER BY id",
      [category],
    );
    return rows;
  }

  static async findById(id) {
    const pool = getPool();
    const [rows] = await pool.execute("SELECT * FROM products WHERE id = ?", [
      id,
    ]);
    return rows[0];
  }

  static async search(query) {
    const pool = getPool();
    const searchTerm = `%${query}%`;
    const [rows] = await pool.execute(
      "SELECT * FROM products WHERE name LIKE ? OR brand LIKE ? OR notes LIKE ? ORDER BY id",
      [searchTerm, searchTerm, searchTerm],
    );
    return rows;
  }

  static async filterByPrice(min, max) {
    const pool = getPool();
    let query = "SELECT * FROM products WHERE 1=1";
    const params = [];

    if (min !== undefined && min !== "") {
      query += " AND price >= ?";
      params.push(min);
    }

    if (max !== undefined && max !== "") {
      query += " AND price <= ?";
      params.push(max);
    }

    query += " ORDER BY id";

    const [rows] = await pool.execute(query, params);
    return rows;
  }
}

module.exports = Product;
