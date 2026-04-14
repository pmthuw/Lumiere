// ══════════════════════════════════════════════════
//  LUMIERE ADMIN — admin.js  (Full Featured)
// ══════════════════════════════════════════════════

const dateEl = document.getElementById("topbar-date");
if (dateEl)
  dateEl.textContent = new Date().toLocaleDateString("vi-VN", {
    weekday: "long",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });

const ADMIN_API = "admin-api.php";

// ══════════════════════════════════════════════════
//  DATA HELPERS
// ══════════════════════════════════════════════════
function load(key, def) {
  return def;
}
function save(key, val) {
  return;
}

const DEFAULT_PRODUCTS = [
  {
    id: 1,
    code: "SP001",
    name: "Chanel No.5",
    category: "Nữ",
    price: 7200000,
    profitRate: 30,
    desc: "Hương thơm hoa cỏ cổ điển, biểu tượng thanh lịch.",
    notes: "Hoa cỏ aldehyde",
    concentration: "Eau de Parfum",
    size: "100ml",
    brand: "Chanel",
    badge: "Bestseller",
    image: "../frontend/images/hinh1.jpg",
    status: "active",
    stock: 0,
    supplier: "Chanel SA",
  },
  {
    id: 2,
    code: "SP002",
    name: "Dior Sauvage",
    category: "Nam",
    price: 8800000,
    profitRate: 25,
    desc: "Hương thơm nam tính, bergamot Calabrian và ambroxan.",
    notes: "Fougère Woody",
    concentration: "Eau de Toilette",
    size: "100ml",
    brand: "Dior",
    badge: "Hot",
    image: "../frontend/images/hinh3.jpg",
    status: "active",
    stock: 0,
    supplier: "Dior Paris",
  },
  {
    id: 3,
    code: "SP003",
    name: "Tom Ford Black Orchid",
    category: "Nữ",
    price: 6600000,
    profitRate: 28,
    desc: "Hương phương Đông bí ẩn với truffle đen và orchid.",
    notes: "Oriental Floral",
    concentration: "Eau de Parfum",
    size: "50ml",
    brand: "Tom Ford",
    badge: "",
    image: "../frontend/images/hinh4.jpg",
    status: "active",
    stock: 0,
    supplier: "Tom Ford Beauty",
  },
  {
    id: 4,
    code: "SP004",
    name: "YSL Black Opium",
    category: "Nữ",
    price: 8400000,
    profitRate: 27,
    desc: "Hương cà phê quyến rũ, hoa nhài trắng và vani.",
    notes: "Oriental Floral",
    concentration: "Eau de Parfum",
    size: "90ml",
    brand: "YSL",
    badge: "New",
    image: "../frontend/images/hinh5.jpg",
    status: "active",
    stock: 0,
    supplier: "YSL Beauté",
  },
  {
    id: 5,
    code: "SP005",
    name: "Creed Aventus",
    category: "Nam",
    price: 14500000,
    profitRate: 35,
    desc: "Hương huyền thoại từ quả cây và gỗ.",
    notes: "Fruity Chypre",
    concentration: "Eau de Parfum",
    size: "100ml",
    brand: "Creed",
    badge: "Luxury",
    image: "../frontend/images/hinh6.jpg",
    status: "active",
    stock: 0,
    supplier: "House of Creed",
  },
  {
    id: 6,
    code: "SP006",
    name: "Jo Malone Peony",
    category: "Nữ",
    price: 8200000,
    profitRate: 25,
    desc: "Hương mẫu đơn nhẹ nhàng kết hợp hồng đào và hổ phách.",
    notes: "Floral Fruity",
    concentration: "Cologne",
    size: "100ml",
    brand: "Jo Malone",
    badge: "",
    image: "../frontend/images/hinh7.jpg",
    status: "active",
    stock: 0,
    supplier: "Jo Malone London",
  },
  {
    id: 7,
    code: "SP007",
    name: "Versace Eros",
    category: "Nam",
    price: 10800000,
    profitRate: 26,
    desc: "Bạc hà Ý, táo xanh và hoa hồng táo bạo.",
    notes: "Oriental Fougère",
    concentration: "Eau de Toilette",
    size: "100ml",
    brand: "Versace",
    badge: "",
    image: "../frontend/images/hinh8.jpg",
    status: "active",
    stock: 0,
    supplier: "Versace S.r.l.",
  },
  {
    id: 8,
    code: "SP008",
    name: "Gucci Bloom",
    category: "Nữ",
    price: 9800000,
    profitRate: 28,
    desc: "Tuberose, jasmine và rangoon creeper thuần khiết.",
    notes: "Floral",
    concentration: "Eau de Parfum",
    size: "100ml",
    brand: "Gucci",
    badge: "",
    image: "../frontend/images/hinh9.jpg",
    status: "active",
    stock: 0,
    supplier: "Gucci SpA",
  },
  {
    id: 9,
    code: "SP009",
    name: "Maison Margiela Replica",
    category: "Nam",
    price: 8900000,
    profitRate: 30,
    desc: "By the Fireplace – hương gợi nhớ đêm bên lò sưởi.",
    notes: "Woody Floral Musk",
    concentration: "Eau de Toilette",
    size: "100ml",
    brand: "Maison Margiela",
    badge: "Giới hạn",
    image: "../frontend/images/hinh10.jpg",
    status: "active",
    stock: 0,
    supplier: "OTB Group",
  },
  {
    id: 10,
    code: "SP010",
    name: "Hermès Terre",
    category: "Nam",
    price: 9200000,
    profitRate: 28,
    desc: "Bưởi, hạt tiêu và gỗ tuyết tùng lịch lãm.",
    notes: "Woody Citrus",
    concentration: "Eau de Toilette",
    size: "75ml",
    brand: "Hermès",
    badge: "",
    image: "../frontend/images/hinh11.jpg",
    status: "active",
    stock: 0,
    supplier: "Hermès Paris",
  },
  {
    id: 11,
    code: "SP011",
    name: "Lancôme La Vie Est Belle",
    category: "Nữ",
    price: 11100000,
    profitRate: 27,
    desc: "Iris, praline và vanilla ngọt ngào.",
    notes: "Oriental Floral",
    concentration: "Eau de Parfum",
    size: "75ml",
    brand: "Lancôme",
    badge: "",
    image: "../frontend/images/hinh12.jpg",
    status: "active",
    stock: 0,
    supplier: "L'Oréal",
  },
  {
    id: 12,
    code: "SP012",
    name: "Kilian Angel Share",
    category: "Nam",
    price: 9800000,
    profitRate: 32,
    desc: "Cảm hứng cognac với cinnamon, nutmeg và caramel.",
    notes: "Oriental Woody",
    concentration: "Eau de Parfum",
    size: "50ml",
    brand: "Kilian",
    badge: "Giới hạn",
    image: "../frontend/images/hinh13.jpg",
    status: "active",
    stock: 0,
    supplier: "By Kilian",
  },
  {
    id: 13,
    code: "SP013",
    name: "Million Elixir",
    category: "Giới hạn",
    price: 9800000,
    profitRate: 30,
    desc: "Oud, hoa hồng đen và amber bí ẩn.",
    notes: "Amber Oud",
    concentration: "Extrait de Parfum",
    size: "50ml",
    brand: "Milion",
    badge: "Giới hạn",
    image: "../frontend/images/hinh14.jpg",
    status: "active",
    stock: 0,
    supplier: "Milion Inc.",
  },
  {
    id: 14,
    code: "SP014",
    name: "Attrape-Rêves",
    category: "Giới hạn",
    price: 13350000,
    profitRate: 35,
    desc: "Vải thiều, mẫu đơn và cacao mơ màng nữ tính.",
    notes: "Floral Fruity Gourmand",
    concentration: "Eau de Parfum",
    size: "100ml",
    brand: "Attrape",
    badge: "Giới hạn",
    image: "../frontend/images/hinh15.jpg",
    status: "active",
    stock: 0,
    supplier: "Attrape Parfums",
  },
];

const DEFAULT_ADMIN_USERS = [
  {
    id: 1,
    username: "admin",
    password: "12345",
    fullname: "Quản trị viên",
    email: "admin@lumiere.vn",
    role: "admin",
    status: "active",
    createdAt: "01/01/2026",
  },
  {
    id: 2,
    username: "staff01",
    password: "staff123",
    fullname: "Người dùng 1",
    email: "staff1@lumiere.vn",
    role: "staff",
    status: "active",
    createdAt: "15/01/2026",
  },
];

let PRODUCTS = [];
let ADMIN_USERS = [];
let CUSTOMERS = [];
let CATEGORIES = [];
let RECEIPTS = [];
let ORDERS = [];
let LOW_STOCK = 5;
let editingUserType = null;

async function saveAll() {
  try {
    await fetch(ADMIN_API, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "saveState",
        data: {
          products: PRODUCTS,
          admin_users: ADMIN_USERS,
          users: CUSTOMERS,
          categories: CATEGORIES,
          receipts: RECEIPTS,
          orders: ORDERS,
          low_stock: LOW_STOCK,
        },
      }),
    });
  } catch (error) {
    console.error("Unable to save admin data:", error);
  }
}

async function fetchAdminData() {
  try {
    const response = await fetch(`${ADMIN_API}?action=loadData`, {
      credentials: "same-origin",
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return await response.json();
  } catch (error) {
    console.error("Failed to fetch admin data:", error);
    return null;
  }
}

async function initializeAdminApp() {
  if (window.ADMIN_DASHBOARD_SERVER_RENDERED) {
    if (window.ADMIN_SESSION) {
      const avatar = document.querySelector(".topbar-avatar");
      const nameLabel = document.querySelector(".topbar-info span:last-child");
      if (avatar)
        avatar.textContent = window.ADMIN_SESSION.fullname
          ? window.ADMIN_SESSION.fullname.charAt(0).toUpperCase()
          : "A";
      if (nameLabel)
        nameLabel.textContent =
          window.ADMIN_SESSION.fullname ||
          window.ADMIN_SESSION.username ||
          "Quản lý";
    }
    return;
  }

  const data = await fetchAdminData();
  PRODUCTS = data?.products?.length ? data.products : DEFAULT_PRODUCTS;
  ADMIN_USERS = data?.admin_users?.length
    ? data.admin_users
    : DEFAULT_ADMIN_USERS;
  CUSTOMERS = data?.users?.length ? data.users : [];
  CATEGORIES = data?.categories?.length
    ? data.categories
    : ["Nữ", "Nam", "Giới hạn"];
  RECEIPTS = data?.receipts?.length ? data.receipts : [];
  ORDERS = data?.orders?.length ? data.orders : [];
  LOW_STOCK = data?.low_stock ?? 5;

  if (window.ADMIN_SESSION) {
    const avatar = document.querySelector(".topbar-avatar");
    const nameLabel = document.querySelector(".topbar-info span:last-child");
    if (avatar)
      avatar.textContent = window.ADMIN_SESSION.fullname
        ? window.ADMIN_SESSION.fullname.charAt(0).toUpperCase()
        : "A";
    if (nameLabel)
      nameLabel.textContent =
        window.ADMIN_SESSION.fullname ||
        window.ADMIN_SESSION.username ||
        "Quản lý";
  }

  recomputeStock();
  renderDashboard();
}

function normalizeCustomer(user) {
  return {
    id: user.id || Date.now(),
    fullname:
      user.fullname ||
      `${user.lastname || ""} ${user.firstname || ""}`.trim() ||
      "Khách hàng",
    username:
      user.username || (user.email ? user.email.split("@")[0] : "") || "—",
    email: user.email || "—",
    password: user.password || "—",
    role: user.role === "admin" ? "admin" : "customer",
    status: user.status || "active",
    createdAt: user.createdAt || "—",
  };
}

function getCustomers() {
  return CUSTOMERS.map(normalizeCustomer);
}

function recomputeStock() {
  PRODUCTS.forEach((p) => {
    const imp = RECEIPTS.filter((r) => r.status === "done")
      .flatMap((r) => r.items)
      .filter((i) => i.productId === p.id)
      .reduce((s, i) => s + i.qty, 0);
    const out = ORDERS.reduce((s, o) => {
      if (o.status !== "Đã giao") return s;
      if (o.productId === p.id) return s + (o.qty || 1);
      if (Array.isArray(o.items)) {
        return (
          s +
          o.items.reduce(
            (sum, item) => sum + (item.id === p.id ? item.qty || 1 : 0),
            0,
          )
        );
      }
      return s;
    }, 0);
    p.stock = Math.max(0, imp - out);
  });
  save("lum_products", PRODUCTS);
}

function generateSampleOrders() {
  const statuses = ["Chưa xử lý", "Đã xác nhận", "Đã giao", "Đã hủy"];
  const names = [
    "Nguyễn Văn An",
    "Trần Thị Bình",
    "Lê Minh Cường",
    "Phạm Thu Hà",
    "Hoàng Đức Việt",
    "Võ Thị Lan",
    "Đặng Quốc Huy",
    "Bùi Ngọc Mai",
  ];
  const wards = [
    "P. Bến Nghé",
    "P. Bến Thành",
    "P. Cầu Ông Lãnh",
    "P. Cô Giang",
    "P. Nguyễn Thái Bình",
    "P. Phạm Ngũ Lão",
  ];
  const orders = [];
  for (let i = 1; i <= 28; i++) {
    const prod = PRODUCTS[Math.floor(Math.random() * PRODUCTS.length)];
    const qty = Math.floor(Math.random() * 3) + 1;
    const d = new Date(
      2026,
      Math.floor(Math.random() * 3),
      Math.floor(Math.random() * 28) + 1,
    );
    orders.push({
      id: `LM${String(1000 + i).padStart(4, "0")}`,
      customer: names[Math.floor(Math.random() * names.length)],
      product: prod.name,
      productId: prod.id,
      qty,
      total: prod.price * qty,
      date: d.toLocaleDateString("vi-VN"),
      dateRaw: d.getTime(),
      ward: wards[Math.floor(Math.random() * wards.length)],
      address: `${Math.floor(Math.random() * 200) + 1} Lê Lợi, ${wards[Math.floor(Math.random() * wards.length)]}, Q.1`,
      status: statuses[Math.floor(Math.random() * statuses.length)],
      phone: `09${Math.floor(Math.random() * 90000000 + 10000000)}`,
    });
  }
  return orders;
}

// ══════════════════════════════════════════════════
//  NAVIGATION
// ══════════════════════════════════════════════════
function showPage(name, btn) {
  document
    .querySelectorAll(".admin-page")
    .forEach((p) => p.classList.remove("active"));
  document
    .querySelectorAll(".sidebar-link")
    .forEach((b) => b.classList.remove("active"));
  document.getElementById("page-" + name).classList.add("active");
  if (btn) btn.classList.add("active");
  const map = {
    dashboard: renderDashboard,
    products: () => {
      filteredProds = [...PRODUCTS];
      prodPage = 1;
      renderProductTable();
    },
    orders: () => {
      filteredOrds = [...ORDERS];
      ordPage = 1;
      renderOrderTable();
    },
    users: renderUserTable,
    receipts: renderReceiptTable,
    pricing: renderPricingTable,
    inventory: renderInventory,
    report: renderInventoryReport,
    categories: renderCategoryTable,
  };
  if (map[name]) map[name]();
}

// ══════════════════════════════════════════════════
//  DASHBOARD
// ══════════════════════════════════════════════════
function renderDashboard() {
  if (!document.getElementById("dash-stats")) {
    return;
  }

  recomputeStock();
  const done = ORDERS.filter((o) => o.status === "Đã giao");
  const revenue = done.reduce((s, o) => s + o.total, 0);
  const pending = ORDERS.filter((o) => o.status === "Chưa xử lý").length;
  const lowCnt = PRODUCTS.filter(
    (p) => p.stock <= LOW_STOCK && p.status === "active",
  ).length;

  document.getElementById("dash-stats").innerHTML = [
    {
      label: "Doanh thu",
      value: fmtPrice(revenue),
      sub: "Đơn đã giao",
      icon: "◈",
    },
    {
      label: "Đơn hàng",
      value: ORDERS.length,
      sub: `${pending} chưa xử lý`,
      icon: "◫",
    },
    { label: "Sản phẩm", value: PRODUCTS.length, sub: "Trong kho", icon: "◻" },
    {
      label: "Sắp hết hàng",
      value: lowCnt,
      sub: `Ngưỡng ≤ ${LOW_STOCK}`,
      icon: "⚠",
    },
  ]
    .map(
      (s) =>
        `<div class="stat-card"><div class="stat-card-icon">${s.icon}</div><div class="stat-card-label">${s.label}</div><div class="stat-card-value">${s.value}</div><div class="stat-card-sub">${s.sub}</div></div>`,
    )
    .join("");

  const months = [
      "T1",
      "T2",
      "T3",
      "T4",
      "T5",
      "T6",
      "T7",
      "T8",
      "T9",
      "T10",
      "T11",
      "T12",
    ],
    vals = [4.2, 5.8, 6.1, 7.9, 6.5, 8.3, 7.1, 9.2, 8.8, 11.1, 12.4, 10.6],
    mx = Math.max(...vals);
  document.getElementById("revenue-chart").innerHTML = vals
    .map(
      (v, i) =>
        `<div class="bar-item"><div class="bar" style="height:${(v / mx) * 100}%" title="${v} tỷ VNĐ"></div><span class="bar-label">${months[i]}</span></div>`,
    )
    .join("");

  document.getElementById("dash-recent-orders").innerHTML = [...ORDERS]
    .sort((a, b) => (b.dateRaw || 0) - (a.dateRaw || 0))
    .slice(0, 6)
    .map(
      (o) =>
        `<tr><td class="td-gold">${o.id}</td><td class="td-name">${o.customer}</td><td class="td-muted" style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${o.product}</td><td class="td-gold">${fmtPrice(o.total)}</td><td>${orderBadge(o.status)}</td></tr>`,
    )
    .join("");

  const sold = {};
  ORDERS.forEach((o) => {
    sold[o.product] = (sold[o.product] || 0) + (o.qty || 1);
  });
  document.getElementById("dash-top-products").innerHTML = Object.entries(sold)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 6)
    .map(
      ([n, q]) =>
        `<tr><td class="td-name" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${n}</td><td><span class="badge badge-gold">${q}</span></td></tr>`,
    )
    .join("");

  const lowItems = PRODUCTS.filter(
    (p) => p.stock <= LOW_STOCK && p.status === "active",
  );
  const alertEl = document.getElementById("dash-low-stock");
  if (alertEl)
    alertEl.innerHTML =
      lowItems.length === 0
        ? '<p style="color:var(--muted);padding:1rem;font-size:0.85rem">Không có sản phẩm sắp hết.</p>'
        : lowItems
            .map(
              (p) =>
                `<div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;border-bottom:1px solid var(--border)"><span class="td-name">${p.name}</span><span class="badge badge-danger">⚠ Còn ${p.stock}</span></div>`,
            )
            .join("");
}
function refreshDashboard() {
  renderDashboard();
  showToast("✓ Đã làm mới!", "success");
}

// ══════════════════════════════════════════════════
//  USER MANAGEMENT
// ══════════════════════════════════════════════════
function renderUserTable() {
  const users = [...ADMIN_USERS, ...getCustomers()];
  document.getElementById("user-table-body").innerHTML = users
    .map((u) => {
      const isAdmin = u.role === "admin";
      const roleLabel = isAdmin ? "Quản lý" : "Khách hàng";
      const roleBadge = isAdmin ? "badge-gold" : "badge-muted";
      const statusLabel = u.status === "active" ? "Hoạt động" : "Đã khóa";
      const statusBadge =
        u.status === "active" ? "badge-success" : "badge-danger";
      const lockControl =
        u.id === 1
          ? '<span class="td-muted" style="font-size:0.75rem">—</span>'
          : `<button class="btn btn-sm ${u.status === "active" ? "btn-danger" : "btn-ghost"}" onclick="toggleUserStatus(${u.id})">${u.status === "active" ? "🔒" : "🔓"}</button>`;
      return `
      <tr>
        <td class="td-name">${u.fullname || "—"}</td>
        <td class="td-muted">${u.username || "—"}</td>
        <td class="td-muted">${u.email || "—"}</td>
        <td class="td-muted">${u.password || "—"}</td>
        <td><span class="badge ${roleBadge}">${roleLabel}</span></td>
        <td><span class="badge ${statusBadge}">${statusLabel}</span></td>
        <td class="td-muted">${u.createdAt || "—"}</td>
        <td>
          <div style="display:flex;gap:0.5rem">
            <button class="btn btn-ghost btn-sm btn-icon" onclick="openUserModal(${u.id})">✏</button>
            ${lockControl}
          </div>
        </td>
      </tr>`;
    })
    .join("");
}

function openUserModal(id) {
  let u = null;
  editingUserType = null;
  if (id) {
    const numericId = parseInt(id, 10);
    u =
      ADMIN_USERS.find((x) => x.id === numericId) ||
      CUSTOMERS.find((x) => x.id === numericId);
    editingUserType = ADMIN_USERS.some((x) => x.id === numericId)
      ? "admin"
      : CUSTOMERS.some((x) => x.id === numericId)
        ? "customer"
        : null;
  }
  document.getElementById("user-modal-title").textContent = u
    ? "Chỉnh sửa khách hàng"
    : "Thêm khách hàng";
  document.getElementById("edit-user-id").value = u ? u.id : "";
  document.getElementById("user-fullname").value = u ? u.fullname : "";
  document.getElementById("user-username").value = u ? u.username : "";
  document.getElementById("user-email").value = u ? u.email : "";
  document.getElementById("user-role").value = u
    ? u.role === "admin"
      ? "admin"
      : "customer"
    : "customer";
  document.getElementById("user-password").value = "";
  document.getElementById("user-pass-hint").style.display = u
    ? "block"
    : "none";
  openModal("user-modal");
}

function saveUser() {
  const id = parseInt(document.getElementById("edit-user-id").value, 10);
  const fullname = document.getElementById("user-fullname").value.trim();
  const username = document.getElementById("user-username").value.trim();
  const email = document.getElementById("user-email").value.trim();
  const role = document.getElementById("user-role").value;
  const password = document.getElementById("user-password").value;
  if (!fullname || !username || !email) {
    showToast("⚠ Điền đầy đủ thông tin!", "warn");
    return;
  }
  const exists = [...ADMIN_USERS, ...CUSTOMERS].find(
    (u) => u.username === username && u.id !== id,
  );
  if (exists) {
    showToast("⚠ Tên đăng nhập đã tồn tại!", "warn");
    return;
  }
  if (id) {
    let u = ADMIN_USERS.find((x) => x.id === id);
    if (!u) u = CUSTOMERS.find((x) => x.id === id);
    if (!u) {
      showToast("⚠ Người dùng không tồn tại!", "warn");
      return;
    }
    Object.assign(u, { fullname, username, email, role });
    if (password) u.password = password;
    showToast("✓ Đã cập nhật!", "success");
  } else {
    if (!password) {
      showToast("⚠ Nhập mật khẩu!", "warn");
      return;
    }
    const newUser = {
      id: Date.now(),
      fullname,
      username,
      email,
      role,
      password,
      status: "active",
      createdAt: new Date().toLocaleDateString("vi-VN"),
    };
    if (role === "admin") {
      ADMIN_USERS.push(newUser);
    } else {
      CUSTOMERS.push(newUser);
    }
    showToast("✓ Đã thêm tài khoản!", "success");
  }
  saveAll();
  closeModal("user-modal");
  renderUserTable();
}

function toggleUserStatus(id) {
  const numericId = parseInt(id, 10);
  const u =
    ADMIN_USERS.find((x) => x.id === numericId) ||
    CUSTOMERS.find((x) => x.id === numericId);
  if (!u || u.id === 1) return;
  u.status = u.status === "active" ? "locked" : "active";
  saveAll();
  renderUserTable();
  showToast(
    `${u.status === "active" ? "🔓 Mở khóa" : "🔒 Khóa"} ${u.username}!`,
    u.status === "active" ? "success" : "warn",
  );
}
function deleteUser(id) {
  const numericId = parseInt(id, 10);
  if (numericId === 1) return;
  showConfirm("Xóa tài khoản này?", () => {
    ADMIN_USERS = ADMIN_USERS.filter((u) => u.id !== numericId);
    CUSTOMERS = CUSTOMERS.filter((u) => u.id !== numericId);
    saveAll();
    renderUserTable();
    showToast("✓ Đã xóa!", "success");
  });
}

// ══════════════════════════════════════════════════
//  CATEGORIES
// ══════════════════════════════════════════════════
function renderCategoryTable() {
  document.getElementById("cat-table-body").innerHTML = CATEGORIES.map(
    (c, i) => `
    <tr>
      <td class="td-name">${c}</td>
      <td><span class="badge badge-muted">${PRODUCTS.filter((p) => p.category === c).length} SP</span></td>
      <td><div style="display:flex;gap:0.5rem">
        <button class="btn btn-ghost btn-sm" onclick="editCategory(${i})">Đổi tên</button>
        <button class="btn btn-danger btn-sm btn-icon" onclick="deleteCategory(${i})">🗑</button>
      </div></td>
    </tr>`,
  ).join("");
}
function addCategory() {
  const val = document.getElementById("new-cat-input").value.trim();
  if (!val) {
    showToast("⚠ Nhập tên loại!", "warn");
    return;
  }
  if (CATEGORIES.includes(val)) {
    showToast("⚠ Đã tồn tại!", "warn");
    return;
  }
  CATEGORIES.push(val);
  saveAll();
  renderCategoryTable();
  document.getElementById("new-cat-input").value = "";
  showToast("✓ Đã thêm loại SP!", "success");
}
function editCategory(i) {
  const name = prompt("Đổi tên loại:", CATEGORIES[i]);
  if (!name || !name.trim()) return;
  const old = CATEGORIES[i];
  CATEGORIES[i] = name.trim();
  PRODUCTS.forEach((p) => {
    if (p.category === old) p.category = name.trim();
  });
  saveAll();
  renderCategoryTable();
  showToast("✓ Đã đổi tên!", "success");
}
function deleteCategory(i) {
  if (PRODUCTS.some((p) => p.category === CATEGORIES[i])) {
    showToast("⚠ Không thể xóa loại đang có SP!", "warn");
    return;
  }
  showConfirm(`Xóa loại "${CATEGORIES[i]}"?`, () => {
    CATEGORIES.splice(i, 1);
    saveAll();
    renderCategoryTable();
    showToast("✓ Đã xóa!", "success");
  });
}

// ══════════════════════════════════════════════════
//  PRODUCTS
// ══════════════════════════════════════════════════
let prodPage = 1,
  filteredProds = [...PRODUCTS];
const prodPerPage = 8;

function filterProducts() {
  const q = (document.getElementById("prod-search").value || "").toLowerCase();
  const cat = document.getElementById("prod-cat-filter").value;
  const sta = document.getElementById("prod-status-filter").value;
  filteredProds = PRODUCTS.filter(
    (p) =>
      (!q ||
        p.name.toLowerCase().includes(q) ||
        p.code.toLowerCase().includes(q) ||
        p.brand.toLowerCase().includes(q)) &&
      (!cat || p.category === cat) &&
      (!sta || p.status === sta),
  );
  prodPage = 1;
  renderProductTable();
}

function renderProductTable() {
  recomputeStock();
  const total = filteredProds.length,
    pages = Math.max(1, Math.ceil(total / prodPerPage));
  if (prodPage > pages) prodPage = 1;
  const start = (prodPage - 1) * prodPerPage,
    items = filteredProds.slice(start, start + prodPerPage);
  document.getElementById("prod-count").textContent = `(${total} SP)`;
  document.getElementById("prod-page-info").textContent =
    total > 0
      ? `${start + 1}–${Math.min(start + prodPerPage, total)} / ${total}`
      : "";
  document.getElementById("product-table-body").innerHTML =
    items.length === 0
      ? `<tr><td colspan="9"><div class="empty-state"><div class="empty-icon">◻</div><div class="empty-title">Không tìm thấy sản phẩm</div></div></td></tr>`
      : items
          .map(
            (p) => `<tr class="${p.status === "hidden" ? "row-hidden" : ""}">
      <td class="td-muted">${p.code}</td>
      <td><div class="product-thumb-cell"><div class="product-thumb">${p.image ? `<img src="${p.image}" alt="" onerror="this.parentElement.textContent='◈'">` : "◈"}</div><div><div class="td-name">${p.name}</div><div class="td-muted">${p.size} · ${p.brand}</div></div></div></td>
      <td><span class="badge badge-muted">${p.category}</span></td>
      <td class="td-muted">${p.supplier || "—"}</td>
      <td class="td-gold">${fmtPrice(p.price)}</td>
      <td><span class="badge ${p.stock <= LOW_STOCK ? "badge-danger" : p.stock <= 15 ? "badge-warning" : "badge-success"}">${p.stock}</span></td>
      <td>${p.profitRate || 0}%</td>
      <td><span class="badge ${p.status === "active" ? "badge-success" : "badge-danger"}">${p.status === "active" ? "Đang bán" : "Ẩn"}</span></td>
      <td><div style="display:flex;gap:0.4rem">
        <button class="btn btn-ghost btn-sm btn-icon" onclick="openProductModal(${p.id})" title="Sửa">✏</button>
        <button class="btn btn-sm ${p.status === "active" ? "btn-danger" : "btn-ghost"} btn-icon" onclick="toggleProductStatus(${p.id})" title="${p.status === "active" ? "Ẩn" : "Hiện"}">${p.status === "active" ? "👁" : "✓"}</button>
        <button class="btn btn-danger btn-sm btn-icon" onclick="deleteProduct(${p.id})" title="Xóa">🗑</button>
      </div></td>
    </tr>`,
          )
          .join("");
  renderPag("prod", pages, prodPage, (p) => {
    prodPage = p;
    renderProductTable();
  });
}

function getNextProductCode() {
  const codes = PRODUCTS.map((p) => parseInt(p.code.slice(2)))
    .filter((n) => !isNaN(n))
    .sort((a, b) => a - b);
  const next = codes.length > 0 ? codes[codes.length - 1] + 1 : 1;
  return "SP" + String(next).padStart(3, "0");
}
function openProductModal(id) {
  const p = id ? PRODUCTS.find((x) => x.id === id) : null;
  document.getElementById("product-modal-title").textContent = p
    ? "Chỉnh sửa sản phẩm"
    : "Thêm sản phẩm";
  [
    "edit-prod-id",
    "prod-code",
    "prod-name",
    "prod-brand",
    "prod-supplier",
    "prod-concentration",
    "prod-price",
    "prod-size",
    "prod-profit",
    "prod-badge",
    "prod-status",
    "prod-desc",
    "prod-image-val",
    "prod-stock",
  ].forEach((fid) => {
    const key = {
      "edit-prod-id": "id",
      "prod-code": "code",
      "prod-name": "name",
      "prod-brand": "brand",
      "prod-supplier": "supplier",
      "prod-concentration": "concentration",
      "prod-price": "price",
      "prod-size": "size",
      "prod-profit": "profitRate",
      "prod-badge": "badge",
      "prod-status": "status",
      "prod-desc": "desc",
      "prod-image-val": "image",
      "prod-stock": "stock",
    }[fid];
    document.getElementById(fid).value = p && key ? (p[key] ?? "") : "";
  });
  const codeInput = document.getElementById("prod-code");
  if (!id) {
    codeInput.value = getNextProductCode();
    codeInput.readOnly = true;
    document.getElementById("prod-status").value = "active";
  } else {
    codeInput.readOnly = true;
  }
  const catSel = document.getElementById("prod-category");
  catSel.innerHTML = CATEGORIES.map(
    (c) =>
      `<option value="${c}"${p && p.category === c ? " selected" : ""}>${c}</option>`,
  ).join("");
  const img = document.getElementById("prod-img-preview");
  img.src = p && p.image ? p.image : "";
  img.style.display = p && p.image ? "block" : "none";
  openModal("product-modal");
}
function updateImgPreview() {
  const s = document.getElementById("prod-image-val").value.trim();
  const img = document.getElementById("prod-img-preview");
  img.src = s;
  img.style.display = s ? "block" : "none";
}
function clearProductImage() {
  document.getElementById("prod-image-val").value = "";
  const img = document.getElementById("prod-img-preview");
  img.src = "";
  img.style.display = "none";
}

function saveProduct() {
  const id = document.getElementById("edit-prod-id").value;
  const name = document.getElementById("prod-name").value.trim();
  const price = parseInt(document.getElementById("prod-price").value);
  if (!name || !price) {
    showToast("⚠ Tên và giá không được để trống!", "warn");
    return;
  }
  const data = {
    code: id
      ? PRODUCTS.find((p) => p.id === parseInt(id)).code
      : document.getElementById("prod-code").value.trim(),
    name,
    price,
    brand: document.getElementById("prod-brand").value.trim(),
    supplier: document.getElementById("prod-supplier").value.trim(),
    category: document.getElementById("prod-category").value,
    concentration: document.getElementById("prod-concentration").value,
    size: document.getElementById("prod-size").value.trim(),
    stock: parseInt(document.getElementById("prod-stock").value) || 0,
    profitRate: parseInt(document.getElementById("prod-profit").value) || 25,
    badge: document.getElementById("prod-badge").value,
    status: document.getElementById("prod-status").value,
    desc: document.getElementById("prod-desc").value.trim(),
    image: document.getElementById("prod-image-val").value.trim(),
  };
  if (id) {
    const idx = PRODUCTS.findIndex((p) => p.id === parseInt(id));
    PRODUCTS[idx] = { ...PRODUCTS[idx], ...data };
    showToast("✓ Đã cập nhật!", "success");
  } else {
    data.id = Date.now();
    PRODUCTS.push(data);
    showToast("✓ Đã thêm sản phẩm!", "success");
  }
  saveAll();
  closeModal("product-modal");
  filteredProds = [...PRODUCTS];
  renderProductTable();
}
function toggleProductStatus(id) {
  const p = PRODUCTS.find((x) => x.id === id);
  p.status = p.status === "active" ? "hidden" : "active";
  saveAll();
  filteredProds = [...PRODUCTS];
  renderProductTable();
  showToast(
    `${p.status === "active" ? "✓ Đang hiển thị" : "👁 Đã ẩn"}: ${p.name}`,
    "success",
  );
}
function deleteProduct(id) {
  const p = PRODUCTS.find((x) => x.id === id);
  const hasImport = RECEIPTS.some((r) =>
    r.items.some((i) => i.productId === id),
  );
  if (hasImport) {
    showConfirm(
      `"${p.name}" đã có phiếu nhập hàng → sẽ ẩn thay vì xóa.`,
      () => {
        p.status = "hidden";
        saveAll();
        filteredProds = [...PRODUCTS];
        renderProductTable();
        showToast("👁 Đã ẩn sản phẩm!", "warn");
      },
    );
  } else {
    showConfirm(`Xóa hẳn "${p.name}" khỏi cơ sở dữ liệu?`, () => {
      PRODUCTS = PRODUCTS.filter((x) => x.id !== id);
      saveAll();
      filteredProds = [...PRODUCTS];
      renderProductTable();
      showToast("✓ Đã xóa sản phẩm!", "success");
    });
  }
}

// ══════════════════════════════════════════════════
//  RECEIPTS
// ══════════════════════════════════════════════════
let currentReceipt = null;

function renderReceiptTable() {
  const q = (
    document.getElementById("receipt-search") || { value: "" }
  ).value.toLowerCase();
  const list = [...RECEIPTS]
    .filter((r) => !q || r.id.toLowerCase().includes(q))
    .reverse();
  document.getElementById("receipt-table-body").innerHTML =
    list.length === 0
      ? `<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">◫</div><div class="empty-title">Chưa có phiếu nhập</div></div></td></tr>`
      : list
          .map((r) => {
            const total = r.items.reduce((s, i) => s + i.qty * i.costPrice, 0);
            return `<tr>
        <td class="td-gold">${r.id}</td><td class="td-muted">${r.date}</td>
        <td class="td-name">${r.items.length} sản phẩm</td><td class="td-gold">${fmtPrice(total)}</td>
        <td><span class="badge ${r.status === "done" ? "badge-success" : "badge-warning"}">${r.status === "done" ? "Hoàn thành" : "Nháp"}</span></td>
        <td><div style="display:flex;gap:0.5rem">
          <button class="btn btn-ghost btn-sm" onclick="openReceiptDetail('${r.id}')">Xem</button>
          ${r.status === "draft" ? `<button class="btn btn-gold btn-sm" onclick="completeReceipt('${r.id}')">✓ Hoàn thành</button><button class="btn btn-danger btn-sm btn-icon" onclick="deleteReceipt('${r.id}')">🗑</button>` : ""}
        </div></td>
      </tr>`;
          })
          .join("");
}

function openNewReceipt() {
  currentReceipt = {
    id: "PN" + String(Date.now()).slice(-6),
    date: new Date().toLocaleDateString("vi-VN"),
    dateRaw: Date.now(),
    items: [],
    status: "draft",
  };
  renderReceiptEditor();
  openModal("receipt-modal");
}
function openReceiptDetail(id) {
  currentReceipt = RECEIPTS.find((r) => r.id === id);
  renderReceiptEditor();
  openModal("receipt-modal");
}

function renderReceiptEditor() {
  const r = currentReceipt;
  document.getElementById("receipt-modal-title").textContent =
    r.status === "done" ? `Phiếu nhập ${r.id}` : `Lập phiếu nhập – ${r.id}`;
  document.getElementById("receipt-id-display").textContent = r.id;
  document.getElementById("receipt-date-display").textContent = r.date;
  document.getElementById("receipt-status-display").innerHTML =
    `<span class="badge ${r.status === "done" ? "badge-success" : "badge-warning"}">${r.status === "done" ? "Hoàn thành" : "Nháp"}</span>`;
  const addRow = document.getElementById("receipt-add-row");
  if (addRow) addRow.style.display = r.status === "done" ? "none" : "flex";
  const cBtn = document.getElementById("receipt-complete-btn");
  if (cBtn) cBtn.style.display = r.status === "done" ? "none" : "inline-flex";
  const saveBtn = document.getElementById("receipt-save-btn");
  if (saveBtn)
    saveBtn.style.display = r.status === "done" ? "none" : "inline-flex";
  const sel = document.getElementById("receipt-prod-select");
  if (sel)
    sel.innerHTML =
      `<option value="">-- Chọn sản phẩm --</option>` +
      PRODUCTS.filter((p) => p.status === "active")
        .map((p) => `<option value="${p.id}">${p.code} – ${p.name}</option>`)
        .join("");
  const isDraft = r.status === "draft";
  document.getElementById("receipt-items-body").innerHTML =
    r.items.length === 0
      ? `<tr><td colspan="${isDraft ? 6 : 5}" style="text-align:center;color:var(--muted);padding:2rem">Chưa có sản phẩm</td></tr>`
      : r.items
          .map((item, i) => {
            const p = PRODUCTS.find((x) => x.id === item.productId);
            return `<tr><td class="td-name">${p ? p.name : "Không xác định"}</td><td class="td-muted">${p ? p.code : "—"}</td><td>${item.qty}</td><td class="td-gold">${fmtPrice(item.costPrice)}</td><td class="td-gold">${fmtPrice(item.qty * item.costPrice)}</td>${isDraft ? `<td><button class="btn btn-danger btn-sm btn-icon" onclick="removeReceiptItem(${i})">🗑</button></td>` : ""}</tr>`;
          })
          .join("");
  document.getElementById("receipt-total").textContent = fmtPrice(
    r.items.reduce((s, i) => s + i.qty * i.costPrice, 0),
  );
}

function addReceiptItem() {
  const prodId = parseInt(document.getElementById("receipt-prod-select").value);
  const qty = parseInt(document.getElementById("receipt-qty-input").value) || 0;
  const cost =
    parseInt(document.getElementById("receipt-cost-input").value) || 0;
  if (!prodId || qty <= 0 || cost <= 0) {
    showToast("⚠ Chọn sản phẩm, nhập số lượng và giá nhập!", "warn");
    return;
  }
  const ex = currentReceipt.items.find((i) => i.productId === prodId);
  if (ex) {
    ex.qty += qty;
    ex.costPrice = cost;
  } else currentReceipt.items.push({ productId: prodId, qty, costPrice: cost });
  document.getElementById("receipt-qty-input").value = "";
  document.getElementById("receipt-cost-input").value = "";
  renderReceiptEditor();
}
function removeReceiptItem(i) {
  currentReceipt.items.splice(i, 1);
  renderReceiptEditor();
}

function saveReceiptDraft() {
  if (!currentReceipt.items.length) {
    showToast("⚠ Thêm ít nhất 1 sản phẩm!", "warn");
    return;
  }
  const idx = RECEIPTS.findIndex((r) => r.id === currentReceipt.id);
  if (idx >= 0) RECEIPTS[idx] = currentReceipt;
  else RECEIPTS.push(currentReceipt);
  saveAll();
  closeModal("receipt-modal");
  renderReceiptTable();
  showToast("✓ Đã lưu nháp!", "success");
}
function completeCurrentReceipt() {
  if (!currentReceipt.items.length) {
    showToast("⚠ Thêm ít nhất 1 sản phẩm!", "warn");
    return;
  }
  currentReceipt.status = "done";
  const idx = RECEIPTS.findIndex((r) => r.id === currentReceipt.id);
  if (idx >= 0) RECEIPTS[idx] = currentReceipt;
  else RECEIPTS.push(currentReceipt);
  saveAll();
  recomputeStock();
  closeModal("receipt-modal");
  renderReceiptTable();
  showToast("✓ Phiếu nhập hoàn thành – tồn kho đã cập nhật!", "success");
}
function completeReceipt(id) {
  currentReceipt = RECEIPTS.find((r) => r.id === id);
  completeCurrentReceipt();
}
function deleteReceipt(id) {
  showConfirm("Xóa phiếu nhập nháp?", () => {
    RECEIPTS = RECEIPTS.filter((r) => r.id !== id);
    saveAll();
    renderReceiptTable();
    showToast("✓ Đã xóa phiếu!", "success");
  });
}

// ══════════════════════════════════════════════════
//  PRICING
// ══════════════════════════════════════════════════
function renderPricingTable() {
  const q = (
    document.getElementById("pricing-search") || { value: "" }
  ).value.toLowerCase();
  const list = PRODUCTS.filter(
    (p) =>
      !q ||
      p.name.toLowerCase().includes(q) ||
      p.code.toLowerCase().includes(q),
  );
  const inputStyle =
    "padding:0.3rem 0.5rem;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:0.5rem;color:var(--text);font-family:Jost,sans-serif";
  document.getElementById("pricing-table-body").innerHTML = list
    .map((p) => {
      const lastR = RECEIPTS.filter(
        (r) => r.status === "done" && r.items.some((i) => i.productId === p.id),
      ).slice(-1)[0];
      const lastI = lastR
        ? lastR.items.find((i) => i.productId === p.id)
        : null;
      const cost =
        p.customCost != null ? p.customCost : lastI ? lastI.costPrice : 0;
      const sale = Math.round(cost * (1 + (p.profitRate || 0) / 100));
      return `<tr>
      <td class="td-muted">${p.code}</td>
      <td class="td-name">${p.name}</td>
      <td><input type="number" id="cost-${p.id}" value="${cost}" min="0" step="1000" style="width:140px;${inputStyle}" oninput="recalcRow(${p.id})" /></td>
      <td><div style="display:flex;align-items:center;gap:0.5rem">
        <input type="number" id="profit-${p.id}" value="${p.profitRate || 0}" min="0" max="500" style="width:65px;${inputStyle}" oninput="recalcRow(${p.id})" />
        <span class="td-muted">%</span>
      </div></td>
      <td class="td-gold" id="sale-${p.id}">${fmtPrice(sale)}</td>
      <td><button class="btn btn-gold btn-sm" onclick="savePricingRow(${p.id})">Lưu</button></td>
    </tr>`;
    })
    .join("");
}

function recalcRow(id) {
  const cost = parseInt(document.getElementById("cost-" + id).value) || 0;
  const profit = parseInt(document.getElementById("profit-" + id).value) || 0;
  document.getElementById("sale-" + id).textContent = fmtPrice(
    Math.round(cost * (1 + profit / 100)),
  );
}

function savePricingRow(id) {
  const p = PRODUCTS.find((x) => x.id === id);
  p.customCost = parseInt(document.getElementById("cost-" + id).value) || 0;
  p.profitRate = parseInt(document.getElementById("profit-" + id).value) || 0;
  p.price = Math.round(p.customCost * (1 + p.profitRate / 100));
  saveAll();
  showToast(`✓ Đã lưu giá: ${p.name}!`, "success");
}

// ══════════════════════════════════════════════════
//  ORDERS
// ══════════════════════════════════════════════════
let ordPage = 1,
  filteredOrds = [...ORDERS];
const ordPerPage = 8;

function filterOrders() {
  const q = (document.getElementById("order-search").value || "").toLowerCase();
  const sta = document.getElementById("order-status-filter").value;
  const ward = document.getElementById("order-ward-filter").value;
  const dfrom = document.getElementById("order-date-from").value;
  const dto = document.getElementById("order-date-to").value;
  const sortBy = document.getElementById("order-sort-by").value;
  filteredOrds = ORDERS.filter((o) => {
    const productText =
      o.product ||
      (Array.isArray(o.items)
        ? o.items
            .map(
              (item) =>
                PRODUCTS.find((p) => p.id === item.id)?.name ||
                item.name ||
                `SP ${item.id}`,
            )
            .join(" ")
        : "") ||
      "";
    const matchQ =
      !q ||
      o.id.toLowerCase().includes(q) ||
      o.customer.toLowerCase().includes(q) ||
      productText.toLowerCase().includes(q);
    const matchSta = !sta || o.status === sta;
    const matchW =
      !ward ||
      (o.ward || o.address || "").toLowerCase().includes(ward.toLowerCase());
    const d =
      o.dateRaw || (o.createdAt ? new Date(o.createdAt).getTime() : 0) || 0;
    const from = dfrom ? new Date(dfrom).getTime() : 0;
    const to = dto ? new Date(dto).getTime() + 86400000 : Infinity;
    return matchQ && matchSta && matchW && d >= from && d <= to;
  });
  if (sortBy === "ward")
    filteredOrds.sort((a, b) => (a.ward || "").localeCompare(b.ward || ""));
  else filteredOrds.sort((a, b) => (b.dateRaw || 0) - (a.dateRaw || 0));
  ordPage = 1;
  renderOrderTable();
}

function orderProductSummary(o) {
  if (Array.isArray(o.items) && o.items.length) {
    const first = o.items[0];
    const name =
      PRODUCTS.find((x) => x.id === first.id)?.name ||
      first.name ||
      `SP ${first.id}`;
    const qty = first.qty || 1;
    return `${name}${qty > 1 ? ` ×${qty}` : ""}${
      o.items.length > 1 ? ` +${o.items.length - 1} SP` : ""
    }`;
  }
  if (o.product) return `${o.product}${(o.qty || 1) > 1 ? ` ×${o.qty}` : ""}`;
  return "—";
}

function renderOrderTable() {
  const total = filteredOrds.length,
    pages = Math.max(1, Math.ceil(total / ordPerPage));
  if (ordPage > pages) ordPage = 1;
  const start = (ordPage - 1) * ordPerPage,
    items = filteredOrds.slice(start, start + ordPerPage);
  document.getElementById("order-page-info").textContent =
    total > 0
      ? `${start + 1}–${Math.min(start + ordPerPage, total)} / ${total}`
      : "0";
  document.getElementById("order-table-body").innerHTML =
    items.length === 0
      ? `<tr><td colspan="8"><div class="empty-state"><div class="empty-icon">◫</div><div class="empty-title">Không tìm thấy đơn hàng</div></div></td></tr>`
      : items
          .map(
            (o) => `<tr>
      <td><a href="#" class="td-gold" onclick="openOrderDetail('${o.id}');return false;">${o.id}</a></td>
      <td class="td-name">${o.customer}</td>
      <td class="td-muted" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${orderProductSummary(o)}</td>
      <td class="td-gold">${fmtPrice(o.total)}</td>
      <td class="td-muted">${o.date || o.createdAt || "—"}</td>
      <td class="td-muted" style="max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${o.ward || o.address || "—"}</td>
      <td>${orderBadge(o.status)}</td>
      <td><select class="search-inline" style="width:auto;padding:0.35rem 0.5rem;font-size:0.78rem" onchange="quickUpdateOrder('${o.id}',this.value)">${["Chưa xử lý", "Đang xử lý", "Đã xác nhận", "Đang giao", "Đã giao", "Đã hủy"].map((s) => `<option${o.status === s ? " selected" : ""}>${s}</option>`).join("")}</select></td>
    </tr>`,
          )
          .join("");
  renderPag("order", pages, ordPage, (p) => {
    ordPage = p;
    renderOrderTable();
  });
}

function quickUpdateOrder(id, status) {
  const o = ORDERS.find((x) => x.id === id);
  o.status = status;
  saveAll();
  renderOrderTable();
  showToast(`✓ Cập nhật đơn ${id}!`, "success");
}

function openOrderDetail(id) {
  const o = ORDERS.find((x) => x.id === id);
  document.getElementById("order-detail-content").innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem">
      <div><div class="td-muted" style="font-size:0.7rem;letter-spacing:0.1em;text-transform:uppercase">Mã đơn</div><div class="td-gold" style="font-size:1.1rem;font-weight:600;margin-top:0.2rem">${o.id}</div></div>
      <div><div class="td-muted" style="font-size:0.7rem;letter-spacing:0.1em;text-transform:uppercase">Trạng thái</div><div style="margin-top:0.2rem">${orderBadge(o.status)}</div></div>
      <div><div class="td-muted" style="font-size:0.7rem;letter-spacing:0.1em;text-transform:uppercase">Khách hàng</div><div class="td-name" style="margin-top:0.2rem">${o.customer}</div></div>
      <div><div class="td-muted" style="font-size:0.7rem;letter-spacing:0.1em;text-transform:uppercase">Điện thoại</div><div style="margin-top:0.2rem">${o.phone || "—"}</div></div>
      <div style="grid-column:1/-1"><div class="td-muted" style="font-size:0.7rem;letter-spacing:0.1em;text-transform:uppercase">Địa chỉ giao hàng</div><div style="margin-top:0.2rem">${o.address || "—"}</div></div>
    </div>
    <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:1rem;overflow:hidden">
      <table><thead><tr><th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
      <tbody><tr><td class="td-name">${o.product}</td><td>${o.qty || 1}</td><td class="td-gold">${fmtPrice(o.total / (o.qty || 1))}</td><td class="td-gold">${fmtPrice(o.total)}</td></tr></tbody></table>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.25rem">
      <span class="td-muted">Ngày đặt: ${o.date} · ${o.ward || ""}</span>
      <div style="font-family:'Playfair Display',serif;font-size:1.35rem;color:var(--gold)">Tổng: ${fmtPrice(o.total)}</div>
    </div>
    <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border)">
      <div class="td-muted" style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:0.75rem">Cập nhật trạng thái</div>
      <div style="display:flex;gap:0.6rem;flex-wrap:wrap" id="order-status-btns">
        ${["Chưa xử lý", "Đã xác nhận", "Đã giao", "Đã hủy"].map((s) => `<button class="btn ${o.status === s ? "btn-gold" : "btn-ghost"} btn-sm" onclick="quickUpdateOrder('${o.id}','${s}');document.getElementById('order-status-btns').querySelectorAll('button').forEach(b=>{b.className='btn btn-ghost btn-sm'});this.className='btn btn-gold btn-sm';renderOrderTable()">${s}</button>`).join("")}
      </div>
    </div>`;
  openModal("order-modal");
}

// ══════════════════════════════════════════════════
//  INVENTORY
// ══════════════════════════════════════════════════
function renderInventory() {
  recomputeStock();
  const threshold =
    parseInt(
      (document.getElementById("inv-low-threshold") || { value: LOW_STOCK })
        .value,
    ) || LOW_STOCK;
  document.getElementById("inv-low-threshold").value = LOW_STOCK;
  const dateVal = (document.getElementById("inv-date") || { value: "" }).value;
  const qDate = dateVal ? new Date(dateVal).getTime() : Date.now();
  document.getElementById("inv-table-body").innerHTML = PRODUCTS.map((p) => {
    const imp = RECEIPTS.filter(
      (r) => r.status === "done" && (r.dateRaw || 0) <= qDate,
    )
      .flatMap((r) => r.items)
      .filter((i) => i.productId === p.id)
      .reduce((s, i) => s + i.qty, 0);
    const exp = ORDERS.filter(
      (o) =>
        o.status === "Đã giao" &&
        (o.dateRaw || 0) <= qDate &&
        o.productId === p.id,
    ).reduce((s, o) => s + (o.qty || 1), 0);
    const stock = Math.max(0, imp - exp);
    const isLow = stock <= LOW_STOCK;
    return `<tr class="${isLow ? "row-warning" : ""}"><td class="td-muted">${p.code}</td><td class="td-name">${p.name}</td><td><span class="badge badge-muted">${p.category}</span></td><td style="color:var(--success)">${imp}</td><td style="color:var(--danger)">${exp}</td><td><span class="badge ${isLow ? "badge-danger" : stock <= 15 ? "badge-warning" : "badge-success"}">${stock}</span></td><td>${isLow ? '<span class="badge badge-danger">⚠ Sắp hết</span>' : ""}</td></tr>`;
  }).join("");
}

// ══════════════════════════════════════════════════
//  REPORT (trang riêng)
// ══════════════════════════════════════════════════
function renderInventoryReport() {
  const fromVal = (document.getElementById("inv-from") || { value: "" }).value;
  const toVal = (document.getElementById("inv-to") || { value: "" }).value;
  const from = fromVal ? new Date(fromVal).getTime() : 0;
  const to = toVal ? new Date(toVal).getTime() + 86400000 : Infinity;
  const rows = PRODUCTS.map((p) => {
    const imp = RECEIPTS.filter(
      (r) =>
        r.status === "done" &&
        (r.dateRaw || 0) >= from &&
        (r.dateRaw || 0) <= to,
    )
      .flatMap((r) => r.items)
      .filter((i) => i.productId === p.id)
      .reduce((s, i) => s + i.qty, 0);
    const exp = ORDERS.filter(
      (o) =>
        o.status === "Đã giao" &&
        (o.dateRaw || 0) >= from &&
        (o.dateRaw || 0) <= to &&
        o.productId === p.id,
    ).reduce((s, o) => s + (o.qty || 1), 0);
    if (!imp && !exp) return "";
    return `<tr><td class="td-name">${p.name}</td><td style="color:var(--success)">+${imp}</td><td style="color:var(--danger)">-${exp}</td><td class="${imp - exp >= 0 ? "td-gold" : ""}">Còn ${imp - exp >= 0 ? "+" : ""}${imp - exp}</td></tr>`;
  })
    .filter(Boolean)
    .join("");
  document.getElementById("inv-report-body").innerHTML =
    rows ||
    `<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:2rem">Không có dữ liệu trong khoảng thời gian này</td></tr>`;
}

function applyLowThreshold() {
  LOW_STOCK = parseInt(document.getElementById("inv-low-threshold").value) || 5;
  saveAll();
  renderInventory();
  showToast(`✓ Ngưỡng cảnh báo: ≤ ${LOW_STOCK} SP`, "success");
}

// ══════════════════════════════════════════════════
//  PAGINATION
// ══════════════════════════════════════════════════
function renderPag(prefix, totalPages, currentPage, onPage) {
  const el = document.getElementById(prefix + "-pagination");
  if (!el || totalPages <= 1) {
    if (el) el.innerHTML = "";
    return;
  }
  let html = `<button class="pag-btn" onclick="(${onPage.toString()})(${currentPage - 1})" ${currentPage === 1 ? "disabled" : ""}>‹</button>`;
  for (let i = 1; i <= totalPages; i++) {
    if (
      totalPages > 7 &&
      i > 2 &&
      i < totalPages - 1 &&
      Math.abs(i - currentPage) > 1
    ) {
      if (i === 3 || i === totalPages - 2)
        html += `<span style="color:var(--muted);align-self:center;padding:0 0.2rem">…</span>`;
      continue;
    }
    html += `<button class="pag-btn ${i === currentPage ? "active" : ""}" onclick="(${onPage.toString()})(${i})">${i}</button>`;
  }
  html += `<button class="pag-btn" onclick="(${onPage.toString()})(${currentPage + 1})" ${currentPage === totalPages ? "disabled" : ""}>›</button>`;
  el.innerHTML = html;
}

// ══════════════════════════════════════════════════
//  UI HELPERS
// ══════════════════════════════════════════════════
function orderBadge(s) {
  const m = {
    "Chưa xử lý": "badge-muted",
    "Đang xử lý": "badge-warning",
    "Đã xác nhận": "badge-warning",
    "Đang giao": "badge-info",
    "Đã giao": "badge-success",
    "Đã hủy": "badge-danger",
  };
  return `<span class="badge ${m[s] || "badge-muted"}">${s}</span>`;
}
function fmtPrice(n) {
  return Number(n || 0).toLocaleString("vi-VN") + "₫";
}
function openModal(id) {
  document.getElementById(id).classList.add("open");
  document.body.style.overflow = "hidden";
}
function closeModal(id) {
  document.getElementById(id).classList.remove("open");
  document.body.style.overflow = "";
}

let _cb = null;
function showConfirm(msg, cb) {
  document.getElementById("confirm-msg").textContent = msg;
  _cb = cb;
  document.getElementById("confirm-dialog").classList.add("open");
}
function confirmAction() {
  if (_cb) _cb();
  closeConfirm();
}
function closeConfirm() {
  document.getElementById("confirm-dialog").classList.remove("open");
  _cb = null;
}

function showToast(msg, type = "info") {
  const icons = { success: "✓", warn: "⚠", info: "✦", error: "✕" };
  document.getElementById("toast-msg").textContent = msg;
  document.getElementById("toast-icon").textContent = icons[type] || "✦";
  const t = document.getElementById("admin-toast");
  t.classList.add("show");
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove("show"), 3500);
}
function adminLogout() {
  window.location.href = "admin-logout.php";
}

document.querySelectorAll(".modal-overlay").forEach((el) =>
  el.addEventListener("click", (e) => {
    if (e.target === el) closeModal(el.id);
  }),
);

// ── INIT ──
initializeAdminApp();
