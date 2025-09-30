/**
 * ============================================
 * 📄 SISTEMA DE PAGINACIÓN
 * Librería RL - Paginación funcional
 * ============================================
 */

class Pagination {
    constructor(options = {}) {
        this.currentPage = options.currentPage || 1;
        this.totalPages = options.totalPages || 1;
        this.itemsPerPage = options.itemsPerPage || 12;
        this.totalItems = options.totalItems || 0;
        this.maxVisiblePages = options.maxVisiblePages || 5;
        this.containerId = options.containerId || 'paginacion-container';
        this.onPageChange = options.onPageChange || (() => {});
        this.baseUrl = options.baseUrl || window.location.pathname;
        
        this.init();
    }
    
    init() {
        this.render();
        this.attachEvents();
    }
    
    /**
     * Renderizar la paginación
     */
    render() {
        const container = document.getElementById(this.containerId);
        if (!container) {
            console.error(`Container ${this.containerId} not found`);
            return;
        }
        
        if (this.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        const paginationHTML = this.generateHTML();
        container.innerHTML = paginationHTML;
    }
    
    /**
     * Generar HTML de la paginación
     */
    generateHTML() {
        const pages = this.calculatePageNumbers();
        let html = '<nav class="paginacion" role="navigation" aria-label="Navegación de páginas">';
        
        // Botón anterior
        if (this.currentPage > 1) {
            html += `
                <a href="${this.buildUrl(this.currentPage - 1)}" 
                   class="paginacion-btn paginacion-prev" 
                   data-page="${this.currentPage - 1}"
                   aria-label="Página anterior">
                    ‹
                </a>
            `;
        } else {
            html += `<span class="paginacion-btn paginacion-disabled">‹</span>`;
        }
        
        // Primera página
        if (pages[0] > 1) {
            html += `
                <a href="${this.buildUrl(1)}" 
                   class="paginacion-btn" 
                   data-page="1">
                    1
                </a>
            `;
            if (pages[0] > 2) {
                html += `<span class="paginacion-ellipsis">...</span>`;
            }
        }
        
        // Páginas visibles
        pages.forEach(page => {
            if (page === this.currentPage) {
                html += `
                    <span class="paginacion-btn actual" aria-current="page">
                        ${page}
                    </span>
                `;
            } else {
                html += `
                    <a href="${this.buildUrl(page)}" 
                       class="paginacion-btn" 
                       data-page="${page}"
                       aria-label="Página ${page}">
                        ${page}
                    </a>
                `;
            }
        });
        
        // Última página
        const lastVisible = pages[pages.length - 1];
        if (lastVisible < this.totalPages) {
            if (lastVisible < this.totalPages - 1) {
                html += `<span class="paginacion-ellipsis">...</span>`;
            }
            html += `
                <a href="${this.buildUrl(this.totalPages)}" 
                   class="paginacion-btn" 
                   data-page="${this.totalPages}">
                    ${this.totalPages}
                </a>
            `;
        }
        
        // Botón siguiente
        if (this.currentPage < this.totalPages) {
            html += `
                <a href="${this.buildUrl(this.currentPage + 1)}" 
                   class="paginacion-btn paginacion-next" 
                   data-page="${this.currentPage + 1}"
                   aria-label="Siguiente página">
                    ›
                </a>
            `;
        } else {
            html += `<span class="paginacion-btn paginacion-disabled">›</span>`;
        }
        
        html += '</nav>';
        
        // Información adicional
        html += this.renderInfo();
        
        return html;
    }
    
    /**
     * Renderizar información de paginación
     */
    renderInfo() {
        const start = (this.currentPage - 1) * this.itemsPerPage + 1;
        const end = Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
        
        return `
            <div class="paginacion-info">
                Mostrando ${start} - ${end} de ${this.totalItems} productos
            </div>
        `;
    }
    
    /**
     * Calcular números de página visibles
     */
    calculatePageNumbers() {
        const pages = [];
        const halfVisible = Math.floor(this.maxVisiblePages / 2);
        
        let startPage = Math.max(this.currentPage - halfVisible, 1);
        let endPage = Math.min(startPage + this.maxVisiblePages - 1, this.totalPages);
        
        // Ajustar si estamos cerca del final
        if (endPage - startPage + 1 < this.maxVisiblePages) {
            startPage = Math.max(endPage - this.maxVisiblePages + 1, 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }
        
        return pages;
    }
    
    /**
     * Construir URL con parámetro de página
     */
    buildUrl(page) {
        const url = new URL(this.baseUrl, window.location.origin);
        const params = new URLSearchParams(window.location.search);
        params.set('page', page);
        return `${url.pathname}?${params.toString()}`;
    }
    
    /**
     * Adjuntar eventos a los botones
     */
    attachEvents() {
        const container = document.getElementById(this.containerId);
        if (!container) return;
        
        container.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-page]');
            if (link) {
                e.preventDefault();
                const page = parseInt(link.dataset.page);
                this.goToPage(page);
            }
        });
    }
    
    /**
     * Ir a una página específica
     */
    goToPage(page) {
        if (page < 1 || page > this.totalPages || page === this.currentPage) {
            return;
        }
        
        this.currentPage = page;
        this.render();
        this.onPageChange(page);
        
        // Scroll al inicio de la página
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    /**
     * Actualizar configuración
     */
    update(options = {}) {
        Object.assign(this, options);
        this.render();
    }
}

/**
 * Utilidades de paginación
 */
const PaginationUtils = {
    /**
     * Calcular total de páginas
     */
    calculateTotalPages(totalItems, itemsPerPage) {
        return Math.ceil(totalItems / itemsPerPage);
    },
    
    /**
     * Obtener página actual de la URL
     */
    getCurrentPageFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const page = parseInt(params.get('page')) || 1;
        return page > 0 ? page : 1;
    },
    
    /**
     * Calcular offset para consulta SQL
     */
    calculateOffset(page, itemsPerPage) {
        return (page - 1) * itemsPerPage;
    },
    
    /**
     * Crear paginación desde datos
     */
    createFromData(data, options = {}) {
        const totalPages = PaginationUtils.calculateTotalPages(
            data.totalItems || data.length,
            options.itemsPerPage || 12
        );
        
        return new Pagination({
            currentPage: PaginationUtils.getCurrentPageFromUrl(),
            totalPages,
            totalItems: data.totalItems || data.length,
            ...options
        });
    }
};

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.Pagination = Pagination;
    window.PaginationUtils = PaginationUtils;
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Pagination, PaginationUtils };
}