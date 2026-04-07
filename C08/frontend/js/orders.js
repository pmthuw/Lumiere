// ══════════════════════════════════════════════════
//  ORDERS MANAGEMENT
// ══════════════════════════════════════════════════

// Xem danh sách đơn hàng
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

  const orderList = document.getElementById("order-list");
  if (orderList) {
    orderList.innerHTML = html;
  }

  openModal("order-modal");
}
