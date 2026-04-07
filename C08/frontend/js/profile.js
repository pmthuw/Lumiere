// ══════════════════════════════════════════════════
//  USER PROFILE MANAGEMENT
// ══════════════════════════════════════════════════

// Mở modal hồ sơ người dùng
function openProfile() {
  if (!state.user) return;

  const u = state.user;

  // Avatar initials
  const initials = (
    u.lastname.charAt(0) + (u.firstname.charAt(0) || "")
  ).toUpperCase();

  const profAvatar = document.getElementById("prof-avatar");
  const profName = document.getElementById("prof-name");
  const profEmail = document.getElementById("prof-email");

  if (profAvatar) profAvatar.textContent = initials;
  if (profName) profName.textContent = u.lastname + " " + u.firstname;
  if (profEmail) profEmail.textContent = u.email;

  // Điền dữ liệu vào form
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

  const infoMsg = document.getElementById("pf-info-msg");
  if (infoMsg) infoMsg.innerHTML = "";

  closeUserMenu();
  openModal("profile-modal");
}

// Chuyển tab giữa "Thông tin" và "Mật khẩu"
function switchProfTab(tab) {
  ["info", "pw"].forEach((t) => {
    const tabBtn = document.getElementById("ptab-" + t);
    const panel = document.getElementById("ppanel-" + t);

    if (tabBtn) tabBtn.classList.toggle("active", t === tab);
    if (panel) panel.style.display = t === tab ? "block" : "none";
  });
}

// Lưu thông tin hồ sơ
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

  // Cập nhật trong mảng users
  const idx = state.users.findIndex((u) => u.email === state.user.email);
  if (idx !== -1) state.users[idx] = { ...state.users[idx], ...state.user };

  localStorage.setItem("lum_user", JSON.stringify(state.user));
  localStorage.setItem("lum_users", JSON.stringify(state.users));

  // Refresh avatar & greeting
  const profAvatar = document.getElementById("prof-avatar");
  const profName = document.getElementById("prof-name");

  if (profAvatar)
    profAvatar.textContent = (
      state.user.lastname.charAt(0) + state.user.firstname.charAt(0)
    ).toUpperCase();
  if (profName)
    profName.textContent = state.user.lastname + " " + state.user.firstname;

  updateUserUI();
  showProfileMsg("pf-info-msg", "Cập nhật thông tin thành công!", true);
}

// Hủy thay đổi thông tin
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

  const infoMsg = document.getElementById("pf-info-msg");
  if (infoMsg) infoMsg.innerHTML = "";
}

// Lưu mật khẩu mới
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
    showProfileMsg("pf-pw-msg", "Mật khẩu phải có ít nhất 6 ký tự!", false);
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

// Xóa các field mật khẩu
function clearPwFields() {
  const fields = ["pw-old", "pw-new", "pw-confirm"];
  fields.forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.value = "";
  });

  const strengthBar = document.getElementById("pw-strength-bar");
  if (strengthBar) strengthBar.style.width = "0";

  const pwMsg = document.getElementById("pf-pw-msg");
  if (pwMsg) pwMsg.innerHTML = "";
}

// Bật/tắt hiển thị mật khẩu
function togglePwField(id, btn) {
  const el = document.getElementById(id);
  if (!el) return;

  const isPassword = el.type === "password";
  el.type = isPassword ? "text" : "password";
  if (btn) btn.textContent = isPassword ? "🙈" : "👁";
}

// Cập nhật thanh độ mạnh mật khẩu
function updatePwStrength(val) {
  const bar = document.getElementById("pw-strength-bar");
  if (!bar) return;

  let strength = 0;
  if (val.length >= 6) strength += 25;
  if (val.length >= 8) strength += 25;
  if (/[A-Z]/.test(val)) strength += 25;
  if (/[0-9]/.test(val) || /[^A-Za-z0-9]/.test(val)) strength += 25;

  bar.style.width = strength + "%";

  let color = "#E24B4A";
  if (strength >= 75) color = "#4caf50";
  else if (strength >= 50) color = "#ff9800";
  bar.style.background = color;
}
