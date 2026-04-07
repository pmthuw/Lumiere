// ══════════════════════════════════════════════════
//  UTILITIES & HELPERS
// ══════════════════════════════════════════════════

// Format giá tiền theo Việt Nam
function fmtPrice(n) {
  return n.toLocaleString("vi-VN") + "₫";
}

// Hiển thị thông báo Toast
function showToast(msg) {
  const t = document.getElementById("toast");
  if (!t) {
    const toast = document.createElement("div");
    toast.id = "toast";
    toast.style.cssText =
      "position:fixed;bottom:2rem;right:2rem;background:var(--gold);color:#000;padding:1rem 1.5rem;border-radius:0.75rem;z-index:9999;opacity:0;transition:opacity 0.3s;";
    document.body.appendChild(toast);
  }
  const toastEl = document.getElementById("toast");
  toastEl.textContent = msg;
  toastEl.classList.add("show");
  toastEl.style.opacity = "1";
  setTimeout(() => {
    toastEl.classList.remove("show");
    toastEl.style.opacity = "0";
  }, 3000);
}

// Mở modal
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add("open");
    document.body.style.overflow = "hidden";
  }
}

// Đóng modal
function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove("open");
    document.body.style.overflow = "";
  }
}

// Bật/tắt dropdown user menu
function toggleUserMenu() {
  const menu = document.getElementById("user-dropdown");
  if (menu) menu.classList.toggle("open");
}

// Đóng dropdown user menu
function closeUserMenu() {
  const menu = document.getElementById("user-dropdown");
  if (menu) menu.classList.remove("open");
}

// Hiển thị thông báo trong profile
function showProfileMsg(id, text, ok) {
  const el = document.getElementById(id);
  if (!el) return;
  el.innerHTML = `<div style="padding:0.75rem 1rem;border-radius:0.5rem;background:${ok ? "rgba(76,175,80,0.2);color:#4caf50;border:1px solid #4caf50" : "rgba(244,67,54,0.2);color:#f44336;border:1px solid #f44336"};font-size:0.9rem;">${text}</div>`;
}

// Highlight tử trong đoạn chữ
function highlightText(text, q) {
  if (!q) return text;
  const esc = q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  return text.replace(new RegExp(`(${esc})`, "gi"), "<mark>$1</mark>");
}
