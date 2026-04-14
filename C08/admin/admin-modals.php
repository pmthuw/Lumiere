    <!-- ---------- PRODUCT MODAL ---------- -->
    <div class="modal-overlay" id="product-modal">
      <div class="modal" style="max-width: 700px">
        <div class="modal-inner">
          <div class="modal-header">
            <h2 id="product-modal-title">Th�m s?n ph?m</h2>
            <button class="modal-close" onclick="closeModal('product-modal')">
              ?
            </button>
          </div>
          <input type="hidden" id="edit-prod-id" />
          <div class="form-row form-row-2">
            <div class="form-group">
              <label>Mã s?n ph?m</label>
              <input
                class="form-control"
                id="prod-code"
                type="text"
                placeholder="SP001"
              />
            </div>
            <div class="form-group">
              <label>Tên s?n ph?m</label>
              <input
                class="form-control"
                id="prod-name"
                type="text"
                placeholder="Chanel No.5"
              />
            </div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label>Thuong hi?u</label>
              <input
                class="form-control"
                id="prod-brand"
                type="text"
                placeholder="Chanel"
              />
            </div>
            <div class="form-group">
              <label>Nhà cung c?p</label>
              <input
                class="form-control"
                id="prod-supplier"
                type="text"
                placeholder="Chanel SA"
              />
            </div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label>Lo?i s?n ph?m</label>
              <select class="form-control" id="prod-category"></select>
            </div>
            <div class="form-group">
              <label>N?ng d?</label>
              <select class="form-control" id="prod-concentration">
                <option>Eau de Parfum</option>
                <option>Eau de Toilette</option>
                <option>Cologne</option>
                <option>Extrait de Parfum</option>
              </select>
            </div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label>Giá ni�m y?t (VNĐ)</label>
              <input
                class="form-control"
                id="prod-price"
                type="number"
                placeholder="7200000"
              />
            </div>
            <div class="form-group">
              <label>Dung t�ch</label>
              <input
                class="form-control"
                id="prod-size"
                type="text"
                placeholder="100ml"
              />
            </div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label>S? lu?ng ban d?u</label>
              <input
                class="form-control"
                id="prod-stock"
                type="number"
                placeholder="0"
                min="0"
              />
            </div>
            <div class="form-group">
              <label>T? l? l?i nhu?n mong mu?n (%)</label>
              <input
                class="form-control"
                id="prod-profit"
                type="number"
                placeholder="25"
                min="0"
                max="500"
              />
            </div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label>Badge</label>
              <select class="form-control" id="prod-badge">
                <option value="">Khàng c�</option>
                <option value="Bestseller">Bestseller</option>
                <option value="Hot">Hot</option>
                <option value="New">New</option>
                <option value="Giới hạn">Giới hạn</option>
                <option value="Luxury">Luxury</option>
              </select>
            </div>
            <div class="form-group">
              <label>Hi?n tr?ng</label>
              <select class="form-control" id="prod-status">
                <option value="active">Hi?n th? (dang b�n)</option>
                <option value="hidden">?n (không b�n)</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Mã t?</label>
              <textarea
                class="form-control"
                id="prod-desc"
                placeholder="Mã t? huong thom..."
              ></textarea>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>H�nh ?nh (du?ng d?n)</label>
              <div style="display: flex; gap: 0.75rem; align-items: center">
                <input
                  class="form-control"
                  id="prod-image-val"
                  type="text"
                  placeholder="../frontend/images/hinh1.jpg"
                  oninput="updateImgPreview()"
                />
                <button
                  class="btn btn-ghost btn-sm"
                  onclick="clearProductImage()"
                  title="B? h�nh"
                >
                  ?
                </button>
              </div>
              <img
                id="prod-img-preview"
                src=""
                alt="Preview"
                style="
                  display: none;
                  width: 100px;
                  height: 100px;
                  object-fit: cover;
                  border-radius: 0.75rem;
                  margin-top: 0.75rem;
                  border: 1px solid var(--border);
                "
              />
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('product-modal')">
              H?y
            </button>
            <button class="btn btn-gold" onclick="saveProduct()">
              Luu s?n ph?m
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ---------- RECEIPT MODAL ---------- -->
    <div class="modal-overlay" id="receipt-modal">
      <div class="modal" style="max-width: 780px">
        <div class="modal-inner">
          <div class="modal-header">
            <h2 id="receipt-modal-title">L?p phi?u nh?p hàng</h2>
            <button class="modal-close" onclick="closeModal('receipt-modal')">
              ?
            </button>
          </div>
          <div
            style="
              display: grid;
              grid-template-columns: repeat(3, 1fr);
              gap: 1rem;
              margin-bottom: 1.5rem;
            "
          >
            <div
              style="
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid var(--border);
                border-radius: 0.9rem;
                padding: 0.9rem 1rem;
              "
            >
              <div
                class="td-muted"
                style="
                  font-size: 0.7rem;
                  letter-spacing: 0.1em;
                  text-transform: uppercase;
                "
              >
                Mã phi?u
              </div>
              <div
                class="td-gold"
                id="receipt-id-display"
                style="font-weight: 600; margin-top: 0.2rem"
              ></div>
            </div>
            <div
              style="
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid var(--border);
                border-radius: 0.9rem;
                padding: 0.9rem 1rem;
              "
            >
              <div
                class="td-muted"
                style="
                  font-size: 0.7rem;
                  letter-spacing: 0.1em;
                  text-transform: uppercase;
                "
              >
                Ng�y nh?p
              </div>
              <div id="receipt-date-display" style="margin-top: 0.2rem"></div>
            </div>
            <div
              style="
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid var(--border);
                border-radius: 0.9rem;
                padding: 0.9rem 1rem;
              "
            >
              <div
                class="td-muted"
                style="
                  font-size: 0.7rem;
                  letter-spacing: 0.1em;
                  text-transform: uppercase;
                "
              >
                Tr?ng th�i
              </div>
              <div id="receipt-status-display" style="margin-top: 0.2rem"></div>
            </div>
          </div>
          <div
            id="receipt-add-row"
            style="
              display: flex;
              gap: 0.75rem;
              align-items: flex-end;
              margin-bottom: 1.25rem;
              flex-wrap: wrap;
              background: rgba(201, 169, 110, 0.06);
              border: 1px solid var(--border-gold);
              border-radius: 1rem;
              padding: 1.25rem;
            "
          >
            <div style="flex: 2; min-width: 200px">
              <div
                class="td-muted"
                style="
                  font-size: 0.7rem;
                  letter-spacing: 0.1em;
                  text-transform: uppercase;
                  margin-bottom: 0.4rem;
                "
              >
                S?n ph?m
              </div>
              <select class="form-control" id="receipt-prod-select"></select>
            </div>
            <div style="flex: 0 0 90px">
              <div
                class="td-muted"
                style="
                  font-size: 0.7rem;
                  letter-spacing: 0.1em;
                  text-transform: uppercase;
                  margin-bottom: 0.4rem;
                "
              >
                S? lu?ng
              </div>
              <input
                class="form-control"
                id="receipt-qty-input"
                type="number"
                min="1"
                placeholder="10"
              />
            </div>
            <div style="flex: 0 0 140px">
              <div
                class="td-muted"
                style="
                  font-size: 0.7rem;
                  letter-spacing: 0.1em;
                  text-transform: uppercase;
                  margin-bottom: 0.4rem;
                "
              >
                Giá nh?p (VNĐ)
              </div>
              <input
                class="form-control"
                id="receipt-cost-input"
                type="number"
                min="0"
                placeholder="5000000"
              />
            </div>
            <button
              class="btn btn-gold"
              onclick="addReceiptItem()"
              style="flex-shrink: 0"
            >
              + Th�m
            </button>
          </div>
          <div
            style="
              border: 1px solid var(--border);
              border-radius: 0.9rem;
              overflow: hidden;
              margin-bottom: 1rem;
            "
          >
            <table>
              <thead>
                <tr>
                  <th>S?n ph?m</th>
                  <th>Mã</th>
                  <th>S? lu?ng</th>
                  <th>Giá nh?p</th>
                  <th>Th�nh ti?n</th>
                  <th id="receipt-remove-col"></th>
                </tr>
              </thead>
              <tbody id="receipt-items-body"></tbody>
            </table>
          </div>
          <div
            style="
              display: flex;
              justify-content: flex-end;
              margin-bottom: 1rem;
            "
          >
            <div
              style="
                font-family: 'Playfair Display', serif;
                font-size: 1.3rem;
                color: var(--gold);
              "
            >
              T?ng: <span id="receipt-total">0?</span>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('receipt-modal')">
              ��ng
            </button>
            <button
              class="btn btn-ghost"
              id="receipt-save-btn"
              onclick="saveReceiptDraft()"
            >
              Luu nh�p
            </button>
            <button
              class="btn btn-gold"
              id="receipt-complete-btn"
              onclick="completeCurrentReceipt()"
            >
              ? Ho�n th�nh phi?u nh?p
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ---------- ORDER DETAIL MODAL ---------- -->
    <div class="modal-overlay" id="order-modal">
      <div class="modal" style="max-width: 580px">
        <div class="modal-inner">
          <div class="modal-header">
            <h2>Chi ti?t don hàng</h2>
            <button class="modal-close" onclick="closeModal('order-modal')">
              ?
            </button>
          </div>
          <input type="hidden" id="edit-order-id" />
          <div id="order-detail-content"></div>
        </div>
      </div>
    </div>

    <!-- ---------- USER MODAL ---------- -->
    <div class="modal-overlay" id="user-modal">
      <div class="modal" style="max-width: 500px">
        <div class="modal-inner">
          <div class="modal-header">
            <h2 id="user-modal-title">Th�m t�i kho?n</h2>
            <button class="modal-close" onclick="closeModal('user-modal')">
              ?
            </button>
          </div>
          <input type="hidden" id="edit-user-id" />
          <div class="form-row">
            <div class="form-group">
              <label>H? v� t�n</label>
              <input
                class="form-control"
                id="user-fullname"
                type="text"
                placeholder="Nguy?n Van A"
              />
            </div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label>Tên dang nh?p</label>
              <input
                class="form-control"
                id="user-username"
                type="text"
                placeholder="admin2"
              />
            </div>
            <div class="form-group">
              <label>Vai tr�</label>
              <select class="form-control" id="user-role">
                <option value="customer">Khách hàng</option>
                <option value="admin">Qu?n L�</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email</label>
              <input
                class="form-control"
                id="user-email"
                type="email"
                placeholder="user@lumiere.vn"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>M?t kh?u (kh?i t?o)</label>
              <input
                class="form-control"
                id="user-password"
                type="password"
                placeholder="Nh?p m?t kh?u m?i..."
              />
              <p
                id="user-pass-hint"
                style="
                  color: var(--muted);
                  font-size: 0.78rem;
                  margin-top: 0.4rem;
                  display: none;
                "
              >
                �? tr?ng n?u không d?i m?t kh?u.
              </p>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('user-modal')">
              H?y
            </button>
            <button class="btn btn-gold" onclick="saveUser()">
              Luu t�i kho?n
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ---------- CONFIRM DIALOG ---------- -->
    <div class="confirm-dialog" id="confirm-dialog">
      <div class="confirm-box">
        <div class="confirm-icon">?</div>
        <div class="confirm-title">Xác nh?n</div>
        <div class="confirm-msg" id="confirm-msg">B?n c� ch?c ch?n?</div>
        <div class="confirm-actions">
          <button class="btn btn-ghost" onclick="closeConfirm()">H?y</button>
          <button class="btn btn-danger" onclick="confirmAction()">
            Xác nh?n
          </button>
        </div>
      </div>
    </div>

    <!-- TOAST -->
    <div class="toast" id="admin-toast">
      <span class="toast-icon" id="toast-icon"></span>
      <span id="toast-msg"></span>
    </div>
