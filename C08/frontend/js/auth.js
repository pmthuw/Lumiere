// ══════════════════════════════════════════════════
//  AUTHENTICATION (LOGIN / REGISTER / LOGOUT)
// ══════════════════════════════════════════════════

// Mở modal đăng nhập/đăng ký
function openAuth(tab) {
  switchAuthTab(tab);
  openModal("auth-modal");
  initRegisterAddressSelectors();
  closeUserMenu();
}

// Chuyển tab giữa Login và Register
function switchAuthTab(tab) {
  document
    .querySelectorAll(".auth-tab")
    .forEach((t) => t.classList.remove("active"));
  document
    .querySelectorAll(".auth-form")
    .forEach((f) => f.classList.remove("active"));
  document.getElementById("tab-" + tab).classList.add("active");
  document.getElementById("form-" + tab).classList.add("active");
  if (tab === "register") {
    initRegisterAddressSelectors();
  }
}

let registerAddressData = [];
let registerAddressPromise = null;

async function loadCurrentUserFromDB(options = {}) {
  const { silent = false } = options;

  try {
    const response = await fetch("api-current-user.php", {
      cache: "no-store",
      credentials: "same-origin",
    });
    const result = await response.json();

    if (response.ok && result?.success && result?.user) {
      state.user = result.user;
      return state.user;
    }

    state.user = null;
    return null;
  } catch (error) {
    if (!silent) {
      showToast("Không thể tải thông tin tài khoản.");
    }
    state.user = null;
    return null;
  }
}

function fillSelectOptions(selectEl, options, placeholder) {
  if (!selectEl) return;
  const oldValue = selectEl.value;
  selectEl.innerHTML = "";

  const first = document.createElement("option");
  first.value = "";
  first.textContent = placeholder;
  selectEl.appendChild(first);

  options.forEach((item) => {
    const opt = document.createElement("option");
    opt.value = item;
    opt.textContent = item;
    selectEl.appendChild(opt);
  });

  if (oldValue && options.includes(oldValue)) {
    selectEl.value = oldValue;
  }
}

function normalizeProvinceName(name) {
  return String(name || "")
    .replace(/^(Tỉnh|Thành phố)\s+/i, "")
    .trim();
}

function getProvinceByName(name) {
  const target = String(name || "").trim();
  return (
    registerAddressData.find((p) => {
      const raw = String(p?.name || "").trim();
      const normalized = normalizeProvinceName(raw);
      return raw === target || normalized === target;
    }) || null
  );
}

function loadRegisterAddressData() {
  if (registerAddressData.length > 0) {
    return Promise.resolve(registerAddressData);
  }

  if (registerAddressPromise) {
    return registerAddressPromise;
  }

  registerAddressPromise = fetch("assets/data.json", { cache: "force-cache" })
    .then((res) => {
      if (!res.ok) {
        throw new Error("Unable to load address data");
      }
      return res.json();
    })
    .then((data) => {
      registerAddressData = Array.isArray(data) ? data : [];
      return registerAddressData;
    })
    .catch(() => {
      registerAddressData = [];
      return registerAddressData;
    });

  return registerAddressPromise;
}

function initRegisterAddressSelectors() {
  const cityEl = document.getElementById("reg-city");
  const districtEl = document.getElementById("reg-district");
  const wardEl = document.getElementById("reg-ward");

  if (!cityEl || !districtEl || !wardEl) return;
  if (cityEl.dataset.boundAddress === "1") return;

  const updateDistricts = () => {
    const city = cityEl.value;
    if (!city) {
      fillSelectOptions(districtEl, [], "Chọn Quận / Huyện");
      fillSelectOptions(wardEl, [], "Chọn Phường / Xã");
      return;
    }
    const province = getProvinceByName(city);
    const districts = Array.isArray(province?.districts)
      ? province.districts.map((d) => d.name).filter(Boolean)
      : [];
    fillSelectOptions(districtEl, districts, "Chọn Quận / Huyện");
    fillSelectOptions(wardEl, [], "Chọn Phường / Xã");
  };

  const updateWards = () => {
    const city = cityEl.value;
    const district = districtEl.value;
    if (!city || !district) {
      fillSelectOptions(wardEl, [], "Chọn Phường / Xã");
      return;
    }
    const province = getProvinceByName(city);
    const districtObj = Array.isArray(province?.districts)
      ? province.districts.find((d) => d.name === district)
      : null;
    const wards = Array.isArray(districtObj?.wards)
      ? districtObj.wards.map((w) => w.name).filter(Boolean)
      : [];
    fillSelectOptions(wardEl, wards, "Chọn Phường / Xã");
  };

  cityEl.addEventListener("change", updateDistricts);
  districtEl.addEventListener("change", updateWards);
  cityEl.dataset.boundAddress = "1";

  loadRegisterAddressData().then((data) => {
    const cityOptions = data
      .map((p) => normalizeProvinceName(p?.name))
      .filter(Boolean);
    fillSelectOptions(cityEl, cityOptions, "Chọn Tỉnh / Thành phố");
    updateDistricts();
  });
}

// Xử lý đăng nhập
async function doLogin() {
  const identifier = document.getElementById("login-identifier").value.trim();
  const password = document.getElementById("login-password").value;

  if (!identifier || !password) {
    showToast("Vui lòng điền đầy đủ thông tin!");
    return;
  }

  try {
    const response = await fetch("api-login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ identifier, password }),
    });

    const result = await response.json();
    if (!response.ok || !result?.success) {
      showToast(result?.message || "Đăng nhập thất bại. Vui lòng thử lại!");
      return;
    }

    const user = (await loadCurrentUserFromDB({ silent: true })) || result.user;
    state.user = user;

    closeModal("auth-modal");
    updateUserUI();
    if (typeof loadCartFromDB === "function") {
      loadCartFromDB(() => updateCartBadge());
    } else {
      updateCartBadge();
    }
    showToast(`Chào mừng, ${user.firstname || user.fullname}!`);
  } catch (error) {
    showToast("Không thể kết nối máy chủ. Vui lòng thử lại!");
  }
}

// Xử lý đăng ký
async function doRegister() {
  const fields = [
    "reg-lastname",
    "reg-firstname",
    "reg-email",
    "reg-username",
    "reg-phone",
    "reg-password",
    "reg-address",
    "reg-ward",
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
    email,
    username,
    phone,
    password,
    address,
    ward,
    district,
    city,
  ] = vals;

  if (!/^[0-9]{10}$/.test(phone)) {
    showToast("Số điện thoại phải có đúng 10 chữ số!");
    return;
  }

  if (username.length < 4) {
    showToast("Tên tài khoản phải có ít nhất 4 ký tự!");
    return;
  }

  if (password.length < 6) {
    showToast("Mật khẩu phải có ít nhất 6 ký tự!");
    return;
  }

  const payload = {
    lastname,
    firstname,
    username,
    email,
    phone,
    password,
    address,
    ward,
    district,
    city,
    role: "customer",
    status: "active",
  };

  try {
    const response = await fetch("register.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const result = await response.json();
    if (!response.ok || !result?.success) {
      showToast(result?.message || "Đăng ký thất bại. Vui lòng thử lại!");
      return;
    }

    // Clear registration form
    fields.forEach((f) => {
      const el = document.getElementById(f);
      if (el) el.value = "";
    });

    showToast("Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.");

    // Switch to login tab for user to login
    if (typeof switchAuthTab === "function") {
      switchAuthTab("login");
    }
  } catch (error) {
    showToast("Không thể kết nối máy chủ. Vui lòng thử lại!");
  }
}

// Xử lý đăng xuất
async function logout() {
  try {
    await fetch("api-logout.php", {
      method: "POST",
      credentials: "same-origin",
    });
  } catch (error) {
    // Keep local cleanup even if request fails.
  }

  state.user = null;
  state.cart = [];
  localStorage.removeItem("lum_cart");
  saveCart();
  updateCartBadge();
  updateUserUI();
  closeUserMenu();
  showToast("Đã đăng xuất thành công!");
}

// Cập nhật giao diện người dùng theo trạng thái đăng nhập
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
