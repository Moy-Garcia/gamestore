<?php
session_start();
require_once 'config.php';
requireLogin();

$conn = conectarDB();
$uid  = (int)$_SESSION['usuario_id'];

$items = $conn->query("
    SELECT c.cantidad, p.nombre, p.precio, p.precio_crypto, p.id as pid
    FROM carrito c JOIN productos p ON p.id=c.producto_id
    WHERE c.usuario_id=$uid
")->fetch_all(MYSQLI_ASSOC);

if (empty($items)) { header('Location: carrito.php'); exit; }

$total        = array_sum(array_map(fn($i)=>$i['precio']*$i['cantidad'], $items));
$total_btc    = array_sum(array_map(fn($i)=>$i['precio_crypto']*$i['cantidad'], $items));
$total_eth    = $total_btc * 16.5; // ratio demo
$total_sol    = $total * 0.0000083;
$saldo_cartera= (float)$conn->query("SELECT saldo_cartera FROM usuarios WHERE id=$uid")->fetch_assoc()['saldo_cartera'];

$conn->close();
$page_title = 'Checkout';
include 'header.php';
?>
<style>
.checkout-layout{display:grid;grid-template-columns:1fr 360px;gap:32px;align-items:start}
.metodos-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px}
.metodo-btn{
    padding:16px 12px;border-radius:12px;border:2px solid var(--border);
    background:var(--bg-card);cursor:pointer;transition:all .2s;text-align:center;
    display:flex;flex-direction:column;align-items:center;gap:8px
}
.metodo-btn:hover,.metodo-btn.selected{border-color:var(--cyan);background:rgba(0,245,255,.05)}
.metodo-btn .micon{font-size:1.8rem}
.metodo-btn .mlabel{font-size:.85rem;font-weight:700;letter-spacing:.5px;color:var(--text)}
.metodo-btn .msub{font-size:.72rem;color:var(--text-dim)}
.pago-panel{display:none}
.pago-panel.active{display:block;animation:fadeUp .3s ease}
.wallet-addr{
    background:var(--bg-dark);border:1px solid var(--border);border-radius:8px;
    padding:12px 14px;font-family:monospace;font-size:.78rem;color:var(--cyan);
    word-break:break-all;margin:12px 0;cursor:pointer;
}
.wallet-addr:hover{border-color:var(--cyan)}
.qr-pago{display:flex;justify-content:center;margin:16px 0}
.qr-pago img{border-radius:10px;padding:8px;background:white}
.resumen-sticky{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;position:sticky;top:80px}
.res-row{display:flex;justify-content:space-between;margin-bottom:10px;font-size:.88rem}
.res-total{font-family:'Orbitron',monospace;font-size:1.2rem;color:var(--cyan);border-top:1px solid var(--border);padding-top:12px;margin-top:4px;display:flex;justify-content:space-between}
.saldo-info{background:rgba(191,90,242,.1);border:1px solid rgba(191,90,242,.3);border-radius:8px;padding:12px;margin:12px 0;font-size:.88rem}
.saldo-ok{background:rgba(57,255,20,.1);border:1px solid rgba(57,255,20,.3);color:var(--green)}
.saldo-no{background:rgba(255,45,85,.1);border:1px solid rgba(255,45,85,.3);color:#ff6b6b}
.crypto-tabs{display:flex;gap:8px;margin-bottom:16px}
.crypto-tab{
    padding:8px 14px;border-radius:8px;border:1px solid var(--border);
    background:transparent;color:var(--text-dim);cursor:pointer;font-family:'Rajdhani',sans-serif;font-weight:600;font-size:.85rem;transition:.2s
}
.crypto-tab.active{border-color:var(--orange);color:var(--orange);background:rgba(255,107,0,.1)}
.crypto-panel{display:none}.crypto-panel.active{display:block}
@media(max-width:768px){.checkout-layout,.metodos-grid{grid-template-columns:1fr}}
</style>

<h1 class="section-title">💳 CHECKOUT</h1>

<div class="checkout-layout">
    <div>
        <div class="card">
            <h3 style="font-family:'Orbitron',monospace;color:var(--purple);margin-bottom:20px;font-size:1rem">
                ELIGE TU MÉTODO DE PAGO
            </h3>

            <div class="metodos-grid">
                <button class="metodo-btn" onclick="selMetodo('tarjeta',this)">
                    <span class="micon">💳</span>
                    <span class="mlabel">TARJETA</span>
                    <span class="msub">Visa / Mastercard</span>
                </button>
                <button class="metodo-btn" onclick="selMetodo('paypal',this)">
                    <span class="micon">🅿️</span>
                    <span class="mlabel">PAYPAL</span>
                    <span class="msub">Pago rápido y seguro</span>
                </button>
                <button class="metodo-btn" onclick="selMetodo('cripto',this)">
                    <span class="micon">₿</span>
                    <span class="mlabel">CRYPTO</span>
                    <span class="msub">BTC / ETH / SOL / TRX</span>
                </button>
                <button class="metodo-btn" onclick="selMetodo('cartera',this)">
                    <span class="micon">💎</span>
                    <span class="mlabel">CARTERA</span>
                    <span class="msub">Saldo: <?= formatoPrecio($saldo_cartera) ?></span>
                </button>
            </div>

            <!-- PANEL TARJETA -->
            <div class="pago-panel" id="panel-tarjeta">
                <div class="alert alert-info">
                    🧪 <strong>Modo prueba Stripe.</strong> Usa la tarjeta de prueba: <code>4242 4242 4242 4242</code>, fecha futura, CVC cualquiera.
                </div>
                <form action="procesar_pago.php" method="POST">
                    <input type="hidden" name="metodo" value="tarjeta">
                    <div class="form-group">
                        <label>Nombre en la tarjeta</label>
                        <input type="text" name="card_name" class="form-control" placeholder="Juan Pérez" required>
                    </div>
                    <div class="form-group">
                        <label>Número de tarjeta</label>
                        <input type="text" name="card_num" class="form-control" placeholder="4242 4242 4242 4242" maxlength="19" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div class="form-group">
                            <label>Vencimiento</label>
                            <input type="text" name="card_exp" class="form-control" placeholder="MM/AA" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label>CVC</label>
                            <input type="text" name="card_cvc" class="form-control" placeholder="123" maxlength="4" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        💳 PAGAR <?= formatoPrecio($total) ?>
                    </button>
                </form>
            </div>

            <!-- PANEL PAYPAL -->
            <div class="pago-panel" id="panel-paypal">
                <div class="alert alert-info">🅿️ Se abrirá la ventana de PayPal Sandbox para completar el pago.</div>
                <div style="text-align:center;padding:20px 0">
                    <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg"
                         alt="PayPal" style="height:50px;border-radius:8px;margin-bottom:16px"><br>
                    <form action="procesar_pago.php" method="POST">
                        <input type="hidden" name="metodo" value="paypal">
                        <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg,#0070ba,#003087)">
                            🅿️ PAGAR CON PAYPAL – <?= formatoPrecio($total) ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- PANEL CRYPTO -->
            <div class="pago-panel" id="panel-cripto">
                <div class="crypto-tabs">
                    <button class="crypto-tab active" onclick="selCrypto('btc',this)">₿ BTC</button>
                    <button class="crypto-tab" onclick="selCrypto('eth',this)">⟠ ETH</button>
                    <button class="crypto-tab" onclick="selCrypto('sol',this)">◎ SOL</button>
                    <button class="crypto-tab" onclick="selCrypto('trx',this)">♦ TRX</button>
                </div>

                <!-- BTC -->
                <div class="crypto-panel active" id="cpanel-btc">
                    <p style="color:var(--text-dim);font-size:.88rem;margin-bottom:12px">
                        Envía exactamente <strong style="color:var(--orange)"><?= number_format($total_btc,8) ?> BTC</strong> a esta dirección:
                    </p>
                    <div class="wallet-addr" onclick="copiar(this,'<?= WALLET_BTC ?>')" title="Clic para copiar">
                        <?= WALLET_BTC ?> <span style="color:var(--text-dim);float:right">📋</span>
                    </div>
                    <div class="qr-pago">
                        <img src="<?= generarQR(BASE_URL.'/pago_crypto.php?token='.urlencode($token_pago ?? 'demo').'&metodo=bitcoin&monto='.$total_btc.'&pedido=DEMO', 160) ?>" alt="QR BTC">
                    </div>
                    <form action="procesar_pago.php" method="POST">
                        <input type="hidden" name="metodo" value="bitcoin">
                        <button type="submit" class="btn btn-green" style="width:100%;justify-content:center">
                            ✓ YA PAGUÉ – CONFIRMAR TRANSACCIÓN
                        </button>
                    </form>
                </div>

                <!-- ETH -->
                <div class="crypto-panel" id="cpanel-eth">
                    <p style="color:var(--text-dim);font-size:.88rem;margin-bottom:12px">
                        Envía <strong style="color:var(--purple)"><?= number_format($total_eth,8) ?> ETH</strong>:
                    </p>
                    <div class="wallet-addr" onclick="copiar(this,'<?= WALLET_ETH ?>')" title="Clic para copiar">
                        <?= WALLET_ETH ?> <span style="color:var(--text-dim);float:right">📋</span>
                    </div>
                    <div class="qr-pago">
                        <img src="<?= generarQR(BASE_URL.'/pago_crypto.php?token=demo&metodo=ethereum&monto='.$total_eth.'&pedido=DEMO', 160) ?>" alt="QR ETH">
                    </div>
                    <form action="procesar_pago.php" method="POST">
                        <input type="hidden" name="metodo" value="ethereum">
                        <button type="submit" class="btn btn-purple" style="width:100%;justify-content:center">
                            ✓ YA PAGUÉ – CONFIRMAR
                        </button>
                    </form>
                </div>

                <!-- SOL -->
                <div class="crypto-panel" id="cpanel-sol">
                    <p style="color:var(--text-dim);font-size:.88rem;margin-bottom:12px">
                        Envía <strong style="color:var(--green)"><?= number_format($total_sol,4) ?> SOL</strong>:
                    </p>
                    <div class="wallet-addr" onclick="copiar(this,'<?= WALLET_SOL ?>')"><?= WALLET_SOL ?></div>
                    <div class="qr-pago">
                        <img src="<?= generarQR(BASE_URL.'/pago_crypto.php?token=demo&metodo=solana&monto='.$total_sol.'&pedido=DEMO', 160) ?>" alt="QR SOL">
                    </div>
                    <form action="procesar_pago.php" method="POST">
                        <input type="hidden" name="metodo" value="solana">
                        <button type="submit" class="btn btn-green" style="width:100%;justify-content:center">
                            ✓ YA PAGUÉ – CONFIRMAR
                        </button>
                    </form>
                </div>

                <!-- TRX -->
                <div class="crypto-panel" id="cpanel-trx">
                    <p style="color:var(--text-dim);font-size:.88rem;margin-bottom:12px">
                        Envía <strong style="color:var(--red)"><?= number_format($total*0.08,2) ?> TRX</strong>:
                    </p>
                    <div class="wallet-addr" onclick="copiar(this,'<?= WALLET_TRX ?>')"><?= WALLET_TRX ?></div>
                    <div class="qr-pago">
                        <img src="<?= generarQR(BASE_URL.'/pago_crypto.php?token=demo&metodo=trx&monto='.round($total*0.08,2).'&pedido=DEMO', 160) ?>" alt="QR TRX">
                    </div>
                    <form action="procesar_pago.php" method="POST">
                        <input type="hidden" name="metodo" value="solana">
                        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center">
                            ✓ YA PAGUÉ – CONFIRMAR
                        </button>
                    </form>
                </div>
            </div>

            <!-- PANEL CARTERA -->
            <div class="pago-panel" id="panel-cartera">
                <?php if($saldo_cartera >= $total): ?>
                <div class="saldo-info saldo-ok">
                    ✅ Tienes suficiente saldo en cartera: <strong><?= formatoPrecio($saldo_cartera) ?></strong><br>
                    Se descontarán: <strong><?= formatoPrecio($total) ?></strong>
                </div>
                <form action="procesar_pago.php" method="POST">
                    <input type="hidden" name="metodo" value="cartera">
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;background:linear-gradient(135deg,var(--purple),#6e2db8)">
                        💎 PAGAR CON MI CARTERA – <?= formatoPrecio($total) ?>
                    </button>
                </form>
                <?php else: ?>
                <div class="saldo-info saldo-no">
                    ❌ Saldo insuficiente. Tienes <strong><?= formatoPrecio($saldo_cartera) ?></strong>, necesitas <strong><?= formatoPrecio($total) ?></strong>
                </div>
                <a href="cartera.php" class="btn btn-purple" style="width:100%;justify-content:center">
                    💎 RECARGAR MI CARTERA
                </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- RESUMEN -->
    <div class="resumen-sticky">
        <h3 style="font-family:'Orbitron',monospace;color:var(--purple);margin-bottom:16px;font-size:1rem">RESUMEN</h3>
        <?php foreach($items as $item): ?>
        <div class="res-row">
            <span style="color:var(--text-dim)"><?= limpiar(substr($item['nombre'],0,20)) ?>… ×<?= $item['cantidad'] ?></span>
            <span><?= formatoPrecio($item['precio']*$item['cantidad']) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="res-total">
            <span>TOTAL</span>
            <span><?= formatoPrecio($total) ?></span>
        </div>
        <div style="text-align:center;margin-top:8px;font-size:.75rem;color:var(--text-dim)">
            ≈ <?= number_format($total_btc,8) ?> BTC
        </div>
        <hr style="border-color:var(--border);margin:16px 0">
        <div style="font-size:.8rem;color:var(--text-dim);line-height:1.6">
            🔒 Pago 100% seguro<br>
            ₿ Crypto con confirmación en red<br>
            🅿️ PayPal con protección al comprador
        </div>
    </div>
</div>

<script>
function selMetodo(m, el) {
    document.querySelectorAll('.metodo-btn').forEach(b=>b.classList.remove('selected'));
    document.querySelectorAll('.pago-panel').forEach(p=>p.classList.remove('active'));
    el.classList.add('selected');
    document.getElementById('panel-'+m).classList.add('active');
}

function selCrypto(c, el) {
    document.querySelectorAll('.crypto-tab').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.crypto-panel').forEach(p=>p.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('cpanel-'+c).classList.add('active');
}

function copiar(el, texto) {
    navigator.clipboard.writeText(texto).then(()=>{
        const orig = el.innerHTML;
        el.style.borderColor = 'var(--green)';
        setTimeout(()=>{ el.style.borderColor=''; }, 1500);
    });
}
</script>

<?php include 'footer.php'; ?>
