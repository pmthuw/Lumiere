// ══════════════════════════════════════════════════
//  GLOBAL STATE & INITIALIZATION
// ══════════════════════════════════════════════════

// Global state
let state = {
  currentCategory: "",
  filterCategory: "",
  priceMin: null,
  priceMax: null,
  page: 1,
  perPage: 6,
  isSearching: false,
  cart: JSON.parse(localStorage.getItem("lum_cart") || "[]"),
  user: null,
  users: [],
  orders: JSON.parse(localStorage.getItem("lum_orders") || "[]"),
};

// Các section có sẵn
const ALL_SECTIONS = ["home", "products", "contact"];

// Hiển thị/ẩn section
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

// Khởi tạo khi DOM ready
document.addEventListener("DOMContentLoaded", async () => {
  // Khởi tạo search input
  initSearchInput();

  // Click ngoài → đóng dropdown + đóng user menu
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".search-input-wrap")) closeSuggestions();
    if (!e.target.closest(".user-menu-wrap")) closeUserMenu();
  });

  // Ẩn contact section lúc đầu
  const contact = document.getElementById("contact");
  if (contact) contact.style.display = "none";

  // Cập nhật UI và lấy trạng thái đăng nhập từ session/DB
  updateUserUI();
  if (typeof loadCurrentUserFromDB === "function") {
    await loadCurrentUserFromDB({ silent: true });
    updateUserUI();
  }

  if (state.user && typeof loadCartFromDB === "function") {
    loadCartFromDB(() => updateCartBadge());
  } else {
    updateCartBadge();
  }

  // Chỉ nạp dữ liệu nền cho cart/search, không render đè danh sách PHP server-side
  loadProducts(false);

  if (typeof loadCategories === "function") {
    loadCategories();
  }
});
