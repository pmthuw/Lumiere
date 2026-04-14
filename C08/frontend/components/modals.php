<?php
// All modals component
?>
<!-- ══════════ PRODUCT DETAIL MODAL ══════════ -->
<div class="modal-overlay" id="detail-modal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('detail-modal')">
      ✕
    </button>
    <div class="modal-inner">
      <div class="detail-grid" id="detail-content"></div>
    </div>
  </div>
</div>

<!-- ══════════ AUTH MODAL ══════════ -->
<div class="modal-overlay" id="auth-modal">
  <div class="modal" style="max-width: 560px">
    <button class="modal-close" onclick="closeModal('auth-modal')">
      ✕
    </button>
    <div class="modal-inner">
      <div class="auth-tabs">
        <button
          class="auth-tab active"
          id="tab-login"
          onclick="switchAuthTab('login')"
        >
          Đăng nhập
        </button>
        <button
          class="auth-tab"
          id="tab-register"
          onclick="switchAuthTab('register')"
        >
          Đăng ký
        </button>
      </div>

      <!-- Login -->
      <div class="auth-form active" id="form-login">
        <h2>Chào mừng trở lại</h2>
        <p>Đăng nhập để xem giỏ hàng và đặt hàng của bạn.</p>
        <div class="form-group">
          <label>Tên đăng nhập</label>
          <input
            type="text"
            id="login-identifier"
            placeholder="Tên tài khoản để đăng nhập"
          />
        </div>
        <div class="form-group">
          <label>Mật khẩu</label>
          <input
            type="password"
            id="login-password"
            placeholder="••••••••"
          />
        </div>
        <button
          class="btn btn-gold"
          style="width: 100%"
          onclick="doLogin()"
        >
          Đăng nhập
        </button>
        <p
          style="
            text-align: center;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: var(--muted);
          "
        >
          Chưa có tài khoản?
          <a
            href="#"
            style="color: var(--gold)"
            onclick="switchAuthTab('register')"
            >Đăng ký ngay</a
          >
        </p>
      </div>

      <!-- Register -->
      <div class="auth-form" id="form-register">
        <h2>Tạo tài khoản</h2>
        <p>Đăng ký để mua sắm và nhận ưu đãi từ LUMIERE.</p>
        <div class="form-grid">
          <div class="form-group full">
            <label>Tên tài khoản</label>
            <input
              type="text"
              id="reg-username"
              placeholder="Tên tài khoản dùng để đăng nhập"
            />
          </div>
          <div class="form-group full">
            <label>Mật khẩu</label>
            <input
              type="password"
              id="reg-password"
              placeholder="Tối thiểu 6 ký tự"
            />
          </div>
          <div class="form-group">
            <label>Họ</label>
            <input type="text" id="reg-lastname" placeholder="Nguyễn" />
          </div>
          <div class="form-group">
            <label>Tên</label>
            <input type="text" id="reg-firstname" placeholder="Văn A" />
          </div>
          <div class="form-group full">
            <label>Email</label>
            <input
              type="email"
              id="reg-email"
              placeholder="email@example.com"
            />
          </div>
          <div class="form-group full">
            <label>Số điện thoại</label>
            <input
              type="tel"
              id="reg-phone"
              placeholder="09xx xxx xxx"
              pattern="[0-9]{10}"
              required
              title="Số điện thoại phải có đúng 10 chữ số"
            />
          </div>
          <div class="form-group full">
            <label>Địa chỉ giao hàng</label>
            <input
              type="text"
              id="reg-address"
              placeholder="Số nhà, tên đường"
            />
          </div>
          <div class="form-group">
            <label>Phường / Xã</label>
            <select id="reg-ward" required>
              <option value="">Chọn Phường / Xã</option>
            </select>
          </div>
          <div class="form-group">
            <label>Quận / Huyện</label>
            <select id="reg-district" required>
              <option value="">Chọn Quận / Huyện</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tỉnh / Thành phố</label>
            <select id="reg-city" required>
              <option value="">Chọn Tỉnh / Thành phố</option>
            </select>
          </div>
        </div>
        <button
          class="btn btn-gold"
          style="width: 100%; margin-top: 0.5rem"
          onclick="doRegister(event)"
        >
          Đăng ký
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ CART DRAWER ══════════ -->
<div class="backdrop" id="backdrop" onclick="closeCart()"></div>
<div class="cart-drawer" id="cart-drawer">
  <div class="cart-header">
    <h3>Giỏ hàng</h3>
    <button class="icon-btn" onclick="closeCart()">✕</button>
  </div>
  <div class="cart-body" id="cart-body"></div>
  <div class="cart-footer" id="cart-footer"></div>
</div>

<!-- ORDER MODAL -->
<div class="modal-overlay" id="order-modal">
  <div class="modal" style="max-width: 700px">
    <button class="modal-close" onclick="closeModal('order-modal')">
      ✕
    </button>
    <div class="modal-inner">
      <h2>Đơn hàng của tôi</h2>
      <div id="order-list"></div>
    </div>
  </div>
</div>

<!-- BILL MODAL -->
<div class="modal-overlay" id="bill-modal">
  <div class="modal" style="max-width: 700px">
    <button class="modal-close" onclick="closeModal('bill-modal')">
      ✕
    </button>
    <div class="modal-inner">
      <h2>Hóa đơn thanh toán</h2>
      <div id="bill-content"></div>
    </div>
  </div>
</div>

<!-- PROFILE MODAL -->
<div class="modal-overlay" id="profile-modal">
  <div class="modal" style="max-width:620px">
    <button class="modal-close" onclick="closeModal('profile-modal')">✕</button>
    <div class="modal-inner">

      <!-- Avatar header -->
      <div style="display:flex;align-items:center;gap:16px;margin-bottom:1.5rem;
                  padding:1rem 1.25rem;background:rgba(255,255,255,0.04);
                  border:1px solid var(--border);border-radius:1.25rem;">
        <div id="prof-avatar" style="width:56px;height:56px;border-radius:50%;
             background:rgba(201,169,110,0.15);display:flex;align-items:center;
             justify-content:center;font-size:20px;font-weight:600;
             color:var(--gold);flex-shrink:0;"></div>
        <div>
          <p id="prof-name" style="font-size:1rem;font-weight:600;color:var(--text);margin:0 0 2px"></p>
          <span id="prof-email" style="font-size:0.8rem;color:var(--muted)"></span>
        </div>
      </div>

      <!-- Tabs -->
      <div style="display:flex;gap:4px;background:rgba(255,255,255,0.04);
                  border-radius:0.75rem;padding:3px;margin-bottom:1.5rem;">
        <button class="prof-tab active" id="ptab-info" onclick="switchProfTab('info')">Thông tin cá nhân</button>
        <button class="prof-tab" id="ptab-pw" onclick="switchProfTab('pw')">Đổi mật khẩu</button>
      </div>

      <!-- Panel: thông tin -->
      <div id="ppanel-info" class="prof-panel active">
        <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);
                    border-radius:1.25rem;padding:1.25rem;margin-bottom:1rem;">
          <div style="font-size:0.75rem;font-weight:600;color:var(--muted);
                      text-transform:uppercase;letter-spacing:.08em;
                      margin-bottom:1rem;padding-bottom:.75rem;
                      border-bottom:1px solid var(--border);">Thông tin cơ bản</div>
          <div class="form-grid">
            <div class="form-group"><label>Họ</label><input type="text" id="pf-lastname"/></div>
            <div class="form-group"><label>Tên</label><input type="text" id="pf-firstname"/></div>
            <div class="form-group full"><label>Email</label><input type="email" id="pf-email" disabled style="opacity:.5;cursor:not-allowed"/></div>
            <div class="form-group full"><label>Số điện thoại</label><input type="tel" id="pf-phone" maxlength="10"/></div>
          </div>
        </div>
        <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);
                    border-radius:1.25rem;padding:1.25rem;margin-bottom:1rem;">
          <div style="font-size:0.75rem;font-weight:600;color:var(--muted);
                      text-transform:uppercase;letter-spacing:.08em;
                      margin-bottom:1rem;padding-bottom:.75rem;
                      border-bottom:1px solid var(--border);">Địa chỉ giao hàng</div>
          <div class="form-grid">
            <div class="form-group full"><label>Địa chỉ</label><input type="text" id="pf-address"/></div>
            <div class="form-group"><label>Quận / Huyện</label><input type="text" id="pf-district"/></div>
            <div class="form-group">
              <label>Tỉnh / Thành phố</label>
              <select id="pf-city">
                <option value="">Chọn tỉnh / thành</option>
                <option value="TP. HCM">TP. HCM</option><option value="Hà Nội">Hà Nội</option>
                <option value="Đà Nẵng">Đà Nẵng</option><option value="Hải Phòng">Hải Phòng</option>
                <option value="Cần Thơ">Cần Thơ</option><option value="Bình Dương">Bình Dương</option>
                <option value="Đồng Nai">Đồng Nai</option><option value="Khánh Hòa">Khánh Hòa</option>
                <option value="Bà Rịa - Vũng Tàu">Bà Rịa - Vũng Tàu</option>
                <option value="Long An">Long An</option><option value="Lâm Đồng">Lâm Đồng</option>
                <option value="Gia Lai">Gia Lai</option><option value="Nghệ An">Nghệ An</option>
                <option value="Thanh Hóa">Thanh Hóa</option>
                <option value="Thừa Thiên Huế">Thừa Thiên Huế</option>
                <option value="Quảng Nam">Quảng Nam</option>
                <option value="Quảng Ninh">Quảng Ninh</option>
                <option value="Ninh Bình">Ninh Bình</option>
              </select>
            </div>
          </div>
        </div>
        <div id="pf-info-msg"></div>
        <div style="display:flex;gap:8px;margin-top:.5rem">
          <button class="btn btn-ghost" style="flex:1" onclick="cancelProfileEdit()">Hủy thay đổi</button>
          <button class="btn btn-gold" style="flex:1" onclick="saveProfileInfo()">Lưu thông tin</button>
        </div>
      </div>

      <!-- Panel: đổi mật khẩu -->
      <div id="ppanel-pw" class="prof-panel" style="display:none">
        <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);
                    border-radius:1.25rem;padding:1.25rem;margin-bottom:1rem;">
          <div style="font-size:0.75rem;font-weight:600;color:var(--muted);
                      text-transform:uppercase;letter-spacing:.08em;
                      margin-bottom:1rem;padding-bottom:.75rem;
                      border-bottom:1px solid var(--border);">Thay đổi mật khẩu</div>
          <div style="display:flex;flex-direction:column;gap:.9rem">
            <div class="form-group" style="position:relative">
              <label>Mật khẩu hiện tại</label>
              <input type="password" id="pw-old" placeholder="Nhập mật khẩu hiện tại"/>
              <button onclick="togglePwField('pw-old',this)" style="position:absolute;right:10px;bottom:10px;
                background:none;border:none;cursor:pointer;color:var(--muted);font-size:14px">👁</button>
            </div>
            <div class="form-group" style="position:relative">
              <label>Mật khẩu mới</label>
              <input type="password" id="pw-new" placeholder="Tối thiểu 6 ký tự" oninput="updatePwStrength(this.value)"/>
              <button onclick="togglePwField('pw-new',this)" style="position:absolute;right:10px;bottom:10px;
                background:none;border:none;cursor:pointer;color:var(--muted);font-size:14px">👁</button>
              <div id="pw-strength-bar" style="height:3px;border-radius:2px;margin-top:6px;
                   width:0;background:#E24B4A;transition:all .3s"></div>
            </div>
            <div class="form-group" style="position:relative">
              <label>Xác nhận mật khẩu mới</label>
              <input type="password" id="pw-confirm" placeholder="Nhập lại mật khẩu mới"/>
              <button onclick="togglePwField('pw-confirm',this)" style="position:absolute;right:10px;bottom:10px;
                background:none;border:none;cursor:pointer;color:var(--muted);font-size:14px">👁</button>
            </div>
          </div>
        </div>
        <div id="pf-pw-msg"></div>
        <div style="display:flex;gap:8px;margin-top:.5rem">
          <button class="btn btn-ghost" style="flex:1" onclick="clearPwFields()">Xóa trắng</button>
          <button class="btn btn-gold" style="flex:1" onclick="savePassword()">Đổi mật khẩu</button>
        </div>
      </div>

    </div>
  </div>
</div>
