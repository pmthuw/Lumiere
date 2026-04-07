<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LUMIERE | Thanh toán</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Cormorant+Garamond:wght@300;400;600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg: #06060a;
      --surface: rgba(18,18,26,0.97);
      --text: #f0ebe2;
      --muted: #8a8070;
      --gold: #c9a96e;
      --gold-light: rgba(201,169,110,0.13);
      --border: rgba(255,255,255,0.07);
      --radius: 1.5rem;
      --success: #4caf80;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Jost', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ── HEADER ── */
    .site-header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(6,6,10,0.88);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
    }
    .header-inner {
      max-width: 1320px; margin: 0 auto;
      display: flex; align-items: center; justify-content: space-between;
      padding: 1.1rem 2rem; gap: 1rem;
    }
    .brand { font-family: 'Playfair Display', serif; font-size: 1.7rem; letter-spacing: 0.14em; color: var(--gold); text-decoration: none; }
    .back-btn {
      display: flex; align-items: center; gap: 0.55rem;
      background: none; border: 1px solid var(--border);
      color: var(--muted); border-radius: 999px;
      padding: 0.55rem 1.25rem;
      font: 0.8rem 'Jost', sans-serif; letter-spacing: 0.07em; text-transform: uppercase;
      cursor: pointer; transition: all 0.2s;
    }
    .back-btn:hover { border-color: var(--gold); color: var(--gold); }
    .step-bar {
      display: flex; align-items: center; gap: 0.5rem;
      font-size: 0.72rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted);
    }
    .step-bar .step { display: flex; align-items: center; gap: 0.4rem; }
    .step-bar .step.done { color: var(--gold); }
    .step-bar .step.active { color: var(--text); font-weight: 600; }
    .step-bar .sep { color: var(--border); font-size: 0.6rem; }

    /* ── MAIN LAYOUT ── */
    .checkout-wrap {
      max-width: 1320px; margin: 0 auto;
      display: grid; grid-template-columns: 1fr 420px;
      gap: 2rem; padding: 3rem 2rem 5rem;
      align-items: start;
    }

    /* ── SECTION CARD ── */
    .card {
      background: rgba(255,255,255,0.025);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2rem 2.5rem;
      margin-bottom: 1.5rem;
      transition: border-color 0.3s;
    }
    .card:focus-within { border-color: rgba(201,169,110,0.25); }
    .card-title {
      display: flex; align-items: center; gap: 0.9rem;
      font-family: 'Playfair Display', serif; font-size: 1.3rem;
      margin-bottom: 1.75rem;
    }
    .card-num {
      width: 32px; height: 32px; border-radius: 50%;
      background: var(--gold-light); border: 1px solid rgba(201,169,110,0.25);
      color: var(--gold); font-size: 0.75rem; font-weight: 700;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }

    /* ── FORM FIELDS ── */
    .form-row { display: grid; gap: 1rem; margin-bottom: 1rem; }
    .form-row.cols2 { grid-template-columns: 1fr 1fr; }
    .form-row.cols3 { grid-template-columns: 1fr 1fr 1fr; }
    .field { display: flex; flex-direction: column; gap: 0.45rem; }
    .field label {
      font-size: 0.68rem; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--muted);
    }
    .field input, .field select, .field textarea {
      padding: 0.85rem 1.1rem;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border);
      border-radius: 0.85rem;
      color: var(--text);
      font-family: 'Jost', sans-serif; font-size: 0.93rem;
      outline: none; transition: border-color 0.2s, background 0.2s;
      width: 100%;
    }
    .field input:focus, .field select:focus, .field textarea:focus {
      border-color: var(--gold);
      background: rgba(201,169,110,0.05);
    }
    .field input::placeholder { color: var(--muted); }
    .field select option { background: #111; }
    .field input.error { border-color: #e05050; }

    /* ── PAYMENT METHODS ── */
    .pay-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem;
      margin-bottom: 1.25rem;
    }
    .pay-option {
      position: relative; cursor: pointer;
    }
    .pay-option input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
    .pay-label {
      display: flex; align-items: center; gap: 0.85rem;
      padding: 1rem 1.2rem;
      border: 1.5px solid var(--border);
      border-radius: 1.1rem;
      background: rgba(255,255,255,0.025);
      transition: all 0.2s; cursor: pointer;
      user-select: none;
    }
    .pay-option input:checked + .pay-label {
      border-color: var(--gold);
      background: var(--gold-light);
    }
    .pay-icon {
      width: 40px; height: 40px; border-radius: 0.6rem;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.25rem; flex-shrink: 0;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.07);
    }
    .pay-icon img { width: 28px; height: 28px; object-fit: contain; border-radius: 4px; }
    .pay-info { min-width: 0; }
    .pay-name { font-size: 0.88rem; font-weight: 500; color: var(--text); }
    .pay-sub { font-size: 0.72rem; color: var(--muted); margin-top: 0.1rem; }
    .pay-check {
      margin-left: auto; width: 18px; height: 18px; border-radius: 50%;
      border: 2px solid var(--border); flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.2s;
    }
    .pay-option input:checked ~ .pay-label .pay-check {
      border-color: var(--gold); background: var(--gold);
    }
    .pay-option input:checked ~ .pay-label .pay-check::after {
      content: ''; display: block;
      width: 6px; height: 6px; border-radius: 50%; background: #080808;
    }

    /* Bank transfer detail panel */
    .bank-panel {
      display: none; margin-top: 0.75rem;
      padding: 1.25rem 1.5rem;
      background: rgba(201,169,110,0.06);
      border: 1px solid rgba(201,169,110,0.18);
      border-radius: 1rem;
      font-size: 0.88rem; line-height: 2;
      color: var(--muted);
    }
    .bank-panel.show { display: block; }
    .bank-panel strong { color: var(--gold); font-weight: 600; }
    .bank-panel .copy-row {
      display: flex; align-items: center; gap: 0.75rem;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border); border-radius: 0.65rem;
      padding: 0.6rem 1rem; margin-top: 0.5rem;
    }
    .bank-panel .copy-row span { flex: 1; font-weight: 600; color: var(--text); font-size: 0.93rem; }
    .copy-btn {
      background: none; border: none; color: var(--gold);
      cursor: pointer; font-size: 0.75rem; letter-spacing: 0.07em;
      text-transform: uppercase; padding: 0.2rem 0.6rem;
      border-radius: 0.4rem; transition: background 0.2s;
    }
    .copy-btn:hover { background: rgba(201,169,110,0.1); }

    /* ── ORDER SUMMARY (RIGHT PANEL) ── */
    .summary-panel {
      position: sticky; top: 6rem;
    }
    .summary-card {
      background: rgba(255,255,255,0.025);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }
    .summary-header {
      padding: 1.5rem 2rem;
      border-bottom: 1px solid var(--border);
      font-family: 'Playfair Display', serif; font-size: 1.2rem;
    }
    .order-items { padding: 1.25rem 2rem; }
    .order-item {
      display: grid; grid-template-columns: 72px 1fr auto;
      gap: 1rem; align-items: center;
      padding: 0.9rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .order-item:last-child { border-bottom: none; }
    .item-img {
      width: 72px; height: 72px; border-radius: 0.85rem;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border);
      overflow: hidden;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem; opacity: 0.15;
      position: relative;
    }
    .item-img img {
      width: 100%; height: 100%; object-fit: cover;
      border-radius: 0.85rem;
      position: absolute; inset: 0; opacity: 1;
    }
    .item-badge {
      position: absolute; top: 4px; right: 4px;
      background: var(--gold); color: #080808;
      font-size: 0.58rem; font-weight: 800; border-radius: 999px;
      padding: 2px 6px; letter-spacing: 0.05em;
    }
    .item-info { min-width: 0; }
    .item-name { font-size: 0.88rem; font-weight: 500; margin-bottom: 0.2rem; }
    .item-meta { font-size: 0.72rem; color: var(--muted); }
    .item-qty {
      display: inline-block; margin-top: 0.3rem;
      background: rgba(255,255,255,0.05);
      border-radius: 999px; padding: 0.18rem 0.65rem;
      font-size: 0.72rem; color: var(--muted);
    }
    .item-price { font-size: 0.9rem; color: var(--gold); font-weight: 600; white-space: nowrap; }

    .summary-totals {
      padding: 1.25rem 2rem;
      border-top: 1px solid var(--border);
    }
    .total-row {
      display: flex; justify-content: space-between;
      align-items: center; padding: 0.45rem 0;
      font-size: 0.88rem; color: var(--muted);
    }
    .total-row.grand {
      padding-top: 1rem; margin-top: 0.5rem;
      border-top: 1px solid var(--border);
      font-family: 'Playfair Display', serif;
      font-size: 1.35rem; color: var(--gold);
    }
    .total-row.grand span:last-child { font-weight: 700; }

    /* ── CONFIRM BUTTON ── */
    .confirm-area { padding: 1.25rem 2rem 2rem; }
    .btn-confirm {
      width: 100%; padding: 1.15rem;
      background: var(--gold); color: #080808;
      border: none; border-radius: 999px;
      font: 600 0.85rem 'Jost', sans-serif;
      letter-spacing: 0.12em; text-transform: uppercase;
      cursor: pointer; transition: all 0.25s;
      display: flex; align-items: center; justify-content: center; gap: 0.6rem;
    }
    .btn-confirm:hover:not(:disabled) { background: #d9bc82; transform: translateY(-2px); box-shadow: 0 12px 36px rgba(201,169,110,0.25); }
    .btn-confirm:disabled { opacity: 0.55; cursor: default; transform: none; }
    .btn-confirm .spinner {
      width: 16px; height: 16px; border-radius: 50%;
      border: 2px solid rgba(0,0,0,0.2); border-top-color: #080808;
      animation: spin 0.7s linear infinite; display: none;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .secure-note {
      text-align: center; margin-top: 0.85rem;
      font-size: 0.75rem; color: var(--muted);
      display: flex; align-items: center; justify-content: center; gap: 0.4rem;
    }

    /* ── SUCCESS OVERLAY ── */
    .success-overlay {
      position: fixed; inset: 0; z-index: 400;
      background: rgba(6,6,10,0.96);
      backdrop-filter: blur(20px);
      display: flex; align-items: center; justify-content: center;
      flex-direction: column; gap: 1.5rem;
      opacity: 0; pointer-events: none; transition: opacity 0.4s;
      padding: 2rem; text-align: center;
    }
    .success-overlay.show { opacity: 1; pointer-events: all; }
    .success-ring {
      width: 90px; height: 90px; border-radius: 50%;
      border: 2px solid var(--gold);
      display: flex; align-items: center; justify-content: center;
      font-size: 2.4rem;
      animation: pulse-ring 1.5s ease-out;
    }
    @keyframes pulse-ring {
      0% { transform: scale(0.5); opacity: 0; }
      60% { transform: scale(1.05); opacity: 1; }
      100% { transform: scale(1); }
    }
    .success-overlay h2 { font-family: 'Playfair Display', serif; font-size: 2.2rem; }
    .success-overlay p { color: var(--muted); font-size: 0.95rem; max-width: 400px; line-height: 1.8; }
    .order-code {
      background: var(--gold-light); border: 1px solid rgba(201,169,110,0.25);
      border-radius: 0.85rem; padding: 0.85rem 2rem;
      font-size: 1.05rem; color: var(--gold); font-weight: 600; letter-spacing: 0.08em;
    }
    .success-btns { display: flex; gap: 0.85rem; flex-wrap: wrap; justify-content: center; margin-top: 0.5rem; }
    .btn { display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: 0.9rem 2rem; border: 1px solid transparent; cursor: pointer; font: 500 0.82rem/1 'Jost', sans-serif; letter-spacing: 0.08em; text-transform: uppercase; transition: all 0.25s ease; text-decoration: none; }
    .btn-gold { background: var(--gold); color: #080808; border-color: var(--gold); }
    .btn-gold:hover { background: #d9bc82; }
    .btn-ghost { border-color: rgba(255,255,255,0.15); color: var(--text); background: transparent; }
    .btn-ghost:hover { border-color: var(--gold); color: var(--gold); }

    /* ── TOAST ── */
    .toast {
      position: fixed; bottom: 2rem; left: 50%;
      transform: translateX(-50%) translateY(100px);
      background: #1a1a22; border: 1px solid var(--border);
      color: var(--text); padding: 0.85rem 1.75rem; border-radius: 999px;
      font: 0.88rem 'Jost', sans-serif; z-index: 500;
      transition: transform 0.3s ease; white-space: nowrap;
    }
    .toast.gold { background: var(--gold); color: #080808; border-color: var(--gold); font-weight: 600; }
    .toast.show { transform: translateX(-50%) translateY(0); }

    /* ── DECORATIVE LINES ── */
    .deco-line {
      height: 1px; background: linear-gradient(90deg, transparent, var(--border) 30%, var(--border) 70%, transparent);
      margin: 0.25rem 0 1.75rem;
    }

    @media (max-width: 1000px) {
      .checkout-wrap { grid-template-columns: 1fr; }
      .summary-panel { position: static; }
    }
    @media (max-width: 680px) {
      .form-row.cols2, .form-row.cols3 { grid-template-columns: 1fr; }
      .pay-grid { grid-template-columns: 1fr; }
      .header-inner { padding: 1rem; }
      .checkout-wrap { padding: 1.5rem 1rem 4rem; }
      .card { padding: 1.5rem 1.25rem; }
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
  <div class="header-inner">
    <a class="brand" href="index.php">LUMIERE</a>
    <div class="step-bar">
      
      <span class="step active">Thanh toán</span>
    </div>
    <button class="back-btn" onclick="history.back()">
      ← Quay lại giỏ hàng
    </button>
  </div>
</header>

<!-- MAIN -->
<main>
  <div class="checkout-wrap">

    <!-- LEFT: FORM -->
    <div class="left-col">

      <!-- 1. THÔNG TIN NGƯỜI NHẬN -->
      <div class="card" id="card-info">
        <div class="card-title">
          <span class="card-num">1</span>
          Thông tin người nhận
        </div>

        <div class="form-row cols2">
          <div class="field">
            <label>Họ</label>
            <input type="text" id="f-lastname" placeholder="Nguyễn" />
          </div>
          <div class="field">
            <label>Tên</label>
            <input type="text" id="f-firstname" placeholder="Văn A" />
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label>Số điện thoại</label>
            <input type="tel" id="f-phone" placeholder="09xx xxx xxx" />
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label>Email (nhận xác nhận đơn hàng)</label>
            <input type="email" id="f-email" placeholder="email@example.com" />
          </div>
        </div>
      </div>

      <!-- 2. ĐỊA CHỈ GIAO HÀNG -->
      <div class="card" id="card-addr">
        <div class="card-title">
          <span class="card-num">2</span>
          Địa chỉ giao hàng
        </div>

        <div class="form-row">
          <div class="field">
            <label>Số nhà, tên đường</label>
            <input type="text" id="f-address" placeholder="123 Lê Lợi" />
          </div>
        </div>
        <div class="form-row cols3">
          <div class="field">
            <label>Phường / Xã</label>
            <input type="text" id="f-ward" placeholder="Phường Bến Nghé" />
          </div>
          <div class="field">
            <label>Quận / Huyện</label>
            <input type="text" id="f-district" placeholder="Quận 1" />
          </div>
          <div class="field">
            <label>Tỉnh / Thành phố</label>
            <select id="f-city">
              <option value="">Chọn Tỉnh / Thành phố</option>
              <option value="TP. HCM">TP. HCM</option>
              <option value="Hà Nội">Hà Nội</option>
              <option value="Đà Nẵng">Đà Nẵng</option>
              <option value="Hải Phòng">Hải Phòng</option>
              <option value="Cần Thơ">Cần Thơ</option>
              <option value="Bình Dương">Bình Dương</option>
              <option value="Đồng Nai">Đồng Nai</option>
              <option value="Khánh Hòa">Khánh Hòa</option>
              <option value="Bà Rịa - Vũng Tàu">Bà Rịa - Vũng Tàu</option>
              <option value="Long An">Long An</option>
              <option value="Đồng Tháp">Đồng Tháp</option>
              <option value="Tiền Giang">Tiền Giang</option>
              <option value="Vĩnh Long">Vĩnh Long</option>
              <option value="Bến Tre">Bến Tre</option>
              <option value="Tây Ninh">Tây Ninh</option>
              <option value="Bình Phước">Bình Phước</option>
              <option value="Bình Định">Bình Định</option>
              <option value="Quảng Nam">Quảng Nam</option>
              <option value="Nghệ An">Nghệ An</option>
              <option value="Thanh Hóa">Thanh Hóa</option>
              <option value="Thừa Thiên Huế">Thừa Thiên Huế</option>
              <option value="Quảng Ninh">Quảng Ninh</option>
              <option value="Ninh Bình">Ninh Bình</option>
              <option value="Nam Định">Nam Định</option>
              <option value="Hưng Yên">Hưng Yên</option>
              <option value="Hải Dương">Hải Dương</option>
              <option value="Bắc Ninh">Bắc Ninh</option>
              <option value="Phú Thọ">Phú Thọ</option>
              <option value="Lâm Đồng">Lâm Đồng</option>
              <option value="Hà Tĩnh">Hà Tĩnh</option>
              <option value="Quảng Ngãi">Quảng Ngãi</option>
              <option value="Ninh Thuận">Ninh Thuận</option>
              <option value="Cà Mau">Cà Mau</option>
              <option value="Kon Tum">Kon Tum</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label>Ghi chú giao hàng (không bắt buộc)</label>
            <input type="text" id="f-note" placeholder="Giao giờ hành chính, gọi trước khi giao…" />
          </div>
        </div>
      </div>

      <!-- 3. PHƯƠNG THỨC THANH TOÁN -->
      <div class="card" id="card-pay">
        <div class="card-title">
          <span class="card-num">3</span>
          Phương thức thanh toán
        </div>

        <div class="pay-grid">

          <label class="pay-option">
            <input type="radio" name="payment" value="cod" checked onchange="onPayChange(this)" />
            <div class="pay-label">
              
              <div class="pay-info">
                <div class="pay-name">Thanh toán khi nhận hàng</div>
                <div class="pay-sub">COD · Miễn phí</div>
              </div>
              <div class="pay-check"></div>
            </div>
          </label>

          <label class="pay-option">
            <input type="radio" name="payment" value="momo" onchange="onPayChange(this)" />
            <div class="pay-label">
              
              <div class="pay-info">
                <div class="pay-name">MoMo</div>
                <div class="pay-sub">Ví điện tử</div>
              </div>
              <div class="pay-check"></div>
            </div>
          </label>

          <label class="pay-option">
            <input type="radio" name="payment" value="vnpay" onchange="onPayChange(this)" />
            <div class="pay-label">
              
              <div class="pay-info">
                <div class="pay-name">VNPay QR</div>
                <div class="pay-sub">Ngân hàng nội địa</div>
              </div>
              <div class="pay-check"></div>
            </div>
          </label>

          <label class="pay-option">
            <input type="radio" name="payment" value="zalopay" onchange="onPayChange(this)" />
            <div class="pay-label">
              
              <div class="pay-info">
                <div class="pay-name">ZaloPay</div>
                <div class="pay-sub">Ví điện tử</div>
              </div>
              <div class="pay-check"></div>
            </div>
          </label>

          <label class="pay-option">
            <input type="radio" name="payment" value="banking" onchange="onPayChange(this)" />
            <div class="pay-label">
              
              <div class="pay-info">
                <div class="pay-name">Chuyển khoản ngân hàng</div>
                <div class="pay-sub">Vietcombank · ACB · Techcombank</div>
              </div>
              <div class="pay-check"></div>
            </div>
          </label>

          <label class="pay-option">
            <input type="radio" name="payment" value="card" onchange="onPayChange(this)" />
            <div class="pay-label">
              
              <div class="pay-info">
                <div class="pay-name">Thẻ tín dụng / ghi nợ</div>
                <div class="pay-sub">Visa · Mastercard · JCB</div>
              </div>
              <div class="pay-check"></div>
            </div>
          </label>

        </div>

        <!-- COD note -->
        <div class="bank-panel show" id="panel-cod">
          Bạn sẽ thanh toán trực tiếp cho nhân viên giao hàng. Vui lòng chuẩn bị đúng số tiền để thuận tiện giao nhận.
        </div>

        <!-- Banking detail -->
        <div class="bank-panel" id="panel-banking">
          <div style="margin-bottom:0.75rem;">Chuyển khoản đến tài khoản ngân hàng của LUMIERE:</div>
          <div><strong>Ngân hàng:</strong> Vietcombank — Chi nhánh Q.1 TP.HCM</div>
          <div><strong>Tên tài khoản:</strong> CÔNG TY TNHH LUMIERE VIETNAM</div>
          <div class="copy-row">
            <span id="bank-acct">1234 5678 9012 3456</span>
            <button class="copy-btn" onclick="copyText('1234567890123456', this)">Sao chép</button>
          </div>
          <div style="margin-top:0.6rem;font-size:0.8rem;color:var(--muted);">
            Nội dung chuyển khoản: <strong style="color:var(--gold);" id="transfer-note">LUMIERE-ORDER</strong>
          </div>
        </div>

        <!-- MoMo -->
        <div class="bank-panel" id="panel-momo">
          Quét mã QR MoMo hoặc chuyển đến số <strong>0909 123 456</strong> (LUMIERE Store). Đơn hàng sẽ được xử lý sau khi xác nhận thanh toán.
        </div>

        <!-- VNPay -->
        <div class="bank-panel" id="panel-vnpay">
          Sau khi đặt hàng, bạn sẽ được chuyển đến trang VNPay để quét mã QR thanh toán an toàn qua ngân hàng nội địa.
        </div>

        <!-- ZaloPay -->
        <div class="bank-panel" id="panel-zalopay">
          Mở ứng dụng ZaloPay, chọn "Quét mã" và quét mã QR sẽ hiển thị sau khi đặt hàng. Thanh toán nhanh trong 60 giây.
        </div>

        <!-- Card -->
        <div class="bank-panel" id="panel-card">
          <div style="margin-bottom:1rem;">Nhập thông tin thẻ thanh toán:</div>
          <div class="form-row" style="margin-bottom:0.85rem;">
            <div class="field">
              <label>Số thẻ</label>
              <input type="text" placeholder="xxxx xxxx xxxx xxxx" maxlength="19" oninput="fmtCard(this)" />
            </div>
          </div>
          <div class="form-row cols2" style="margin-bottom:0;">
            <div class="field">
              <label>Ngày hết hạn</label>
              <input type="text" placeholder="MM / YY" maxlength="7" oninput="fmtExpiry(this)" />
            </div>
            <div class="field">
              <label>CVV</label>
              <input type="password" placeholder="•••" maxlength="4" />
            </div>
          </div>
        </div>

      </div><!-- /card-pay -->

    </div><!-- /left-col -->

    <!-- RIGHT: ORDER SUMMARY -->
    <div class="summary-panel">
      <div class="summary-card">
        <div class="summary-header">Đơn hàng của bạn</div>
        <div class="order-items" id="order-items">
          <!-- rendered by JS -->
        </div>
        <div class="summary-totals">
          <div class="total-row"><span>Tạm tính</span><span id="subtotal-val">—</span></div>
          <div class="total-row"><span>Phí giao hàng</span><span style="color:var(--success);">Miễn phí</span></div>
          <div class="total-row grand"><span>Tổng cộng</span><span id="grand-val">—</span></div>
        </div>
        <div class="confirm-area">
          <button class="btn-confirm" id="confirm-btn" onclick="placeOrder()">
            <span class="spinner" id="spinner"></span>
            <span id="confirm-text">Xác nhận đặt hàng →</span>
          </button>
          <div class="secure-note">🔒 Thanh toán được bảo mật &amp; mã hóa SSL</div>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- SUCCESS OVERLAY -->
<div class="success-overlay" id="success-overlay">
  <div class="success-ring">✓</div>
  <h2>Đặt hàng thành công!</h2>
  <p>Cảm ơn bạn đã tin tưởng LUMIERE. Đơn hàng của bạn đang được xử lý và sẽ được giao sớm nhất có thể.</p>
  <div class="order-code" id="success-order-code">#LUM-000000</div>
  <p style="font-size:0.8rem;color:var(--muted);">Email xác nhận đã được gửi đến hộp thư của bạn.</p>
  <div class="success-btns">
    <a href="index.php" class="btn btn-gold">Tiếp tục mua sắm</a>
    <a href="index.php" class="btn btn-ghost">Xem đơn hàng</a>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
// ── LOAD DATA FROM LOCALSTORAGE ──────────────────────────────────────────────
const PRODUCTS = [
  { id:1, name:"Chanel No.5", category:"Nữ", price:7200000, brand:"Chanel", badge:"Bestseller", notes:"Hoa cỏ aldehyde", concentration:"Eau de Parfum", size:"100ml", image:"images/hinh1.jpg" },
  { id:2, name:"Dior Sauvage", category:"Nam", price:8800000, brand:"Dior", badge:"Hot", notes:"Fougère Woody", concentration:"Eau de Toilette", size:"100ml", image:"images/hinh3.jpg" },
  { id:3, name:"Tom Ford Black Orchid", category:"Unisex", price:6600000, brand:"Tom Ford", badge:"", notes:"Oriental Floral", concentration:"Eau de Parfum", size:"50ml", image:"images/hinh4.jpg" },
  { id:4, name:"YSL Black Opium", category:"Nữ", price:8400000, brand:"YSL", badge:"New", notes:"Oriental Floral", concentration:"Eau de Parfum", size:"90ml", image:"images/hinh5.jpg" },
  { id:5, name:"Creed Aventus", category:"Nam", price:14500000, brand:"Creed", badge:"Luxury", notes:"Fruity Chypre", concentration:"Eau de Parfum", size:"100ml", image:"images/hinh6.jpg" },
  { id:6, name:"Jo Malone Peony", category:"Unisex", price:8200000, brand:"Jo Malone", badge:"", notes:"Floral Fruity", concentration:"Cologne", size:"100ml", image:"images/hinh7.jpg" },
  { id:7, name:"Versace Eros", category:"Nam", price:10800000, brand:"Versace", badge:"", notes:"Oriental Fougère", concentration:"Eau de Toilette", size:"100ml", image:"images/hinh8.jpg" },
  { id:8, name:"Gucci Bloom", category:"Nữ", price:9800000, brand:"Gucci", badge:"", notes:"Floral", concentration:"Eau de Parfum", size:"100ml", image:"images/hinh9.jpg" },
  { id:9, name:"Maison Margiela Replica", category:"Unisex", price:8900000, brand:"Maison Margiela", badge:"Limited", notes:"Woody Floral Musk", concentration:"Eau de Toilette", size:"100ml", image:"images/hinh10.jpg" },
  { id:10, name:"Hermès Terre", category:"Nam", price:9200000, brand:"Hermès", badge:"", notes:"Woody Citrus", concentration:"Eau de Toilette", size:"75ml", image:"images/hinh11.jpg" },
  { id:11, name:"Lancôme La Vie Est Belle", category:"Nữ", price:11100000, brand:"Lancôme", badge:"", notes:"Oriental Floral", concentration:"Eau de Parfum", size:"75ml", image:"images/hinh12.jpg" },
  { id:12, name:"Kilian Angel Share", category:"Unisex", price:9800000, brand:"Kilian", badge:"Limited", notes:"Oriental Woody", concentration:"Eau de Parfum", size:"50ml", image:"images/hinh13.jpg" },
  { id:13, name:"Million Elixir", category:"Limited", price:9800000, brand:"Milion", badge:"Limited", notes:"Amber Oud", concentration:"Extrait de Parfum", size:"50ml", image:"images/hinh14.jpg" },
  { id:14, name:"Attrape-Rêves", category:"Limited", price:13350000, brand:"Attrape", badge:"Limited", notes:"Floral Fruity Gourmand", concentration:"Eau de Parfum", size:"100ml", image:"images/hinh15.jpg" },
];

const cart  = JSON.parse(localStorage.getItem('lum_cart')  || '[]');
const user  = JSON.parse(localStorage.getItem('lum_user')  || 'null');

function fmt(n){ return n.toLocaleString('vi-VN') + '₫'; }

// ── PRE-FILL FORM ────────────────────────────────────────────────────────────
if (user) {
  document.getElementById('f-lastname').value  = user.lastname  || '';
  document.getElementById('f-firstname').value = user.firstname || '';
  document.getElementById('f-phone').value     = user.phone     || '';
  document.getElementById('f-email').value     = user.email     || '';
  document.getElementById('f-address').value   = user.address   || '';
  document.getElementById('f-district').value  = user.district  || '';
  document.getElementById('f-city').value      = user.city      || '';
}

// ── RENDER ORDER ITEMS ───────────────────────────────────────────────────────
(function renderItems(){
  const wrap = document.getElementById('order-items');
  if (!cart.length) {
    wrap.innerHTML = '<p style="color:var(--muted);text-align:center;padding:1.5rem;">Giỏ hàng trống</p>';
    return;
  }
  let subtotal = 0;
  wrap.innerHTML = cart.map(item => {
    const p = PRODUCTS.find(x => x.id === item.id);
    if (!p) return '';
    subtotal += p.price * item.qty;
    return `
      <div class="order-item">
        <div class="item-img">
          ◈
          ${p.image ? `<img src="${p.image}" alt="${p.name}" onerror="this.style.display='none'">` : ''}
          ${p.badge ? `<span class="item-badge">${p.badge}</span>` : ''}
        </div>
        <div class="item-info">
          <div class="item-name">${p.name}</div>
          <div class="item-meta">${p.brand} · ${p.concentration} · ${p.size}</div>
          <div class="item-meta" style="margin-top:0.2rem;">${p.notes}</div>
          <span class="item-qty">SL: ${item.qty}</span>
        </div>
        <div class="item-price">${fmt(p.price * item.qty)}</div>
      </div>`;
  }).join('');

  document.getElementById('subtotal-val').textContent = fmt(subtotal);
  document.getElementById('grand-val').textContent    = fmt(subtotal);

  // dynamic transfer note
  document.getElementById('transfer-note').textContent = 'LUMIERE-' + Date.now().toString().slice(-6);
})();

// ── PAYMENT SWITCH ───────────────────────────────────────────────────────────
const allPanels = ['cod','banking','momo','vnpay','zalopay','card'];
function onPayChange(radio) {
  allPanels.forEach(k => {
    const el = document.getElementById('panel-' + k);
    if (el) el.classList.toggle('show', k === radio.value);
  });
}

// ── CARD FORMATTING ──────────────────────────────────────────────────────────
function fmtCard(inp) {
  let v = inp.value.replace(/\D/g, '').slice(0,16);
  inp.value = v.replace(/(.{4})/g, '$1 ').trim();
}
function fmtExpiry(inp) {
  let v = inp.value.replace(/\D/g, '').slice(0,4);
  if (v.length >= 3) v = v.slice(0,2) + ' / ' + v.slice(2);
  inp.value = v;
}

// ── COPY ACCOUNT NUMBER ──────────────────────────────────────────────────────
function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.textContent;
    btn.textContent = '✓ Đã sao chép';
    setTimeout(() => { btn.textContent = orig; }, 2000);
  });
}

// ── VALIDATION + ORDER ───────────────────────────────────────────────────────
function placeOrder() {
  const fields = [
    ['f-lastname',  'Họ'],
    ['f-firstname', 'Tên'],
    ['f-phone',     'Số điện thoại'],
    ['f-email',     'Email'],
    ['f-address',   'Địa chỉ'],
    ['f-district',  'Quận / Huyện'],
    ['f-city',      'Tỉnh / Thành phố'],
  ];

  let ok = true;
  fields.forEach(([id, label]) => {
    const el = document.getElementById(id);
    const val = el.value.trim();
    el.classList.remove('error');
    if (!val) {
      el.classList.add('error');
      ok = false;
    }
  });

  const phone = document.getElementById('f-phone').value.trim();
  if (phone && !/^[0-9]{10}$/.test(phone.replace(/\s/g,''))) {
    document.getElementById('f-phone').classList.add('error');
    showToast('Số điện thoại không hợp lệ!');
    return;
  }

  if (!ok) { showToast('Vui lòng điền đầy đủ thông tin!'); return; }

  // Simulate processing
  const btn = document.getElementById('confirm-btn');
  const spinner = document.getElementById('spinner');
  const txt = document.getElementById('confirm-text');
  btn.disabled = true;
  spinner.style.display = 'block';
  txt.textContent = 'Đang xử lý…';

  setTimeout(async () => {
    // Save order to backend database
    const payMethod = document.querySelector('input[name=payment]:checked').value;
    const payLabels = { cod:'Thanh toán khi nhận hàng', momo:'MoMo', vnpay:'VNPay QR', zalopay:'ZaloPay', banking:'Chuyển khoản ngân hàng', card:'Thẻ tín dụng / ghi nợ' };
    const subtotal = cart.reduce((s, item) => {
      const p = PRODUCTS.find(x => x.id === item.id);
      return s + (p ? p.price * item.qty : 0);
    }, 0);

    const payload = {
      customer_name: document.getElementById('f-lastname').value.trim() + ' ' + document.getElementById('f-firstname').value.trim(),
      customer_phone: phone,
      customer_email: document.getElementById('f-email').value.trim(),
      shipping_address: document.getElementById('f-address').value.trim(),
      ward: document.getElementById('f-ward').value.trim(),
      district: document.getElementById('f-district').value.trim(),
      city: document.getElementById('f-city').value.trim(),
      notes: document.getElementById('f-note').value.trim(),
      payment_method: payLabels[payMethod],
      user_email: user ? user.email : '',
      items: cart.map(item => ({ id: item.id, qty: item.qty })),
      total_amount: subtotal
    };

    try {
      const res = await fetch('../backend/api/place_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Không thể tạo đơn hàng');
      }

      // Clear cart
      localStorage.setItem('lum_cart', '[]');

      // Show success
      document.getElementById('success-order-code').textContent = '#' + data.order_number;
      document.getElementById('success-overlay').classList.add('show');
    } catch (err) {
      btn.disabled = false;
      spinner.style.display = 'none';
      txt.textContent = 'Xác nhận đặt hàng →';
      showToast(err.message || 'Đặt hàng thất bại, vui lòng thử lại!');
    }
  }, 1800);
}

// ── TOAST ────────────────────────────────────────────────────────────────────
function showToast(msg, gold) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast' + (gold ? ' gold' : '') + ' show';
  setTimeout(() => { t.className = 'toast' + (gold ? ' gold' : ''); }, 3000);
}
</script>
</body>
</html>