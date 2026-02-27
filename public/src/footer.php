<?php
/**
 * ======================================================================
 * FOOTER HTML - LIGHT VERSION
 * ======================================================================
 */

$placeholder_buscador = "Buscar...";
if (isset($USUARIO_ROL)) {
    if ($USUARIO_ROL === 'admin' || $USUARIO_ROL === 'supervisor') {
        $placeholder_buscador = "Buscar insumo, categoría, depósito...";
    } else {
        $placeholder_buscador = "Buscar insumo...";
    }
}

if (!isset($body_class) || $body_class != 'login-page-body'): 
?>
    
    <footer class="mt-auto bg-transparent text-muted text-center p-3 border-top">
        <div class="container-fluid">
            <p class="mb-0 small">&copy; <?php echo date("Y"); ?> Gestor de Insumos</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <div class="modal fade search-modal" id="searchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-transparent ps-3 text-muted">
                            <i class="bi bi-search fs-5"></i>
                        </span>
                        <input type="text" class="search-input" id="globalSearchInput" 
                               placeholder="<?php echo htmlspecialchars($placeholder_buscador); ?>" 
                               autocomplete="off">
                    </div>
                </div>
                <div class="modal-body p-0" style="max-height: 60vh; overflow-y: auto;">
                    <div class="list-group list-group-flush" id="searchResults"></div>
                    
                    <div id="noResults" class="p-4 text-center text-muted" style="display:none;">
                        <i class="bi bi-emoji-frown fs-4 mb-2 d-block"></i>
                        <span id="noResultsText">No se encontraron resultados.</span>
                    </div>
                    
                    <div id="searchLoading" class="p-4 text-center text-muted" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <div class="small">Buscando en la base de datos...</div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2 d-flex justify-content-between small text-muted">
                    <div>
                        <span class="badge bg-secondary me-1">Enter</span> seleccionar
                        <span class="badge bg-secondary ms-2 me-1">Esc</span> cerrar
                    </div>
                    <div>Gestor Inteligente v5.0</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const searchModalEl = document.getElementById('searchModal');
        
        if(searchModalEl) {
            const searchModal = new bootstrap.Modal(searchModalEl);
            const input = document.getElementById('globalSearchInput');
            const resultsContainer = document.getElementById('searchResults');
            const noResults = document.getElementById('noResults');
            const loadingState = document.getElementById('searchLoading');
            
            let debounceTimer;

            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault(); 
                    searchModal.show();
                }
            });

            searchModalEl.addEventListener('shown.bs.modal', function () {
                input.value = '';
                resultsContainer.innerHTML = ''; 
                noResults.style.display = 'none';
                loadingState.style.display = 'none';
                input.focus();
            });

            input.addEventListener('input', function(e) {
                const term = e.target.value.trim();
                
                clearTimeout(debounceTimer);
                
                if (term.length < 2) {
                    resultsContainer.innerHTML = '';
                    noResults.style.display = 'none';
                    loadingState.style.display = 'none';
                    return;
                }

                loadingState.style.display = 'block';
                resultsContainer.innerHTML = '';
                noResults.style.display = 'none';

                debounceTimer = setTimeout(async () => {
                    try {
                        const response = await fetch(`api_global_search.php?q=${encodeURIComponent(term)}`);
                        if (!response.ok) throw new Error('Error de red');
                        const data = await response.json();
                        renderResults(data);
                    } catch (error) {
                        console.error(error);
                        loadingState.style.display = 'none';
                        noResults.style.display = 'block';
                        document.getElementById('noResultsText').textContent = "Error al conectar con el buscador.";
                    }
                }, 300);
            });

            function renderResults(data) {
                loadingState.style.display = 'none';
                resultsContainer.innerHTML = '';

                if (data.length === 0) {
                    noResults.style.display = 'block';
                    document.getElementById('noResultsText').textContent = "No se encontraron coincidencias.";
                } else {
                    noResults.style.display = 'none';
                    const grouped = data.reduce((acc, item) => {
                        (acc[item.category] = acc[item.category] || []).push(item);
                        return acc;
                    }, {});

                    for (const [category, items] of Object.entries(grouped)) {
                        const header = document.createElement('div');
                        header.className = 'search-category-header';
                        header.textContent = category;
                        resultsContainer.appendChild(header);

                        items.forEach(item => {
                            const link = document.createElement('a');
                            link.href = item.url;
                            link.className = 'list-group-item list-group-item-action d-flex align-items-center';
                            link.innerHTML = `
                                <div class="d-flex align-items-center flex-grow-1">
                                    <div class="bg-light rounded p-2 me-3 text-center" style="width:40px; height:40px;">
                                        <i class="bi ${item.icon} text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium text-dark">${item.title}</div>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            `;
                            resultsContainer.appendChild(link);
                        });
                    }
                }
            }
        }
    });
    </script>

    <script>
        function mostrarToast(mensaje, tipo = 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                },
                customClass: { popup: `toast-popup bg-${tipo} text-white` }
            });
            let icon = (tipo === 'danger') ? 'error' : (tipo === 'warning' ? 'warning' : 'success');
            Toast.fire({ icon: icon, title: mensaje });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Menú Móvil
            const btnMenu = document.getElementById("btn-menu-movil");
            const sidebar = document.querySelector(".sidebar");
            const overlay = document.getElementById("sidebar-overlay");
            if (btnMenu && sidebar && overlay) {
                const cerrarMenu = () => { sidebar.classList.remove("sidebar-show"); overlay.classList.remove("sidebar-overlay-show"); };
                const abrirMenu = () => { sidebar.classList.add("sidebar-show"); overlay.classList.add("sidebar-overlay-show"); };
                btnMenu.addEventListener("click", function() {
                    if (sidebar.classList.contains("sidebar-show")) cerrarMenu(); else abrirMenu();
                });
                overlay.addEventListener("click", cerrarMenu);
            }
        });
    </script>

    <?php endif; ?>

</body>
</html>