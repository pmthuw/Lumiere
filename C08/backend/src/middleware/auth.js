const jwt = require("jsonwebtoken");
const env = require("../config/env");

function requireAuth(req, res, next) {
  const header = req.headers.authorization;
  const token = header && header.startsWith("Bearer ") ? header.slice(7) : null;
  if (!token) {
    return res.status(401).json({ success: false, error: { message: "Unauthorized" } });
  }

  try {
    const payload = jwt.verify(token, env.JWT_SECRET);
    // payload: { userId, role, email }
    req.auth = payload;
    return next();
  } catch {
    return res.status(401).json({ success: false, error: { message: "Invalid or expired token" } });
  }
}

function requireAdmin(req, res, next) {
  if (!req.auth || req.auth.role !== "admin") {
    return res.status(403).json({ success: false, error: { message: "Forbidden: admin only" } });
  }
  return next();
}

module.exports = { requireAuth, requireAdmin };

