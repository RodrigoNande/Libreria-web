<?php
/**
 * ============================================
 * 📄 HELPER DE PAGINACIÓN PHP
 * Librería RL - Funciones de servidor
 * ============================================
 */

class Paginacion {
    private $paginaActual;
    private $itemsPorPagina;
    private $totalItems;
    private $totalPaginas;
    private $maxPaginasVisibles;
    
    /**
     * Constructor
     */
    public function __construct($totalItems, $itemsPorPagina = 12, $paginaActual = null, $maxPaginasVisibles = 5) {
        $this->totalItems = max(0, (int)$totalItems);
        $this->itemsPorPagina = max(1, (int)$itemsPorPagina);
        $this->maxPaginasVisibles = max(3, (int)$maxPaginasVisibles);
        $this->totalPaginas = $this->calcularTotalPaginas();
        
        // Obtener página actual de URL o usar valor proporcionado
        if ($paginaActual === null) {
            $this->paginaActual = $this->obtenerPaginaActual();
        } else {
            $this->paginaActual = $this->validarPagina($paginaActual);
        }
    }
    
    /**
     * Calcular total de páginas
     */
    private function calcularTotalPaginas() {
        if ($this->totalItems === 0) return 1;
        return (int)ceil($this->totalItems / $this->itemsPorPagina);
    }
    
    /**
     * Obtener página actual de la URL
     */
    private function obtenerPaginaActual() {
        $pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        return $this->validarPagina($pagina);
    }
    
    /**
     * Validar número de página
     */
    private function validarPagina($pagina) {
        $pagina = max(1, (int)$pagina);
        return min($pagina, max(1, $this->totalPaginas));
    }
    
    /**
     * Obtener offset para SQL
     */
    public function obtenerOffset() {
        return ($this->paginaActual - 1) * $this->itemsPorPagina;
    }
    
    /**
     * Obtener limit para SQL
     */
    public function obtenerLimit() {
        return $this->itemsPorPagina;
    }
    
    /**
     * Generar cláusula LIMIT para SQL
     */
    public function obtenerClausulaSQL() {
        return "LIMIT " . $this->obtenerLimit() . " OFFSET " . $this->obtenerOffset();
    }
    
    /**
     * Obtener páginas visibles
     */
    private function obtenerPaginasVisibles() {
        $paginas = [];
        $mitadVisibles = floor($this->maxPaginasVisibles / 2);
        
        $paginaInicio = max($this->paginaActual - $mitadVisibles, 1);
        $paginaFin = min($paginaInicio + $this->maxPaginasVisibles - 1, $this->totalPaginas);
        
        // Ajustar si estamos cerca del final
        if ($paginaFin - $paginaInicio + 1 < $this->maxPaginasVisibles) {
            $paginaInicio = max($paginaFin - $this->maxPaginasVisibles + 1, 1);
        }
        
        for ($i = $paginaInicio; $i <= $paginaFin; $i++) {
            $paginas[] = $i;
        }
        
        return $paginas;
    }
    
    /**
     * Construir URL con parámetro de página
     */
    private function construirUrl($pagina) {
        $params = $_GET;
        $params['page'] = $pagina;
        $query = http_build_query($params);
        $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
        return $baseUrl . '?' . $query;
    }
    
    /**
     * Renderizar HTML de paginación
     */
    public function renderizar() {
        if ($this->totalPaginas <= 1) {
            return '';
        }
        
        $paginas = $this->obtenerPaginasVisibles();
        $html = '<nav class="paginacion" role="navigation" aria-label="Navegación de páginas">';
        
        // Botón anterior
        if ($this->paginaActual > 1) {
            $url = $this->construirUrl($this->paginaActual - 1);
            $html .= sprintf(
                '<a href="%s" class="paginacion-btn paginacion-prev" aria-label="Página anterior">‹</a>',
                htmlspecialchars($url)
            );
        } else {
            $html .= '<span class="paginacion-btn paginacion-disabled">‹</span>';
        }
        
        // Primera página
        if ($paginas[0] > 1) {
            $url = $this->construirUrl(1);
            $html .= sprintf(
                '<a href="%s" class="paginacion-btn">1</a>',
                htmlspecialchars($url)
            );
            if ($paginas[0] > 2) {
                $html .= '<span class="paginacion-ellipsis">...</span>';
            }
        }
        
        // Páginas visibles
        foreach ($paginas as $pagina) {
            if ($pagina === $this->paginaActual) {
                $html .= sprintf(
                    '<span class="paginacion-btn actual" aria-current="page">%d</span>',
                    $pagina
                );
            } else {
                $url = $this->construirUrl($pagina);
                $html .= sprintf(
                    '<a href="%s" class="paginacion-btn" aria-label="Página %d">%d</a>',
                    htmlspecialchars($url),
                    $pagina,
                    $pagina
                );
            }
        }
        
        // Última página
        $ultimaVisible = end($paginas);
        if ($ultimaVisible < $this->totalPaginas) {
            if ($ultimaVisible < $this->totalPaginas - 1) {
                $html .= '<span class="paginacion-ellipsis">...</span>';
            }
            $url = $this->construirUrl($this->totalPaginas);
            $html .= sprintf(
                '<a href="%s" class="paginacion-btn">%d</a>',
                htmlspecialchars($url),
                $this->totalPaginas
            );
        }
        
        // Botón siguiente
        if ($this->paginaActual < $this->totalPaginas) {
            $url = $this->construirUrl($this->paginaActual + 1);
            $html .= sprintf(
                '<a href="%s" class="paginacion-btn paginacion-next" aria-label="Siguiente página">›</a>',
                htmlspecialchars($url)
            );
        } else {
            $html .= '<span class="paginacion-btn paginacion-disabled">›</span>';
        }
        
        $html .= '</nav>';
        
        // Información adicional
        $html .= $this->renderizarInfo();
        
        return $html;
    }
    
    /**
     * Renderizar información de paginación
     */
    private function renderizarInfo() {
        $inicio = ($this->paginaActual - 1) * $this->itemsPorPagina + 1;
        $fin = min($this->paginaActual * $this->itemsPorPagina, $this->totalItems);
        
        return sprintf(
            '<div class="paginacion-info">Mostrando %d - %d de %d productos</div>',
            $inicio,
            $fin,
            $this->totalItems
        );
    }
    
    /**
     * Getters
     */
    public function getPaginaActual() {
        return $this->paginaActual;
    }
    
    public function getTotalPaginas() {
        return $this->totalPaginas;
    }
    
    public function getTotalItems() {
        return $this->totalItems;
    }
    
    public function getItemsPorPagina() {
        return $this->itemsPorPagina;
    }
    
    /**
     * Verificar si hay página anterior
     */
    public function tienePaginaAnterior() {
        return $this->paginaActual > 1;
    }
    
    /**
     * Verificar si hay página siguiente
     */
    public function tienePaginaSiguiente() {
        return $this->paginaActual < $this->totalPaginas;
    }
    
    /**
     * Obtener datos para JSON
     */
    public function toArray() {
        return [
            'paginaActual' => $this->paginaActual,
            'itemsPorPagina' => $this->itemsPorPagina,
            'totalItems' => $this->totalItems,
            'totalPaginas' => $this->totalPaginas,
            'offset' => $this->obtenerOffset(),
            'tienePaginaAnterior' => $this->tienePaginaAnterior(),
            'tienePaginaSiguiente' => $this->tienePaginaSiguiente()
        ];
    }
}

/**
 * Funciones helper globales
 */

/**
 * Crear instancia de paginación
 */
function crear_paginacion($totalItems, $itemsPorPagina = 12, $paginaActual = null, $maxPaginasVisibles = 5) {
    return new Paginacion($totalItems, $itemsPorPagina, $paginaActual, $maxPaginasVisibles);
}

/**
 * Sanitizar parámetros de entrada
 */
function sanitizar_entrada($valor, $tipo = 'string') {
    switch ($tipo) {
        case 'int':
            return (int)$valor;
        case 'float':
            return (float)$valor;
        case 'email':
            return filter_var($valor, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($valor, FILTER_SANITIZE_URL);
        case 'string':
        default:
            return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validar entrada
 */
function validar_entrada($valor, $tipo, $opciones = []) {
    switch ($tipo) {
        case 'email':
            return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;
        
        case 'int':
            $min = $opciones['min'] ?? null;
            $max = $opciones['max'] ?? null;
            $val = filter_var($valor, FILTER_VALIDATE_INT);
            if ($val === false) return false;
            if ($min !== null && $val < $min) return false;
            if ($max !== null && $val > $max) return false;
            return true;
        
        case 'float':
            $min = $opciones['min'] ?? null;
            $max = $opciones['max'] ?? null;
            $val = filter_var($valor, FILTER_VALIDATE_FLOAT);
            if ($val === false) return false;
            if ($min !== null && $val < $min) return false;
            if ($max !== null && $val > $max) return false;
            return true;
        
        case 'url':
            return filter_var($valor, FILTER_VALIDATE_URL) !== false;
        
        case 'telefono':
            $limpio = preg_replace('/\D/', '', $valor);
            return strlen($limpio) >= 8 && strlen($limpio) <= 15;
        
        case 'alfanumerico':
            return preg_match('/^[a-zA-Z0-9\s]+$/', $valor);
        
        case 'alpha':
            return preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $valor);
        
        case 'numeric':
            return preg_match('/^[0-9]+$/', $valor);
        
        case 'longitud':
            $min = $opciones['min'] ?? 0;
            $max = $opciones['max'] ?? PHP_INT_MAX;
            $len = mb_strlen($valor);
            return $len >= $min && $len <= $max;
        
        default:
            return !empty(trim($valor));
    }
}

/**
 * Validar múltiples campos
 */
function validar_formulario($datos, $reglas) {
    $errores = [];
    
    foreach ($reglas as $campo => $reglascampo) {
        $valor = $datos[$campo] ?? '';
        
        // Requerido
        if (isset($reglascampo['requerido']) && $reglascampo['requerido'] && empty(trim($valor))) {
            $errores[$campo] = 'Este campo es obligatorio';
            continue;
        }
        
        // Si no es requerido y está vacío, saltar otras validaciones
        if (empty(trim($valor)) && !isset($reglascamp['requerido'])) {
            continue;
        }
        
        // Email
        if (isset($reglascamp['email']) && $reglascamp['email'] && !validar_entrada($valor, 'email')) {
            $errores[$campo] = 'Ingrese un email válido';
            continue;
        }
        
        // Teléfono
        if (isset($reglascamp['telefono']) && $reglascamp['telefono'] && !validar_entrada($valor, 'telefono')) {
            $errores[$campo] = 'Ingrese un teléfono válido';
            continue;
        }
        
        // Longitud mínima
        if (isset($reglascamp['min_longitud']) && mb_strlen($valor) < $reglascamp['min_longitud']) {
            $errores[$campo] = sprintf('Debe tener al menos %d caracteres', $reglascamp['min_longitud']);
            continue;
        }
        
        // Longitud máxima
        if (isset($reglascamp['max_longitud']) && mb_strlen($valor) > $reglascamp['max_longitud']) {
            $errores[$campo] = sprintf('No puede exceder %d caracteres', $reglascamp['max_longitud']);
            continue;
        }
        
        // Valor mínimo (numérico)
        if (isset($reglascamp['min']) && (float)$valor < $reglascamp['min']) {
            $errores[$campo] = sprintf('El valor mínimo es %s', $reglascamp['min']);
            continue;
        }
        
        // Valor máximo (numérico)
        if (isset($reglascamp['max']) && (float)$valor > $reglascamp['max']) {
            $errores[$campo] = sprintf('El valor máximo es %s', $reglascamp['max']);
            continue;
        }
        
        // Coincidencia
        if (isset($reglascamp['coincide']) && $valor !== ($datos[$reglascamp['coincide']] ?? '')) {
            $errores[$campo] = 'Los campos no coinciden';
            continue;
        }
        
        // Patrón personalizado
        if (isset($reglascamp['patron']) && !preg_match($reglascamp['patron'], $valor)) {
            $mensaje = $reglascamp['mensaje'] ?? 'El formato no es válido';
            $errores[$campo] = $mensaje;
            continue;
        }
    }
    
    return [
        'valido' => empty($errores),
        'errores' => $errores
    ];
}

/**
 * Escapar salida para HTML
 */
function escapar_html($valor) {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Generar ID único
 */
function generar_id_unico($prefijo = '') {
    return $prefijo . strtoupper(bin2hex(random_bytes(8))) . '_' . time();
}