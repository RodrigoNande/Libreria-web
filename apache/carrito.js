// ========================================
// JAVASCRIPT PARA CARRITO MEJORADO
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const carritoToggle = document.getElementById('carrito-toggle');
    const vistaPrevia = document.getElementById('vista-previa-carrito');
    const cerrarVistaPrevia = document.querySelector('.cerrar-vista-previa');
    const overlay = document.getElementById('overlay');
    const modal = document.getElementById('modal-confirmacion');
    const cerrarModal = document.querySelector('.cerrar-modal');
    const continuarComprando = document.getElementById('continuar-comprando');
    const botonesAgregar = document.querySelectorAll('.btn-agregar-carrito');
    const contadorCarrito = document.getElementById('contador-carrito');

    let vistaAbbierta = false;

    // ==========================================
    // VISTA PREVIA DEL CARRITO
    // ==========================================
    
    if (carritoToggle) {
        carritoToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (!vistaAbbierta) {
                cargarVistaPrevia();
                mostrarVistaPrevia();
            } else {
                ocultarVistaPrevia();
            }
        });
    }

    if (cerrarVistaPrevia) {
        cerrarVistaPrevia.addEventListener('click', function() {
            ocultarVistaPrevia();
        });
    }

    // Cerrar vista previa al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (vistaAbbierta && !e.target.closest('.carrito-container')) {
            ocultarVistaPrevia();
        }
    });

    function mostrarVistaPrevia() {
        if (vistaPrevia) {
            vistaPrevia.classList.remove('vista-previa-oculta');
            setTimeout(() => {
                vistaPrevia.classList.add('mostrar');
            }, 10);
            vistaAbbierta = true;
        }
    }

    function ocultarVistaPrevia() {
        if (vistaPrevia) {
            vistaPrevia.classList.remove('mostrar');
            setTimeout(() => {
                vistaPrevia.classList.add('vista-previa-oculta');
            }, 300);
            vistaAbbierta = false;
        }
    }

    function cargarVistaPrevia() {
        fetch('vistapreviacarrito.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                const contenedor = document.getElementById('productos-vista-previa');
                
                if (data.productos && data.productos.length > 0) {
                    let html = '';
                    data.productos.forEach(producto => {
                        html += `
                            <div class="producto-preview">
                                <img src="${producto.imagen || 'img/no-image.png'}" alt="${producto.nombre}">
                                <div class="producto-info">
                                    <h4>${producto.nombre}</h4>
                                    <p>Cantidad: ${producto.cantidad} - $${parseFloat(producto.precio).toFixed(2)}</p>
                                </div>
                            </div>
                        `;
                    });
                    html += `
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                            <strong>Total: $${parseFloat(data.total).toFixed(2)}</strong>
                        </div>
                    `;
                    contenedor.innerHTML = html;
                } else {
                    contenedor.innerHTML = `
                        <div class="carrito-vacio">
                            <p>Tu carrito está vacío</p>
                            <small>¡Agrega algunos productos!</small>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error al cargar vista previa:', error);
                const contenedor = document.getElementById('productos-vista-previa');
                if (contenedor) {
                    contenedor.innerHTML = `
                        <div class="carrito-vacio">
                            <p>Error al cargar el carrito</p>
                            <small>${error.message}</small>
                        </div>
                    `;
                }
            });
    }

    // ==========================================
    // AGREGAR AL CARRITO CON MODAL
    // ==========================================
    
    botonesAgregar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productoId = this.dataset.id;
            const productoNombre = this.dataset.nombre;
            const productoPrecio = this.dataset.precio;
            
            // Validar datos
            if (!productoId || !productoNombre || !productoPrecio) {
                console.error('Datos del producto incompletos', {
                    id: productoId,
                    nombre: productoNombre,
                    precio: productoPrecio
                });
                alert('Error: Datos del producto incompletos');
                return;
            }
            
            // Cambiar estado del botón
            const botonOriginal = this.innerHTML;
            this.innerHTML = 'Agregando...';
            this.classList.add('agregando');
            this.disabled = true;

            // Enviar solicitud AJAX
            fetch('carrito_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${encodeURIComponent(productoId)}&accion=agregar`
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
                }
                
                return response.text().then(text => {
                    console.log('Response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Error parsing JSON: ${e.message}. Response: ${text.substring(0, 200)}...`);
                    }
                });
            })
            .then(data => {
                console.log('Data received:', data);
                
                // Restaurar botón
                this.innerHTML = botonOriginal;
                this.classList.remove('agregando');
                this.disabled = false;

                if (data.exito) {
                    // Actualizar contador
                    actualizarContadorCarrito(data.cantidad_total);
                    
                    // Mostrar modal de confirmación
                    mostrarModalConfirmacion(productoNombre, productoPrecio);
                    
                    // Efecto visual en el botón
                    this.style.background = '#26d45c';
                    setTimeout(() => {
                        this.style.background = '';
                    }, 1000);
                } else {
                    alert('Error: ' + (data.mensaje || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                
                // Restaurar botón
                this.innerHTML = botonOriginal;
                this.classList.remove('agregando');
                this.disabled = false;
                
                alert('Error de conexión: ' + error.message + '\nRevisa la consola para más detalles.');
            });
        });
    });

    function actualizarContadorCarrito(cantidad) {
        if (contadorCarrito) {
            contadorCarrito.textContent = cantidad;
            contadorCarrito.style.animation = 'none';
            setTimeout(() => {
                contadorCarrito.style.animation = 'pulse 0.5s ease-in-out';
            }, 10);
        }
    }

    function mostrarModalConfirmacion(nombre, precio) {
        const mensajeProducto = document.getElementById('mensaje-producto');
        if (mensajeProducto && overlay && modal) {
            mensajeProducto.innerHTML = `<strong>${nombre}</strong><br>$${parseFloat(precio).toFixed(2)} agregado a tu carrito`;
            
            overlay.classList.remove('overlay-oculto');
            modal.classList.remove('modal-oculto');
            
            setTimeout(() => {
                overlay.classList.add('mostrar');
                modal.classList.add('mostrar');
            }, 10);
        }
    }

    function ocultarModal() {
        if (overlay && modal) {
            overlay.classList.remove('mostrar');
            modal.classList.remove('mostrar');
            
            setTimeout(() => {
                overlay.classList.add('overlay-oculto');
                modal.classList.add('modal-oculto');
            }, 300);
        }
    }

    // Event listeners para cerrar modal
    if (cerrarModal) {
        cerrarModal.addEventListener('click', ocultarModal);
    }
    if (continuarComprando) {
        continuarComprando.addEventListener('click', ocultarModal);
    }
    if (overlay) {
        overlay.addEventListener('click', ocultarModal);
    }

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ocultarModal();
            if (vistaAbbierta) {
                ocultarVistaPrevia();
            }
        }
    });

    // ==========================================
    // ANIMACIONES Y EFECTOS VISUALES
    // ==========================================
    
    // Efecto hover en productos
    const productos = document.querySelectorAll('.producto');
    productos.forEach(producto => {
        producto.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        producto.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Auto-cerrar modal después de 10 segundos
    let timeoutModal;
    function autoCloseModal() {
        timeoutModal = setTimeout(() => {
            if (modal && modal.classList.contains('mostrar')) {
                ocultarModal();
            }
        }, 10000);
    }

    // Limpiar timeout si se cierra manualmente
    if (cerrarModal) {
        cerrarModal.addEventListener('click', () => clearTimeout(timeoutModal));
    }
    if (continuarComprando) {
        continuarComprando.addEventListener('click', () => clearTimeout(timeoutModal));
    }

    // Activar auto-close cuando se muestra el modal
    const originalMostrarModal = mostrarModalConfirmacion;
    mostrarModalConfirmacion = function(nombre, precio) {
        originalMostrarModal(nombre, precio);
        autoCloseModal();
    };
});