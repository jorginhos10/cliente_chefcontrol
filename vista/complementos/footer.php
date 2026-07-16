<?php
// vista/complementos/footer.php

// Datos del negocio + tamaño de papel configurado, para poder imprimir el
// vaucher de un pedido de menú digital desde la campanita de notificaciones
// en CUALQUIER página (footer.php se incluye en todas). Autocontenido con
// try/catch porque no todas las páginas ya tienen $comercio cargado.
$footerComercio = [];
$footerPapel    = ['pageSize' => '80mm auto', 'margin' => '0.2mm', 'fontSize' => '13pt', 'charWidth' => 26];
$footerCodigo   = '';
try {
    // obtenerCodigoFacturacion() exige comercio_id en sesión (aborta si no hay) —
    // solo se intenta si de verdad hay un tenant activo, para no romper páginas
    // públicas o sin sesión que por algún motivo incluyan este footer.
    if (!empty($_SESSION['comercio_id'])) {
        require_once __DIR__ . '/../../modelo/comercioModel.php';
        $footerComercioModel = new ComercioModel();
        $footerComercio = $footerComercioModel->obtener() ?? [];
        $footerPapel    = ComercioModel::parametrosPapel($footerComercio['tamano_papel'] ?? '80mm');
        $footerCodigo   = $footerComercioModel->obtenerCodigoFacturacion();
    }
} catch (\Throwable $e) {}
?>
            </div> <!-- Cierre de contentWrapper -->
        </main>
    </div>

    <!-- Script para el dropdown del usuario -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownBtn  = document.querySelector('.userDropdownBtn');
            const dropdownMenu = document.querySelector('.dropdownMenu');

            function closeDropdown() {
                dropdownMenu.classList.remove('show');
                dropdownBtn.classList.remove('open');
            }

            if (dropdownBtn && dropdownMenu) {
                dropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isOpen = dropdownMenu.classList.contains('show');
                    if (isOpen) {
                        closeDropdown();
                    } else {
                        dropdownMenu.classList.add('show');
                        dropdownBtn.classList.add('open');
                    }
                });

                document.addEventListener('click', closeDropdown);

                // Los links del menú navegan normalmente, el dropdown se cierra solo al ir
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
    </script>

    <!-- Campana de notificaciones (todas las páginas) -->
    <script>
    (function() {
        const bell       = document.getElementById('notifBell');
        const btn        = document.getElementById('notifBtn');
        const badge      = document.getElementById('notifBadge');
        const dropdown   = document.getElementById('notifDropdown');
        const dropBody   = document.getElementById('notifDropBody');
        const dropCount  = document.getElementById('notifDropCount');
        if (!bell) return;

        const BASE = document.querySelector('meta[name="base-path"]')?.content || '';
        let isOpen    = false;
        let prevTotal = 0;
        let notifData = [];

        // ── Datos para imprimir el vaucher de un pedido de menú digital ─────────
        const NOTIF_BASEURL = <?php echo json_encode($baseUrl ?? ''); ?>;
        const NOTIF_COMERC  = <?php echo json_encode($footerComercio); ?>;
        const NOTIF_CODIGO  = <?php echo json_encode($footerCodigo); ?>;
        const NOTIF_PAPEL   = <?php echo json_encode($footerPapel); ?>;

        // ── Carga ──────────────────────────────────────────────────────────────
        async function cargarNotificaciones() {
            try {
                const r = await fetch(BASE + '/notificaciones/resumen');
                const d = await r.json();
                if (!d.success) return;
                notifData = d.data;
                actualizarBadge(d.total);
                if (isOpen) renderDropdown(notifData);

                // Badges del sidebar
                const cocinaBadge = document.getElementById('cocinaBadgeSb');
                if (cocinaBadge) {
                    const c = d.sidebar_cocina || 0;
                    cocinaBadge.textContent = c > 99 ? '99+' : c;
                    cocinaBadge.style.display = c > 0 ? 'inline-flex' : 'none';
                }
                const cocinaDot = document.getElementById('cocinaDotSb');
                if (cocinaDot) cocinaDot.style.display = (d.sidebar_cocina || 0) > 0 ? 'block' : 'none';

                const chatBadge = document.getElementById('chatBadgeSb');
                if (chatBadge) {
                    const c = d.sidebar_chat || 0;
                    chatBadge.textContent = c > 99 ? '99+' : c;
                    chatBadge.style.display = c > 0 ? 'inline-flex' : 'none';
                }
                const chatDot = document.getElementById('chatDotSb');
                if (chatDot) chatDot.style.display = (d.sidebar_chat || 0) > 0 ? 'block' : 'none';

                const pqrsBadge = document.getElementById('pqrsBadgeSb');
                if (pqrsBadge) {
                    const c = d.sidebar_pqrs || 0;
                    pqrsBadge.textContent = c > 99 ? '99+' : c;
                    pqrsBadge.style.display = c > 0 ? 'inline-flex' : 'none';
                }

                const domBadge = document.getElementById('domiciliosBadgeSb');
                if (domBadge) {
                    const c = d.sidebar_domicilios || 0;
                    domBadge.textContent = c > 99 ? '99+' : c;
                    domBadge.style.display = c > 0 ? 'inline-flex' : 'none';
                }
                const domDot = document.getElementById('domiciliosDotSb');
                if (domDot) domDot.style.display = (d.sidebar_domicilios || 0) > 0 ? 'block' : 'none';
            } catch {}
        }

        function actualizarBadge(total) {
            if (total > 0) {
                badge.textContent = total > 99 ? '99+' : total;
                badge.classList.add('show');
            } else {
                badge.classList.remove('show');
            }

            // Sacudir campana sólo cuando llega una nueva notificación
            if (total > prevTotal) {
                btn.classList.add('ringing');
                btn.addEventListener('animationend', () => btn.classList.remove('ringing'), { once: true });
            }
            prevTotal = total;
        }

        // ── Render dropdown ────────────────────────────────────────────────────
        function renderDropdown(data) {
            const unread = data.filter(n => !n.leido).length;
            if (unread > 0) {
                dropCount.textContent = unread;
                dropCount.classList.add('show');
            } else {
                dropCount.classList.remove('show');
            }

            if (!data.length) {
                dropBody.innerHTML =
                    '<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>Sin notificaciones</p></div>';
                return;
            }

            // Ordenar por tiempo descendente (más reciente primero), nulos al final
            const sorted = [...data].sort((a, b) => {
                if (!a.tiempo) return 1;
                if (!b.tiempo) return -1;
                return new Date(b.tiempo.replace(' ', 'T')) - new Date(a.tiempo.replace(' ', 'T'));
            });

            dropBody.innerHTML = sorted.map(n => {
                const fg      = n.color || '#7f8c8d';
                const bg      = fg + '22';
                const t       = n.tiempo ? fmtAgo(n.tiempo) : 'Ahora';
                const unreadC = n.leido ? '' : ' notif-unread';
                const printBtn = n.tipo === 'pedido_digital'
                    ? `<button type="button" class="notif-print-btn" title="Imprimir vaucher" data-id-venta="${n.id_venta}">
                         <i class="fas fa-print"></i>
                       </button>`
                    : '';
                return `<div class="notif-item${unreadC}">
                    <a class="notif-item-link" href="${BASE}${n.url}" onclick="cerrarNotif()">
                        <div class="notif-item-icon" style="background:${bg};color:${fg}">
                            <i class="fas ${n.icon}"></i>
                        </div>
                        <div class="notif-item-body">
                            <div class="notif-item-title">${escN(n.titulo)}</div>
                            ${n.texto ? `<div class="notif-item-text">${escN(n.texto)}</div>` : ''}
                            <div class="notif-item-time"><i class="fas fa-clock"></i> ${t}</div>
                        </div>
                    </a>
                    ${printBtn}
                </div>`;
            }).join('');

            dropBody.querySelectorAll('.notif-print-btn').forEach(b => {
                b.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    imprimirVoucherPedido(this.dataset.idVenta);
                });
            });
        }

        // ── Toggle ─────────────────────────────────────────────────────────────
        btn.addEventListener('click', e => {
            e.stopPropagation();
            isOpen = !isOpen;
            dropdown.classList.toggle('show', isOpen);
            if (isOpen) {
                renderDropdown(notifData);
                // Marcar notificaciones de historial como leídas
                fetch(BASE + '/notificaciones/marcar-leidas', { method: 'POST' })
                    .then(() => {
                        // Actualizar leido=true en los datos locales (historial + pedidos digitales)
                        notifData = notifData.map(n =>
                            (n.tipo === 'estado_cambio' || n.tipo === 'pedido_digital') ? { ...n, leido: true } : n
                        );
                        // Recalcular badge (chat y pedidos siguen contando)
                        const newTotal = notifData.filter(n => !n.leido).length;
                        actualizarBadge(newTotal);
                    }).catch(() => {});
            }
        });

        window.cerrarNotif = function() {
            isOpen = false;
            dropdown.classList.remove('show');
        };

        document.addEventListener('click', e => {
            if (!bell.contains(e.target)) cerrarNotif();
        });

        // ── Helpers ────────────────────────────────────────────────────────────
        function fmtAgo(str) {
            const s = Math.floor((Date.now() - new Date(str.replace(' ','T')).getTime()) / 1000);
            if (s < 60)    return 'Ahora';
            if (s < 3600)  return Math.floor(s / 60) + ' min';
            if (s < 86400) return Math.floor(s / 3600) + 'h';
            return Math.floor(s / 86400) + 'd';
        }
        function escN(s) {
            return String(s ?? '')
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        // Init
        cargarNotificaciones();
        setInterval(cargarNotificaciones, 5000);
    })();
    </script>

    <!-- Badge de chat no leídos: actualizado por el IIFE de notificaciones (sidebar_chat) -->

    <?php echo $jsExtra ?? ''; ?>

    <!-- Configuración global de SweetAlert2: ningún popup se cierra al hacer clic fuera -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swal !== 'undefined') {
            Swal.mixin({ allowOutsideClick: false });
            const _fire = Swal.fire.bind(Swal);
            Swal.fire = function(...args) {
                if (args.length === 1 && typeof args[0] === 'object' && args[0] !== null) {
                    args[0] = Object.assign({ allowOutsideClick: false }, args[0]);
                }
                return _fire(...args);
            };
        }
    });

    // Sobrescribir también cuando SweetAlert2 se carga después del DOMContentLoaded
    (function patchSwal() {
        if (typeof Swal !== 'undefined') {
            if (!Swal.__patched) {
                Swal.__patched = true;
                const _fire = Swal.fire.bind(Swal);
                Swal.fire = function(...args) {
                    if (args.length === 1 && typeof args[0] === 'object' && args[0] !== null) {
                        args[0] = Object.assign({ allowOutsideClick: false }, args[0]);
                    }
                    return _fire(...args);
                };
            }
        } else {
            setTimeout(patchSwal, 50);
        }
    })();
    </script>

    <!-- Service worker + prompt "Agregar a pantalla de inicio" (solo móvil, con sesión iniciada) -->
    <script>
    (function () {
        const BASE = document.querySelector('meta[name="base-path"]')?.content || '';

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(BASE + '/sw.js').catch(() => {});
        }

        const esMovil = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || window.innerWidth <= 768;
        const yaInstalada = window.matchMedia('(display-mode: standalone)').matches
                          || window.navigator.standalone === true;
        if (!esMovil || yaInstalada) return;
        if (sessionStorage.getItem('cc_install_prompt_shown')) return;

        const esIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        let deferredPrompt = null;

        function cerrarBanner(banner) {
            banner.style.transform = 'translateY(120%)';
            setTimeout(() => banner.remove(), 250);
        }

        function mostrarBanner(tipo) {
            if (sessionStorage.getItem('cc_install_prompt_shown')) return;
            sessionStorage.setItem('cc_install_prompt_shown', '1');

            const banner = document.createElement('div');
            banner.style.cssText =
                'position:fixed;left:12px;right:12px;bottom:12px;z-index:99999;' +
                'background:#1a1d27;color:#fff;border-radius:16px;padding:16px 16px 14px;' +
                'box-shadow:0 10px 30px rgba(0,0,0,.35);display:flex;gap:12px;align-items:flex-start;' +
                'transform:translateY(0);transition:transform .25s ease;font-family:inherit;';

            const textoBoton = tipo === 'ios'
                ? 'Toca <i class="fas fa-arrow-up-from-bracket"></i> Compartir y luego "Agregar a inicio".'
                : 'Instálala para entrar más rápido, como una app.';

            banner.innerHTML = `
                <img src="${BASE}/assets/media/src/logo.png" style="width:42px;height:42px;object-fit:contain;border-radius:10px;background:#fff;padding:4px;flex-shrink:0;">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:14px;margin-bottom:2px;">Agregar ChefControl al inicio</div>
                    <div style="font-size:12px;color:rgba(255,255,255,.7);line-height:1.4;">${textoBoton}</div>
                    <div style="display:flex;gap:8px;margin-top:10px;">
                        ${tipo === 'android' ? '<button id="ccInstallBtn" style="background:#3498db;color:#fff;border:none;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:700;cursor:pointer;">Instalar</button>' : ''}
                        <button id="ccInstallDismiss" style="background:rgba(255,255,255,.1);color:#fff;border:none;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;">${tipo === 'ios' ? 'Entendido' : 'Ahora no'}</button>
                    </div>
                </div>
                <button id="ccInstallClose" style="background:none;border:none;color:rgba(255,255,255,.5);font-size:16px;cursor:pointer;padding:2px;flex-shrink:0;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            document.body.appendChild(banner);

            document.getElementById('ccInstallClose').addEventListener('click', () => cerrarBanner(banner));
            document.getElementById('ccInstallDismiss').addEventListener('click', () => cerrarBanner(banner));
            const btnInstalar = document.getElementById('ccInstallBtn');
            if (btnInstalar) {
                btnInstalar.addEventListener('click', async () => {
                    cerrarBanner(banner);
                    if (!deferredPrompt) return;
                    deferredPrompt.prompt();
                    await deferredPrompt.userChoice;
                    deferredPrompt = null;
                });
            }
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            mostrarBanner('android');
        });

        // iOS no dispara beforeinstallprompt: mostramos la instrucción manual directamente.
        if (esIOS) {
            setTimeout(() => mostrarBanner('ios'), 2500);
        }
    })();
    </script>
</body>
</html>