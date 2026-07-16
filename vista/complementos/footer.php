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

        // ── Imprimir vaucher de un pedido del menú digital ───────────────────────
        // El popup se abre ANTES del fetch (todavía dentro del gesto de clic del
        // usuario) para que el navegador no lo bloquee; se rellena al llegar los datos.
        window.imprimirVoucherPedido = function(idVenta) {
            const w = window.open('', '_blank', 'width=320,height=500,toolbar=0,scrollbars=0,status=0,menubar=0');
            if (!w) { console.warn('Popup bloqueado por el navegador'); return; }
            w.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:sans-serif;padding:20px;color:#888;">Cargando...</body></html>');
            w.document.close();

            fetch(BASE + '/ventas/comprobante/' + idVenta)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        w.close();
                        if (typeof Swal !== 'undefined') Swal.fire({ icon:'error', title:'No se pudo obtener el pedido', text: data.message || '' });
                        return;
                    }
                    const venta = data.data;
                    const bf  = parseInt(NOTIF_PAPEL.fontSize) || 13;
                    const css = `
                        *{box-sizing:border-box;margin:0;padding:0;}
                        @page{size:${NOTIF_PAPEL.pageSize};margin:${NOTIF_PAPEL.margin};}
                        body{font-family:'Courier New',monospace;font-size:${bf}pt;width:100%;background:#fff;color:#000;
                             padding:0 2mm;overflow-wrap:break-word;word-break:break-word;}
                        .t-center{text-align:center;}
                        .t-negocio{font-size:${bf + 3}pt;font-weight:900;margin-bottom:3px;}
                        .t-titulo{font-size:${Math.max(8, bf - 2)}pt;letter-spacing:2px;margin-bottom:5px;}
                        .t-sep{border:none;border-top:1px dashed #000;margin:6px 0;}
                        .t-meta{font-size:${Math.max(9, bf - 1)}pt;margin:3px 0;}
                        .t-total{display:flex;justify-content:space-between;font-size:${bf + 1}pt;font-weight:900;margin-top:5px;}
                        table{width:100%;border-collapse:collapse;font-size:${Math.max(9, bf - 1)}pt;}
                        td{vertical-align:top;}
                    `;

                    const ahora   = venta.created_at ? new Date(venta.created_at.replace(' ','T')) : new Date();
                    const fecha   = ahora.toLocaleDateString('es', {day:'2-digit', month:'2-digit', year:'numeric'});
                    const hora    = ahora.toLocaleTimeString('es', {hour:'2-digit', minute:'2-digit'});
                    const negocio = NOTIF_COMERC.nombre || 'CHEFCONTROL';
                    const eslogan = NOTIF_COMERC.eslogan || '';
                    const rut     = NOTIF_COMERC.rut     || '';
                    const mesaTxt = venta.mesa_numero ? ('Mesa ' + venta.mesa_numero + (venta.mesa_nombre ? ' · ' + venta.mesa_nombre : '')) : '';

                    const rows = (venta.items || []).map(it => `
                        <tr>
                            <td style="padding:3px 0;font-weight:900;white-space:nowrap;width:22px;">${it.cantidad}×</td>
                            <td style="padding:3px 6px;">${escN(it.receta_nombre)}</td>
                            <td style="padding:3px 0;text-align:right;white-space:nowrap;">$${parseFloat(it.subtotal).toFixed(2)}</td>
                        </tr>`).join('');

                    const logoHtml = NOTIF_COMERC.logo
                        ? `<div class="t-center" style="margin-bottom:4px;"><img src="${BASE}/assets/uploads/comercio/${NOTIF_COMERC.logo}" style="max-width:110px;max-height:60px;object-fit:contain;"></div>`
                        : '';

                    const body = `
                        ${logoHtml}
                        <div class="t-center t-negocio">${escN(negocio)}</div>
                        ${eslogan ? `<div class="t-center" style="font-size:9pt;">${escN(eslogan)}</div>` : ''}
                        ${rut     ? `<div class="t-center" style="font-size:9pt;">RUT: ${escN(rut)}</div>` : ''}
                        <div class="t-center t-titulo">&#8212; PEDIDO MEN&Uacute; DIGITAL &#8212;</div>
                        <hr class="t-sep">
                        ${mesaTxt ? `<div class="t-meta"><b>${escN(mesaTxt)}</b></div>` : ''}
                        <div class="t-meta">Orden: <b>${escN(venta.numero_orden)}</b></div>
                        <div class="t-meta">${fecha} &nbsp; ${hora}</div>
                        <hr class="t-sep">
                        <table>${rows}</table>
                        <hr class="t-sep">
                        <div class="t-total"><span>TOTAL</span><span>$${parseFloat(venta.total).toFixed(2)}</span></div>
                        <hr class="t-sep">
                        <div class="t-center" style="font-size:9pt;">&iexcl;Gracias por su pedido!</div>
                    `;

                    w.document.open();
                    w.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><style>${css}</style></head><body>${body}<script>window.onload=function(){window.focus();window.print();setTimeout(function(){window.close();},500);}<\/script></body></html>`);
                    w.document.close();
                })
                .catch(e => {
                    if (w && !w.closed) w.close();
                    console.error('Error al imprimir vaucher:', e);
                });
        };

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