<?php
session_start();
require_once 'config.php';
requireLogin();

$conn = conectarDB();
$uid  = (int)$_SESSION['usuario_id'];
$msg  = $err = '';

// Procesar recarga
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['recargar'])) {
    $monto = (float)($_POST['monto'] ?? 0);
    if ($monto < 100) {
        $err = 'El monto mínimo de recarga es $100.00 MXN.';
    } elseif ($monto > 50000) {
        $err = 'El monto máximo por recarga es $50,000.00 MXN.';
    } else {
        // Obtener saldo anterior
        $saldo_ant = (float)$conn->query("SELECT saldo_cartera FROM usuarios WHERE id=$uid")->fetch_assoc()['saldo_cartera'];
        $saldo_nuevo = $saldo_ant + $monto;

        $conn->query("UPDATE usuarios SET saldo_cartera=saldo_cartera+$monto WHERE id=$uid");

        $desc = "Recarga de saldo";
        $st   = $conn->prepare("INSERT INTO cartera_movimientos (usuario_id, tipo, monto, saldo_anterior, saldo_posterior, descripcion) VALUES (?,?,?,?,?,?)");
        $tipo = 'recarga';
        $st->bind_param('isddds', $uid, $tipo, $monto, $saldo_ant, $saldo_nuevo, $desc);
        $st->execute();

        $msg = "Se acreditaron " . formatoPrecio($monto) . " a tu cartera correctamente.";
    }
}

$user = $conn->query("SELECT * FROM usuarios WHERE id=$uid")->fetch_assoc();
$movs = $conn->query("SELECT * FROM cartera_movimientos WHERE usuario_id=$uid ORDER BY creado_en DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// QR de la cartera del usuario
$qr_wallet = generarQR($user['wallet_address'] ?? 'sin-wallet', 140);

$conn->close();
$page_title = 'Mi Cartera';
include 'header.php';
?>
    <style>
        .cartera-grid{display:grid;grid-template-columns:1fr 380px;gap:32px;align-items:start}

        /* Hero saldo */
        .saldo-hero{
            background:linear-gradient(135deg,#1a0035 0%,#00102a 100%);
            border:1px solid rgba(191,90,242,.4);border-radius:20px;
            padding:36px;text-align:center;margin-bottom:24px;
            position:relative;overflow:hidden
        }
        .saldo-hero::before{content:'💎';font-size:9rem;position:absolute;right:-15px;bottom:-25px;opacity:.06;pointer-events:none}
        .saldo-label{font-size:.72rem;text-transform:uppercase;letter-spacing:2px;color:var(--text-dim);margin-bottom:8px}
        .saldo-amount{font-family:'Orbitron',monospace;font-size:2.8rem;color:var(--purple);line-height:1;text-shadow:0 0 30px rgba(191,90,242,.5)}
        .saldo-crypto{font-size:.82rem;color:var(--text-dim);margin-top:8px}
        .wallet-id{
            display:inline-block;margin-top:16px;
            font-family:monospace;font-size:.72rem;color:var(--text-dim);
            background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);
            border-radius:8px;padding:8px 14px;word-break:break-all
        }

        /* Stats rápidas */
        .stats-mini{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:24px}
        .stat-mini{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:14px;text-align:center}
        .stat-mini-val{font-family:'Orbitron',monospace;font-size:.95rem;color:var(--cyan)}
        .stat-mini-lbl{font-size:.7rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:.5px;margin-top:4px}

        /* Historial */
        .mov-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--border)}
        .mov-row:last-child{border-bottom:none}
        .mov-tipo{display:inline-flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:3px 8px;border-radius:4px}
        .tipo-recarga{background:rgba(57,255,20,.1);color:var(--green)}
        .tipo-compra{background:rgba(255,45,85,.1);color:#ff6b6b}
        .tipo-transferencia_enviada{background:rgba(0,245,255,.1);color:var(--cyan)}
        .tipo-transferencia_recibida{background:rgba(191,90,242,.1);color:var(--purple)}
        .tipo-reembolso{background:rgba(255,107,0,.1);color:var(--orange)}
        .mov-monto{font-family:'Orbitron',monospace;font-size:.95rem;white-space:nowrap}

        /* Panel derecho */
        .recarga-card{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;position:sticky;top:80px}

        .montos-rapidos{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:12px 0 20px}
        .monto-btn{
            padding:12px;border-radius:8px;border:1px solid var(--border);
            background:var(--bg-dark);color:var(--text);font-family:'Rajdhani',sans-serif;
            font-weight:600;cursor:pointer;transition:.2s;font-size:.9rem
        }
        .monto-btn:hover{border-color:var(--purple);color:var(--purple);background:rgba(191,90,242,.06)}

        /* QR wallet */
        .qr-wallet-box{
            background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);
            border-radius:12px;padding:16px;text-align:center;margin-top:20px
        }
        .qr-wallet-box img{background:white;padding:8px;border-radius:8px;display:inline-block}
        .qr-wallet-lbl{font-size:.72rem;color:var(--text-dim);margin-top:8px;letter-spacing:.5px;text-transform:uppercase}

        @media(max-width:900px){.cartera-grid{grid-template-columns:1fr}.stats-mini{grid-template-columns:1fr 1fr}}
    </style>

    <h1 class="section-title">💎 MI CARTERA DIGITAL</h1>

    <div class="cartera-grid">
        <div>
            <!-- Saldo principal -->
            <div class="saldo-hero">
                <div class="saldo-label">Saldo disponible</div>
                <div class="saldo-amount"><?= formatoPrecio($user['saldo_cartera']) ?></div>
                <div class="saldo-crypto">≈ <?= number_format((float)$user['saldo_crypto'], 8) ?> BTC</div>
                <div class="wallet-id"><?= limpiar($user['wallet_address'] ?? 'Sin wallet asignada') ?></div>
            </div>

            <!-- Stats rápidas -->
            <?php
            $conn2 = conectarDB();
            $total_recargado = $conn2->query("SELECT COALESCE(SUM(monto),0) as t FROM cartera_movimientos WHERE usuario_id=$uid AND tipo='recarga'")->fetch_assoc()['t'];
            $total_gastado   = $conn2->query("SELECT COALESCE(SUM(monto),0) as t FROM cartera_movimientos WHERE usuario_id=$uid AND tipo='compra'")->fetch_assoc()['t'];
            $total_movs      = $conn2->query("SELECT COUNT(*) as c FROM cartera_movimientos WHERE usuario_id=$uid")->fetch_assoc()['c'];
            $conn2->close();
            ?>
            <div class="stats-mini">
                <div class="stat-mini">
                    <div class="stat-mini-val"><?= formatoPrecio($total_recargado) ?></div>
                    <div class="stat-mini-lbl">Total recargado</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-val"><?= formatoPrecio($total_gastado) ?></div>
                    <div class="stat-mini-lbl">Total gastado</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-val"><?= $total_movs ?></div>
                    <div class="stat-mini-lbl">Movimientos</div>
                </div>
            </div>

            <!-- Historial -->
            <div class="card">
                <h3 style="font-family:'Orbitron',monospace;color:var(--cyan);font-size:.95rem;margin-bottom:20px">
                    📊 HISTORIAL DE MOVIMIENTOS
                </h3>
                <?php if(empty($movs)): ?>
                    <p style="color:var(--text-dim);text-align:center;padding:30px">
                        Aún no hay movimientos en tu cartera.
                    </p>
                <?php else: ?>
                    <?php
                    $tipos_label = [
                        'recarga'               => '⬆ Recarga',
                        'compra'                => '⬇ Compra',
                        'transferencia_enviada' => '↗ Enviado',
                        'transferencia_recibida'=> '↙ Recibido',
                        'reembolso'             => '↺ Reembolso',
                    ];
                    foreach($movs as $m):
                        ?>
                        <div class="mov-row">
                            <div>
                    <span class="mov-tipo tipo-<?= $m['tipo'] ?>">
                        <?= $tipos_label[$m['tipo']] ?? $m['tipo'] ?>
                    </span>
                                <div style="font-size:.78rem;color:var(--text-dim);margin-top:5px">
                                    <?= limpiar($m['descripcion']) ?> &nbsp;·&nbsp;
                                    <?= date('d/m/Y H:i', strtotime($m['creado_en'])) ?>
                                </div>
                            </div>
                            <div class="mov-monto" style="color:<?= in_array($m['tipo'],['recarga','transferencia_recibida','reembolso'])?'var(--green)':'#ff6b6b' ?>">
                                <?= in_array($m['tipo'],['recarga','transferencia_recibida','reembolso'])?'+':'-' ?><?= formatoPrecio($m['monto']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel de recarga -->
        <div class="recarga-card">
            <h3 style="font-family:'Orbitron',monospace;color:var(--purple);margin-bottom:16px;font-size:.95rem">
                ⚡ AGREGAR SALDO
            </h3>

            <?php if($msg): ?>
                <div class="alert alert-success">✅ <?= limpiar($msg) ?></div>
            <?php endif; ?>
            <?php if($err): ?>
                <div class="alert alert-error">❌ <?= limpiar($err) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Monto a agregar (MXN)</label>
                    <input type="number" name="monto" id="monto-input" class="form-control"
                           placeholder="0.00" min="100" max="50000" step="0.01" required>
                </div>

                <div class="montos-rapidos">
                    <button type="button" class="monto-btn" onclick="setMonto(500)">$500</button>
                    <button type="button" class="monto-btn" onclick="setMonto(1000)">$1,000</button>
                    <button type="button" class="monto-btn" onclick="setMonto(2500)">$2,500</button>
                    <button type="button" class="monto-btn" onclick="setMonto(5000)">$5,000</button>
                </div>

                <button type="submit" name="recargar" class="btn btn-purple"
                        style="width:100%;justify-content:center">
                    💎 CONFIRMAR RECARGA
                </button>
            </form>

            <hr style="border-color:var(--border);margin:20px 0">

            <div style="font-size:.8rem;color:var(--text-dim);line-height:1.8">
                🔐 Saldo protegido y cifrado<br>
                💸 Sin comisiones en compras internas<br>
                ↺ Reembolsos procesados en 24-48h<br>
                📦 Úsalo en cualquier pedido
            </div>

            <!-- QR de la wallet -->
            <div class="qr-wallet-box">
                <img src="<?= $qr_wallet ?>" alt="QR Wallet" width="140" height="140">
                <div class="qr-wallet-lbl">Tu dirección de wallet</div>
            </div>
        </div>
    </div>

    <script>
        function setMonto(m) {
            document.getElementById('monto-input').value = m;
            document.getElementById('monto-input').focus();
        }
    </script>

<?php include 'footer.php'; ?>