// ══════════════════════════════════════════════════
//  SHOPPING CART MANAGEMENT
// ══════════════════════════════════════════════════

// Thêm sản phẩm vào giỏ hàng
function addToCart(id, silent) {
  if (!state.user) {
    showToast("Vui lòng đăng nhập để thêm vào giỏ hàng!");
    openAuth("login");
    return;
  }
  const p = PRODUCTS.find((x) => x.id === id);
  const existing = state.cart.find((c) => c.id === id);
  if (existing) existing.qty++;
  else state.cart.push({ id, qty: 1 });
  saveCart();
  updateCartBadge();
  if (!silent) showToast(`Đã thêm "${p.name}" vào giỏ hàng!`);
}

// Lưu giỏ hàng vào localStorage
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
  renderCart();
  const drawer = document.getElementById("cart-drawer");
  const backdrop = document.getElementById("backdrop");
  if (drawer) drawer.classList.add("open");
  if (backdrop) backdrop.classList.add("open");
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
      const p = PRODUCTS.find((x) => x.id === item.id);
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

  const total = state.cart.reduce(
    (s, c) => s + PRODUCTS.find((x) => x.id === c.id).price * c.qty,
    0,
  );

  footer.innerHTML = `
    <div class="cart-total"><span class="label">Tổng cộng</span><span class="amount">${fmtPrice(total)}</span></div>
    <button class="btn btn-gold" style="width:100%" onclick="checkout()">Thanh toán →</button>
    <button class="btn btn-ghost" style="width:100%;margin-top:0.5rem" onclick="closeCart()">Tiếp tục mua sắm</button>`;
}

// Thay đổi số lượng sản phẩm trong giỏ
function updateCartQty(id, d) {
  const item = state.cart.find((c) => c.id === id);
  if (!item) return;
  item.qty = Math.max(1, item.qty + d);
  saveCart();
  updateCartBadge();
  renderCart();
}

// Xóa sản phẩm khỏi giỏ
function removeCartItem(id) {
  state.cart = state.cart.filter((c) => c.id !== id);
  saveCart();
  updateCartBadge();
  renderCart();
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
  // Lưu cart trước rồi chuyển trang
  saveCart();
  window.location.href = "checkout.php";
}

// Xác nhận đơn hàng (sau khi thanh toán)
function confirmOrder() {
  const total = state.cart.reduce((sum, item) => {
    const p = PRODUCTS.find((x) => x.id === item.id);
    return sum + p.price * item.qty;
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
