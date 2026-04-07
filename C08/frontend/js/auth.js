// ══════════════════════════════════════════════════
//  AUTHENTICATION (LOGIN / REGISTER / LOGOUT)
// ══════════════════════════════════════════════════

// Mở modal đăng nhập/đăng ký
function openAuth(tab) {
  switchAuthTab(tab);
  openModal("auth-modal");
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
}

// Xử lý đăng nhập
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

// Xử lý đăng ký
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

// Xử lý đăng xuất
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
