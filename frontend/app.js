const API = (path, opt={}) =>
  fetch(path, { headers:{'Content-Type':'application/json'}, ...opt })
    .then(r => r.json());

const routes = {
  '/productos': renderProductos,
  '/checkout': renderCheckout,
  '/pedidos': renderPedidos
};

const state = { cart: [] };

function navigate(){ (routes[location.hash.slice(1)] || renderProductos)(); }
window.addEventListener('hashchange', navigate);
window.addEventListener('load', navigate);

async function renderProductos(){
  const app = document.getElementById('app');
  const params = new URLSearchParams(location.search);
  const search = params.get('search') || '';
  const res = await API(`/api/productos?search=${encodeURIComponent(search)}&sort=created_at&dir=desc&page=1&pageSize=20`);
  app.innerHTML = `
    <div class="card">
      <h2>Productos</h2>
      <div class="row">
        <input id="q" class="grow" placeholder="Buscar SKU o nombre" value="${search}"/>
        <button id="btnBuscar">Buscar</button>
      </div>
      <table>
        <thead><tr><th>SKU</th><th>Nombre</th><th>Precio</th><th>Stock</th><th></th></tr></thead>
        <tbody>
          ${res.map(p => `
            <tr>
              <td>${escapeHtml(p.sku)}</td>
              <td>${escapeHtml(p.nombre)}</td>
              <td>$${Number(p.precio).toFixed(2)}</td>
              <td>${p.stock}</td>
              <td><button data-add='${JSON.stringify({id:p.id,sku:p.sku,nombre:p.nombre,precio:parseFloat(p.precio)})}'>Agregar</button></td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>
  `;
  app.querySelector('#btnBuscar').onclick = () => {
    const q = app.querySelector('#q').value.trim();
    history.replaceState(null,'',`#/productos`);
    location.search = q ? `?search=${encodeURIComponent(q)}` : '';
  };
  app.querySelectorAll('button[data-add]').forEach(b => b.onclick = () => {
    const it = JSON.parse(b.dataset.add);
    const found = state.cart.find(x => x.producto_id === it.id);
    if(found){ found.cantidad += 1; } else {
      state.cart.push({ producto_id: it.id, sku: it.sku, nombre: it.nombre, cantidad:1, precio_unitario: it.precio });
    }
    alert('Agregado al carrito');
  });
}

function renderCheckout(){
  const app = document.getElementById('app');
  const rows = state.cart.map((it,i)=>`
    <tr>
      <td>${escapeHtml(it.sku)}</td>
      <td>${escapeHtml(it.nombre)}</td>
      <td><input type="number" min="1" value="${it.cantidad}" data-i="${i}" class="qty"/></td>
      <td>$${it.precio_unitario.toFixed(2)}</td>
      <td>$${(it.cantidad * it.precio_unitario).toFixed(2)}</td>
      <td><button data-del="${i}">Quitar</button></td>
    </tr>`).join('');
  const subtotal = state.cart.reduce((a,c)=>a + c.cantidad*c.precio_unitario, 0);
  const descuento = subtotal>100 ? subtotal*0.10 : 0;
  const base = subtotal - descuento;
  const iva = base * 0.12;
  const total = base + iva;

  app.innerHTML = `
    <div class="card">
      <h2>Checkout</h2>
      <table>
        <thead><tr><th>SKU</th><th>Nombre</th><th>Cant.</th><th>Precio</th><th>Importe</th><th></th></tr></thead>
        <tbody>${rows || `<tr><td colspan="6">Carrito vacío</td></tr>`}</tbody>
      </table>
      <p>Subtotal: $${subtotal.toFixed(2)} | Descuento: $${descuento.toFixed(2)} | IVA: $${iva.toFixed(2)} | <b>Total: $${total.toFixed(2)}</b></p>
      <button id="btnCrear" ${state.cart.length? '' : 'disabled'}>Crear pedido</button>
    </div>
  `;

  app.querySelectorAll('.qty').forEach(inp => inp.onchange = e => {
    const i = parseInt(e.target.dataset.i,10);
    state.cart[i].cantidad = Math.max(1, parseInt(e.target.value,10)||1);
    renderCheckout();
  });
  app.querySelectorAll('button[data-del]').forEach(b => b.onclick = () => {
    state.cart.splice(parseInt(b.dataset.del,10),1); renderCheckout();
  });
  const btn = app.querySelector('#btnCrear');
  if(btn) btn.onclick = async () => {
    const payload = { items: state.cart.map(({producto_id,cantidad,precio_unitario})=>({producto_id,cantidad,precio_unitario})) };
    const res = await API('/api/pedidos',{ method:'POST', body: JSON.stringify(payload) });
    if(res.error){ alert(res.error); return; }
    alert(`Pedido ${res.id} creado. Total: $${Number(res.total).toFixed(2)}`);
    state.cart = [];
    location.hash = '#/pedidos';
  };
}

async function renderPedidos(){
  const app = document.getElementById('app');
  const res = await API('/api/pedidos');
  app.innerHTML = `
    <div class="card">
      <h2>Pedidos</h2>
      <table>
        <thead><tr><th>ID</th><th>Subtotal</th><th>Desc.</th><th>IVA</th><th>Total</th><th>Fecha</th><th></th></tr></thead>
        <tbody>
          ${res.map(p=>`
            <tr>
              <td>${p.id}</td>
              <td>$${Number(p.subtotal).toFixed(2)}</td>
              <td>$${Number(p.descuento).toFixed(2)}</td>
              <td>$${Number(p.iva).toFixed(2)}</td>
              <td><b>$${Number(p.total).toFixed(2)}</b></td>
              <td>${new Date(p.created_at).toLocaleString()}</td>
              <td><button data-id="${p.id}">Ver</button></td>
            </tr>`).join('')}
        </tbody>
      </table>
      <div id="detalle"></div>
    </div>
  `;
  app.querySelectorAll('button[data-id]').forEach(b => b.onclick = async () => {
    const id = b.dataset.id;
    const det = await API(`/api/pedidos/${id}`);
    const items = det.items?.map(i=>`
      <tr>
        <td>${i.producto_id}</td><td>${escapeHtml(i.sku)}</td>
        <td>${escapeHtml(i.nombre)}</td><td>${i.cantidad}</td>
        <td>$${Number(i.precio_unitario).toFixed(2)}</td>
      </tr>`).join('') || '';
    document.getElementById('detalle').innerHTML = `
      <div class="card">
        <h3>Pedido #${det.id}</h3>
        <p>Subtotal: $${Number(det.subtotal).toFixed(2)} | Desc: $${Number(det.descuento).toFixed(2)} | IVA: $${Number(det.iva).toFixed(2)} | Total: <b>$${Number(det.total).toFixed(2)}</b></p>
        <table>
          <thead><tr><th>ProductoID</th><th>SKU</th><th>Nombre</th><th>Cant.</th><th>Precio</th></tr></thead>
          <tbody>${items}</tbody>
        </table>
      </div>`;
  });
}

function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
