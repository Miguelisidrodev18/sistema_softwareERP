/* ============================================================
   SISTEMA EPIS UNH - JavaScript Principal
   ============================================================ */

'use strict';

/* ---- SIDEBAR TOGGLE ---- */
document.addEventListener('DOMContentLoaded', function () {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebar-overlay');
  const toggle   = document.getElementById('sidebar-toggle');
  const mainCont = document.getElementById('main-content');
  const topbar   = document.getElementById('topbar');

  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      if (window.innerWidth <= 768) {
        sidebar.classList.toggle('open');
        overlay && overlay.classList.toggle('show');
      } else {
        sidebar.classList.toggle('collapsed');
        if (sidebar.classList.contains('collapsed')) {
          sidebar.style.width = '0';
          if (mainCont) mainCont.style.marginLeft = '0';
          if (topbar)   topbar.style.left = '0';
        } else {
          sidebar.style.width = '';
          if (mainCont) mainCont.style.marginLeft = '';
          if (topbar)   topbar.style.left = '';
        }
      }
    });
  }

  overlay && overlay.addEventListener('click', function () {
    sidebar && sidebar.classList.remove('open');
    overlay.classList.remove('show');
  });

  // Marcar link activo en sidebar
  const currentUrl = window.location.href;
  document.querySelectorAll('#sidebar .nav-link').forEach(link => {
    if (link.href && currentUrl.includes(link.getAttribute('href'))) {
      link.classList.add('active');
    }
  });
});

/* ---- TOAST NOTIFICATIONS ---- */
const Toast = {
  container: null,
  init() {
    if (!this.container) {
      this.container = document.getElementById('toast-container');
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        document.body.appendChild(this.container);
      }
    }
  },
  show(message, type = 'info', duration = 3500) {
    this.init();
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
    const colors = { success: '#059669', error: '#dc2626', warning: '#d97706', info: '#003087' };
    const el = document.createElement('div');
    el.className = `toast-epis ${type}`;
    el.innerHTML = `<i class="fas ${icons[type]||icons.info}" style="color:${colors[type]};font-size:1.1rem;margin-top:2px;flex-shrink:0"></i>
                    <div style="flex:1;font-size:0.88rem">${message}</div>
                    <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#6c757d;padding:0;font-size:1rem;flex-shrink:0">&times;</button>`;
    this.container.appendChild(el);
    setTimeout(() => { el.style.opacity='0'; el.style.transform='translateX(40px)'; el.style.transition='all 0.3s'; setTimeout(()=>el.remove(),300); }, duration);
  },
  success(msg, d) { this.show(msg,'success',d); },
  error(msg, d)   { this.show(msg,'error',d); },
  warning(msg, d) { this.show(msg,'warning',d); },
  info(msg, d)    { this.show(msg,'info',d); }
};

/* ---- LOADING ---- */
const Loading = {
  show() {
    let el = document.getElementById('loading-overlay');
    if (!el) {
      el = document.createElement('div');
      el.id = 'loading-overlay';
      el.className = 'loading-overlay';
      el.innerHTML = '<div class="spinner-epis"></div>';
      document.body.appendChild(el);
    }
    el.style.display = 'flex';
  },
  hide() {
    const el = document.getElementById('loading-overlay');
    if (el) el.style.display = 'none';
  }
};

/* ---- AJAX HELPER ---- */
const Api = {
  csrfToken: () => document.querySelector('meta[name="csrf-token"]')?.content || '',

  async post(url, data = {}) {
    data.csrf_token = this.csrfToken();
    const formData = new FormData();
    for (const [k, v] of Object.entries(data)) {
      if (Array.isArray(v)) v.forEach(i => formData.append(k+'[]', i));
      else formData.append(k, v ?? '');
    }
    const res = await fetch(url, { method: 'POST', body: formData });
    if (!res.ok) throw new Error('Error de red: ' + res.status);
    return res.json();
  },

  async get(url, params = {}) {
    const qs = new URLSearchParams(params).toString();
    const res = await fetch(qs ? `${url}?${qs}` : url);
    if (!res.ok) throw new Error('Error de red: ' + res.status);
    return res.json();
  }
};

/* ---- CONFIRMACION DE ELIMINACION ---- */
function confirmDelete(message, callback) {
  const modal = document.getElementById('confirmModal');
  if (modal) {
    document.getElementById('confirmMessage').textContent = message || '¿Esta seguro de eliminar este registro?';
    const btn = document.getElementById('confirmBtn');
    btn.onclick = () => { bootstrap.Modal.getInstance(modal).hide(); callback(); };
    bootstrap.Modal.getOrCreateInstance(modal).show();
  } else if (confirm(message || '¿Esta seguro?')) {
    callback();
  }
}

/* ---- INICIALIZAR DATATABLES ---- */
function initDataTable(tableId, options = {}) {
  const el = document.getElementById(tableId);
  if (!el || typeof $.fn === 'undefined') return null;
  const defaults = {
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    },
    pageLength: 15,
    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    ...options
  };
  return $(el).DataTable(defaults);
}

/* ---- UTILIDADES ---- */
function escapeHtml(str) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(str || ''));
  return div.innerHTML;
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric' });
}

function formatTime(timeStr) {
  if (!timeStr) return '-';
  const [h, m] = timeStr.split(':');
  const hour = parseInt(h);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  return `${String(hour > 12 ? hour-12 : hour||12).padStart(2,'0')}:${m} ${ampm}`;
}

/* ---- FORM VALIDATION ---- */
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return true;
  form.classList.add('was-validated');
  return form.checkValidity();
}

/* ---- RESET FORM MODAL ---- */
function resetModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.querySelectorAll('form').forEach(f => {
    f.reset();
    f.classList.remove('was-validated');
  });
  modal.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
  modal.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
}

/* ---- GENERADOR DE GRAFICOS ---- */
const Charts = {
  colors: {
    blue:   '#003087',
    yellow: '#F5A623',
    green:  '#198754',
    red:    '#dc3545',
    teal:   '#0dcaf0',
    purple: '#6f42c1',
  },
  palette: ['#003087','#F5A623','#198754','#dc3545','#0dcaf0','#6f42c1','#fd7e14','#20c997'],

  barChart(canvasId, labels, data, label, color) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{ label, data, backgroundColor: color || this.colors.blue, borderRadius: 6, borderSkipped: false }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#f0f0f0' } }, x: { grid: { display: false } } }
      }
    });
  },

  doughnutChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{ data, backgroundColor: this.palette, borderWidth: 2, borderColor: '#fff', hoverBorderWidth: 3 }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } } },
        cutout: '62%'
      }
    });
  },

  lineChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'line',
      data: { labels, datasets },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, grid: { color: '#f0f0f0' } }, x: { grid: { display: false } } },
        tension: 0.4
      }
    });
  },

  horizontalBar(canvasId, labels, data, colors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{ data, backgroundColor: colors || this.palette, borderRadius: 6, borderSkipped: false }]
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, grid: { color: '#f0f0f0' } }, y: { grid: { display: false } } }
      }
    });
  }
};

/* ---- PRINT / EXPORT ---- */
function printSection(sectionId) {
  const el = document.getElementById(sectionId);
  if (!el) { window.print(); return; }
  const orig = document.body.innerHTML;
  document.body.innerHTML = el.innerHTML;
  window.print();
  document.body.innerHTML = orig;
  location.reload();
}

/* ---- AUTO-DISMISS ALERTS ---- */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.alert-auto-dismiss').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.4s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 4000);
  });
});
