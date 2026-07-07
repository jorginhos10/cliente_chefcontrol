<?php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Facturación - CHEFCONTROL';
$paginaActual = 'facturacion';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$btnCancelar        = (int)($comercioConfig['btn_cancelar_venta']     ?? 0);
$imprimirComanda    = (int)($comercioConfig['imprimir_comanda_auto']  ?? 0);
$imprimirFactura    = (int)($comercioConfig['imprimir_factura_cobro'] ?? 0);
$propinaActiva      = (int)($comercioConfig['propina_activa']         ?? 0);
$propinaPorcentaje  = (int)($comercioConfig['propina_porcentaje']     ?? 10);
$propinaLabelHeader    = (int)($comercioConfig['propina_label_header']    ?? 1);
$propinaDistribucion   = $comercioConfig['propina_distribucion']          ?? 'individual';
$propinaNumPersonas    = max(2, (int)($comercioConfig['propina_num_personas'] ?? 2));

require_once __DIR__ . '/../complementos/header.php';
?>

<div style="padding:28px;">

    <!-- Cabecera -->
    <div style="background:linear-gradient(135deg,#0a6e63,#0d8a7e);border-radius:16px;padding:28px 32px;color:#fff;display:flex;align-items:center;gap:20px;margin-bottom:28px;box-shadow:0 4px 20px rgba(0,0,0,.12);">
        <div style="width:60px;height:60px;background:rgba(255,255,255,.2);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div>
            <h1 style="margin:0 0 4px;font-size:26px;font-weight:800;">Facturación</h1>
            <p style="margin:0;opacity:.75;font-size:14px;">Gestión de facturación electrónica y documentos fiscales</p>
        </div>
    </div>

    <!-- Grid de dos columnas -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

        <!-- ── Sección: Ventas ── -->
        <div style="background:#fff;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden;">
            <div style="padding:18px 24px;border-bottom:1px solid #f0f2f5;display:flex;align-items:center;gap:12px;">
                <div style="width:36px;height:36px;background:#e8f8f7;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#0d8a7e;font-size:16px;flex-shrink:0;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:15px;color:#2c3e50;">Ventas</div>
                    <div style="font-size:12px;color:#95a5a6;">Opciones relacionadas con el módulo de ventas</div>
                </div>
            </div>
            <div style="padding:8px 0;">

                <label class="fact-toggle-row" for="chkCancelarVenta">
                    <div class="fact-toggle-info">
                        <span class="fact-toggle-label">Agregar botón para cancelar ventas</span>
                        <span class="fact-toggle-desc">Muestra un botón en el historial de ventas que permite cancelar o anular una venta registrada.</span>
                    </div>
                    <div class="fact-toggle-wrap">
                        <input type="checkbox" id="chkCancelarVenta" class="fact-toggle-input"
                               <?php echo $btnCancelar ? 'checked' : ''; ?>>
                        <span class="fact-toggle-slider"></span>
                    </div>
                </label>

                <label class="fact-toggle-row" for="chkImprimirComanda">
                    <div class="fact-toggle-info">
                        <span class="fact-toggle-label">Imprimir comanda automáticamente</span>
                        <span class="fact-toggle-desc">Envía la comanda a la impresora de forma automática cada vez que se registra una nueva orden.</span>
                    </div>
                    <div class="fact-toggle-wrap">
                        <input type="checkbox" id="chkImprimirComanda" class="fact-toggle-input"
                               <?php echo $imprimirComanda ? 'checked' : ''; ?>>
                        <span class="fact-toggle-slider"></span>
                    </div>
                </label>

                <label class="fact-toggle-row" for="chkImprimirFactura">
                    <div class="fact-toggle-info">
                        <span class="fact-toggle-label">Imprimir factura total de la mesa al cobrar</span>
                        <span class="fact-toggle-desc">Imprime automáticamente la factura completa con todos los platos y el total al momento de cobrar una mesa.</span>
                    </div>
                    <div class="fact-toggle-wrap">
                        <input type="checkbox" id="chkImprimirFactura" class="fact-toggle-input"
                               <?php echo $imprimirFactura ? 'checked' : ''; ?>>
                        <span class="fact-toggle-slider"></span>
                    </div>
                </label>

            </div>
        </div>

        <!-- ── Sección: Propinas ── -->
        <div style="background:#fff;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden;">
            <div style="padding:18px 24px;border-bottom:1px solid #f0f2f5;display:flex;align-items:center;gap:12px;">
                <div style="width:36px;height:36px;background:#fff8e6;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#f0a500;font-size:16px;flex-shrink:0;">
                    <i class="fas fa-hand-holding-dollar"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700;font-size:15px;color:#2c3e50;">Propinas</div>
                    <div style="font-size:12px;color:#95a5a6;">Configuración de propinas en las mesas</div>
                </div>
                <a href="<?php echo $basePath; ?>/propinas"
                   style="font-size:12px;font-weight:600;color:#0d8a7e;text-decoration:none;display:flex;align-items:center;gap:5px;background:#e8f8f7;padding:5px 12px;border-radius:20px;transition:background .15s;"
                   onmouseover="this.style.background='#d0f0ed'" onmouseout="this.style.background='#e8f8f7'">
                    <i class="fas fa-arrow-up-right-from-square" style="font-size:11px;"></i> Ver reporte
                </a>
            </div>
            <div style="padding:8px 0;">

                <!-- Label propinas en header -->
                <label class="fact-toggle-row" for="chkPropinaLabel">
                    <div class="fact-toggle-info">
                        <span class="fact-toggle-label">Mostrar propinas acumuladas en el menú de usuario</span>
                        <span class="fact-toggle-desc">Muestra al usuario cuánto ha acumulado en propinas durante el período vigente, visible en el menú desplegable del header.</span>
                    </div>
                    <div class="fact-toggle-wrap">
                        <input type="checkbox" id="chkPropinaLabel" class="fact-toggle-input"
                               <?php echo $propinaLabelHeader ? 'checked' : ''; ?>>
                        <span class="fact-toggle-slider"></span>
                    </div>
                </label>

                <!-- Distribución (visible solo cuando el label está activo) -->
                <div id="seccionDistribucion" style="padding:14px 24px 16px;border-top:1px solid #f7f8fa;<?php echo !$propinaLabelHeader ? 'display:none;' : ''; ?>">
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:200px;">
                            <div style="font-size:13px;font-weight:600;color:#2c3e50;margin-bottom:3px;">Distribución del monto</div>
                            <div style="font-size:12px;color:#95a5a6;">Define cómo se mostrará el monto en la etiqueta.</div>
                        </div>
                        <select id="selDistribucion"
                                style="border:1.5px solid #dde1e7;border-radius:8px;padding:7px 12px;font-size:13px;color:#2c3e50;background:#fff;outline:none;cursor:pointer;min-width:160px;">
                            <option value="individual" <?php echo $propinaDistribucion === 'individual' ? 'selected' : ''; ?>>Individual</option>
                            <option value="colectiva"  <?php echo $propinaDistribucion === 'colectiva'  ? 'selected' : ''; ?>>Colectiva</option>
                        </select>
                    </div>

                    <!-- Panel personas (solo cuando es colectiva) -->
                    <div id="panelPersonasDistrib" style="margin-top:12px;background:#f8f9fa;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:12px;<?php echo $propinaDistribucion !== 'colectiva' ? 'display:none;' : ''; ?>">
                        <i class="fas fa-people-group" style="color:#0d8a7e;font-size:16px;"></i>
                        <span style="font-size:13px;color:#2c3e50;font-weight:500;">Dividir entre</span>
                        <input type="number" id="inputPersonasDistrib" min="2" max="99"
                               value="<?php echo $propinaNumPersonas; ?>"
                               style="width:64px;border:1.5px solid #dde1e7;border-radius:8px;padding:5px 8px;font-size:14px;font-weight:700;text-align:center;color:#2c3e50;outline:none;">
                        <span style="font-size:13px;color:#95a5a6;">personas</span>
                    </div>
                </div>

                <!-- Propina voluntaria -->
                <label class="fact-toggle-row" for="chkPropina">
                    <div class="fact-toggle-info">
                        <span class="fact-toggle-label">Activar propina voluntaria</span>
                        <span class="fact-toggle-desc">Muestra una propina sugerida en la cuenta de la mesa. El cliente decide si la incluye.</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <input type="number" id="inputPropinaPct" min="1" max="100"
                                   value="<?php echo $propinaPorcentaje; ?>"
                                   <?php echo !$propinaActiva ? 'disabled' : ''; ?>
                                   style="width:60px;padding:5px 8px;border:1.5px solid #dde1e7;border-radius:8px;font-size:14px;font-weight:700;text-align:center;color:#2c3e50;background:<?php echo !$propinaActiva ? '#f0f2f5' : '#fff'; ?>;outline:none;transition:all .2s;">
                            <span style="font-size:13px;color:#95a5a6;font-weight:600;">%</span>
                        </div>
                        <div class="fact-toggle-wrap">
                            <input type="checkbox" id="chkPropina" class="fact-toggle-input"
                                   <?php echo $propinaActiva ? 'checked' : ''; ?>>
                            <span class="fact-toggle-slider"></span>
                        </div>
                    </div>
                </label>

                <!-- Período de propina -->
                <div style="padding:16px 24px;border-top:1px solid #f7f8fa;">
                    <div style="font-size:14px;font-weight:600;color:#2c3e50;margin-bottom:4px;">Definición de período de propina</div>
                    <div style="font-size:12px;color:#95a5a6;margin-bottom:14px;">Define cada cuánto se liquidan las propinas del personal.</div>

                    <!-- Presets -->
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;" id="periodPresets">
                        <?php
                        $presets = [1,7,10,14,15,20,30];
                        foreach ($presets as $d):
                        ?>
                        <button type="button" class="period-preset-btn" data-dias="<?php echo $d; ?>">
                            <?php echo $d; ?> <?php echo $d === 1 ? 'día' : 'días'; ?>
                        </button>
                        <?php endforeach; ?>
                        <button type="button" class="period-preset-btn" data-dias="personalizado" id="btnPersonalizado">
                            <i class="fas fa-sliders" style="font-size:11px;"></i> Personalizado
                        </button>
                    </div>

                    <!-- Picker personalizado -->
                    <div id="customPeriodPicker" style="display:none;background:#f8f9fa;border-radius:10px;padding:14px;margin-bottom:12px;border:1.5px solid #e8ecf0;">
                        <div style="font-size:12px;font-weight:700;color:#7f8c8d;margin-bottom:10px;text-transform:uppercase;letter-spacing:.4px;">Agregar período personalizado</div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <label style="font-size:13px;color:#2c3e50;font-weight:600;">Del día</label>
                                <select id="pickerDesde" style="border:1.5px solid #dde1e7;border-radius:8px;padding:6px 10px;font-size:13px;color:#2c3e50;outline:none;background:#fff;"></select>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <label style="font-size:13px;color:#2c3e50;font-weight:600;">al día</label>
                                <select id="pickerHasta" style="border:1.5px solid #dde1e7;border-radius:8px;padding:6px 10px;font-size:13px;color:#2c3e50;outline:none;background:#fff;"></select>
                                <span style="font-size:12px;color:#95a5a6;">de cada mes</span>
                            </div>
                            <button type="button" id="btnAgregarPeriodo"
                                    style="background:#0d8a7e;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>
                        <div style="font-size:11px;color:#95a5a6;margin-top:8px;"><i class="fas fa-circle-info"></i> Si el mes tiene menos días, el período se ajusta automáticamente al último día disponible.</div>
                    </div>

                    <!-- Chips de períodos seleccionados -->
                    <div id="periodChips" style="display:flex;flex-wrap:wrap;gap:8px;min-height:28px;"></div>
                </div>

                <!-- Info -->
                <div style="margin:4px 24px 16px;padding:14px 16px;background:#fffbf0;border-radius:10px;border:1px solid rgba(240,165,0,.2);display:flex;align-items:flex-start;gap:12px;">
                    <i class="fas fa-circle-info" style="color:#f0a500;margin-top:1px;flex-shrink:0;"></i>
                    <div style="font-size:12px;color:#7f8c8d;line-height:1.5;">
                        Las propinas cobradas se acumulan automáticamente. Consúltalas en el
                        <a href="<?php echo $basePath; ?>/propinas" style="color:#f0a500;font-weight:600;text-decoration:none;">módulo de Propinas</a>.
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /grid -->
</div>

<style>
.fact-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 16px 24px;
    cursor: pointer;
    transition: background .15s;
    border-bottom: 1px solid #f7f8fa;
}
.fact-toggle-row:last-child { border-bottom: none; }
.fact-toggle-row:hover { background: #fafbfc; }

.fact-toggle-info { flex: 1; min-width: 0; }
.fact-toggle-label { display: block; font-size: 14px; font-weight: 600; color: #2c3e50; margin-bottom: 3px; }
.fact-toggle-desc  { display: block; font-size: 12px; color: #95a5a6; line-height: 1.4; }

.fact-toggle-wrap  { position: relative; flex-shrink: 0; }
.fact-toggle-input { position: absolute; opacity: 0; width: 0; height: 0; }
.fact-toggle-slider {
    display: block;
    width: 44px; height: 24px;
    background: #dde1e7;
    border-radius: 12px;
    cursor: pointer;
    transition: background .2s;
    position: relative;
}
.fact-toggle-slider::after {
    content: '';
    position: absolute;
    top: 3px; left: 3px;
    width: 18px; height: 18px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 1px 4px rgba(0,0,0,.2);
    transition: transform .2s;
}
.fact-toggle-input:checked + .fact-toggle-slider { background: #0d8a7e; }
.fact-toggle-input:checked + .fact-toggle-slider::after { transform: translateX(20px); }

.period-preset-btn {
    border: 1.5px solid #dde1e7;
    background: #fff;
    border-radius: 20px;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 600;
    color: #7f8c8d;
    cursor: pointer;
    transition: all .15s;
}
.period-preset-btn:hover { border-color: #0d8a7e; color: #0d8a7e; background: #e8f8f7; }
.period-preset-btn.active { background: #0d8a7e; color: #fff; border-color: #0d8a7e; }

.period-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e8f8f7;
    color: #0a6e63;
    border-radius: 20px;
    padding: 5px 10px 5px 13px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #c0ece8;
}
.period-chip-remove {
    background: none;
    border: none;
    cursor: pointer;
    color: #0a6e63;
    font-size: 15px;
    line-height: 1;
    padding: 0;
    opacity: .55;
    transition: opacity .1s;
}
.period-chip-remove:hover { opacity: 1; }
</style>

<script>
(function () {
    const basePath = '<?php echo $basePath; ?>';

    function guardarConfig(campo, valor) {
        fetch(basePath + '/facturacion/guardarConfig', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ campo, valor }),
        })
        .then(r => r.json())
        .then(data => { if (!data.success) console.error('Error al guardar configuración'); })
        .catch(e => console.error(e));
    }

    const seccionDistribucion  = document.getElementById('seccionDistribucion');
    const selDistribucion      = document.getElementById('selDistribucion');
    const panelPersonasDistrib = document.getElementById('panelPersonasDistrib');
    const inputPersonasDistrib = document.getElementById('inputPersonasDistrib');

    document.getElementById('chkPropinaLabel').addEventListener('change', function () {
        guardarConfig('propina_label_header', this.checked ? 1 : 0);
        seccionDistribucion.style.display = this.checked ? '' : 'none';
    });

    selDistribucion.addEventListener('change', function () {
        guardarConfig('propina_distribucion', this.value);
        panelPersonasDistrib.style.display = this.value === 'colectiva' ? '' : 'none';
    });

    let timerPersonas;
    inputPersonasDistrib.addEventListener('input', function () {
        clearTimeout(timerPersonas);
        timerPersonas = setTimeout(() => {
            const val = Math.max(2, Math.min(99, parseInt(this.value) || 2));
            this.value = val;
            guardarConfig('propina_num_personas', val);
        }, 600);
    });

    document.getElementById('chkCancelarVenta').addEventListener('change', function () {
        guardarConfig('btn_cancelar_venta', this.checked ? 1 : 0);
    });
    document.getElementById('chkImprimirComanda').addEventListener('change', function () {
        guardarConfig('imprimir_comanda_auto', this.checked ? 1 : 0);
    });
    document.getElementById('chkImprimirFactura').addEventListener('change', function () {
        guardarConfig('imprimir_factura_cobro', this.checked ? 1 : 0);
    });

    const chkPropina      = document.getElementById('chkPropina');
    const inputPropinaPct = document.getElementById('inputPropinaPct');
    let propinaTimer;

    chkPropina.addEventListener('change', function () {
        inputPropinaPct.disabled         = !this.checked;
        inputPropinaPct.style.background = this.checked ? '#fff' : '#f0f2f5';
        guardarConfig('propina_activa', this.checked ? 1 : 0);
    });

    inputPropinaPct.addEventListener('input', function () {
        clearTimeout(propinaTimer);
        propinaTimer = setTimeout(() => {
            const val = Math.max(1, Math.min(100, parseInt(this.value) || 1));
            this.value = val;
            guardarConfig('propina_porcentaje', val);
        }, 600);
    });

    // ── Período de propina ──────────────────────────────────────
    const rawPeriodo = <?php echo json_encode($comercioConfig['propina_periodo_config'] ?? '[]'); ?>;
    let periodConfig = [];
    try { periodConfig = JSON.parse(rawPeriodo) || []; } catch(e) { periodConfig = []; }

    function poblarSelectDias(sel, defaultVal) {
        sel.innerHTML = '';
        for (let i = 1; i <= 31; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = i;
            if (i === defaultVal) opt.selected = true;
            sel.appendChild(opt);
        }
    }
    const pickerDesde = document.getElementById('pickerDesde');
    const pickerHasta = document.getElementById('pickerHasta');
    poblarSelectDias(pickerDesde, 1);
    poblarSelectDias(pickerHasta, 15);

    function savePeriodConfig() {
        guardarConfig('propina_periodo_config', JSON.stringify(periodConfig));
    }

    function renderChips() {
        const container = document.getElementById('periodChips');
        if (!periodConfig.length) {
            container.innerHTML = '<span style="font-size:12px;color:#bdc3cb;font-style:italic;">Ningún período definido</span>';
            return;
        }
        container.innerHTML = periodConfig.map((p, i) => {
            const label = p.tipo === 'predefinido'
                ? `${p.valor} ${p.valor === 1 ? 'día' : 'días'}`
                : `Del día ${p.desde} al ${p.hasta} de cada mes`;
            return `<span class="period-chip">${label}<button type="button" class="period-chip-remove" data-idx="${i}" title="Eliminar">×</button></span>`;
        }).join('');
        container.querySelectorAll('.period-chip-remove').forEach(btn => {
            btn.addEventListener('click', function () {
                periodConfig.splice(parseInt(this.dataset.idx), 1);
                updatePresetButtons();
                renderChips();
                savePeriodConfig();
            });
        });
    }

    function updatePresetButtons() {
        document.querySelectorAll('#periodPresets .period-preset-btn').forEach(btn => {
            if (btn.dataset.dias === 'personalizado') return;
            const val = parseInt(btn.dataset.dias);
            btn.classList.toggle('active', periodConfig.some(p => p.tipo === 'predefinido' && p.valor === val));
        });
    }

    document.querySelectorAll('#periodPresets .period-preset-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.dataset.dias === 'personalizado') {
                const picker = document.getElementById('customPeriodPicker');
                picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
                return;
            }
            const val = parseInt(this.dataset.dias);
            const idx = periodConfig.findIndex(p => p.tipo === 'predefinido' && p.valor === val);
            if (idx >= 0) periodConfig.splice(idx, 1);
            else periodConfig.push({ tipo: 'predefinido', valor: val });
            updatePresetButtons();
            renderChips();
            savePeriodConfig();
        });
    });

    document.getElementById('btnAgregarPeriodo').addEventListener('click', function () {
        const desde = parseInt(pickerDesde.value);
        const hasta = parseInt(pickerHasta.value);
        if (desde >= hasta) {
            alert('El día "Del" debe ser menor que el día "al".');
            return;
        }
        if (periodConfig.some(p => p.tipo === 'personalizado' && p.desde === desde && p.hasta === hasta)) {
            alert('Este período ya está definido.');
            return;
        }
        periodConfig.push({ tipo: 'personalizado', desde, hasta });
        renderChips();
        savePeriodConfig();
        pickerDesde.value = 1;
        pickerHasta.value = 15;
    });

    updatePresetButtons();
    renderChips();
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
