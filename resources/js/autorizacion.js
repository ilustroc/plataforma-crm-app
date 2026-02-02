import Alpine from 'alpinejs';

// 1. Definimos la lógica del componente en una constante
const autorizacionData = () => ({
    modalFichaOpen: false,
    modalNotaOpen: false,
    modalRechazoOpen: false,
    modalPagosOpen: false,
    
    // Datos
    ficha: {},
    pagosDni: '',
    pagosList: [],
    pagosLoading: false,

    // Acciones dinámicas
    actionUrl: '',
    actionTitle: '',

    init() {
        console.log('Componente Autorización Iniciado'); // Debug: Verás esto en la consola si carga bien
    },

    // --- FICHA ---
    openFicha(data) {
        this.ficha = data;
        // Parseo defensivo por si viene texto o objeto
        try { 
            this.ficha.cuentas = typeof data.cuentas === 'string' ? JSON.parse(data.cuentas) : data.cuentas; 
        } catch(e) { 
            this.ficha.cuentas = []; 
        }
        try { 
            this.ficha.crono = typeof data.crono === 'string' ? JSON.parse(data.crono) : data.crono; 
        } catch(e) { 
            this.ficha.crono = []; 
        }
        
        this.modalFichaOpen = true;
    },

    // --- NOTA (Pre-aprobar / Aprobar) ---
    openNota(url, title) {
        this.actionUrl = url;
        this.actionTitle = title;
        this.modalNotaOpen = true;
        // Pequeño retraso para dar foco al input
        setTimeout(() => {
            const el = document.getElementById('txtNota');
            if(el) el.focus();
        }, 100);
    },

    // --- RECHAZO ---
    openRechazo(url) {
        this.actionUrl = url;
        this.modalRechazoOpen = true;
        setTimeout(() => {
            const el = document.getElementById('txtRechazo');
            if(el) el.focus();
        }, 100);
    },

    // --- PAGOS ---
    async openPagos(dni) {
        this.pagosDni = dni;
        this.pagosList = [];
        this.pagosLoading = true;
        this.modalPagosOpen = true;

        try {
            // Ajusta la URL si es necesario
            const response = await fetch(`/autorizacion/pagos/${dni}`, {
                headers: { 'Accept': 'application/json' }
            });
            
            if (!response.ok) throw new Error('Error en red');
            
            const data = await response.json();
            this.pagosList = data.pagos || [];
        } catch (error) {
            console.error('Error cargando pagos:', error);
            this.pagosList = [];
        } finally {
            this.pagosLoading = false;
        }
    },

    // Helpers
    fmtMoney(amount) {
        return 'S/ ' + parseFloat(amount || 0).toFixed(2);
    },
    
    formatDate(dateStr) {
        if(!dateStr) return '—';
        const date = new Date(dateStr);
        // Ajuste zona horaria simple para visualización
        return date.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
});

// 2. Registro Robusto del Componente
// Si window.Alpine ya existe (cargado por app.js u otro), usamos ese.
// Si no, usamos el que acabamos de importar.
if (window.Alpine) {
    window.Alpine.data('autorizacion', autorizacionData);
} else {
    window.Alpine = Alpine;
    Alpine.data('autorizacion', autorizacionData);
    Alpine.start();
}