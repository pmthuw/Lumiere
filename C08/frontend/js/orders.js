// ══════════════════════════════════════════════════
//  ORDERS MANAGEMENT
// ══════════════════════════════════════════════════

function mapStatusLabel(status) {
  const key = String(status || "").toLowerCase();
  if (key === "pending" || key === "processing") return "Đang xử lý";
  if (key === "shipping" || key === "shipped") return "Đang giao";
  if (key === "delivered" || key === "completed") return "Đã giao";
  return status || "Đang xử lý";
}

function formatOrderDate(raw) {
  if (!raw) return "Không rõ thời gian";
  const dt = new Date(raw);
  if (Number.isNaN(dt.getTime())) return String(raw);
  return dt.toLocaleString("vi-VN");
}

async function fetchUserOrders(userEmail) {
  if (!userEmail) return [];

  const res = await fetch(
    `${API_BASE}/orders.php?user=${encodeURIComponent(userEmail)}`,
  );
  const data = await res.json();

  if (!res.ok || !data.success) {
    throw new Error(data.message || "Không thể tải đơn hàng");
  }

  if (!Array.isArray(data.orders)) return [];

  return data.orders.map((order) => ({
    id: order.order_number || order.id,
    userEmail: state.user ? state.user.email : userEmail,
    createdAt: formatOrderDate(order.created_at),
    status: mapStatusLabel(order.status),
    total: Number(order.total_amount) || 0,
    items: Array.isArray(order.items)
      ? order.items.map((item) => ({
          id: Number(item.id) || 0,
          name: item.name || "Sản phẩm",
          qty: Number(item.qty) || 1,
          price: Number(item.price) || 0,
        }))
      : [],
  }));
}

// Xem danh sách đơn hàng
async function openOrders() {
  closeUserMenu();

  if (!state.user) {
    showToast("Vui lòng đăng nhập!");
    return;
  }
  window.location.href = `orders.php?user=${encodeURIComponent(state.user.email)}`;
}
