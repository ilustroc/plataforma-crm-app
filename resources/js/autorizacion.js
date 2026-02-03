import Alpine from 'alpinejs';

const autorizacionData = () => ({
  modalFichaOpen: false,
  modalNotaOpen: false,
  modalRechazoOpen: false,
  modalPagosOpen: false,

  ficha: {},            // asegúrate que sea "ficha", no "fecha"
  actionUrl: '',
  actionTitle: '',

  init() {
    console.log('Componente Autorización Iniciado');
  },

  openFicha(data) {
    this.ficha = data;

    try { this.ficha.cuentas = typeof data.cuentas === 'string' ? JSON.parse(data.cuentas) : (data.cuentas || []); }
    catch { this.ficha.cuentas = []; }

    try { this.ficha.crono = typeof data.crono === 'string' ? JSON.parse(data.crono) : (data.crono || []); }
    catch { this.ficha.crono = []; }

    this.modalFichaOpen = true;
  },

  openNota(url, title) {
    this.actionUrl = url;
    this.actionTitle = title;
    this.modalNotaOpen = true;
  },

  openRechazo(url) {
    this.actionUrl = url;
    this.modalRechazoOpen = true;
  },

  fmtMoney(amount) {
    return 'S/ ' + parseFloat(amount || 0).toFixed(2);
  },

  formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return isNaN(d) ? dateStr : d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }
});

// ✅ Siempre usa el Alpine global si existe
const A = window.Alpine ?? Alpine;
window.Alpine = A;

// Registra el componente
A.data('autorizacion', autorizacionData);

// ✅ Si Alpine ya estaba iniciado, “monta” el componente en este DOM
queueMicrotask(() => {
  const root = document.querySelector('[x-data="autorizacion"]');
  if (root) A.initTree(root);
});

if (!A.initialized) {
  A.start();
  A.initialized = true;
}
