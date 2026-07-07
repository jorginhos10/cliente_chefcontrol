<?php
// vista/complementos/footer.php
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
                return `<a class="notif-item${unreadC}" href="${BASE}${n.url}" onclick="cerrarNotif()">
                    <div class="notif-item-icon" style="background:${bg};color:${fg}">
                        <i class="fas ${n.icon}"></i>
                    </div>
                    <div class="notif-item-body">
                        <div class="notif-item-title">${escN(n.titulo)}</div>
                        ${n.texto ? `<div class="notif-item-text">${escN(n.texto)}</div>` : ''}
                        <div class="notif-item-time"><i class="fas fa-clock"></i> ${t}</div>
                    </div>
                </a>`;
            }).join('');
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
                        // Actualizar leido=true en los datos locales (historial)
                        notifData = notifData.map(n =>
                            n.tipo === 'estado_cambio' ? { ...n, leido: true } : n
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
</body>
</html>