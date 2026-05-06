// Lens & Frame Co. — Frontend interactions

document.addEventListener('DOMContentLoaded', () => {
  initConfirms();
  initSalesForm();
  initFormValidation();
  initFlashAutoDismiss();
  initUserMenu();
  initCharts();
});

function initConfirms() {
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', (e) => {
      const msg = form.getAttribute('data-confirm') || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });
}

function initSalesForm() {
  const productSelect = document.getElementById('product-select');
  const qtyInput = document.getElementById('quantity-input');
  const totalPreview = document.getElementById('total-preview');

  if (!(productSelect && qtyInput && totalPreview)) return;

  const updateTotal = () => {
    const opt = productSelect.options[productSelect.selectedIndex];
    if (!opt || !opt.value) {
      totalPreview.value = '¥0';
      return;
    }
    const price = parseFloat(opt.dataset.price || 0);
    const stock = parseInt(opt.dataset.stock || 0, 10);
    let qty = Math.max(1, parseInt(qtyInput.value || 1, 10));

    qtyInput.max = stock;
    if (qty > stock) {
      qty = stock;
      qtyInput.value = stock;
    }
    totalPreview.value = '¥' + (price * qty).toLocaleString('en-US', { maximumFractionDigits: 0 });
  };

  productSelect.addEventListener('change', updateTotal);
  qtyInput.addEventListener('input', updateTotal);
  updateTotal();
}

function initFormValidation() {
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', (e) => {
      let valid = true;
      form.querySelectorAll('[required]').forEach(el => {
        if (!String(el.value || '').trim()) {
          el.style.borderColor = 'var(--danger)';
          valid = false;
        } else {
          el.style.borderColor = '';
        }
      });
      if (!valid) e.preventDefault();
    });
  });
}

function initFlashAutoDismiss() {
  const flash = document.querySelector('.flash');
  if (!flash) return;
  setTimeout(() => {
    flash.style.transition = 'opacity 0.3s ease';
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 300);
  }, 4000);
}

function initUserMenu() {
  const menus = document.querySelectorAll('[data-menu]');
  menus.forEach(menu => {
    const trigger = menu.querySelector('[data-menu-trigger]');
    const panel   = menu.querySelector('[data-menu-panel]');
    if (!trigger || !panel) return;

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = !panel.hasAttribute('hidden');
      document.querySelectorAll('[data-menu-panel]').forEach(p => p.setAttribute('hidden', ''));
      if (!isOpen) panel.removeAttribute('hidden');
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('[data-menu-panel]').forEach(p => p.setAttribute('hidden', ''));
  });
}

function initCharts() {
  if (typeof Chart === 'undefined') return;

  Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
  Chart.defaults.color = '#6b6b6b';
  Chart.defaults.borderColor = '#e8e6e0';

  const accent = '#1f3a5f';
  const success = '#2f855a';

  const sales = document.getElementById('salesChart');
  if (sales) {
    const labels = JSON.parse(sales.dataset.labels || '[]');
    const revenue = JSON.parse(sales.dataset.revenue || '[]');
    if (labels.length) {
      new Chart(sales, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Revenue (¥)',
            data: revenue,
            borderColor: accent,
            backgroundColor: 'rgba(31, 58, 95, 0.08)',
            tension: 0.35,
            fill: true,
            borderWidth: 2,
            pointRadius: 4,
            pointBackgroundColor: accent,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (v) => '¥' + v.toLocaleString('en-US', { maximumFractionDigits: 0 })
              }
            }
          }
        }
      });
    }
  }

  const typeCanvas = document.getElementById('typeChart');
  if (typeCanvas) {
    const labels = JSON.parse(typeCanvas.dataset.labels || '[]');
    const revenue = JSON.parse(typeCanvas.dataset.revenue || '[]');
    if (labels.length) {
      new Chart(typeCanvas, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data: revenue,
            backgroundColor: [accent, success, '#c05621', '#805ad5'],
            borderWidth: 2,
            borderColor: '#fff',
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: (ctx) => `${ctx.label}: ¥${ctx.parsed.toLocaleString('en-US')}`
              }
            }
          }
        }
      });
    }
  }
}
