        <!-- --- DASHBOARD --- -->
        <section class="admin-page<?php echo ($page === 'dashboard') ? ' active' : ''; ?>" id="page-dashboard">
          <div class="page-header">
            <div class="page-header-left">
              <span class="eyebrow">Tong quan he thong</span>
              <h1>Dashboard</h1>
            </div>
            <button class="btn btn-gold btn-sm" onclick="refreshDashboard()">
              Lam moi
            </button>
          </div>
          <div class="stats-grid" id="dash-stats"></div>
          <div class="chart-wrap" style="margin-bottom: 1.5rem">
            <div class="chart-title">
              Doanh thu theo thang <span>2026</span>
            </div>
            <div class="bar-chart" id="revenue-chart"></div>
          </div>
          <div class="dashboard-grid">
            <div class="recent-orders-wrap">
              <div class="inner-table-header">Don hang gan day</div>
              <table>
                <thead>
                  <tr>
                    <th>Ma don</th>
                    <th>Khach hang</th>
                    <th>San pham</th>
                    <th>Tong</th>
                    <th>Trang thai</th>
                  </tr>
                </thead>
                <tbody id="dash-recent-orders"></tbody>
              </table>
            </div>
            <div>
              <div class="top-products-wrap" style="margin-bottom: 1.5rem">
                <div class="inner-table-header">Ban chay nhat</div>
                <table>
                  <thead>
                    <tr>
                      <th>San pham</th>
                      <th>SL</th>
                    </tr>
                  </thead>
                  <tbody id="dash-top-products"></tbody>
                </table>
              </div>
              <div class="recent-orders-wrap">
                <div
                  class="inner-table-header"
                  style="
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                  "
                >
                  <span>Sap het hang</span>
                  <button
                    class="btn btn-ghost btn-sm"
                    onclick="
                      (() => {
                        const btns = [
                          ...document.querySelectorAll('.sidebar-link'),
                        ];
                        showPage(
                          'inventory',
                          btns.find((b) => b.textContent.includes('Ton kho')),
                        );
                      })()
                    "
                  >
                    Xem tat ca
                  </button>
                </div>
                <div id="dash-low-stock"></div>
              </div>
            </div>
          </div>
        </section>
