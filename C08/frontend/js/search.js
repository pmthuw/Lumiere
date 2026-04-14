// ══════════════════════════════════════════════════
//  SEARCH & SUGGESTIONS
// ══════════════════════════════════════════════════

let _suggestBox = null;
let _sgIndex = -1;

// Hiển thị gợi ý tìm kiếm
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
          <div class="sg-name">${highlightText(p.name, query.trim())}</div>
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

// Đóng dropdown gợi ý
function closeSuggestions() {
  if (_suggestBox) _suggestBox.style.display = "none";
  _sgIndex = -1;
}

// Chọn một gợi ý
function selectSuggestion(id) {
  const inp = document.getElementById("search-input");
  const p = PRODUCTS.find((x) => x.id === id);
  if (inp && p) inp.value = p.name;
  closeSuggestions();
  showSection("products");
  setTimeout(() => openDetail(id), 120);
}

// Lấy box element chứa gợi ý (tạo nếu chưa có)
function _getSuggestBox() {
  if (!_suggestBox) {
    const inp = document.getElementById("search-input");
    if (!inp) return null;
    const box = document.createElement("div");
    box.className = "sg-box";
    box.style.cssText =
      "position:absolute;top:100%;left:0;right:0;background:var(--bg);border:1px solid var(--border);border-radius:0.75rem;max-height:400px;overflow-y:auto;z-index:100;display:none;";
    inp.parentElement.style.position = "relative";
    inp.parentElement.appendChild(box);
    _suggestBox = box;
  }
  return _suggestBox;
}

// Xử lý phím tắt khi dropdown gợi ý đang mở
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

// Cập nhật dòng active trong gợi ý
function _updateSgActive(items) {
  items.forEach((item, i) =>
    item.classList.toggle("sg-active", i === _sgIndex),
  );
  if (_sgIndex >= 0) items[_sgIndex].scrollIntoView({ block: "nearest" });
}

// Bật/tắt bộ lọc nâng cao
function toggleAdvanced() {
  const panel = document.getElementById("advanced-panel");
  if (panel) panel.classList.toggle("open");
}

// Thực hiện tìm kiếm - submit server-side form
function doSearch() {
  const form = document.getElementById("products-search-form");
  if (form && typeof form.submit === "function") {
    form.submit();
  }
}

// Xóa bộ lọc - redirect to clean query
function resetSearch() {
  window.location.href = "?page=1#products";
}

// Mở trang tìm kiếm
function openSearch() {
  showSection("products");
  setTimeout(() => {
    const inp = document.getElementById("search-input");
    if (inp) inp.focus();
  }, 500);
}

// Khởi tạo event listener cho search input
function initSearchInput() {
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
}
