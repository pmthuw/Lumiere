// ══════════════════════════════════════════════════
//  DATA
// ══════════════════════════════════════════════════
let PRODUCTS = []; // Will be loaded from API
const API_BASE = (() => {
  const path = window.location.pathname;
  const frontendIndex = path.indexOf("/frontend/");
  if (frontendIndex >= 0) {
    const projectRoot = path.slice(0, frontendIndex);
    return `${window.location.origin}${projectRoot}/backend/api`;
  }
  return "../backend/api";
})();

// Load products from API
async function loadProducts() {
  try {
    const response = await fetch(`${API_BASE}/products.php`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    PRODUCTS = await response.json();
    renderProducts();
  } catch (error) {
    console.error("Failed to load products:", error);
    // Fallback to hardcoded data if API fails
    PRODUCTS = [
      {
        id: 1,
        name: "Chanel No.5",
        category: "Nữ",
        price: 7200000,
        desc: "Hương thơm hoa cỏ cổ điển, biểu tượng cho vẻ đẹp thanh lịch và tinh tế. Gồm hoa hồng, hoa nhài và ylang-ylang.",
        notes: "Hoa cỏ aldehyde",
        concentration: "Eau de Parfum",
        size: "100ml",
        brand: "Chanel",
        badge: "Bestseller",
        image: "images/hinh1.jpg",
      },
      {
        id: 2,
        name: "Dior Sauvage",
        category: "Nam",
        price: 8800000,
        desc: "Hương thơm nam tính, mạnh mẽ và đầy cuốn hút với bergamot Calabrian và ambroxan đặc trưng.",
        notes: "Fougère Woody",
        concentration: "Eau de Toilette",
        size: "100ml",
        brand: "Dior",
        badge: "Hot",
        image: "images/hinh3.jpg",
      },
      {
        id: 3,
        name: "Tom Ford Black Orchid",
        category: "Unisex",
        price: 6600000,
        desc: "Hương phương Đông bí ẩn, quyến rũ và sang trọng. Kết hợp truffle đen, ylang và orchid đen.",
        notes: "Oriental Floral",
        concentration: "Eau de Parfum",
        size: "50ml",
        brand: "Tom Ford",
        badge: "",
        image: "images/hinh4.jpg",
      },
      {
        id: 4,
        name: "YSL Black Opium",
        category: "Nữ",
        price: 8400000,
        desc: "Hương cà phê quyến rũ, pha trộn với hoa nhài trắng và vani ngọt ngào, tạo nên cá tính riêng biệt.",
        notes: "Oriental Floral",
        concentration: "Eau de Parfum",
        size: "90ml",
        brand: "YSL",
        badge: "New",
        image: "images/hinh5.jpg",
      },
      {
        id: 5,
        name: "Creed Aventus",
        category: "Nam",
        price: 14500000,
        desc: "Hương thơm huyền thoại từ quả cây và gỗ, truyền cảm hứng từ cuộc đời Napoleon Bonaparte.",
        notes: "Fruity Chypre",
        concentration: "Eau de Parfum",
        size: "100ml",
        brand: "Creed",
        badge: "Luxury",
        image: "images/hinh6.jpg",
      },
      {
        id: 6,
        name: "Jo Malone Peony",
        category: "Unisex",
        price: 8200000,
        desc: "Hương mẫu đơn nhẹ nhàng kết hợp hồng đào và hổ phách mang lại cảm giác tươi mới, sang trọng.",
        notes: "Floral Fruity",
        concentration: "Cologne",
        size: "100ml",
        brand: "Jo Malone",
        badge: "",
        image: "images/hinh7.jpg",
      },
      {
        id: 7,
        name: "Versace Eros",
        category: "Nam",
        price: 10800000,
        desc: "Sự kết hợp táo bạo giữa bạc hà Ý, táo xanh và hoa hồng, tượng trưng cho vẻ đẹp và ham muốn.",
        notes: "Oriental Fougère",
        concentration: "Eau de Toilette",
        size: "100ml",
        brand: "Versace",
        badge: "",
        image: "images/hinh8.jpg",
      },
      {
        id: 8,
        name: "Gucci Bloom",
        category: "Nữ",
        price: 9800000,
        desc: "Bó hoa trắng phong phú của tuberose, jasmine và rangoon creeper tạo nên hương thơm thuần khiết.",
        notes: "Floral",
        concentration: "Eau de Parfum",
        size: "100ml",
        brand: "Gucci",
        badge: "",
        image: "images/hinh9.jpg",
      },
      {
        id: 9,
        name: "Maison Margiela Replica",
        category: "Unisex",
        price: 8900000,
        desc: '"By the Fireplace" – hương thơm gợi nhớ những đêm bên lò sưởi ấm áp với gỗ và vani dịu dàng.',
        notes: "Woody Floral Musk",
        concentration: "Eau de Toilette",
        size: "100ml",
        brand: "Maison Margiela",
        badge: "Limited",
        image: "images/hinh10.jpg",
      },
      {
        id: 10,
        name: "Hermès Terre",
        category: "Nam",
        price: 9200000,
        desc: "Sự kết hợp hoàn hảo giữa bưởi, hạt tiêu và gỗ tuyết tùng, thể hiện nam tính lịch lãm và tự do.",
        notes: "Woody Citrus",
        concentration: "Eau de Toilette",
        size: "75ml",
        brand: "Hermès",
        badge: "",
        image: "images/hinh11.jpg",
      },
      {
        id: 11,
        name: "Lancôme La Vie Est Belle",
        category: "Nữ",
        price: 11100000,
        desc: 'Tên nghĩa là "Cuộc sống thật tươi đẹp" – hương hoa iris kết hợp praline và vanilla ngọt ngào.',
        notes: "Oriental Floral",
        concentration: "Eau de Parfum",
        size: "75ml",
        brand: "Lancôme",
        badge: "",
        image: "images/hinh12.jpg",
      },
      {
        id: 12,
        name: "Kilian Angel Share",
        category: "Unisex",
        price: 9800000,
        desc: "Lấy cảm hứng từ nghệ thuật ủ rượu cognac, hương thơm kết hợp cinnamon, nutmeg và caramel.",
        notes: "Oriental Woody",
        concentration: "Eau de Parfum",
        size: "50ml",
        brand: "Kilian",
        badge: "Limited",
        image: "images/hinh13.jpg",
      },
      {
        id: 13,
        name: "Million Elixir",
        category: "Limited",
        price: 9800000,
        desc: "Một sáng tạo giới hạn với sự hòa quyện của oud, hoa hồng đen và amber, mang đến chiều sâu bí ẩn và cảm giác xa hoa đầy cuốn hút.",
        notes: "Amber Oud",
        concentration: "Extrait de Parfum",
        size: "50ml",
        brand: "Milion",
        badge: "Limited",
        image: "images/hinh14.jpg",
      },
      {
        id: 14,
        name: "Attrape-Rêves",
        category: "Limited",
        price: 13350000,
        desc: "Một hương thơm quyến rũ với vải thiều chín mọng, hoa mẫu đơn và cacao nhẹ, tạo nên cảm giác mơ màng, nữ tính và đầy cuốn hút.",
        notes: "Floral Fruity Gourmand",
        concentration: "Eau de Parfum",
        size: "100ml",
        brand: "Attrape",
        badge: "Limited",
        image: "images/hinh15.jpg",
      },
    ];
    renderProducts();
  }
}

// ══════════════════════════════════════════════════
//  STATE
// ══════════════════════════════════════════════════
let state = {
  currentCategory: "",
  filterCategory: "",
  priceMin: null,
  priceMax: null,
  page: 1,
  perPage: 6,
  isSearching: false,
  cart: JSON.parse(localStorage.getItem("lum_cart") || "[]"),
  user: JSON.parse(localStorage.getItem("lum_user") || "null"),
  users: JSON.parse(localStorage.getItem("lum_users") || "[]"),
  orders: JSON.parse(localStorage.getItem("lum_orders") || "[]"),
};

// ══════════════════════════════════════════════════
//  SECTION NAVIGATION
// ══════════════════════════════════════════════════
const ALL_SECTIONS = ["home", "products", "contact"];

function showSection(name) {
  ALL_SECTIONS.forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (name === "contact") {
      el.style.display = id === "contact" ? "block" : "none";
    } else {
      el.style.display = id === "contact" ? "none" : "block";
      if (id === name) {
        setTimeout(
          () => el.scrollIntoView({ behavior: "smooth", block: "start" }),
          50,
        );
      }
    }
  });

  document.querySelectorAll(".main-nav a").forEach((a) => {
    a.classList.remove("active");
    const oc = a.getAttribute("onclick") || "";
    if (oc.includes(`'${name}'`) || oc.includes(`"${name}"`))
      a.classList.add("active");
  });

  if (name === "contact") window.scrollTo({ top: 0, behavior: "smooth" });
}

function _highlight(text, q) {
  if (!q) return text;
  const esc = q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  return text.replace(new RegExp(`(${esc})`, "gi"), "<mark>$1</mark>");
}

function renderSuggestions(query) {
  const box = _getSuggestBox();
  const q = query.trim().toLowerCase();

  if (!q) {
    closeSuggestions();
    return;
  }

  const hits = PRODUCTS.filter(
    (p) =>
      p.name.toLowerCase().includes(q) ||
      p.brand.toLowerCase().includes(q) ||
      p.category.toLowerCase().includes(q) ||
      p.notes.toLowerCase().includes(q),
  ).slice(0, 6);

  _sgIndex = -1;

  if (!hits.length) {
    box.innerHTML = `
      <div class="sg-header">Gợi ý tìm kiếm</div>
      <div class="sg-empty">Không tìm thấy sản phẩm phù hợp 🔍</div>`;
  } else {
    const rows = hits
      .map(
        (p) => `
      <div class="sg-item" onclick="selectSuggestion(${p.id})" data-id="${p.id}">
        <div class="sg-thumb">
          ${
            p.image
              ? `<img src="${p.image}" alt="${p.name}" onerror="this.parentElement.textContent='◈'">`
              : "◈"
          }
        </div>
        <div class="sg-info">
          <div class="sg-name">${_highlight(p.name, query.trim())}</div>
          <div class="sg-meta">${p.brand} · ${p.category} · ${p.size}</div>
        </div>
        <div class="sg-price">${fmtPrice(p.price)}</div>
      </div>`,
      )
      .join("");

    box.innerHTML = `
      <div class="sg-header">Gợi ý — ${hits.length} sản phẩm</div>
      ${rows}
      <div class="sg-footer" onclick="doSearch()">Xem tất cả kết quả →</div>`;
  }

  box.style.display = "block";
}

function closeSuggestions() {
  if (_suggestBox) _suggestBox.style.display = "none";
  _sgIndex = -1;
}

function selectSuggestion(id) {
  const inp = document.getElementById("search-input");
  const p = PRODUCTS.find((x) => x.id === id);
  if (inp && p) inp.value = p.name;
  closeSuggestions();
  showSection("products");
  setTimeout(() => openDetail(id), 120);
}

function _handleSuggestKey(e) {
  if (!_suggestBox || _suggestBox.style.display === "none") return;
  const items = _suggestBox.querySelectorAll(".sg-item");
  if (!items.length) return;

  if (e.key === "ArrowDown") {
    e.preventDefault();
    _sgIndex = Math.min(_sgIndex + 1, items.length - 1);
    _updateSgActive(items);
  } else if (e.key === "ArrowUp") {
    e.preventDefault();
    _sgIndex = Math.max(_sgIndex - 1, -1);
    _updateSgActive(items);
  } else if (e.key === "Enter" && _sgIndex >= 0) {
    e.preventDefault();
    selectSuggestion(parseInt(items[_sgIndex].dataset.id));
  } else if (e.key === "Escape") {
    closeSuggestions();
  }
}

function _updateSgActive(items) {
  items.forEach((item, i) =>
    item.classList.toggle("sg-active", i === _sgIndex),
  );
  if (_sgIndex >= 0) items[_sgIndex].scrollIntoView({ block: "nearest" });
}

// ══════════════════════════════════════════════════
//  FILTER & DISPLAY
// ══════════════════════════════════════════════════
function getFilteredProducts() {
  let list = [...PRODUCTS];
  if (state.currentCategory)
    list = list.filter((p) => p.category === state.currentCategory);
  if (state.filterCategory)
    list = list.filter((p) => p.category === state.filterCategory);
  if (state.priceMin !== null)
    list = list.filter((p) => p.price >= state.priceMin);
  if (state.priceMax !== null)
    list = list.filter((p) => p.price <= state.priceMax);
  return list;
}

function renderProducts() {
  const filtered = getFilteredProducts();
  const total = filtered.length;
  const totalPages = Math.max(1, Math.ceil(total / state.perPage));
  if (state.page > totalPages) state.page = 1;
  const start = (state.page - 1) * state.perPage;
  const pageItems = filtered.slice(start, start + state.perPage);

  const grid = document.getElementById("product-grid");
  document.getElementById("result-count").textContent = `${total} sản phẩm`;
  document.getElementById("page-info").textContent =
    total > 0
      ? `Hiển thị ${start + 1}–${Math.min(start + state.perPage, total)} / ${total} sản phẩm`
      : "";

  if (!pageItems.length) {
    grid.innerHTML = `<div class="no-results"><div class="icon">🔍</div><p>Không tìm thấy sản phẩm phù hợp.</p><button class="btn btn-ghost" style="margin-top:1rem" onclick="resetSearch()">Xóa bộ lọc</button></div>`;
  } else {
    grid.innerHTML = pageItems
      .map(
        (p) => `
      <article class="product-card" onclick="openDetail(${p.id})">
        <div class="product-card-img">
          ${p.badge ? `<span class="product-badge">${p.badge}</span>` : ""}
          ${p.image ? `<img src="${p.image}" alt="${p.name}" style="width:100%;height:100%;object-fit:cover;display:block;" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">` : ""}
          <div class="placeholder-icon" style="${p.image ? "display:none" : ""}">◈</div>
        </div>
        <div class="product-card-body">
          <div class="product-category">${p.category} · ${p.brand}</div>
          <h3>${p.name}</h3>
          <p>${p.desc.substring(0, 80)}...</p>
          <div class="product-footer">
            <span class="product-price">${fmtPrice(p.price)}</span>
            <button class="add-cart-btn" onclick="event.stopPropagation();addToCart(${p.id})">+ Giỏ hàng</button>
          </div>
        </div>
      </article>`,
      )
      .join("");
  }
  renderPagination(totalPages);
}

function renderPagination(totalPages) {
  const el = document.getElementById("pagination");
  if (totalPages <= 1) {
    el.innerHTML = "";
    return;
  }
  let html = `<button class="page-btn" onclick="goPage(${state.page - 1})" ${state.page === 1 ? "disabled" : ""}>‹</button>`;
  for (let i = 1; i <= totalPages; i++) {
    if (
      totalPages > 7 &&
      i > 2 &&
      i < totalPages - 1 &&
      Math.abs(i - state.page) > 1
    ) {
      if (i === 3 || i === totalPages - 2)
        html += `<span style="color:var(--muted);padding:0 0.25rem">…</span>`;
      continue;
    }
    html += `<button class="page-btn ${i === state.page ? "active" : ""}" onclick="goPage(${i})">${i}</button>`;
  }
  html += `<button class="page-btn" onclick="goPage(${state.page + 1})" ${state.page === totalPages ? "disabled" : ""}>›</button>`;
  el.innerHTML = html;
}

function goPage(p) {
  state.page = p;
  renderProducts();
  document
    .getElementById("products")
    .scrollIntoView({ behavior: "smooth", block: "start" });
}

function filterCategory(cat, el) {
  state.currentCategory = cat;
  state.page = 1;
  state.isSearching = false;
  closeSuggestions();
  document
    .querySelectorAll(".tab-btn")
    .forEach((b) => b.classList.remove("active"));
  el.classList.add("active");
  document.getElementById("section-eyebrow").textContent =
    cat || "All Products";
  document.getElementById("section-title").textContent = cat
    ? `Nước hoa ${cat}`
    : "Tất cả sản phẩm";
  renderProducts();
}

// ══════════════════════════════════════════════════
//  SEARCH
// ══════════════════════════════════════════════════
function toggleAdvanced() {
  document.getElementById("advanced-panel").classList.toggle("open");
}

function doSearch() {
  state.filterCategory = document.getElementById("filter-category").value;
  const minVal = document.getElementById("price-min").value;
  const maxVal = document.getElementById("price-max").value;
  state.priceMin = minVal ? parseInt(minVal) : null;
  state.priceMax = maxVal ? parseInt(maxVal) : null;
  state.isSearching = Boolean(
    state.filterCategory || state.priceMin !== null || state.priceMax !== null,
  );
  state.currentCategory = "";
  state.page = 1;
  document
    .querySelectorAll(".tab-btn")
    .forEach((b) => b.classList.remove("active"));
  document.querySelector(".tab-btn").classList.add("active");
  document.getElementById("section-eyebrow").textContent = state.isSearching
    ? "Kết quả lọc"
    : "Sản phẩm";
  document.getElementById("section-title").textContent = state.isSearching
    ? "Kết quả lọc"
    : "Tất cả sản phẩm";
  renderProducts();
  document.getElementById("products").scrollIntoView({ behavior: "smooth" });
}

function resetSearch() {
  state.filterCategory = "";
  state.priceMin = null;
  state.priceMax = null;
  state.isSearching = false;
  state.page = 1;
  document.getElementById("filter-category").value = "";
  document.getElementById("price-min").value = "";
  document.getElementById("price-max").value = "";
  closeSuggestions();
  document.getElementById("section-eyebrow").textContent = "Featured Products";
  document.getElementById("section-title").textContent = "Sản phẩm nổi bật";
  renderProducts();
}

function openSearch() {
  showSection("products");
  setTimeout(() => {
    const inp = document.getElementById("search-input");
    if (inp) inp.focus();
  }, 500);
}

// Gắn event listeners sau DOM sẵn sàng
document.addEventListener("DOMContentLoaded", () => {
  const inp = document.getElementById("search-input");
  if (!inp) return;

  // Gõ chữ → gợi ý real-time
  inp.addEventListener("input", (e) => renderSuggestions(e.target.value));

  // Bàn phím điều hướng
  inp.addEventListener("keydown", (e) => {
    if (
      e.key === "Enter" &&
      (_sgIndex < 0 || !_suggestBox || _suggestBox.style.display === "none")
    ) {
      doSearch();
      return;
    }
    _handleSuggestKey(e);
  });

  // Focus → hiện lại nếu đang có chữ
  inp.addEventListener("focus", (e) => {
    if (e.target.value.trim()) renderSuggestions(e.target.value);
  });
});

// Click ngoài → đóng dropdown + đóng user menu
document.addEventListener("click", (e) => {
  if (!e.target.closest(".search-input-wrap")) closeSuggestions();
  if (!e.target.closest(".user-menu-wrap")) closeUserMenu();
});

// ══════════════════════════════════════════════════
//  PRODUCT DETAIL
// ══════════════════════════════════════════════════
function openDetail(id) {
  const p = PRODUCTS.find((x) => x.id === id);
  if (!p) return;
  document.getElementById("detail-content").innerHTML = `
    <div class="detail-img" style="${p.image ? "padding:0;overflow:hidden" : ""}">
      ${p.image ? `<img src="${p.image}" alt="${p.name}" style="width:100%;height:100%;object-fit:cover;border-radius:1.5rem;" onerror="this.style.display='none'">` : `<div class="placeholder-icon">◈</div>`}
    </div>
    <div class="detail-info">
      <div class="detail-category">${p.brand} · ${p.category}</div>
      <h2 class="detail-name">${p.name}</h2>
      <div class="detail-price">${fmtPrice(p.price)}</div>
      <p class="detail-desc">${p.desc}</p>
      <div class="detail-meta">
        <div class="detail-meta-item"><div class="label">Nồng độ</div><div class="val">${p.concentration}</div></div>
        <div class="detail-meta-item"><div class="label">Dung tích</div><div class="val">${p.size}</div></div>
        <div class="detail-meta-item"><div class="label">Nhóm hương</div><div class="val">${p.notes}</div></div>
        <div class="detail-meta-item"><div class="label">Phân loại</div><div class="val">${p.category}</div></div>
      </div>
      <div class="qty-row">
        <div class="qty-ctrl">
          <button onclick="changeDetailQty(-1)">−</button>
          <span id="detail-qty">1</span>
          <button onclick="changeDetailQty(1)">+</button>
        </div>
        <span style="color:var(--muted);font-size:0.85rem">Còn hàng</span>
      </div>
      <div class="detail-actions">
        <button class="btn btn-gold" onclick="addToCartFromDetail(${p.id})">Thêm vào giỏ</button>
        <button class="btn btn-ghost" onclick="buyNowFromDetail(${p.id})">Mua ngay</button>
      </div>
    </div>`;
  openModal("detail-modal");
}

let detailQty = 1;
function changeDetailQty(d) {
  detailQty = Math.max(1, detailQty + d);
  document.getElementById("detail-qty").textContent = detailQty;
}
function addToCartFromDetail(id) {
  for (let i = 0; i < detailQty; i++) addToCart(id, true);
  detailQty = 1;
  closeModal("detail-modal");
}
function buyNowFromDetail(id) {
  for (let i = 0; i < detailQty; i++) addToCart(id, true);
  detailQty = 1;
  closeModal("detail-modal");
  openCart();
}

// ══════════════════════════════════════════════════
//  CART
// ══════════════════════════════════════════════════
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
function saveCart() {
  localStorage.setItem("lum_cart", JSON.stringify(state.cart));
}
function updateCartBadge() {
  const badge = document.getElementById("cart-badge");
  if (!badge) return;
  badge.textContent = state.cart.reduce((s, c) => s + c.qty, 0);
}
function openCart() {
  renderCart();
  document.getElementById("cart-drawer").classList.add("open");
  document.getElementById("backdrop").classList.add("open");
}
function closeCart() {
  document.getElementById("cart-drawer").classList.remove("open");
  document.getElementById("backdrop").classList.remove("open");
}

function renderCart() {
  const body = document.getElementById("cart-body");
  const footer = document.getElementById("cart-footer");
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

function updateCartQty(id, d) {
  const item = state.cart.find((c) => c.id === id);
  if (!item) return;
  item.qty = Math.max(1, item.qty + d);
  saveCart();
  updateCartBadge();
  renderCart();
}
function removeCartItem(id) {
  state.cart = state.cart.filter((c) => c.id !== id);
  saveCart();
  updateCartBadge();
  renderCart();
}
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

// Khi khách hàng xác nhận đơn hàng
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

//XEM ĐƠN HÀNG
function openOrders() {
  closeUserMenu();
  if (!state.user) {
    showToast("Vui lòng đăng nhập!");
    return;
  }

  const userOrders = state.orders.filter(
    (o) => o.userEmail === state.user.email,
  );

  const statusClass = (s) =>
    ({
      "Đang xử lý": "badge-processing",
      "Đang giao": "badge-shipping",
      "Đã giao": "badge-delivered",
    })[s] || "badge-processing";

  const renderTimeline = (order) => {
    const steps = [
      { label: "Đặt hàng thành công", key: "placed" },
      { label: "Đã xác nhận", key: "confirmed" },
      { label: "Đang giao hàng", key: "shipping" },
      { label: "Giao thành công", key: "delivered" },
    ];
    const statusIndex = { "Đang xử lý": 1, "Đang giao": 2, "Đã giao": 3 };
    const cur = statusIndex[order.status] ?? 0;

    return steps
      .map((step, i) => {
        const done = i < cur;
        const active = i === cur && cur < 3;
        const dotCls = done
          ? "tl-dot done"
          : active
            ? "tl-dot active"
            : "tl-dot pending";
        const icon = done ? "✓" : active ? "→" : "○";
        const lineCls = done ? "tl-line done" : "tl-line pending";
        const time = done
          ? i === 0
            ? order.createdAt
            : "Đã hoàn thành"
          : active
            ? "Đang tiến hành..."
            : "Chưa cập nhật";
        return `
        <div class="tl-row">
          <div class="tl-left">
            <div class="${dotCls}">${icon}</div>
            ${i < steps.length - 1 ? `<div class="${lineCls}"></div>` : ""}
          </div>
          <div class="tl-content">
            <div class="tl-title ${!done && !active ? "tl-muted" : ""}">${step.label}</div>
            <div class="tl-time">${time}</div>
          </div>
        </div>`;
      })
      .join("");
  };

  const html = !userOrders.length
    ? `<div class="orders-empty"><div style="font-size:2.5rem;margin-bottom:1rem">📦</div><p>Bạn chưa có đơn hàng nào.</p></div>`
    : userOrders
        .slice()
        .reverse()
        .map((order) => {
          const items = order.items
            .map((item) => {
              const p = PRODUCTS.find((x) => x.id === item.id) || {
                name: "Sản phẩm",
                price: 0,
              };
              return `<div class="oi-row">
            <div class="oi-thumb">◈</div>
            <div class="oi-name">${p.name}</div>
            <div class="oi-qty">x${item.qty}</div>
            <div class="oi-price">${fmtPrice(p.price * item.qty)}</div>
          </div>`;
            })
            .join("");

          return `
          <div class="order-card">
            <div class="order-card-head" onclick="this.parentElement.classList.toggle('open')">
              <div>
                <div class="oc-id">#${order.id}</div>
                <div class="oc-date">${order.createdAt}</div>
              </div>
              <div class="oc-right">
                <div class="oc-total">${fmtPrice(order.total)}</div>
                <span class="status-badge ${statusClass(order.status)}">${order.status}</span>
                <span class="oc-chevron">▼</span>
              </div>
            </div>
            <div class="order-card-body">
              <div class="tl-wrap">${renderTimeline(order)}</div>
              <div class="oi-wrap">
                <div class="oi-label">Sản phẩm</div>
                ${items}
              </div>
              <div class="oc-footer">
                <span style="color:var(--muted);font-size:0.85rem">Tổng thanh toán</span>
                <span style="font-weight:600;font-size:1rem">${fmtPrice(order.total)}</span>
              </div>
            </div>
          </div>`;
        })
        .join("");

  document.getElementById("order-list").innerHTML = html;
  openModal("order-modal");
}

// ══════════════════════════════════════════════════
//  AUTH
// ══════════════════════════════════════════════════
function openAuth(tab) {
  switchAuthTab(tab);
  openModal("auth-modal");
  closeUserMenu();
}
function switchAuthTab(tab) {
  document
    .querySelectorAll(".auth-tab")
    .forEach((t) => t.classList.remove("active"));
  document
    .querySelectorAll(".auth-form")
    .forEach((f) => f.classList.remove("active"));
  document.getElementById("tab-" + tab).classList.add("active");
  document.getElementById("form-" + tab).classList.add("active");
}

function doLogin() {
  const email = document.getElementById("login-email").value.trim();
  const pass = document.getElementById("login-password").value;
  if (!email || !pass) {
    showToast("Vui lòng điền đầy đủ thông tin!");
    return;
  }
  const found = state.users.find(
    (u) => u.email === email && u.password === pass,
  );
  if (!found) {
    showToast("Email hoặc mật khẩu không đúng!");
    return;
  }
  state.user = found;
  localStorage.setItem("lum_user", JSON.stringify(found));
  closeModal("auth-modal");
  updateUserUI();
  showToast(`Chào mừng, ${found.firstname}!`);
}

function doRegister() {
  const fields = [
    "reg-lastname",
    "reg-firstname",
    "reg-username",
    "reg-email",
    "reg-phone",
    "reg-password",
    "reg-address",
    "reg-district",
    "reg-city",
  ];
  const vals = fields.map((f) => document.getElementById(f).value.trim());
  if (vals.some((v) => !v)) {
    showToast("Vui lòng điền đầy đủ thông tin!");
    return;
  }
  const [
    lastname,
    firstname,
    username,
    email,
    phone,
    password,
    address,
    district,
    city,
  ] = vals;
  if (!/^[0-9]{10}$/.test(phone)) {
    showToast("Số điện thoại phải có đúng 10 chữ số!");
    return;
  }
  if (password.length < 6) {
    showToast("Mật khẩu phải có ít nhất 6 ký tự!");
    return;
  }
  if (state.users.find((u) => u.email === email || u.username === username)) {
    showToast("Email hoặc tên đăng nhập đã được đăng ký!");
    return;
  }
  const user = {
    lastname,
    firstname,
    username,
    email,
    phone,
    password,
    address,
    district,
    city,
    role: "customer",
    status: "active",
    createdAt: new Date().toLocaleDateString("vi-VN"),
  };
  state.users.push(user);
  localStorage.setItem("lum_users", JSON.stringify(state.users));
  state.user = user;
  localStorage.setItem("lum_user", JSON.stringify(user));
  closeModal("auth-modal");
  updateUserUI();
  showToast(`Đăng ký thành công! Chào mừng, ${firstname}!`);
}

function logout() {
  state.user = null;
  state.cart = [];
  localStorage.removeItem("lum_user");
  localStorage.removeItem("lum_cart");
  saveCart();
  updateCartBadge();
  updateUserUI();
  closeUserMenu();
  showToast("Đã đăng xuất thành công!");
}

function updateUserUI() {
  const guest = document.getElementById("guest-menu");
  const logged = document.getElementById("user-menu-logged");
  const headerGreeting = document.getElementById("header-greeting");
  const dropdownTitle = document.getElementById("dropdown-title");
  if (state.user) {
    if (guest) guest.style.display = "none";
    if (logged) logged.style.display = "block";
    const greetingText = `Xin chào, ${state.user.firstname}!`;
    if (headerGreeting) headerGreeting.textContent = greetingText;
    if (dropdownTitle) dropdownTitle.textContent = "Tài khoản";
  } else {
    if (guest) guest.style.display = "block";
    if (logged) logged.style.display = "none";
    if (headerGreeting) headerGreeting.textContent = "";
    if (dropdownTitle) dropdownTitle.textContent = "";
  }
}

// ══════════════════════════════════════════════════
//  UI HELPERS
// ══════════════════════════════════════════════════
function openModal(id) {
  document.getElementById(id).classList.add("open");
  document.body.style.overflow = "hidden";
}
function closeModal(id) {
  document.getElementById(id).classList.remove("open");
  document.body.style.overflow = "";
}
function toggleUserMenu() {
  document.getElementById("user-dropdown").classList.toggle("open");
}
function closeUserMenu() {
  document.getElementById("user-dropdown").classList.remove("open");
}
function showToast(msg) {
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), 3000);
}
function fmtPrice(n) {
  return n.toLocaleString("vi-VN") + "₫";
}

// ══════════════════════════════════════════════════
//  INIT
// ══════════════════════════════════════════════════
updateCartBadge();
updateUserUI();
loadProducts();

(function initSections() {
  const c = document.getElementById("contact");
  if (c) c.style.display = "none";
})();

// ── PROFILE ──────────────────────────────────────
function openProfile() {
  if (!state.user) return;
  const u = state.user;

  // avatar initials
  const initials = (
    u.lastname.charAt(0) + (u.firstname.charAt(0) || "")
  ).toUpperCase();
  document.getElementById("prof-avatar").textContent = initials;
  document.getElementById("prof-name").textContent =
    u.lastname + " " + u.firstname;
  document.getElementById("prof-email").textContent = u.email;

  // fill fields
  const map = {
    lastname: "pf-lastname",
    firstname: "pf-firstname",
    email: "pf-email",
    phone: "pf-phone",
    address: "pf-address",
    district: "pf-district",
    city: "pf-city",
  };
  Object.entries(map).forEach(([k, id]) => {
    const el = document.getElementById(id);
    if (el) el.value = u[k] || "";
  });

  switchProfTab("info");
  clearPwFields();
  document.getElementById("pf-info-msg").innerHTML = "";
  closeUserMenu();
  openModal("profile-modal");
}

function switchProfTab(tab) {
  ["info", "pw"].forEach((t) => {
    document.getElementById("ptab-" + t).classList.toggle("active", t === tab);
    const panel = document.getElementById("ppanel-" + t);
    if (panel) panel.style.display = t === tab ? "block" : "none";
  });
}

function saveProfileInfo() {
  if (!state.user) return;
  const ln = document.getElementById("pf-lastname").value.trim();
  const fn = document.getElementById("pf-firstname").value.trim();
  const ph = document.getElementById("pf-phone").value.trim();
  const ad = document.getElementById("pf-address").value.trim();
  const di = document.getElementById("pf-district").value.trim();
  const ci = document.getElementById("pf-city").value;

  if (!ln || !fn || !ph || !ad || !di || !ci) {
    showProfileMsg("pf-info-msg", "Vui lòng điền đầy đủ thông tin!", false);
    return;
  }
  if (!/^[0-9]{10}$/.test(ph)) {
    showProfileMsg(
      "pf-info-msg",
      "Số điện thoại phải có đúng 10 chữ số!",
      false,
    );
    return;
  }

  Object.assign(state.user, {
    lastname: ln,
    firstname: fn,
    phone: ph,
    address: ad,
    district: di,
    city: ci,
  });

  // cập nhật trong mảng users
  const idx = state.users.findIndex((u) => u.email === state.user.email);
  if (idx !== -1) state.users[idx] = { ...state.users[idx], ...state.user };
  localStorage.setItem("lum_user", JSON.stringify(state.user));
  localStorage.setItem("lum_users", JSON.stringify(state.users));

  // refresh avatar & greeting
  document.getElementById("prof-avatar").textContent = (
    state.user.lastname.charAt(0) + state.user.firstname.charAt(0)
  ).toUpperCase();
  document.getElementById("prof-name").textContent =
    state.user.lastname + " " + state.user.firstname;
  updateUserUI();
  showProfileMsg("pf-info-msg", "Cập nhật thông tin thành công!", true);
}

function cancelProfileEdit() {
  if (!state.user) return;
  const map = {
    lastname: "pf-lastname",
    firstname: "pf-firstname",
    phone: "pf-phone",
    address: "pf-address",
    district: "pf-district",
    city: "pf-city",
  };
  Object.entries(map).forEach(([k, id]) => {
    const el = document.getElementById(id);
    if (el) el.value = state.user[k] || "";
  });
  document.getElementById("pf-info-msg").innerHTML = "";
}

function savePassword() {
  if (!state.user) return;
  const oldPw = document.getElementById("pw-old").value;
  const newPw = document.getElementById("pw-new").value;
  const cfmPw = document.getElementById("pw-confirm").value;

  if (!oldPw || !newPw || !cfmPw) {
    showProfileMsg("pf-pw-msg", "Vui lòng điền đầy đủ!", false);
    return;
  }
  if (oldPw !== state.user.password) {
    showProfileMsg("pf-pw-msg", "Mật khẩu hiện tại không đúng!", false);
    return;
  }
  if (newPw.length < 6) {
    showProfileMsg("pf-pw-msg", "Mật khẩu mới phải có ít nhất 6 ký tự!", false);
    return;
  }
  if (newPw !== cfmPw) {
    showProfileMsg("pf-pw-msg", "Mật khẩu xác nhận không khớp!", false);
    return;
  }

  state.user.password = newPw;
  const idx = state.users.findIndex((u) => u.email === state.user.email);
  if (idx !== -1) state.users[idx].password = newPw;
  localStorage.setItem("lum_user", JSON.stringify(state.user));
  localStorage.setItem("lum_users", JSON.stringify(state.users));

  clearPwFields();
  showProfileMsg("pf-pw-msg", "Đổi mật khẩu thành công!", true);
}

function clearPwFields() {
  ["pw-old", "pw-new", "pw-confirm"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.value = "";
      el.type = "password";
    }
  });
  const bar = document.getElementById("pw-strength-bar");
  const hint = document.getElementById("pw-strength-hint");
  if (bar) bar.style.width = "0";
  if (hint) hint.textContent = "";
  const msg = document.getElementById("pf-pw-msg");
  if (msg) msg.innerHTML = "";
}

function togglePwField(id, btn) {
  const inp = document.getElementById(id);
  if (!inp) return;
  inp.type = inp.type === "password" ? "text" : "password";
  btn.style.opacity = inp.type === "text" ? "0.5" : "1";
}

function updatePwStrength(val) {
  const bar = document.getElementById("pw-strength-bar");
  const hint = document.getElementById("pw-strength-hint");
  if (!bar || !hint) return;
  if (!val) {
    bar.style.width = "0";
    hint.textContent = "";
    return;
  }
  let score = 0;
  if (val.length >= 6) score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    ["0%", "#E24B4A", ""],
    ["30%", "#E24B4A", "Yếu"],
    ["50%", "#EF9F27", "Trung bình"],
    ["75%", "#EF9F27", "Khá"],
    ["90%", "#639922", "Mạnh"],
    ["100%", "#639922", "Rất mạnh"],
  ];
  const [w, c, t] = levels[score] || levels[0];
  bar.style.width = w;
  bar.style.background = c;
  hint.textContent = t;
}

function showProfileMsg(id, text, ok) {
  const el = document.getElementById(id);
  if (!el) return;
  el.innerHTML = `<p style="font-size:0.82rem;padding:8px 12px;border-radius:.75rem;margin-top:.5rem;
    background:${ok ? "rgba(34,197,94,0.1)" : "rgba(239,68,68,0.1)"};
    color:${ok ? "#4ade80" : "#f87171"}">${text}</p>`;
  setTimeout(() => {
    if (el) el.innerHTML = "";
  }, 3500);
}
