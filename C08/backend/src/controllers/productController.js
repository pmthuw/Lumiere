const Product = require("../models/Product");
const asyncHandler = require("../utils/asyncHandler");

exports.getAllProducts = asyncHandler(async (req, res) => {
  const products = await Product.findAll();
  res.json(products);
});

exports.getProductsByCategory = asyncHandler(async (req, res) => {
  const { category } = req.params;
  const products = await Product.findByCategory(category);
  res.json(products);
});

exports.getProductById = asyncHandler(async (req, res) => {
  const { id } = req.params;
  const product = await Product.findById(id);

  if (!product) {
    return res.status(404).json({ error: "Product not found" });
  }

  res.json(product);
});

exports.searchProducts = asyncHandler(async (req, res) => {
  const { q } = req.query;

  if (!q) {
    return res.status(400).json({ error: "Search query is required" });
  }

  const products = await Product.search(q);
  res.json(products);
});

exports.filterProducts = asyncHandler(async (req, res) => {
  const { category, minPrice, maxPrice } = req.query;

  let products;

  if (category && category !== "") {
    products = await Product.findByCategory(category);
  } else {
    products = await Product.findAll();
  }

  // Apply price filter if provided
  if (minPrice || maxPrice) {
    const priceFiltered = products.filter((product) => {
      const price = Number(product.price || 0);
      const min = minPrice ? parseInt(minPrice) : 0;
      const max = maxPrice ? parseInt(maxPrice) : Infinity;
      return price >= min && price <= max;
    });
    products = priceFiltered;
  }

  res.json(products);
});
