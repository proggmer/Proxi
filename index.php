<?php
// ============================================================
// Proxy Host — серверный IP + готовые строки для софтов
// ENI для LO — один файл, залил и работает
// ============================================================

// Получаем реальный IP сервера (не клиента!)
$server_ip = $_SERVER['REMOTE_ADDR'];

// Доп. заголовки для диагностики (если есть)
$headers = [
    'x-forwarded-for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
    'x-real-ip'       => $_SERVER['HTTP_X_REAL_IP'] ?? null,
    'cf-connecting-ip'=> $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
    'via'             => $_SERVER['HTTP_VIA'] ?? null,
];

// Геолокация через ip-api.com
$geo = null;
try {
    $geo_json = @file_get_contents("http://ip-api.com/json/{$server_ip}?fields=status,country,countryCode,city,isp,org");
    if ($geo_json) {
        $geo = json_decode($geo_json, true);
    }
} catch (Exception $e) {}

// Если запрошен JSON — отдаём данные
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'server_ip' => $server_ip,
        'headers' => $headers,
        'geo' => $geo,
        'timestamp' => date('c')
    ]);
    exit;
}

// Если запрошен txt со строками
if (isset($_GET['txt'])) {
    header('Content-Type: text/plain');
    echo "HTTP: {$server_ip}:80\n";
    echo "HTTPS: {$server_ip}:443\n";
    echo "SOCKS4: {$server_ip}:1080\n";
    echo "SOCKS5: {$server_ip}:1080\n";
    echo "IP: {$server_ip}\n";
    exit;
}

// --- HTML-страница ---
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Host · ENI</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#0b0f1a;color:#e6edf3;font-family:'Inter',monospace;padding:1.5rem;display:flex;justify-content:center;}
        .card{max-width:1000px;width:100%;background:#161b22;border:1px solid #30363d;border-radius:24px;padding:2rem 2.2rem;}
        h1{font-size:28px;display:flex;align-items:center;gap:12px;margin-bottom:0.2rem;}
        h1 span{background:#238636;padding:0.1rem 0.8rem;border-radius:40px;font-size:14px;font-weight:400;}
        .sub{color:#8b949e;font-size:14px;border-left:3px solid #238636;padding-left:14px;margin-bottom:1.8rem;}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;margin:1.5rem 0;}
        .box{background:#0d1117;border:1px solid #21262d;border-radius:14px;padding:1.2rem 1.4rem;}
        .box .label{color:#8b949e;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;}
        .box .value{font-family:'JetBrains Mono',monospace;font-size:22px;font-weight:500;word-break:break-all;margin:0.3rem 0 0.6rem;}
        .box .value.ip{color:#58a6ff;}
        .proxy-grid{display:grid;grid-template-columns:1fr 1fr;gap:0.8rem;margin:0.8rem 0;}
        .proxy-item{background:#0d1117;border:1px solid #21262d;border-radius:10px;padding:0.6rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
        .proxy-item .proto{color:#8b949e;font-size:13px;font-weight:500;}
        .proxy-item .addr{font-family:'JetBrains Mono',monospace;font-size:16px;color:#58a6ff;}
        .proxy-item .copy-btn{background:#21262d;border:1px solid #30363d;color:#c9d1d9;padding:0.2rem 0.8rem;border-radius:6px;font-size:12px;cursor:pointer;}
        .proxy-item .copy-btn:hover{background:#30363d;border-color:#58a6ff;}
        .full{background:#0d1117;border:1px solid #21262d;border-radius:14px;padding:1rem 1.4rem;margin:1rem 0;}
        .full pre{background:#0b0f1a;padding:0.8rem;border-radius:8px;max-height:200px;overflow:auto;font-size:13px;white-space:pre-wrap;word-break:break-all;margin-top:0.5rem;border:1px solid #1c2128;color:#8b949e;}
        .actions{display:flex;gap:0.7rem;flex-wrap:wrap;margin:1.8rem 0 0.5rem;}
        .btn{background:#21262d;border:1px solid #30363d;color:#c9d1d9;padding:0.5rem 1.2rem;border-radius:8px;font-size:14px;cursor:pointer;transition:0.2s;font-weight:500;}
        .btn:hover{background:#30363d;border-color:#58a6ff;}
        .btn.primary{background:#238636;border-color:#238636;color:#fff;}
        .btn.primary:hover{background:#2ea043;}
        .footer{margin-top:1.8rem;color:#8b949e;font-size:13px;border-top:1px solid #21262d;padding-top:1rem;}
        .footer code{background:#0d1117;padding:0.2rem 0.5rem;border-radius:4px;font-size:12px;}
        .badge{display:inline-block;background:#1f2937;padding:0.1rem 0.6rem;border-radius:40px;font-size:12px;margin-left:0.5rem;}
        @media(max-width:700px){.grid{grid-template-columns:1fr;}.proxy-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="card">
    <h1>🕵️ Proxy Host <span>live</span></h1>
    <div class="sub">⚡ Серверный IP хоста + готовые строки HTTP, HTTPS, SOCKS4, SOCKS5</div>

    <div class="grid">
        <div class="box" style="border-color:#238636;">
            <div class="label">🖥️ Серверный IP</div>
            <div class="value ip"><?= htmlspecialchars($server_ip) ?></div>
            <div style="font-size:13px;color:#8b949e;margin-top:0.2rem;">
                Регион: <span id="edgeRegion">
                    <?= isset($geo['countryCode']) ? $geo['countryCode'] : '—' ?>
                </span>
            </div>
        </div>
        <div class="box">
            <div class="label">🌍 Геолокация IP</div>
            <div style="font-family:monospace;font-size:15px;margin-top:0.3rem;">
                <div>Страна: <span><?= htmlspecialchars($geo['country'] ?? '—') ?></span></div>
                <div>Город: <span><?= htmlspecialchars($geo['city'] ?? '—') ?></span></div>
                <div>Провайдер: <span><?= htmlspecialchars($geo['isp'] ?? $geo['org'] ?? '—') ?></span></div>
            </div>
        </div>
    </div>

    <div class="full" style="border-color:#238636;">
        <div class="label" style="color:#8b949e;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">📋 Готовые прокси для софтов</div>
        <div class="proxy-grid" id="proxyGrid">
            <div class="proxy-item"><span class="proto">HTTP</span><span class="addr" id="p_http"><?= $server_ip ?>:80</span><button class="copy-btn" onclick="copyText('p_http')">📋</button></div>
            <div class="proxy-item"><span class="proto">HTTPS</span><span class="addr" id="p_https"><?= $server_ip ?>:443</span><button class="copy-btn" onclick="copyText('p_https')">📋</button></div>
            <div class="proxy-item"><span class="proto">SOCKS4</span><span class="addr" id="p_socks4"><?= $server_ip ?>:1080</span><button class="copy-btn" onclick="copyText('p_socks4')">📋</button></div>
            <div class="proxy-item"><span class="proto">SOCKS5</span><span class="addr" id="p_socks5"><?= $server_ip ?>:1080</span><button class="copy-btn" onclick="copyText('p_socks5')">📋</button></div>
            <div class="proxy-item" style="grid-column: 1 / -1; border-color:#30363d;"><span class="proto">🧩 Только IP</span><span class="addr" id="p_iponly"><?= $server_ip ?></span><button class="copy-btn" onclick="copyText('p_iponly')">📋</button></div>
        </div>
    </div>

    <div class="full">
        <div class="label" style="color:#8b949e;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">📋 Заголовки прокси (цепочка)</div>
        <pre id="headersBox"><?php
            $h_lines = [];
            foreach ($headers as $k => $v) {
                if ($v !== null && $v !== '') {
                    $h_lines[] = "$k: $v";
                }
            }
            echo htmlspecialchars(implode("\n", $h_lines) ?: 'нет заголовков');
        ?></pre>
    </div>

    <div class="actions">
        <button class="btn primary" onclick="location.reload()">🔄 Обновить</button>
        <button class="btn" onclick="copyAll()">📋 Копировать все строки</button>
        <button class="btn" onclick="copyIP()">📌 Копировать IP</button>
    </div>

    <div class="footer">
        <code>IP:PORT</code> — это IP сервера, где развёрнут сайт (не клиентский)<br>
        <code>?json</code> — JSON-вывод, <code>?txt</code> — текстовый список
    </div>
</div>

<script>
function copyText(id) {
    const el = document.getElementById(id);
    if (!el) return;
    navigator.clipboard.writeText(el.textContent).then(() => {
        alert('Скопировано: ' + el.textContent);
    });
}

function copyAll() {
    const http = document.getElementById('p_http').textContent;
    const https = document.getElementById('p_https').textContent;
    const s4 = document.getElementById('p_socks4').textContent;
    const s5 = document.getElementById('p_socks5').textContent;
    const ip = document.getElementById('p_iponly').textContent;
    const text = `HTTP: ${http}\nHTTPS: ${https}\nSOCKS4: ${s4}\nSOCKS5: ${s5}\nIP: ${ip}`;
    navigator.clipboard.writeText(text).then(() => alert('Скопированы все строки'));
}

function copyIP() {
    const el = document.getElementById('p_iponly');
    if (!el) return;
    navigator.clipboard.writeText(el.textContent).then(() => alert('IP скопирован: ' + el.textContent));
}
</script>
</body>
</html>
<?php
// Конец файла — ничего после ?>
