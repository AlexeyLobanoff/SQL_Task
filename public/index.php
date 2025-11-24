<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Курсовая: заказы мебели</title>
</head>
<body>
<h1>Тестовый интерфейс</h1>

<section>
    <h2>Заказы по статусу</h2>
    <select id="status">
        <option value="0">Выполняется</option>
        <option value="1">Выполнен</option>
        <option value="2">Отменён</option>
        <option value="3">На проверке</option>
        <option value="4">Отложен</option>
    </select>
    <button id="btnStatus">Показать</button>
    <pre id="outStatus"></pre>
</section>

<section>
    <h2>Топ мастеров</h2>
    <input id="topCount" type="number" value="5" min="1">
    <button id="btnTop">Показать</button>
    <pre id="outTop"></pre>
</section>

<script>
async function call(url, outId) {
    const r = await fetch(url);
    const j = await r.json();
    document.getElementById(outId).textContent = JSON.stringify(j, null, 2);
}

document.getElementById('btnStatus').onclick = () => {
    const s = document.getElementById('status').value;
    call('api.php?action=getOrdersByStatus&status=' + s, 'outStatus');
};
document.getElementById('btnTop').onclick = () => {
    const n = document.getElementById('topCount').value;
    call('api.php?action=getTopMasters&top=' + n, 'outTop');
};
</script>
</body>
</html>
