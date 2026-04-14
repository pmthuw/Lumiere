// ══════════════════════════════════════════════════
//  SHOPPING CART MANAGEMENT (PHP Backend)
// ══════════════════════════════════════════════════

const CART_API = "../backend/api/cart.php";

function getProductById(productId) {
  const productList = Array.isArray(PRODUCTS) ? PRODUCTS : [];
  const normalizedId = Number(productId);
  return productList.find((x) => Number(x.id) === normalizedId) || null;
}

// Thêm sản phẩm vào giỏ hàng
function addToCart(id, nameOrSilent, priceOrUndefined) {
  console.log("[addToCart] Called with:", {
    id,
    nameOrSilent,
    priceOrUndefined,
  });

  // Handle both old API (id, name, price) and new API (id, silent)
  let silent = false;
  if (typeof nameOrSilent === "boolean") {
    silent = nameOrSilent;
  }

  console.log("[addToCart] state.user:", state.user);
  console.log("[addToCart] state.cart before:", JSON.stringify(state.cart));

  if (!state.user) {
    console.log("[addToCart] User not logged in, showing auth modal");
    showToast("Vui lòng đăng nhập để thêm vào giỏ hàng!");
    openAuth("login");
    return;
  }

  const p = getProductById(id);
  if (!p) {
    // If PRODUCTS not loaded yet, still add to cart (will sync to DB)
    console.warn(
      "[addToCart] Product not in PRODUCTS array (may not be loaded yet), but adding to cart anyway",
    );
  }

  // Update local state immediately
  if (!Array.isArray(state.cart)) {
    state.cart = [];
  }

  const normalizedId = Number(id);
  const existing = state.cart.find((c) => Number(c.id) === normalizedId);
  const fallbackName =
    typeof nameOrSilent === "string" ? nameOrSilent : "Sản phẩm";
  const fallbackPrice = Number(priceOrUndefined) || 0;
  if (existing) {
    existing.qty++;
    if (!existing.name) existing.name = p?.name || fallbackName;
    if (!existing.price) existing.price = p?.price || fallbackPrice;
    console.log(
      "[addToCart] Increased qty for product",
      id,
      "to",
      existing.qty,
    );
  } else {
    state.cart.push({
      id: normalizedId,
      qty: 1,
      name: p?.name || fallbackName,
      price: p?.price || fallbackPrice,
    });
    console.log("[addToCart] Added new product", id, "to cart");
  }

  console.log("[addToCart] state.cart after:", JSON.stringify(state.cart));

  // Save to localStorage as fallback
  localStorage.setItem("lum_cart", JSON.stringify(state.cart));
  console.log("[addToCart] Saved to localStorage");

  updateCartBadge();

  if (!silent) {
    const productName = p
      ? p.name
      : typeof nameOrSilent === "string"
        ? nameOrSilent
        : "Sản phẩm";
    showToast(`Đã thêm "${productName}" vào giỏ hàng!`);
  }

  // Sync to database
  console.log("[addToCart] Syncing to API...");
  fetch(
    `${CART_API}?action=add&product_id=${id}&quantity=1&user=${encodeURIComponent(state.user.email)}`,
    {
      method: "POST",
    },
  )
    .then((r) => r.json())
    .then((data) => {
      console.log("[addToCart] API Response:", data);
      if (!data.success) {
        showToast("Lỗi khi cập nhật giỏ hàng trên server!");
        console.error("[addToCart] Cart API error:", data);
      } else {
        console.log("[addToCart] API sync successful");
      }
    })
    .catch((err) => {
      console.error("[addToCart] Fetch error:", err);
      showToast("Lỗi kết nối (nhưng dữ liệu vẫn được lưu)");
    });
}

// Lưu giỏ hàng vào localStorage (fallback, không dùng nữa)
function saveCart() {
  localStorage.setItem("lum_cart", JSON.stringify(state.cart));
}

// Cập nhật số badge giỏ hàng
function updateCartBadge() {
  const badge = document.getElementById("cart-badge");
  if (badge) {
    badge.textContent = state.cart.reduce((s, c) => s + c.qty, 0);
  }
}

// Mở drawer giỏ hàng
function openCart() {
  if (state.user) {
    loadCartFromDB(() => {
      renderCart();
      const drawer = document.getElementById("cart-drawer");
      const backdrop = document.getElementById("backdrop");
      if (drawer) drawer.classList.add("open");
      if (backdrop) backdrop.classList.add("open");
    });
  } else {
    showToast("Vui lòng đăng nhập!");
    openAuth("login");
  }
}

// Load giỏ hàng từ database
function loadCartFromDB(callback) {
  if (!state.user) {
    if (callback) callback();
    return;
  }

  fetch(`${CART_API}?action=get&user=${encodeURIComponent(state.user.email)}`)
    .then((r) => r.json())
    .then((data) => {
      if (data.success && Array.isArray(data.items)) {
        const remoteCart = data.items.map((item) => ({
          id: item.product_id,
          qty: item.quantity,
          name: item.name,
          price: Number(item.price) || 0,
          image: item.image || "",
        }));

        // Server is source of truth for cart content.
        state.cart = remoteCart;
        localStorage.setItem("lum_cart", JSON.stringify(remoteCart));

        updateCartBadge();
      }

      if (callback) callback();
    })
    .catch((err) => {
      console.error("Load cart error:", err);
      if (callback) callback();
    });
}

// Đóng drawer giỏ hàng
function closeCart() {
  const drawer = document.getElementById("cart-drawer");
  const backdrop = document.getElementById("backdrop");
  if (drawer) drawer.classList.remove("open");
  if (backdrop) backdrop.classList.remove("open");
}

// Hiển thị nội dung giỏ hàng
function renderCart() {
  const body = document.getElementById("cart-body");
  const footer = document.getElementById("cart-footer");

  if (!body || !footer) return;

  if (!state.cart.length) {
    body.innerHTML = `<div class="cart-empty"><div class="icon">🛒</div><p>Giỏ hàng của bạn đang trống.</p></div>`;
    footer.innerHTML = "";
    return;
  }

  body.innerHTML = state.cart
    .map((item) => {
      const p = getProductById(item.id) || {
        id: Number(item.id),
        name: item.name || "Sản phẩm",
        price: Number(item.price) || 0,
      };
      return `<div class="cart-item">
      <div class="cart-item-img">◈</div>
      <div>
        <div class="cart-item-name">${p.name}</div>
        <div class="cart-item-price">${fmtPrice(p.price)}</div>
        <div class="cart-item-qty">
          <button onclick="updateCartQty(${p.id},-1)">−</button>
          <span>${item.qty}</span>
          <button onclick="updateCartQty(${p.id},1)">+</button>
        </div>
      </div>
      <button class="cart-remove" onclick="removeCartItem(${p.id})">🗑</button>
    </div>`;
    })
    .join("");

  const total = state.cart.reduce((s, c) => {
    const product = getProductById(c.id);
    if (product) return s + product.price * c.qty;
    return s + (Number(c.price) || 0) * c.qty;
  }, 0);

  footer.innerHTML = `
    <div class="cart-total"><span class="label">Tổng cộng</span><span class="amount">${fmtPrice(total)}</span></div>
    <button class="btn btn-gold" style="width:100%" onclick="checkout()">Thanh toán →</button>
    <button class="btn btn-ghost" style="width:100%;margin-top:0.5rem" onclick="closeCart()">Tiếp tục mua sắm</button>`;
}

// Thay đổi số lượng sản phẩm trong giỏ
function updateCartQty(id, d) {
  if (!state.user) return;

  const item = state.cart.find((c) => c.id === id);
  if (!item) return;

  const newQty = Math.max(1, item.qty + d);
  item.qty = newQty;
  updateCartBadge();

  fetch(
    `${CART_API}?action=update&product_id=${id}&quantity=${newQty}&user=${encodeURIComponent(state.user.email)}`,
    {
      method: "POST",
    },
  )
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        renderCart();
      } else {
        showToast("Lỗi cập nhật: " + (data.error || "?"));
      }
    })
    .catch((err) => console.error("Error:", err));
}

// Xóa sản phẩm khỏi giỏ
function removeCartItem(id) {
  if (!state.user) return;

  state.cart = state.cart.filter((c) => c.id !== id);
  updateCartBadge();

  fetch(
    `${CART_API}?action=remove&product_id=${id}&user=${encodeURIComponent(state.user.email)}`,
    {
      method: "POST",
    },
  )
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        renderCart();
      } else {
        showToast("Lỗi xóa: " + (data.error || "?"));
      }
    })
    .catch((err) => console.error("Error:", err));
}

// Thanh toán
function checkout() {
  if (!state.user) {
    showToast("Vui lòng đăng nhập!");
    openAuth("login");
    return;
  }
  if (!state.cart.length) {
    showToast("Giỏ hàng trống!");
    return;
  }
  window.location.href =
    "cart.php?user=" + encodeURIComponent(state.user.email);
}

// Xác nhận đơn hàng (sau khi thanh toán)
function confirmOrder() {
  const total = state.cart.reduce((sum, item) => {
    const p = getProductById(item.id);
    if (p) return sum + p.price * item.qty;
    return sum + (Number(item.price) || 0) * item.qty;
  }, 0);

  const order = {
    id: Date.now(),
    userEmail: state.user.email,
    customer: `${state.user.lastname} ${state.user.firstname}`,
    phone: state.user.phone,
    address: `${state.user.address}, ${state.user.district}, ${state.user.city}`,
    items: [...state.cart],
    total: total,
    status: "Đang xử lý",
    createdAt: new Date().toLocaleString("vi-VN"),
    dateRaw: Date.now(),
  };

  state.orders.push(order);

  // Tự động cập nhật trạng thái
  setTimeout(() => {
    order.status = "Đang giao";
    localStorage.setItem("lum_orders", JSON.stringify(state.orders));
  }, 5000);

  setTimeout(() => {
    order.status = "Đã giao";
    localStorage.setItem("lum_orders", JSON.stringify(state.orders));
  }, 10000);

  localStorage.setItem("lum_orders", JSON.stringify(state.orders));

  state.cart = [];
  saveCart();
  updateCartBadge();
  renderCart();

  closeModal("bill-modal");
  closeCart();

  showToast("Đặt hàng thành công!");
}
