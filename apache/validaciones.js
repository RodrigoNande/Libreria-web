/**
 * ============================================
 * 🔐 SISTEMA DE VALIDACIÓN UNIFICADO
 * Librería RL - Validaciones cliente
 * ============================================
 */

// Configuración global de validación
const ValidationConfig = {
    // Patrones de validación
    patterns: {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        phone: /^[0-9]{8,15}$/,
        alphanumeric: /^[a-zA-Z0-9\s]+$/,
        alpha: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/,
        numeric: /^[0-9]+$/,
        decimal: /^[0-9]+(\.[0-9]{1,2})?$/,
        password: /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,}$/,
        username: /^[a-zA-Z0-9_]{3,20}$/
    },
    
    // Mensajes de error
    messages: {
        required: 'Este campo es obligatorio',
        email: 'Ingrese un email válido',
        phone: 'Ingrese un teléfono válido (8-15 dígitos)',
        minLength: 'Debe tener al menos {min} caracteres',
        maxLength: 'No puede exceder {max} caracteres',
        min: 'El valor mínimo es {min}',
        max: 'El valor máximo es {max}',
        pattern: 'El formato no es válido',
        match: 'Los campos no coinciden',
        alphanumeric: 'Solo se permiten letras y números',
        alpha: 'Solo se permiten letras',
        numeric: 'Solo se permiten números',
        decimal: 'Debe ser un número válido (ej: 10.50)',
        password: 'La contraseña debe tener al menos 6 caracteres, una letra y un número',
        username: 'El usuario debe tener 3-20 caracteres (letras, números y guión bajo)'
    }
};

/**
 * Clase principal de validación
 */
class FormValidator {
    constructor(formId, options = {}) {
        this.form = document.getElementById(formId);
        this.options = {
            realTimeValidation: true,
            scrollToError: true,
            highlightErrors: true,
            ...options
        };
        this.rules = {};
        this.errors = {};
        
        if (this.form) {
            this.init();
        }
    }
    
    init() {
        // Prevenir submit por defecto
        this.form.addEventListener('submit', (e) => {
            if (!this.validateAll()) {
                e.preventDefault();
                if (this.options.scrollToError) {
                    this.scrollToFirstError();
                }
            }
        });
        
        // Validación en tiempo real si está habilitada
        if (this.options.realTimeValidation) {
            this.setupRealTimeValidation();
        }
    }
    
    /**
     * Agregar reglas de validación para un campo
     */
    addRule(fieldName, rules) {
        this.rules[fieldName] = rules;
        return this;
    }
    
    /**
     * Agregar múltiples reglas
     */
    addRules(rulesObject) {
        Object.assign(this.rules, rulesObject);
        return this;
    }
    
    /**
     * Validar un campo específico
     */
    validateField(fieldName) {
        const field = this.form.elements[fieldName];
        if (!field || !this.rules[fieldName]) {
            return true;
        }
        
        const rules = this.rules[fieldName];
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Requerido
        if (rules.required && !value) {
            isValid = false;
            errorMessage = ValidationConfig.messages.required;
        }
        
        // Solo validar lo demás si hay valor o es requerido
        if (value || rules.required) {
            // Longitud mínima
            if (rules.minLength && value.length < rules.minLength) {
                isValid = false;
                errorMessage = ValidationConfig.messages.minLength.replace('{min}', rules.minLength);
            }
            
            // Longitud máxima
            if (rules.maxLength && value.length > rules.maxLength) {
                isValid = false;
                errorMessage = ValidationConfig.messages.maxLength.replace('{max}', rules.maxLength);
            }
            
            // Valor mínimo (números)
            if (rules.min !== undefined && parseFloat(value) < rules.min) {
                isValid = false;
                errorMessage = ValidationConfig.messages.min.replace('{min}', rules.min);
            }
            
            // Valor máximo (números)
            if (rules.max !== undefined && parseFloat(value) > rules.max) {
                isValid = false;
                errorMessage = ValidationConfig.messages.max.replace('{max}', rules.max);
            }
            
            // Email
            if (rules.email && !ValidationConfig.patterns.email.test(value)) {
                isValid = false;
                errorMessage = ValidationConfig.messages.email;
            }
            
            // Teléfono
            if (rules.phone && !ValidationConfig.patterns.phone.test(value.replace(/\D/g, ''))) {
                isValid = false;
                errorMessage = ValidationConfig.messages.phone;
            }
            
            // Alfanumérico
            if (rules.alphanumeric && !ValidationConfig.patterns.alphanumeric.test(value)) {
                isValid = false;
                errorMessage = ValidationConfig.messages.alphanumeric;
            }
            
            // Solo letras
            if (rules.alpha && !ValidationConfig.patterns.alpha.test(value)) {
                isValid = false;
                errorMessage = ValidationConfig.messages.alpha;
            }
            
            // Solo números
            if (rules.numeric && !ValidationConfig.patterns.numeric.test(value)) {
                isValid = false;
                errorMessage = ValidationConfig.messages.numeric;
            }
            
            // Decimal
            if (rules.decimal && !ValidationConfig.patterns.decimal.test(value)) {
                isValid = false;
                errorMessage = ValidationConfig.messages.decimal;
            }
            
            // Contraseña
            if (rules.password && !ValidationConfig.patterns.password.test(value)) {
                isValid = false;
                errorMessage = ValidationConfig.messages.password;
            }
            
            // Usuario
            if (rules.username && !ValidationConfig.patterns.username.test(value)) {
                isValid = false;
                errorMessage = ValidationConfig.messages.username;
            }
            
            // Coincidencia con otro campo
            if (rules.match) {
                const matchField = this.form.elements[rules.match];
                if (matchField && value !== matchField.value) {
                    isValid = false;
                    errorMessage = ValidationConfig.messages.match;
                }
            }
            
            // Patrón personalizado
            if (rules.pattern && !rules.pattern.test(value)) {
                isValid = false;
                errorMessage = rules.customMessage || ValidationConfig.messages.pattern;
            }
            
            // Validación personalizada
            if (rules.custom && typeof rules.custom === 'function') {
                const customResult = rules.custom(value, field);
                if (customResult !== true) {
                    isValid = false;
                    errorMessage = customResult || 'Validación personalizada falló';
                }
            }
        }
        
        // Actualizar UI
        if (this.options.highlightErrors) {
            this.updateFieldUI(field, isValid, errorMessage);
        }
        
        // Guardar estado del error
        if (isValid) {
            delete this.errors[fieldName];
        } else {
            this.errors[fieldName] = errorMessage;
        }
        
        return isValid;
    }
    
    /**
     * Validar todos los campos
     */
    validateAll() {
        this.errors = {};
        let isValid = true;
        
        Object.keys(this.rules).forEach(fieldName => {
            if (!this.validateField(fieldName)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Actualizar UI del campo
     */
    updateFieldUI(field, isValid, message) {
        // Agregar/quitar clase de error
        if (isValid) {
            field.classList.remove('error');
        } else {
            field.classList.add('error');
        }
        
        // Manejar mensaje de error
        let errorElement = field.parentElement.querySelector('.error-message');
        
        if (!isValid) {
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                field.parentElement.appendChild(errorElement);
            }
            errorElement.textContent = message;
            errorElement.classList.add('show');
        } else if (errorElement) {
            errorElement.classList.remove('show');
        }
    }
    
    /**
     * Configurar validación en tiempo real
     */
    setupRealTimeValidation() {
        Object.keys(this.rules).forEach(fieldName => {
            const field = this.form.elements[fieldName];
            if (field) {
                // Validar al perder el foco
                field.addEventListener('blur', () => {
                    this.validateField(fieldName);
                });
                
                // Limpiar error al escribir (después del primer error)
                field.addEventListener('input', () => {
                    if (this.errors[fieldName]) {
                        this.validateField(fieldName);
                    }
                });
            }
        });
    }
    
    /**
     * Hacer scroll al primer error
     */
    scrollToFirstError() {
        const firstErrorField = Object.keys(this.errors)[0];
        if (firstErrorField) {
            const field = this.form.elements[firstErrorField];
            if (field) {
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                field.focus();
            }
        }
    }
    
    /**
     * Obtener todos los errores
     */
    getErrors() {
        return this.errors;
    }
    
    /**
     * Limpiar todos los errores
     */
    clearErrors() {
        this.errors = {};
        Object.keys(this.rules).forEach(fieldName => {
            const field = this.form.elements[fieldName];
            if (field) {
                this.updateFieldUI(field, true, '');
            }
        });
    }
    
    /**
     * Reset del formulario
     */
    reset() {
        this.form.reset();
        this.clearErrors();
    }
}

/**
 * Utilidades de validación independientes
 */
const ValidationUtils = {
    /**
     * Validar email
     */
    isValidEmail(email) {
        return ValidationConfig.patterns.email.test(email);
    },
    
    /**
     * Validar teléfono
     */
    isValidPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return ValidationConfig.patterns.phone.test(cleaned);
    },
    
    /**
     * Validar contraseña fuerte
     */
    isStrongPassword(password) {
        return ValidationConfig.patterns.password.test(password);
    },
    
    /**
     * Formatear teléfono
     */
    formatPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 8) {
            return cleaned.replace(/(\d{4})(\d{4})/, '$1-$2');
        }
        return phone;
    },
    
    /**
     * Formatear precio
     */
    formatPrice(price) {
        const num = parseFloat(price);
        if (isNaN(num)) return '0.00';
        return num.toFixed(2);
    },
    
    /**
     * Sanitizar entrada (prevenir XSS básico)
     */
    sanitizeInput(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    },
    
    /**
     * Validar rango numérico
     */
    isInRange(value, min, max) {
        const num = parseFloat(value);
        return !isNaN(num) && num >= min && num <= max;
    },
    
    /**
     * Verificar si un string está vacío o solo contiene espacios
     */
    isEmpty(str) {
        return !str || str.trim().length === 0;
    },
    
    /**
     * Generar mensaje de error personalizado
     */
    customMessage(template, replacements) {
        let message = template;
        Object.keys(replacements).forEach(key => {
            message = message.replace(`{${key}}`, replacements[key]);
        });
        return message;
    }
};

/**
 * Validadores específicos para formularios comunes
 */
const CommonValidators = {
    /**
     * Configuración para login
     */
    login: {
        email: {
            required: true,
            email: true
        },
        password: {
            required: true,
            minLength: 6
        }
    },
    
    /**
     * Configuración para registro
     */
    register: {
        nombre: {
            required: true,
            alpha: true,
            minLength: 2,
            maxLength: 50
        },
        apellido: {
            required: true,
            alpha: true,
            minLength: 2,
            maxLength: 50
        },
        correo: {
            required: true,
            email: true
        },
        usuario: {
            required: true,
            username: true
        },
        telefono: {
            phone: true
        },
        contrasena: {
            required: true,
            password: true
        },
        confirmar_contrasena: {
            required: true,
            match: 'contrasena'
        }
    },
    
    /**
     * Configuración para productos
     */
    producto: {
        nombre: {
            required: true,
            minLength: 3,
            maxLength: 100
        },
        marca: {
            required: true,
            minLength: 2,
            maxLength: 50
        },
        precio: {
            required: true,
            decimal: true,
            min: 0.01,
            max: 999999.99
        },
        stock: {
            required: true,
            numeric: true,
            min: 0,
            max: 999999
        }
    }
};

/**
 * Auto-formateo de inputs
 */
class InputFormatter {
    static setupFormatters() {
        // Formatear teléfonos automáticamente
        document.querySelectorAll('input[type="tel"]').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 8) value = value.slice(0, 8);
                if (value.length >= 4) {
                    value = value.slice(0, 4) + '-' + value.slice(4);
                }
                e.target.value = value;
            });
        });
        
        // Formatear precios automáticamente
        document.querySelectorAll('input[data-format="price"]').forEach(input => {
            input.addEventListener('blur', function(e) {
                if (e.target.value) {
                    e.target.value = ValidationUtils.formatPrice(e.target.value);
                }
            });
        });
        
        // Solo números en inputs numéricos
        document.querySelectorAll('input[data-numeric="true"]').forEach(input => {
            input.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        });
        
        // Solo letras en inputs de texto
        document.querySelectorAll('input[data-alpha="true"]').forEach(input => {
            input.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            });
        });
    }
}

// Inicializar formateadores cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        InputFormatter.setupFormatters();
    });
} else {
    InputFormatter.setupFormatters();
}

/**
 * Exportar para uso global
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        FormValidator,
        ValidationUtils,
        CommonValidators,
        ValidationConfig,
        InputFormatter
    };
}