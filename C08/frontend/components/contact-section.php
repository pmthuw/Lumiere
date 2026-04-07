<?php
// Contact section component
?>
<section id="contact" style="display:none;">
  <!-- Hero Banner -->
  <div style="
    padding: 80px 0 60px;
    background: linear-gradient(180deg, #050507 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
  ">
    <div style="max-width:1200px; margin:0 auto; padding:0 2rem;
                display:grid; grid-template-columns:1.1fr 0.9fr;
                gap:3rem; align-items:center;">
 
      <!-- Copy bên trái -->
      <div>
        <span class="eyebrow">✦ Connect</span>
        <h1 style="
          font-family:'Playfair Display',serif;
          font-size:clamp(2.6rem,4.5vw,4.2rem);
          line-height:1.06; margin:1rem 0 1.25rem;
        ">Nghệ thuật <em style="color:var(--gold);font-style:italic;">kết nối</em><br>bằng hương thơm.</h1>
        <p style="color:var(--muted);font-size:1rem;line-height:1.85;max-width:480px;margin-bottom:2rem;">
          Gửi yêu cầu hoặc đặt câu hỏi, đội ngũ LUMIERE sẽ tư vấn nhanh và chu đáo nhất.
        </p>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
          <button class="btn btn-gold"
            onclick="document.getElementById('cp-form').scrollIntoView({behavior:'smooth'})">
            Gửi yêu cầu
          </button>
          <button class="btn btn-ghost"
            onclick="document.getElementById('cp-info').scrollIntoView({behavior:'smooth'})">
            Thông tin liên hệ
          </button>
        </div>
      </div>
 
      <!-- Card bên phải -->
      <div style="display:flex;justify-content:center;">
        <div style="
          width:100%;
          max-width:420px;
          height:420px;
          border-radius:2rem;
          overflow:hidden;
          position:relative;
          box-shadow:0 30px 80px rgba(0,0,0,0.35);
        ">
          
          <!-- Ảnh full khung -->
          <img 
            src="./images/hinh22.webp"
            alt="Perfume"
            style="
              width:100%;
              height:100%;
              object-fit:cover;
              display:block;
            "
          />

          <!-- lớp overlay tối để chữ dễ nhìn -->
          <div style="
            position:absolute;
            inset:0;
            background:linear-gradient(
              to top,
              rgba(0,0,0,0.65),
              rgba(0,0,0,0.2)
            );
          "></div>

          <!-- Nội dung chữ -->
          <div style="
            position:absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            padding:2.5rem;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            z-index:2;
          ">
            <span style="
              font-size:0.7rem;
              letter-spacing:0.3em;
              text-transform:uppercase;
              color:var(--gold);
              font-weight:600;
            ">
              LUMIERE
            </span>

            <p style="
              font-family:'Playfair Display',serif;
              font-size:2.2rem;
              line-height:1.3;
              color:#fff;
              font-weight:600;
            ">
              Luxury<br>Fragrance<br>Experience
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Contact Grid -->
  <div style="padding:80px 0 100px;">
    <div style="
      max-width:1200px; margin:0 auto; padding:0 2rem;
      display:grid; grid-template-columns:0.9fr 1.1fr;
      gap:2.5rem; align-items:start;
    ">
 
      <!-- Info Panel -->
      <div id="cp-info" style="
        background:rgba(255,255,255,0.03);
        border:1px solid var(--border);
        border-radius:1.75rem; padding:2.5rem;
        backdrop-filter:blur(10px);
      ">
        <h2 style="font-family:'Playfair Display',serif;font-size:2rem;margin-bottom:0.75rem;">Thông tin liên hệ</h2>
        <p style="color:var(--muted);font-size:0.92rem;line-height:1.8;margin-bottom:2rem;">
          Chúng tôi luôn sẵn sàng hỗ trợ bạn trong mọi nhu cầu nước hoa từ chọn mùi tới đặt hàng nhanh.
        </p>
 
        <ul style="list-style:none;display:flex;flex-direction:column;gap:1.25rem;margin-bottom:2.5rem;">
          <li style="display:flex;align-items:flex-start;gap:1rem;">
            <span style="width:38px;height:38px;min-width:38px;border-radius:0.75rem;background:var(--gold-light);border:1px solid rgba(201,169,110,0.2);display:flex;align-items:center;justify-content:center;color:var(--gold);">✉</span>
            <div style="display:flex;flex-direction:column;">
              <strong style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--gold);margin-bottom:0.2rem;">Email</strong>
              <span style="color:var(--text);font-size:0.9rem;">belynhcts1tg@gmail.com</span>
            </div>
          </li>
          <li style="display:flex;align-items:flex-start;gap:1rem;">
            <span style="width:38px;height:38px;min-width:38px;border-radius:0.75rem;background:var(--gold-light);border:1px solid rgba(201,169,110,0.2);display:flex;align-items:center;justify-content:center;color:var(--gold);">☎</span>
            <div style="display:flex;flex-direction:column;">
              <strong style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--gold);margin-bottom:0.2rem;">Hotline</strong>
              <span style="color:var(--text);font-size:0.9rem;">0909 123 456</span>
            </div>
          </li>
          <li style="display:flex;align-items:flex-start;gap:1rem;">
            <span style="width:38px;height:38px;min-width:38px;border-radius:0.75rem;background:var(--gold-light);border:1px solid rgba(201,169,110,0.2);display:flex;align-items:center;justify-content:center;color:var(--gold);">⌖</span>
            <div style="display:flex;flex-direction:column;">
              <strong style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--gold);margin-bottom:0.2rem;">Địa chỉ</strong>
              <span style="color:var(--text);font-size:0.9rem;">123 Lê Lợi, Quận 1, TP.HCM</span>
            </div>
          </li>
          <li style="display:flex;align-items:flex-start;gap:1rem;">
            <span style="width:38px;height:38px;min-width:38px;border-radius:0.75rem;background:var(--gold-light);border:1px solid rgba(201,169,110,0.2);display:flex;align-items:center;justify-content:center;color:var(--gold);">◷</span>
            <div style="display:flex;flex-direction:column;">
              <strong style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--gold);margin-bottom:0.2rem;">Giờ làm việc</strong>
              <span style="color:var(--text);font-size:0.9rem;">12:00 – 21:00 mỗi ngày</span>
            </div>
          </li>
        </ul>
 
        <!-- Nút quay về -->
        <button class="btn btn-ghost" style="width:100%;justify-content:center;"
          onclick="showSection('home')">
          ← Quay về trang chủ
        </button>
      </div>
 
      <!-- Form Panel -->
      <div id="cp-form" style="
        background:rgba(255,255,255,0.03);
        border:1px solid var(--border);
        border-radius:1.75rem; padding:2.5rem;
        backdrop-filter:blur(10px);
      ">
        <h2 style="font-family:'Playfair Display',serif;font-size:2rem;margin-bottom:0.75rem;">Gửi yêu cầu</h2>
        <p style="color:var(--muted);font-size:0.92rem;line-height:1.8;margin-bottom:2rem;">
          Điền thông tin bên dưới, chúng tôi sẽ phản hồi trong vòng 24 giờ.
        </p>
 
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
 
          <label style="display:flex;flex-direction:column;gap:0.5rem;">
            <span style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);">Họ tên</span>
            <input id="cf-name" type="text" placeholder="Nhập họ tên của bạn"
              style="width:100%;padding:0.9rem 1.1rem;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:0.9rem;color:var(--text);font-family:'Jost',sans-serif;font-size:0.93rem;outline:none;" />
          </label>
 
          <label style="display:flex;flex-direction:column;gap:0.5rem;">
            <span style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);">Email</span>
            <input id="cf-email" type="email" placeholder="Nhập email"
              style="width:100%;padding:0.9rem 1.1rem;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:0.9rem;color:var(--text);font-family:'Jost',sans-serif;font-size:0.93rem;outline:none;" />
          </label>
 
          <label style="display:flex;flex-direction:column;gap:0.5rem;grid-column:1/-1;">
            <span style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);">Số điện thoại</span>
            <input id="cf-phone" type="tel" placeholder="Nhập số điện thoại"
              style="width:100%;padding:0.9rem 1.1rem;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:0.9rem;color:var(--text);font-family:'Jost',sans-serif;font-size:0.93rem;outline:none;" />
          </label>
 
          <label style="display:flex;flex-direction:column;gap:0.5rem;grid-column:1/-1;">
            <span style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);">Yêu cầu của bạn</span>
            <textarea id="cf-message" rows="6" placeholder="Nhập yêu cầu hoặc câu hỏi của bạn"
              style="width:100%;padding:0.9rem 1.1rem;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:0.9rem;color:var(--text);font-family:'Jost',sans-serif;font-size:0.93rem;outline:none;resize:vertical;"></textarea>
          </label>
 
          <div style="grid-column:1/-1;">
            <button id="contact-submit-btn" class="btn btn-gold"
              style="width:100%;padding:1rem;font-size:0.85rem;letter-spacing:0.12em;">
              Gửi thông tin
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
