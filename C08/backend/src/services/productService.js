const { getPool } = require("../db");

const ALLOWED_GENDERS = ["nam", "nu", "unisex", "qua_tang"];
const ALLOWED_BADGES = ["new", "hot", "sale"];
const ALLOWED_NOTES = ["top", "middle", "base"];

function assertAllowed(value, allowed, fieldName) {
  if (!allowed.includes(value)) {
    const err = new Error(`Invalid ${fieldName}`);
    err.statusCode = 400;
    err.publicMessage = `Invalid ${fieldName}`;
    throw err;
  }
}

async function listProducts({ search, gender }) {
  const pool = getPool();
  const where = [];
  const params = [];

  if (gender) {
    assertAllowed(gender, ALLOWED_GENDERS, "gender");
    where.push("p.gender = ?");
    params.push(gender);
  }

  if (search && String(search).trim() !== "") {
    const q = `%${search.trim()}%`;
    where.push("(p.name LIKE ? OR p.brand LIKE ?)");
    params.push(q, q);
  }

  const whereSql = where.length ? `WHERE ${where.join(" AND ")}` : "";

  const [rows] = await pool.query(
    `
    SELECT
      p.id,
      p.name,
      p.brand,
      p.gender,
      ROUND(COALESCE(p.avg_import_price, 0) * (1 + (COALESCE(p.profit_rate, 0) / 100))) AS price,
      p.old_price,
      p.badge_type,
      p.image_url,
      p.stock,
      p.volume_ml
    FROM products p
    ${whereSql}
    ORDER BY p.id DESC
    LIMIT 24
    `,
    params,
  );

  return rows.map((r) => ({
    id: r.id,
    name: r.name,
    brand: r.brand,
    gender: r.gender,
    price: Number(r.price),
    oldPrice: r.old_price === null ? null : Number(r.old_price),
    badge: r.badge_type,
    imageUrl: r.image_url,
    stock: r.stock,
    volumeMl: r.volume_ml,
  }));
}

async function getProductById(id) {
  const pool = getPool();
  const [rows] = await pool.query(
    `
    SELECT
      p.*,
      p.notes_top,
      p.notes_middle,
      p.notes_base
    FROM products p
    WHERE p.id = ?
    LIMIT 1
    `,
    [id],
  );

  if (rows.length === 0) {
    const err = new Error("Product not found");
    err.statusCode = 404;
    err.publicMessage = "Product not found";
    throw err;
  }

  const p = rows[0];
  return {
    id: p.id,
    name: p.name,
    brand: p.brand,
    gender: p.gender,
    price: Number(p.price),
    oldPrice: p.old_price === null ? null : Number(p.old_price),
    badge: p.badge_type,
    imageUrl: p.image_url,
    description: p.description,
    volumeMl: p.volume_ml,
    concentration: p.concentration,
    origin: p.origin,
    stock: p.stock,
    notes: {
      top: p.notes_top,
      middle: p.notes_middle,
      base: p.notes_base,
    },
    createdAt: p.created_at,
    updatedAt: p.updated_at,
  };
}

async function createProduct(data) {
  const pool = getPool();

  if (data.badge && data.badge !== null)
    assertAllowed(data.badge, ALLOWED_BADGES, "badge");
  assertAllowed(data.gender, ALLOWED_GENDERS, "gender");
  if (data.stock < 0) {
    const err = new Error("Invalid stock");
    err.statusCode = 400;
    err.publicMessage = "Invalid stock";
    throw err;
  }

  const [result] = await pool.query(
    `
    INSERT INTO products
      (name, brand, gender, avg_import_price, old_price, badge_type, image_url, description, volume_ml, concentration, origin, stock, notes_top, notes_middle, notes_base)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `,
    [
      data.name,
      data.brand,
      data.gender,
      Math.round(
        Number(data.price || 0) /
          (1 + (Number(data.profitRate || 30) || 30) / 100),
      ),
      data.oldPrice,
      data.badge || null,
      data.imageUrl,
      data.description,
      data.volumeMl,
      data.concentration,
      data.origin,
      data.stock,
      data.notesTop,
      data.notesMiddle,
      data.notesBase,
    ],
  );

  return { id: result.insertId };
}

async function updateProduct(id, data) {
  const pool = getPool();

  if (data.badge && data.badge !== null)
    assertAllowed(data.badge, ALLOWED_BADGES, "badge");
  assertAllowed(data.gender, ALLOWED_GENDERS, "gender");

  const [result] = await pool.query(
    `
    UPDATE products
    SET
      name = ?,
      brand = ?,
      gender = ?,
      avg_import_price = ?,
      old_price = ?,
      badge_type = ?,
      image_url = ?,
      description = ?,
      volume_ml = ?,
      concentration = ?,
      origin = ?,
      stock = ?,
      notes_top = ?,
      notes_middle = ?,
      notes_base = ?,
      updated_at = NOW()
    WHERE id = ?
    `,
    [
      data.name,
      data.brand,
      data.gender,
      Math.round(
        Number(data.price || 0) /
          (1 + (Number(data.profitRate || 30) || 30) / 100),
      ),
      data.oldPrice,
      data.badge || null,
      data.imageUrl,
      data.description,
      data.volumeMl,
      data.concentration,
      data.origin,
      data.stock,
      data.notesTop,
      data.notesMiddle,
      data.notesBase,
      id,
    ],
  );

  if (result.affectedRows === 0) {
    const err = new Error("Product not found");
    err.statusCode = 404;
    err.publicMessage = "Product not found";
    throw err;
  }

  return { updated: true };
}

async function deleteProduct(id) {
  const pool = getPool();
  const [result] = await pool.query("DELETE FROM products WHERE id = ?", [id]);
  if (result.affectedRows === 0) {
    const err = new Error("Product not found");
    err.statusCode = 404;
    err.publicMessage = "Product not found";
    throw err;
  }
  return { deleted: true };
}

async function listAdminProducts() {
  const pool = getPool();
  const [rows] = await pool.query(
    `
    SELECT
      id,
      name,
      brand,
      gender,
      ROUND(COALESCE(avg_import_price, 0) * (1 + (COALESCE(profit_rate, 0) / 100))) AS price,
      old_price,
      badge_type,
      image_url,
      volume_ml,
      concentration,
      origin,
      stock,
      notes_top,
      notes_middle,
      notes_base,
      description,
      created_at,
      updated_at
    FROM products
    ORDER BY id DESC
    LIMIT 200
    `,
  );

  return rows.map((r) => ({
    id: r.id,
    name: r.name,
    brand: r.brand,
    gender: r.gender,
    price: Number(r.price),
    oldPrice: r.old_price === null ? null : Number(r.old_price),
    badge: r.badge_type,
    imageUrl: r.image_url,
    volumeMl: r.volume_ml,
    concentration: r.concentration,
    origin: r.origin,
    stock: r.stock,
    notes: { top: r.notes_top, middle: r.notes_middle, base: r.notes_base },
    description: r.description,
    createdAt: r.created_at,
    updatedAt: r.updated_at,
  }));
}

module.exports = {
  listProducts,
  getProductById,
  createProduct,
  updateProduct,
  deleteProduct,
  listAdminProducts,
};
