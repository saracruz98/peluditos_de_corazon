<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PetStore</title>
<style>
  :root {
    --green: #2E7D32;
    --green-dark: #1B5E20;
    --green-light: #E8F5E9;
    --amber: #F9A825;
    --amber-light: #FFF8E1;
    --red: #C62828;
    --red-light: #FFEBEE;
    --gray: #f2f2f2;
    --border: #ddd;
    --text: #222;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: var(--gray);
    color: var(--text);
    padding-bottom: 70px;
    -webkit-text-size-adjust: 100%;
  }
  header {
    background: var(--green);
    color: #fff;
    padding: 14px 16px;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,.15);
  }
  header h1 { font-size: 20px; font-weight: 600; }
  header p { font-size: 12px; opacity: .9; margin-top: 2px; }
  main { padding: 12px; max-width: 760px; margin: 0 auto; }
  .tab { display: none; }
  .tab.active { display: block; }
  .card {
    background: #fff;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
  }
  .card h2 { font-size: 15px; margin-bottom: 10px; color: var(--green-dark); }
  label { display: block; font-size: 13px; margin: 8px 0 3px; color: #555; }
  input, select {
    width: 100%;
    padding: 11px;
    font-size: 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: #fff;
  }
  .row { display: flex; gap: 8px; }
  .row > div { flex: 1; }
  button {
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
  }
  button:active { background: var(--green-dark); }
  button.secondary { background: #607D8B; }
  button.danger { background: var(--red); }
  button.small { width: auto; padding: 6px 10px; font-size: 12px; }
  button.ghost { background: transparent; color: var(--green); border: 1px solid var(--green); }
  button.reponer { background: #1976D2; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid var(--gray); }
  th { color: #777; font-size: 12px; text-transform: uppercase; }
  .badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap; }
  .ok { background: var(--green-light); color: var(--green-dark); }
  .low { background: var(--amber-light); color: #B26A00; }
  .out { background: var(--red-light); color: var(--red); }
  .muted { color: #888; font-size: 13px; }
  .toast {
    position: fixed; bottom: 78px; left: 50%; transform: translateX(-50%);
    background: #333; color: #fff; padding: 12px 18px; border-radius: 20px;
    font-size: 14px; z-index: 100; opacity: 0; pointer-events: none; transition: opacity .2s;
    max-width: 90%; text-align: center;
  }
  .toast.show { opacity: 1; }
  .toast.err { background: var(--red); }
  nav {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #fff; display: flex; border-top: 1px solid var(--border);
    z-index: 20;
  }
  nav button {
    flex: 1; background: none; color: #888; border: none; border-radius: 0;
    padding: 10px 2px; font-size: 11px; font-weight: 500; display: flex;
    flex-direction: column; align-items: center; gap: 3px;
  }
  nav button .ico { font-size: 20px; line-height: 1; }
  nav button.active { color: var(--green); font-weight: 700; }
  .empty { text-align: center; color: #999; padding: 20px 10px; font-size: 14px; }
  .actions { display: flex; gap: 6px; margin-top: 12px; }
  .actions button { width: auto; padding: 6px 10px; font-size: 12px; }
  .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--gray); font-size: 14px; }
  .money { text-align: right; }
</style>
</head>
<body>
<header>
  <h1>🐾 PetStore</h1>
  <p>Tienda de mascotas</p>
</header>

<main>
  <!-- PRODUCTOS -->
  <section id="tab-productos" class="tab active">
    <div class="card">
      <h2 id="producto-titulo">Agregar producto</h2>
      <input type="hidden" id="prod-id">
      <label>Nombre *</label>
      <input id="prod-name" placeholder="Ej: Shampoo para perros">
      <label>Categoría</label>
      <select id="prod-category">
        <option>Baño</option>
        <option>Alimento</option>
        <option>Accesorios</option>
        <option>Higiene</option>
        <option>Medicina</option>
        <option>Juguetes</option>
        <option>Otros</option>
      </select>
      <div class="row">
        <div><label>Precio venta</label><input id="prod-sale" type="number" step="0.01" min="0" placeholder="0"></div>
        <div><label>Costo compra</label><input id="prod-cost" type="number" step="0.01" min="0" placeholder="0"></div>
      </div>
      <div class="row">
        <div><label>Stock inicial</label><input id="prod-initial" type="number" min="0" placeholder="0"></div>
        <div><label>Stock mínimo</label><input id="prod-min" type="number" min="0" placeholder="0"></div>
      </div>
      <div class="actions">
        <button id="btn-save-prod" style="flex:1">Guardar</button>
        <button id="btn-cancel-prod" class="secondary" style="flex:1">Cancelar</button>
      </div>
    </div>
    <div class="card">
      <h2>Productos registrados</h2>
      <div id="productos-lista"></div>
    </div>
  </section>

  <!-- VENTAS -->
  <section id="tab-ventas" class="tab">
    <div class="card">
      <h2>Registrar venta</h2>
      <label>Producto</label>
      <select id="venta-producto"></select>
      <label>Cantidad</label>
      <input id="venta-qty" type="number" min="1" value="1">
      <p class="muted" id="venta-stock" style="margin-top:6px"></p>
      <button style="margin-top:10px" onclick="registrarVenta()">Registrar venta</button>
    </div>
    <div class="card">
      <h2>Últimas ventas</h2>
      <div id="ventas-lista"></div>
    </div>
  </section>

  <!-- BAÑOS -->
  <section id="tab-banos" class="tab">
    <div class="card">
      <h2>Registrar baño</h2>
      <div class="row">
        <div><label>Producto usado</label>
          <select id="bano-producto"></select>
        </div>
        <div style="max-width:90px"><label>Cantidad</label>
          <input id="bano-qty" type="number" min="1" value="1">
        </div>
      </div>
      <button class="ghost" style="margin-top:8px" onclick="agregarAlBano()">+ Agregar producto</button>
      <div id="bano-carrito" style="margin-top:8px"></div>
      <div class="row" style="margin-top:8px">
        <div><label>Precio del baño (opcional)</label><input id="bano-price" type="number" step="0.01" min="0" placeholder="0"></div>
        <div><label>Notas</label><input id="bano-notes" placeholder="Ej: baño completo"></div>
      </div>
      <button style="margin-top:10px" onclick="registrarBano()">Registrar baño</button>
    </div>
    <div class="card">
      <h2>Historial de baños</h2>
      <div id="banos-lista"></div>
    </div>
  </section>

  <!-- INVENTARIO -->
  <section id="tab-inventario" class="tab">
    <div class="card">
      <h2>Estado del inventario</h2>
      <p class="muted">Verde = OK · Amarillo = stock bajo · Rojo = agotado</p>
      <div id="inventario-lista" style="margin-top:8px"></div>
    </div>
  </section>

  <!-- REPORTES -->
  <section id="tab-reportes" class="tab">
    <div class="card">
      <h2>Reporte en Excel</h2>
      <p class="muted" style="margin-bottom:10px">
        Incluye: resumen general, productos vendidos, inventario actual,
        productos por comprar y historial de baños.
      </p>
      <button onclick="descargarReporte()">Descargar reporte Excel</button>
    </div>
  </section>
</main>

<nav>
  <button data-tab="productos" class="active"><span class="ico">📦</span>Productos</button>
  <button data-tab="ventas"><span class="ico">💰</span>Ventas</button>
  <button data-tab="banos"><span class="ico">🛁</span>Baños</button>
  <button data-tab="inventario"><span class="ico">📋</span>Inventario</button>
  <button data-tab="reportes"><span class="ico">📊</span>Reportes</button>
</nav>

<div id="toast" class="toast"></div>

<script>
const $ = (id) => document.getElementById(id);
let productos = [];
let banoCarrito = [];

function toast(msg, isErr) {
  const t = $("toast");
  t.textContent = msg;
  t.className = "toast show" + (isErr ? " err" : "");
  setTimeout(() => { t.className = "toast"; }, 2600);
}

async function api(url, method, body) {
  const opt = { method, headers: {} };
  if (body !== undefined) {
    opt.headers["Content-Type"] = "application/json";
    opt.body = JSON.stringify(body);
  }
  const res = await fetch(url, opt);
  let data = {};
  try { data = await res.json(); } catch (e) {}
  if (!res.ok) throw new Error(data.error || "Error en la operación");
  return data;
}

function fmt(n) {
  n = Number(n || 0);
  return "$" + n.toLocaleString("es-CO", { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

document.querySelectorAll("nav button").forEach(btn => {
  btn.addEventListener("click", () => {
    document.querySelectorAll("nav button").forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    $("tab-" + btn.dataset.tab).classList.add("active");
    refrescar();
  });
});

function refrescar() {
  const active = document.querySelector(".tab.active").id;
  if (active === "tab-productos") cargarProductos();
  if (active === "tab-ventas") { cargarSelectores(); cargarVentas(); }
  if (active === "tab-banos") { cargarSelectores(); cargarBanos(); }
  if (active === "tab-inventario") cargarInventario();
}

// ---- Productos
async function cargarProductos() {
  try {
    productos = await api("api.php?action=productos", "GET");
    const lista = $("productos-lista");
    if (!productos.length) { lista.innerHTML = '<div class="empty">No hay productos. Agrega el primero arriba.</div>'; return; }
    lista.innerHTML = `<table><thead><tr><th>Producto</th><th>Stock</th><th></th></tr></thead><tbody>` +
      productos.map(p => `
        <tr>
          <td><strong>${esc(p.name)}</strong><br><span class="muted">${esc(p.category)} · ${fmt(p.sale_price)}</span></td>
          <td>${p.current_stock}</td>
          <td style="text-align:right;white-space:nowrap">
            <button class="small ghost" onclick="editarProducto(${p.id})">Editar</button>
            <button class="small danger" onclick="eliminarProducto(${p.id})">Borrar</button>
          </td>
        </tr>`).join("") + `</tbody></table>`;
  } catch (e) { toast(e.message, true); }
}

function limpiarFormProducto() {
  $("prod-id").value = "";
  $("prod-name").value = "";
  $("prod-category").selectedIndex = 0;
  $("prod-sale").value = "";
  $("prod-cost").value = "";
  $("prod-initial").value = "";
  $("prod-min").value = "";
  $("producto-titulo").textContent = "Agregar producto";
}

function editarProducto(id) {
  const p = productos.find(x => x.id == id);
  if (!p) return;
  $("prod-id").value = p.id;
  $("prod-name").value = p.name;
  $("prod-category").value = p.category;
  $("prod-sale").value = p.sale_price;
  $("prod-cost").value = p.purchase_cost;
  $("prod-initial").value = p.initial_stock;
  $("prod-min").value = p.min_stock;
  $("producto-titulo").textContent = "Editar producto";
  window.scrollTo({ top: 0, behavior: "smooth" });
}

async function guardarProducto() {
  const id = $("prod-id").value;
  const body = {
    name: $("prod-name").value.trim(),
    category: $("prod-category").value,
    sale_price: $("prod-sale").value,
    purchase_cost: $("prod-cost").value,
    initial_stock: $("prod-initial").value,
    min_stock: $("prod-min").value,
  };
  try {
    if (id) await api("api.php?action=producto_editar", "POST", Object.assign({ id: id }, body));
    else await api("api.php?action=productos", "POST", body);
    limpiarFormProducto();
    await cargarProductos();
    toast("Producto guardado");
  } catch (e) { toast(e.message, true); }
}

async function eliminarProducto(id) {
  if (!confirm("¿Eliminar este producto? Se conserva el historial de ventas.")) return;
  try {
    await api("api.php?action=producto_eliminar", "POST", { id: id });
    await cargarProductos();
    toast("Producto eliminado");
  } catch (e) { toast(e.message, true); }
}

// ---- Selectores compartidos
async function cargarSelectores() {
  try {
    productos = await api("api.php?action=productos", "GET");
    const opts = productos.map(p => `<option value="${p.id}">${esc(p.name)}</option>`).join("");
    $("venta-producto").innerHTML = opts;
    $("bano-producto").innerHTML = opts;
    actualizarStockVenta();
  } catch (e) { toast(e.message, true); }
}

function actualizarStockVenta() {
  const pid = Number($("venta-producto").value);
  const p = productos.find(x => x.id == pid);
  $("venta-stock").textContent = p ? `Stock disponible: ${p.current_stock}` : "";
}

// ---- Ventas
async function registrarVenta() {
  const pid = Number($("venta-producto").value);
  const qty = Number($("venta-qty").value);
  if (!pid) { toast("Selecciona un producto", true); return; }
  if (!qty || qty <= 0) { toast("Cantidad inválida", true); return; }
  try {
    const r = await api("api.php?action=ventas", "POST", { product_id: pid, quantity: qty });
    toast("Venta registrada: " + fmt(r.total));
    $("venta-qty").value = 1;
    await cargarVentas();
    await cargarSelectores();
  } catch (e) { toast(e.message, true); }
}

async function cargarVentas() {
  try {
    const ventas = await api("api.php?action=ventas", "GET");
    const lista = $("ventas-lista");
    if (!ventas.length) { lista.innerHTML = '<div class="empty">Aún no hay ventas.</div>'; return; }
    lista.innerHTML = `<table><thead><tr><th>Fecha</th><th>Producto</th><th>Cant.</th><th class="money">Total</th></tr></thead><tbody>` +
      ventas.map(v => `
        <tr>
          <td class="muted">${v.date.slice(5, 16)}</td>
          <td>${esc(v.product)}</td>
          <td>${v.quantity}</td>
          <td class="money">${fmt(v.total)}</td>
        </tr>`).join("") + `</tbody></table>`;
  } catch (e) { toast(e.message, true); }
}

// ---- Baños
function agregarAlBano() {
  const pid = Number($("bano-producto").value);
  const qty = Number($("bano-qty").value);
  if (!pid) { toast("Selecciona un producto", true); return; }
  if (!qty || qty <= 0) { toast("Cantidad inválida", true); return; }
  const p = productos.find(x => x.id == pid);
  const existente = banoCarrito.find(x => x.product_id === pid);
  if (existente) existente.quantity += qty;
  else banoCarrito.push({ product_id: pid, name: p.name, quantity: qty });
  $("bano-qty").value = 1;
  pintarCarrito();
}

function quitarDelBano(idx) {
  banoCarrito.splice(idx, 1);
  pintarCarrito();
}

function pintarCarrito() {
  const div = $("bano-carrito");
  if (!banoCarrito.length) { div.innerHTML = '<p class="muted">Sin productos agregados.</p>'; return; }
  div.innerHTML = banoCarrito.map((x, i) => `
    <div class="cart-item">
      <span>${esc(x.name)} × ${x.quantity}</span>
      <button class="small danger" onclick="quitarDelBano(${i})">Quitar</button>
    </div>`).join("");
}

async function registrarBano() {
  if (!banoCarrito.length) { toast("Agrega al menos un producto", true); return; }
  const body = {
    price: $("bano-price").value || 0,
    notes: $("bano-notes").value.trim(),
    items: banoCarrito.map(x => ({ product_id: x.product_id, quantity: x.quantity })),
  };
  try {
    await api("api.php?action=banos", "POST", body);
    banoCarrito = [];
    $("bano-price").value = "";
    $("bano-notes").value = "";
    pintarCarrito();
    await cargarBanos();
    toast("Baño registrado");
  } catch (e) { toast(e.message, true); }
}

async function cargarBanos() {
  try {
    const banos = await api("api.php?action=banos", "GET");
    const lista = $("banos-lista");
    if (!banos.length) { lista.innerHTML = '<div class="empty">Aún no hay baños registrados.</div>'; return; }
    lista.innerHTML = banos.map(b => `
      <div class="cart-item" style="flex-direction:column;align-items:flex-start">
        <strong>${b.date.slice(5, 16)} — ${fmt(b.price)}</strong>
        <span class="muted">${b.items.map(i => `${esc(i.name)} × ${i.quantity}`).join(", ") || "—"}</span>
        ${b.notes ? `<span class="muted">📝 ${esc(b.notes)}</span>` : ""}
      </div>`).join("");
  } catch (e) { toast(e.message, true); }
}

// ---- Inventario
async function cargarInventario() {
  try {
    const inv = await api("api.php?action=inventario", "GET");
    const lista = $("inventario-lista");
    if (!inv.length) { lista.innerHTML = '<div class="empty">No hay productos.</div>'; return; }
    lista.innerHTML = `<table><thead><tr><th>Producto</th><th>Stock</th><th>Estado</th><th></th></tr></thead><tbody>` +
      inv.map(i => {
        const cls = i.status === "OK" ? "ok" : (i.status === "Stock bajo" ? "low" : "out");
        return `<tr>
          <td>${esc(i.name)}<br><span class="muted">${esc(i.category)}</span></td>
          <td>${i.current_stock} <span class="muted">/ mín ${i.min_stock}</span></td>
          <td><span class="badge ${cls}">${i.status}</span></td>
          <td style="text-align:right"><button class="small reponer" onclick="reponerStock(${i.id})">Reponer</button></td>
        </tr>`;
      }).join("") + `</tbody></table>`;
  } catch (e) { toast(e.message, true); }
}

async function reponerStock(id) {
  const q = prompt("¿Cuántas unidades agregar al stock?");
  if (q === null) return;
  const qty = Number(q);
  if (!qty || qty <= 0) { toast("Cantidad inválida", true); return; }
  try {
    await api("api.php?action=producto_reponer", "POST", { id: id, quantity: qty });
    await cargarInventario();
    toast("Stock actualizado");
  } catch (e) { toast(e.message, true); }
}

// ---- Reporte
function descargarReporte() {
  window.location.href = "api.php?action=reporte";
}

function esc(s) {
  return String(s ?? "").replace(/[&<>"']/g, c => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[c]));
}

$("btn-save-prod").addEventListener("click", guardarProducto);
$("btn-cancel-prod").addEventListener("click", limpiarFormProducto);
$("venta-producto").addEventListener("change", actualizarStockVenta);
cargarProductos();
</script>
</body>
</html>
