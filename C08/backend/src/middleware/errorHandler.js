function errorHandler(err, req, res, _next) {
  const status = err.statusCode || 500;
  const message = err.publicMessage || err.message || "Internal Server Error";

  // Avoid leaking DB/JWT internals in response.
  res.status(status).json({
    success: false,
    error: {
      message,
    },
  });
}

module.exports = { errorHandler };

