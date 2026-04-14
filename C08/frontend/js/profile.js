// ══════════════════════════════════════════════════
//  USER PROFILE MANAGEMENT
// ══════════════════════════════════════════════════

function pickFirstNonEmpty(...values) {
  for (const value of values) {
    const text = String(value ?? "").trim();
    if (text !== "") return text;
  }
  return "";
}

function splitName(fullName) {
  const normalized = String(fullName || "").trim();
  if (!normalized) {
    return { firstname: "", lastname: "" };
  }

  const parts = normalized.split(/\s+/).filter(Boolean);
  if (parts.length === 0) {
    return { firstname: "", lastname: "" };
  }

  const firstname = parts.pop() || "";
  const lastname = parts.join(" ");
  return { firstname, lastname };
}

function hydrateCurrentUserProfile() {
  if (!state.user) return null;

  const current = state.user || {};

  const fullName = pickFirstNonEmpty(
    current.fullname,
    current.full_name,
    current.name,
  );
  const nameParts = splitName(fullName);

  const normalizedUser = {
    ...current,
    lastname: pickFirstNonEmpty(current.lastname, nameParts.lastname),
    firstname: pickFirstNonEmpty(current.firstname, nameParts.firstname),
    phone: pickFirstNonEmpty(current.phone),
    address: pickFirstNonEmpty(current.address, current.shipping_address),
    district: pickFirstNonEmpty(current.district),
    city: pickFirstNonEmpty(current.city),
    ward: pickFirstNonEmpty(current.ward),
  };

  state.user = normalizedUser;
  return normalizedUser;
}

// Mở modal hồ sơ người dùng
async function openProfile() {
  if (typeof loadCurrentUserFromDB === "function") {
    await loadCurrentUserFromDB({ silent: true });
  }

  const u = hydrateCurrentUserProfile();
  if (!u) return;

  // Avatar initials
  const avatarSource = pickFirstNonEmpty(u.lastname, u.firstname, u.email, "?");
  const secondarySource = pickFirstNonEmpty(u.firstname, " ");
  const initials = (
    avatarSource.charAt(0) + secondarySource.charAt(0)
  ).toUpperCase();

  const profAvatar = document.getElementById("prof-avatar");
  const profName = document.getElementById("prof-name");
  const profEmail = document.getElementById("prof-email");

  if (profAvatar) profAvatar.textContent = initials;
  if (profName) {
    const displayName = pickFirstNonEmpty(
      `${u.lastname || ""} ${u.firstname || ""}`.trim(),
      u.fullname,
      u.full_name,
      u.username,
      "Khách hàng",
    );
    profName.textContent = displayName;
  }
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
    if (!el) return;

    const value = u[k] || "";
    if (id === "pf-city" && value && el.tagName === "SELECT") {
      const hasOption = Array.from(el.options).some(
        (opt) => opt.value === value,
      );
      if (!hasOption) {
        const opt = document.createElement("option");
        opt.value = value;
        opt.textContent = value;
        el.appendChild(opt);
      }
    }

    el.value = value;
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
async function saveProfileInfo() {
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

  try {
    const response = await fetch("api-profile.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "same-origin",
      body: JSON.stringify({
        action: "update_info",
        lastname: ln,
        firstname: fn,
        phone: ph,
        address: ad,
        district: di,
        city: ci,
      }),
    });

    const result = await response.json();
    if (!response.ok || !result?.success) {
      showProfileMsg(
        "pf-info-msg",
        result?.message || "Không thể cập nhật thông tin hồ sơ.",
        false,
      );
      return;
    }

    state.user = result.user || state.user;
    state.user = hydrateCurrentUserProfile() || state.user;
  } catch (error) {
    showProfileMsg("pf-info-msg", "Lỗi kết nối máy chủ.", false);
    return;
  }

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
async function savePassword() {
  if (!state.user) return;

  const oldPw = document.getElementById("pw-old").value;
  const newPw = document.getElementById("pw-new").value;
  const cfmPw = document.getElementById("pw-confirm").value;

  if (!oldPw || !newPw || !cfmPw) {
    showProfileMsg("pf-pw-msg", "Vui lòng điền đầy đủ!", false);
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

  if (oldPw.length === 0 || newPw.length === 0 || cfmPw.length === 0) {
    showProfileMsg(
      "pf-pw-msg",
      "Vui lòng không để trống bất kỳ trường nào!",
      false,
    );
    return;
  }

  try {
    const response = await fetch("api-profile.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "same-origin",
      body: JSON.stringify({
        action: "change_password",
        old_password: oldPw,
        new_password: newPw,
        confirm_password: cfmPw,
      }),
    });

    const result = await response.json();
    if (!response.ok || !result?.success) {
      showProfileMsg(
        "pf-pw-msg",
        result?.message || "Không thể đổi mật khẩu.",
        false,
      );
      return;
    }
  } catch (error) {
    showProfileMsg("pf-pw-msg", "Lỗi kết nối máy chủ.", false);
    return;
  }

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
