// ══════════════════════════════════════════════════
//  PRODUCTS MANAGEMENT
// ══════════════════════════════════════════════════

const API_BASE = "../backend/api";
let PRODUCTS = [];
let CATEGORIES = [];

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function buildCategoryUrl(category) {
  const params = new URLSearchParams(window.location.search || "");
  params.set("page", "1");
  if (category && String(category).trim() !== "") {
    params.set("category", category);
  } else {
    params.delete("category");
  }
  const query = params.toString();
  return query ? `?${query}#products` : "?page=1#products";
}

function renderCategoryControls() {
  const tabs = document.getElementById("category-tabs");
  const select = document.getElementById("filter-category");
  if (!tabs && !select) return;

  const selectedCategory = (
    select?.value ||
    state.currentCategory ||
    ""
  ).trim();

  if (tabs) {
    const tabItems = [
      `<a href="${buildCategoryUrl("")}" class="tab-btn ${selectedCategory === "" ? "active" : ""}">Tất cả</a>`,
      ...CATEGORIES.map(
        (catName) =>
          `<a href="${buildCategoryUrl(catName)}" class="tab-btn ${selectedCategory === catName ? "active" : ""}">${escapeHtml(catName)}</a>`,
      ),
    ];
    tabs.innerHTML = tabItems.join("");
  }

  if (select) {
    const options = [
      '<option value="">Tất cả phân loại</option>',
      ...CATEGORIES.map(
        (catName) =>
          `<option value="${escapeHtml(catName)}" ${selectedCategory === catName ? "selected" : ""}>${escapeHtml(catName)}</option>`,
      ),
    ];
    select.innerHTML = options.join("");
    select.value = selectedCategory;
  }
}

async function loadCategories() {
  try {
    const response = await fetch(`${API_BASE}/categories.php`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const payload = await response.json();
    CATEGORIES = Array.isArray(payload?.categories) ? payload.categories : [];
    renderCategoryControls();
  } catch (error) {
    console.error("Failed to load categories:", error);
  }
}

// Tải danh sách sản phẩm từ API
async function loadProducts(shouldRender = true) {
  try {
    const response = await fetch(`${API_BASE}/products.php`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const payload = await response.json();
    if (Array.isArray(payload)) {
      PRODUCTS = payload;
    } else if (Array.isArray(payload?.products)) {
      PRODUCTS = payload.products;
    } else {
      PRODUCTS = [];
      throw new Error(payload?.error || "Invalid products payload");
    }
    if (shouldRender) renderProducts();
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
        category: "Nữ",
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
        notes: "Fruity Chypré",
        concentration: "Eau de Parfum",
        size: "100ml",
        brand: "Creed",
        badge: "Luxury",
        image: "images/hinh6.jpg",
      },
      {
        id: 6,
        name: "Jo Malone Peony",
        category: "Nữ",
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
        category: "Nam",
        price: 8900000,
        desc: '"By the Fireplace" – hương thơm gợi nhớ những đêm bên lò sưởi ấm áp với gỗ và vani dịu dàng.',
        notes: "Woody Floral Musk",
        concentration: "Eau de Toilette",
        size: "100ml",
        brand: "Maison Margiela",
        badge: "Giới hạn",
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
        category: "Nam",
        price: 9800000,
        desc: "Lấy cảm hứng từ nghệ thuật ủ rượu cognac, hương thơm kết hợp cinnamon, nutmeg và caramel.",
        notes: "Oriental Woody",
        concentration: "Eau de Parfum",
        size: "50ml",
        brand: "Kilian",
        badge: "Giới hạn",
        image: "images/hinh13.jpg",
      },
      {
        id: 13,
        name: "Million Elixir",
        category: "Giới hạn",
        price: 9800000,
        desc: "Một sáng tạo giới hạn với sự hòa quyện của oud, hoa hồng đen và amber, mang đến chiều sâu bí ẩn.",
        notes: "Amber Oud",
        concentration: "Extrait de Parfum",
        size: "50ml",
        brand: "Million",
        badge: "Giới hạn",
        image: "images/hinh14.jpg",
      },
      {
        id: 14,
        name: "Attrape-Rêves",
        category: "Giới hạn",
        price: 13350000,
        desc: "Một hương thơm quyến rũ với vải thiều chín mọng, hoa mẫu đơn và cacao nhẹ.",
        notes: "Floral Fruity Gourmand",
        concentration: "Eau de Parfum",
        size: "100ml",
        brand: "Attrape",
        badge: "Giới hạn",
        image: "images/hinh15.jpg",
      },
    ];
    if (shouldRender) renderProducts();
  }
}

// Lọc danh sách sản phẩm theo bộ lọc hiện tại
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

// Hiển thị danh sách sản phẩm
function renderProducts() {
  const filtered = getFilteredProducts();
  const total = filtered.length;
  const totalPages = Math.max(1, Math.ceil(total / state.perPage));
  if (state.page > totalPages) state.page = 1;
  const start = (state.page - 1) * state.perPage;
  const pageItems = filtered.slice(start, start + state.perPage);

  const grid = document.getElementById("product-grid");
  const resultCountEl = document.getElementById("result-count");
  if (resultCountEl) {
    resultCountEl.textContent = `${total} sản phẩm`;
  }

  const pageInfoEl = document.getElementById("page-info");
  if (pageInfoEl) {
    pageInfoEl.textContent =
      total > 0
        ? `Hiển thị ${start + 1}–${Math.min(start + state.perPage, total)} / ${total} sản phẩm`
        : "";
  }

  if (!grid) {
    return;
  }

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

// Hiển thị phân trang
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

// Đi tới trang thứ p
function goPage(p) {
  state.page = p;
  renderProducts();
  document
    .getElementById("products")
    .scrollIntoView({ behavior: "smooth", block: "start" });
}

// Lọc theo danh loại
function filterCategory(cat, el) {
  state.currentCategory = cat;
  state.filterCategory = "";
  state.page = 1;
  state.isSearching = false;
  closeSuggestions();
  const categorySelect = document.getElementById("filter-category");
  if (categorySelect) {
    categorySelect.value = cat;
  }
  document
    .querySelectorAll(".tab-btn")
    .forEach((b) => b.classList.remove("active"));
  if (el) {
    el.classList.add("active");
  }
  const sectionEyebrow = document.getElementById("section-eyebrow");
  if (sectionEyebrow) {
    sectionEyebrow.textContent = cat || "All Products";
  }
  const sectionTitle = document.getElementById("section-title");
  if (sectionTitle) {
    sectionTitle.textContent = cat ? `Nước hoa ${cat}` : "Tất cả sản phẩm";
  }
  renderProducts();
}

// Mở chi tiết sản phẩm
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

// Thay đổi số lượng sản phẩm trong detail modal
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

document.addEventListener("DOMContentLoaded", () => {
  loadCategories();
});
