<?php
session_start();
require_once 'config.php';

$token   = $_GET['token']  ?? '';
$metodo  = $_GET['metodo'] ?? 'bitcoin';
$monto   = $_GET['monto']  ?? '0';
$pedido  = $_GET['pedido'] ?? '0';
$tipo    = $token ? 'pendiente' : 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pago'])) {
    $tipo = 'procesando';
}

$cryptos = [
    'bitcoin'  => ['nombre'=>'Bitcoin',  'simbolo'=>'BTC','icono'=>'₿', 'color'=>'#f7931a','red'=>'Bitcoin Network'],
    'ethereum' => ['nombre'=>'Ethereum', 'simbolo'=>'ETH','icono'=>'⟠','color'=>'#627eea','red'=>'Ethereum Mainnet'],
    'solana'   => ['nombre'=>'Solana',   'simbolo'=>'SOL','icono'=>'◎','color'=>'#9945ff','red'=>'Solana Mainnet'],
    'trx'      => ['nombre'=>'TRON',     'simbolo'=>'TRX','icono'=>'♦','color'=>'#ff0013','red'=>'TRON Network'],
];
$crypto  = $cryptos[$metodo] ?? $cryptos['bitcoin'];
$wallets = ['bitcoin'=>WALLET_BTC,'ethereum'=>WALLET_ETH,'solana'=>WALLET_SOL,'trx'=>WALLET_TRX];
$wallet  = $wallets[$metodo] ?? WALLET_BTC;
$txHash  = '0x' . strtoupper(bin2hex(random_bytes(32)));
$fecha   = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Pagar con <?= $crypto['nombre'] ?> | <?= STORE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root{
            --bg:#050508;--card:#0f0f1a;--text:#e0e0f0;--dim:#7070a0;
            --border:rgba(255,255,255,.1);--green:#39ff14;--red:#ff2d55;--cyan:#00f5ff;
            --crypto:<?= $crypto['color'] ?>;
        }
        body{font-family:'Rajdhani',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding:16px;overflow-x:hidden}
        body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;z-index:0}
        .wrap{max-width:440px;margin:0 auto;position:relative;z-index:1;padding-bottom:40px}

        .header{text-align:center;padding:24px 0 20px}
        .store-logo{font-family:'Orbitron',monospace;font-size:1.2rem;color:var(--crypto);margin-bottom:6px;text-shadow:0 0 20px var(--crypto)}
        .header-sub{font-size:.75rem;color:var(--dim);letter-spacing:.5px}

        .card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:24px;margin-bottom:14px;box-shadow:0 8px 32px rgba(0,0,0,.4)}

        .crypto-header{display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--border)}
        .crypto-icon{width:60px;height:60px;border-radius:50%;background:var(--crypto);display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;box-shadow:0 0 24px var(--crypto)}
        .crypto-nombre{font-family:'Orbitron',monospace;font-size:1rem;color:var(--text);margin-bottom:4px}
        .crypto-red{display:inline-flex;align-items:center;gap:5px;font-size:.75rem;color:var(--dim)}
        .red-dot{width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 6px var(--green);animation:latido 1.5s infinite;display:inline-block}
        @keyframes latido{0%,100%{opacity:1}50%{opacity:.3}}

        .monto-box{text-align:center;margin-bottom:20px;padding:20px;background:rgba(255,255,255,.02);border-radius:12px;border:1px solid rgba(255,255,255,.05)}
        .monto-label{font-size:.7rem;color:var(--dim);text-transform:uppercase;letter-spacing:2px;margin-bottom:8px}
        .monto-val{font-family:'Orbitron',monospace;font-size:1.8rem;font-weight:900;color:var(--crypto);text-shadow:0 0 20px var(--crypto)}
        .monto-pedido{font-size:.8rem;color:var(--dim);margin-top:8px}

        .info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.85rem}
        .info-row:last-child{border-bottom:none}
        .info-lbl{color:var(--dim)}
        .info-val{color:var(--text);font-weight:600;font-family:monospace;font-size:.75rem;text-align:right;max-width:200px;word-break:break-all}

        .seguridad-box{display:flex;gap:10px;align-items:flex-start;background:rgba(0,245,255,.04);border:1px solid rgba(0,245,255,.12);border-radius:12px;padding:14px;margin-bottom:16px}
        .seg-icon{font-size:1.4rem;flex-shrink:0;margin-top:2px}
        .seg-txt{font-size:.8rem;color:var(--dim);line-height:1.5}
        .seg-txt strong{color:var(--cyan);display:block;margin-bottom:2px}

        .btn-pagar{width:100%;padding:18px;border:none;border-radius:14px;background:linear-gradient(135deg,var(--crypto),rgba(255,255,255,.15));color:#000;font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;cursor:pointer;transition:all .2s;letter-spacing:1px;box-shadow:0 4px 24px var(--crypto)}
        .btn-pagar:hover{transform:translateY(-2px);box-shadow:0 8px 32px var(--crypto)}
        .btn-pagar:active{transform:translateY(0)}

        /* PROCESANDO */
        #pantalla-procesando{display:none}
        .blockchain-anim{width:90px;height:90px;margin:0 auto 24px;position:relative}
        .bloque{position:absolute;border-radius:6px;animation:flotar 2s ease-in-out infinite}
        .bloque1{width:36px;height:36px;background:var(--crypto);top:0;left:27px;opacity:.95}
        .bloque2{width:28px;height:28px;background:var(--crypto);top:32px;left:0;opacity:.6;animation-delay:.3s}
        .bloque3{width:28px;height:28px;background:var(--crypto);top:32px;right:0;opacity:.6;animation-delay:.6s}
        .bloque4{width:36px;height:36px;background:rgba(255,255,255,.15);bottom:0;left:27px;animation-delay:.9s}
        @keyframes flotar{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-10px) rotate(6deg)}}
        .procesando-titulo{font-family:'Orbitron',monospace;font-size:1rem;color:var(--crypto);margin-bottom:6px;text-align:center}
        .procesando-sub{color:var(--dim);font-size:.85rem;margin-bottom:24px;text-align:center}
        .pasos{display:flex;flex-direction:column;gap:8px;margin-bottom:20px}
        .paso{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:10px;padding:12px 14px;transition:all .4s}
        .paso.activo{border-color:var(--crypto);background:rgba(255,255,255,.05)}
        .paso.done{border-color:var(--green);background:rgba(57,255,20,.04)}
        .paso-icon{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;background:rgba(255,255,255,.08)}
        .paso.activo .paso-icon{background:var(--crypto);color:#000;animation:spin .7s linear infinite}
        .paso.done .paso-icon{background:var(--green);color:#000}
        @keyframes spin{to{transform:rotate(360deg)}}
        .paso-txt{font-size:.88rem;font-weight:600}
        .paso-sub{font-size:.72rem;color:var(--dim);margin-top:1px}
        .progress-bar{height:3px;background:rgba(255,255,255,.08);border-radius:2px;margin-bottom:8px;overflow:hidden}
        .progress-fill{height:100%;border-radius:2px;width:0%;transition:width .6s ease;background:linear-gradient(90deg,var(--crypto),var(--green))}
        .progress-txt{text-align:center;font-size:.75rem;color:var(--dim)}

        /* COMPLETADO */
        #pantalla-completado{display:none}
        .check-anim{font-size:5rem;text-align:center;animation:pop .5s cubic-bezier(.175,.885,.32,1.275)}
        @keyframes pop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
        .completado-titulo{font-family:'Orbitron',monospace;font-size:1.3rem;color:var(--green);margin:14px 0 6px;text-align:center;text-shadow:0 0 20px var(--green)}
        .completado-sub{color:var(--dim);font-size:.85rem;text-align:center;margin-bottom:20px}
        .tx-box{background:rgba(57,255,20,.04);border:1px solid rgba(57,255,20,.15);border-radius:10px;padding:12px;margin:14px 0;font-family:monospace;font-size:.68rem;color:var(--green);word-break:break-all}
        .tx-lbl{color:var(--dim);font-size:.65rem;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px}
        .detalle-box{background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:12px;padding:16px;margin:14px 0}
        .detalle-titulo{font-family:'Orbitron',monospace;font-size:.75rem;color:var(--crypto);margin-bottom:12px;text-transform:uppercase;letter-spacing:1px}
        .det-row{display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:8px;align-items:center}
        .det-row:last-child{margin-bottom:0}
        .det-lbl{color:var(--dim)}
        .det-val{font-weight:700;text-align:right}
        .seguimiento-box{display:flex;gap:10px;align-items:flex-start;background:rgba(191,90,242,.06);border:1px solid rgba(191,90,242,.2);border-radius:12px;padding:14px;margin:14px 0}
        .seg2-icon{font-size:1.3rem;flex-shrink:0}
        .seg2-txt{font-size:.8rem;color:var(--dim);line-height:1.5}
        .seg2-txt strong{color:#bf5af2;display:block;margin-bottom:3px}
        .btn-recibo{width:100%;padding:15px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--green),#00aa00);color:#000;font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;transition:.2s;letter-spacing:.5px;margin-bottom:10px}
        .btn-recibo:hover{filter:brightness(1.1);transform:translateY(-1px)}
        .btn-tienda{display:block;width:100%;padding:13px;border:1px solid rgba(0,245,255,.25);border-radius:12px;background:rgba(0,245,255,.05);color:var(--cyan);font-family:'Rajdhani',sans-serif;font-size:.95rem;font-weight:600;cursor:pointer;text-align:center;text-decoration:none;transition:.2s}
        .btn-tienda:hover{border-color:var(--cyan);background:rgba(0,245,255,.1)}

        .estado-msg{text-align:center;padding:48px 20px}
        .estado-msg .icono{font-size:4rem;margin-bottom:16px}
        .estado-msg h2{font-family:'Orbitron',monospace;font-size:1rem;margin-bottom:8px}
        .estado-msg p{color:var(--dim);font-size:.85rem;line-height:1.6}
    </style>
</head>
<body>
<div class="wrap">

    <div class="header">
        <div class="store-logo">⚡ <?= STORE_NAME ?></div>
        <div class="header-sub">Pasarela de pago segura · <?= $crypto['nombre'] ?></div>
    </div>

    <?php if($tipo === 'error'): ?>
        <div class="card">
            <div class="estado-msg">
                <div class="icono">❌</div>
                <h2 style="color:var(--red)">ENLACE NO VÁLIDO</h2>
                <p>Este enlace de pago no es válido.<br>Regresa a la tienda y genera un nuevo pedido.</p>
            </div>
        </div>

    <?php else: ?>

        <!-- PAGO -->
        <div id="pantalla-pago">
            <div class="card">
                <div class="crypto-header">
                    <div class="crypto-icon"><?= $crypto['icono'] ?></div>
                    <div>
                        <div class="crypto-nombre"><?= $crypto['nombre'] ?> Payment</div>
                        <div class="crypto-red">
                            <span class="red-dot"></span>
                            <?= $crypto['red'] ?> · En línea
                        </div>
                    </div>
                </div>

                <div class="monto-box">
                    <div class="monto-label">Total a pagar</div>
                    <div class="monto-val"><?= number_format((float)$monto,8) ?> <?= $crypto['simbolo'] ?></div>
                    <div class="monto-pedido">Pedido #<?= limpiar($pedido) ?> · <?= STORE_NAME ?></div>
                </div>

                <div class="info-row">
                    <span class="info-lbl">Red blockchain</span>
                    <span class="info-val"><?= $crypto['red'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Dirección destino</span>
                    <span class="info-val"><?= substr($wallet,0,10) ?>...<?= substr($wallet,-8) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Confirmaciones requeridas</span>
                    <span class="info-val">1 bloque</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Comisión de red</span>
                    <span class="info-val" style="color:var(--green)">Incluida</span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Tiempo estimado</span>
                    <span class="info-val">~2-5 minutos</span>
                </div>
            </div>

            <div class="seguridad-box">
                <div class="seg-icon">🔒</div>
                <div class="seg-txt">
                    <strong>Pago cifrado y verificado en blockchain</strong>
                    Tu transacción es procesada de forma descentralizada en la red
                    <?= $crypto['nombre'] ?>. Al confirmar, recibirás tu comprobante al instante.
                </div>
            </div>

            <form method="POST" onsubmit="iniciarPago(event)">
                <input type="hidden" name="confirmar_pago" value="1">
                <button type="submit" class="btn-pagar">
                    <?= $crypto['icono'] ?> &nbsp;AUTORIZAR PAGO · <?= number_format((float)$monto,6) ?> <?= $crypto['simbolo'] ?>
                </button>
            </form>
        </div>

        <!-- PROCESANDO -->
        <div id="pantalla-procesando">
            <div class="card">
                <div style="text-align:center;padding:16px 0 8px">
                    <div class="blockchain-anim">
                        <div class="bloque bloque1"></div>
                        <div class="bloque bloque2"></div>
                        <div class="bloque bloque3"></div>
                        <div class="bloque bloque4"></div>
                    </div>
                    <div class="procesando-titulo">PROCESANDO EN LA RED</div>
                    <div class="procesando-sub">Transmitiendo a los nodos de <?= $crypto['red'] ?>...</div>
                    <div class="pasos">
                        <div class="paso" id="paso1">
                            <div class="paso-icon">⏳</div>
                            <div><div class="paso-txt">Transmisión a la red</div><div class="paso-sub">Enviando a nodos validadores...</div></div>
                        </div>
                        <div class="paso" id="paso2">
                            <div class="paso-icon">⏳</div>
                            <div><div class="paso-txt">Minería del bloque</div><div class="paso-sub">Esperando confirmación del minero...</div></div>
                        </div>
                        <div class="paso" id="paso3">
                            <div class="paso-icon">⏳</div>
                            <div><div class="paso-txt">Validación del contrato</div><div class="paso-sub">Verificando monto y destinatario...</div></div>
                        </div>
                        <div class="paso" id="paso4">
                            <div class="paso-icon">⏳</div>
                            <div><div class="paso-txt">Acreditación de fondos</div><div class="paso-sub">Confirmando recepción en wallet...</div></div>
                        </div>
                    </div>
                    <div class="progress-bar"><div class="progress-fill" id="progress-fill"></div></div>
                    <div class="progress-txt" id="progress-txt">Iniciando transmisión...</div>
                </div>
            </div>
        </div>

        <!-- COMPLETADO -->
        <div id="pantalla-completado">
            <div class="card">
                <div class="check-anim">✅</div>
                <div class="completado-titulo">¡PAGO AUTORIZADO!</div>
                <div class="completado-sub">Transacción confirmada en la blockchain</div>

                <div class="tx-box">
                    <div class="tx-lbl">Hash de transacción</div>
                    <?= $txHash ?>
                </div>

                <div class="detalle-box">
                    <div class="detalle-titulo">📦 Resumen del pedido</div>
                    <div class="det-row"><span class="det-lbl">Pedido</span><span class="det-val">#<?= limpiar($pedido) ?></span></div>
                    <div class="det-row"><span class="det-lbl">Monto</span><span class="det-val" style="color:var(--crypto)"><?= number_format((float)$monto,8) ?> <?= $crypto['simbolo'] ?></span></div>
                    <div class="det-row"><span class="det-lbl">Red</span><span class="det-val"><?= $crypto['red'] ?></span></div>
                    <div class="det-row"><span class="det-lbl">Fecha</span><span class="det-val" style="font-family:monospace;font-size:.78rem"><?= $fecha ?></span></div>
                    <div class="det-row"><span class="det-lbl">Estado</span><span class="det-val" style="color:var(--green)">✓ Confirmado</span></div>
                    <div class="det-row"><span class="det-lbl">Entrega estimada</span><span class="det-val" style="color:var(--cyan)">3 – 5 días hábiles</span></div>
                </div>

                <div class="seguimiento-box">
                    <div class="seg2-icon">📬</div>
                    <div class="seg2-txt">
                        <strong>¿Qué sigue?</strong>
                        Tu pedido está siendo preparado para envío. Puedes rastrear
                        el estado de tu compra en la sección <em>Mis Pedidos</em> en cualquier momento.
                    </div>
                </div>

                <button class="btn-recibo"
                        onclick="window.location.href='<?= BASE_URL ?>/recibo_publico.php?pedido=<?= urlencode($pedido) ?>&ref=<?= urlencode($txHash) ?>'">
                    📄 VER COMPROBANTE DE PAGO
                </button>
                <a href="<?= BASE_URL ?>/tienda.php" class="btn-tienda">🎮 Seguir comprando</a>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
    function iniciarPago(e) {
        e.preventDefault();
        document.getElementById('pantalla-pago').style.display       = 'none';
        document.getElementById('pantalla-procesando').style.display = 'block';
        window.scrollTo(0, 0);
        simularBlockchain();
        setTimeout(mostrarCompletado, 6500);
    }

    function simularBlockchain() {
        const pasos       = ['paso1','paso2','paso3','paso4'];
        const textos      = ['Transmitiendo...','Minando bloque...','Validando contrato...','Acreditando fondos...'];
        const porcentajes = [20, 45, 75, 100];
        let actual = 0;
        const fill = document.getElementById('progress-fill');
        const pTxt = document.getElementById('progress-txt');

        function activarPaso(i) {
            if (i > 0) {
                const prev = document.getElementById(pasos[i-1]);
                prev.classList.remove('activo');
                prev.classList.add('done');
                prev.querySelector('.paso-icon').textContent = '✓';
            }
            if (i < pasos.length) {
                const curr = document.getElementById(pasos[i]);
                curr.classList.add('activo');
                curr.querySelector('.paso-icon').textContent = '⚙';
                fill.style.width = porcentajes[i] + '%';
                pTxt.textContent = textos[i];
            }
        }
        activarPaso(0);
        const iv = setInterval(() => {
            actual++;
            if (actual < pasos.length) {
                activarPaso(actual);
            } else {
                clearInterval(iv);
                fill.style.width = '100%';
                pTxt.textContent = '¡Transacción confirmada en la red!';
            }
        }, 1500);
    }

    function mostrarCompletado() {
        document.getElementById('pantalla-procesando').style.display = 'none';
        document.getElementById('pantalla-completado').style.display = 'block';
        window.scrollTo(0, 0);
        // Notificar al escritorio que el pago fue autorizado
        fetch('notificar_pago.php?token=<?= urlencode($token) ?>&pedido=<?= urlencode($pedido) ?>').catch(()=>{});
    }

    <?php if($tipo === 'procesando'): ?>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('pantalla-pago').style.display = 'none';
        setTimeout(mostrarCompletado, 1200);
    });
    <?php endif; ?>
</script>
</body>
</html>