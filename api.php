<?php
/**
 * PetStore - API JSON y generación de reporte Excel.
 * Se llama como api.php?action=...
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/xlsx.php';

init_db();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'productos':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            api_producto_crear();
        } else {
            api_productos_listar();
        }
        break;

    case 'producto_editar':
        api_producto_editar();
        break;

    case 'producto_eliminar':
        api_producto_eliminar();
        break;

    case 'producto_reponer':
        api_producto_reponer();
        break;

    case 'inventario':
        api_inventario();
        break;

    case 'ventas':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            api_venta_crear();
        } else {
            api_ventas_listar();
        }
        break;

    case 'banos':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            api_bano_crear();
        } else {
            api_banos_listar();
        }
        break;

    case 'reporte':
        api_reporte();
        break;

    default:
        json_error('Acción no válida', 404);
}

// ------------------------------------------------------------------ Productos
function api_productos_listar(): void
{
    $rows = db()->query(
        "SELECT id, name, category, sale_price, purchase_cost, initial_stock, current_stock, min_stock
         FROM products ORDER BY name"
    )->fetchAll();
    json_out($rows);
}

function api_producto_crear(): void
{
    $d = read_input();
    $name = trim((string)($d['name'] ?? ''));
    $category = trim((string)($d['category'] ?? '')) ?: 'Otros';
    $sale = to_float($d['sale_price'] ?? 0);
    $cost = to_float($d['purchase_cost'] ?? 0);
    $initial = to_int($d['initial_stock'] ?? 0);
    $min = to_int($d['min_stock'] ?? 0);

    if ($name === '') {
        json_error('El nombre es obligatorio');
    }

    $st = db()->prepare(
        "INSERT INTO products (name, category, sale_price, purchase_cost, initial_stock, current_stock, min_stock)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $st->execute([$name, $category, $sale, $cost, $initial, $initial, $min]);
    json_out(['ok' => true]);
}

function api_producto_editar(): void
{
    $d = read_input();
    $id = to_int($d['id'] ?? null);
    $row = db()->prepare("SELECT current_stock, initial_stock FROM products WHERE id = ?");
    $row->execute([$id]);
    $existing = $row->fetch();
    if (!$existing) {
        json_error('Producto no encontrado', 404);
    }

    $name = trim((string)($d['name'] ?? ''));
    $category = trim((string)($d['category'] ?? '')) ?: 'Otros';
    $sale = to_float($d['sale_price'] ?? 0);
    $cost = to_float($d['purchase_cost'] ?? 0);
    $initial = to_int($d['initial_stock'] ?? 0);
    $min = to_int($d['min_stock'] ?? 0);

    if ($name === '') {
        json_error('El nombre es obligatorio');
    }

    $diff = $initial - (int)$existing['initial_stock'];
    $newCurrent = max(0, (int)$existing['current_stock'] + $diff);

    $st = db()->prepare(
        "UPDATE products SET name = ?, category = ?, sale_price = ?, purchase_cost = ?,
         initial_stock = ?, current_stock = ?, min_stock = ? WHERE id = ?"
    );
    $st->execute([$name, $category, $sale, $cost, $initial, $newCurrent, $min, $id]);
    json_out(['ok' => true]);
}

function api_producto_eliminar(): void
{
    $d = read_input();
    $id = to_int($d['id'] ?? null);
    $st = db()->prepare("DELETE FROM products WHERE id = ?");
    $st->execute([$id]);
    json_out(['ok' => true]);
}

function api_producto_reponer(): void
{
    $d = read_input();
    $id = to_int($d['id'] ?? null);
    $qty = to_int($d['quantity'] ?? 0);
    if ($qty <= 0) {
        json_error('La cantidad debe ser mayor a 0');
    }
    $st = db()->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
    $st->execute([$qty, $id]);
    json_out(['ok' => true]);
}

// ------------------------------------------------------------------ Inventario
function api_inventario(): void
{
    $rows = db()->query(
        "SELECT id, name, category, current_stock, min_stock FROM products ORDER BY category, name"
    )->fetchAll();

    foreach ($rows as &$r) {
        if ((int)$r['current_stock'] <= 0) {
            $r['status'] = 'Agotado';
        } elseif ((int)$r['current_stock'] <= (int)$r['min_stock']) {
            $r['status'] = 'Stock bajo';
        } else {
            $r['status'] = 'OK';
        }
    }
    unset($r);
    json_out($rows);
}

// ------------------------------------------------------------------ Ventas
function api_ventas_listar(): void
{
    $rows = db()->query(
        "SELECT s.id, s.date, p.name AS product, s.quantity, s.unit_price, s.total
         FROM sales s JOIN products p ON p.id = s.product_id
         ORDER BY s.id DESC LIMIT 200"
    )->fetchAll();
    json_out($rows);
}

function api_venta_crear(): void
{
    $d = read_input();
    $pid = to_int($d['product_id'] ?? null);
    $qty = to_int($d['quantity'] ?? 0);
    if ($qty <= 0) {
        json_error('La cantidad debe ser mayor a 0');
    }

    $st = db()->prepare("SELECT current_stock, sale_price, purchase_cost FROM products WHERE id = ?");
    $st->execute([$pid]);
    $row = $st->fetch();
    if (!$row) {
        json_error('Producto no encontrado', 404);
    }
    if ($qty > (int)$row['current_stock']) {
        json_error('Solo quedan ' . (int)$row['current_stock'] . ' unidades');
    }

    $total = round($qty * (float)$row['sale_price'], 2);

    $pdo = db();
    $pdo->beginTransaction();
    $st = $pdo->prepare(
        "INSERT INTO sales (product_id, quantity, unit_price, unit_cost, total, date) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $st->execute([$pid, $qty, $row['sale_price'], $row['purchase_cost'], $total, now_str()]);
    $st = $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE id = ?");
    $st->execute([$qty, $pid]);
    $pdo->commit();

    json_out(['ok' => true, 'total' => $total]);
}

// ------------------------------------------------------------------ Baños
function api_banos_listar(): void
{
    $baths = db()->query("SELECT id, date, price, notes FROM baths ORDER BY id DESC LIMIT 200")->fetchAll();
    $st = db()->prepare(
        "SELECT p.name, bi.quantity FROM bath_items bi
         JOIN products p ON p.id = bi.product_id WHERE bi.bath_id = ? ORDER BY p.name"
    );
    foreach ($baths as &$b) {
        $st->execute([$b['id']]);
        $b['items'] = $st->fetchAll();
    }
    unset($b);
    json_out($baths);
}

function api_bano_crear(): void
{
    $d = read_input();
    $price = to_float($d['price'] ?? 0);
    $notes = trim((string)($d['notes'] ?? ''));
    $items = $d['items'] ?? [];

    if (!is_array($items) || count($items) === 0) {
        json_error('Agrega al menos un producto');
    }

    $pdo = db();
    foreach ($items as $it) {
        $pid = to_int($it['product_id'] ?? null);
        $qty = to_int($it['quantity'] ?? 0);
        if ($qty <= 0) {
            json_error('Cantidades deben ser mayores a 0');
        }
        $st = $pdo->prepare("SELECT current_stock, name FROM products WHERE id = ?");
        $st->execute([$pid]);
        $row = $st->fetch();
        if (!$row || $qty > (int)$row['current_stock']) {
            $name = $row ? (string)$row['name'] : (string)$pid;
            json_error("No hay suficiente stock de '$name'");
        }
    }

    $pdo->beginTransaction();
    $st = $pdo->prepare("INSERT INTO baths (date, price, notes) VALUES (?, ?, ?)");
    $st->execute([now_str(), $price, $notes]);
    $bathId = (int)$pdo->lastInsertId();

    foreach ($items as $it) {
        $pid = to_int($it['product_id'] ?? null);
        $qty = to_int($it['quantity'] ?? 0);
        $st = $pdo->prepare("INSERT INTO bath_items (bath_id, product_id, quantity) VALUES (?, ?, ?)");
        $st->execute([$bathId, $pid, $qty]);
        $st = $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE id = ?");
        $st->execute([$qty, $pid]);
    }
    $pdo->commit();

    json_out(['ok' => true]);
}

// ------------------------------------------------------------------ Reporte Excel
function api_reporte(): void
{
    $pdo = db();

    $sales = $pdo->query(
        "SELECT s.id, s.date, p.name, s.quantity, s.unit_price, s.total
         FROM sales s JOIN products p ON p.id = s.product_id ORDER BY s.date"
    )->fetchAll();

    $inventory = $pdo->query(
        "SELECT name, category, sale_price, purchase_cost, initial_stock, current_stock, min_stock
         FROM products ORDER BY category, name"
    )->fetchAll();

    $toBuy = $pdo->query(
        "SELECT name, category, current_stock, min_stock, (min_stock - current_stock) AS faltante
         FROM products WHERE current_stock <= min_stock ORDER BY faltante DESC"
    )->fetchAll();

    $baths = $pdo->query("SELECT id, date, price, notes FROM baths ORDER BY date")->fetchAll();
    $bathDetails = [];
    $st = $pdo->prepare(
        "SELECT p.name, bi.quantity FROM bath_items bi
         JOIN products p ON p.id = bi.product_id WHERE bi.bath_id = ? ORDER BY p.name"
    );
    foreach ($baths as $b) {
        $st->execute([$b['id']]);
        $bathDetails[(int)$b['id']] = $st->fetchAll();
    }

    $totals = $pdo->query(
        "SELECT COUNT(*) AS ventas, COALESCE(SUM(quantity),0) AS unidades,
                COALESCE(SUM(total),0) AS ingresos,
                COALESCE(SUM((unit_price - unit_cost) * quantity),0) AS ganancia
         FROM sales"
    )->fetch();
    $bathTotals = $pdo->query(
        "SELECT COUNT(*) AS total_banos, COALESCE(SUM(price),0) AS ingresos_banos FROM baths"
    )->fetch();

    $xlsx = new SimpleXLSXWriter();

    // Resumen
    $xlsx->addSheet('Resumen', [
        [['v' => 'RESUMEN GENERAL - TIENDA DE MASCOTAS', 's' => 'text']],
        [],
        ['Fecha del reporte', now_str()],
        [],
        ['Productos vendidos (unidades)', (int)$totals['unidades']],
        ['Número de ventas registradas', (int)$totals['ventas']],
        ['Ingresos por ventas', ['v' => (float)$totals['ingresos'], 's' => 'money']],
        ['Ganancia estimada por ventas', ['v' => (float)$totals['ganancia'], 's' => 'money']],
        ['Número de baños realizados', (int)$bathTotals['total_banos']],
        ['Ingresos por baños', ['v' => (float)$bathTotals['ingresos_banos'], 's' => 'money']],
        ['Productos registrados', count($inventory)],
        ['Productos por comprar', count($toBuy)],
    ], [42, 24], false);

    // Ventas
    $rows = [['ID', 'Fecha', 'Producto', 'Cantidad', 'Precio unitario', 'Total']];
    foreach ($sales as $s) {
        $rows[] = [
            (int)$s['id'],
            $s['date'],
            $s['name'],
            (int)$s['quantity'],
            ['v' => (float)$s['unit_price'], 's' => 'money'],
            ['v' => (float)$s['total'], 's' => 'money'],
        ];
    }
    $xlsx->addSheet('Ventas', $rows, [8, 20, 30, 12, 16, 16]);

    // Inventario
    $rows = [['Producto', 'Categoría', 'Precio venta', 'Costo', 'Stock inicial', 'Stock actual', 'Mínimo', 'Estado']];
    foreach ($inventory as $i) {
        if ((int)$i['current_stock'] <= 0) {
            $estado = 'Agotado';
        } elseif ((int)$i['current_stock'] <= (int)$i['min_stock']) {
            $estado = 'Stock bajo';
        } else {
            $estado = 'OK';
        }
        $rows[] = [
            $i['name'],
            $i['category'],
            ['v' => (float)$i['sale_price'], 's' => 'money'],
            ['v' => (float)$i['purchase_cost'], 's' => 'money'],
            (int)$i['initial_stock'],
            (int)$i['current_stock'],
            (int)$i['min_stock'],
            $estado,
        ];
    }
    $xlsx->addSheet('Inventario', $rows, [30, 16, 14, 12, 13, 13, 10, 12]);

    // Por comprar
    $rows = [['Producto', 'Categoría', 'Stock actual', 'Mínimo', 'Faltante para reponer']];
    foreach ($toBuy as $t) {
        $rows[] = [$t['name'], $t['category'], (int)$t['current_stock'], (int)$t['min_stock'], (int)$t['faltante']];
    }
    $xlsx->addSheet('Por comprar', $rows, [30, 16, 13, 10, 22]);

    // Baños
    $rows = [['ID', 'Fecha', 'Precio', 'Productos usados', 'Notas']];
    foreach ($baths as $b) {
        $det = $bathDetails[(int)$b['id']] ?? [];
        $detStr = implode(', ', array_map(
            fn($i) => $i['name'] . ' x' . $i['quantity'],
            $det
        ));
        $rows[] = [
            (int)$b['id'],
            $b['date'],
            ['v' => (float)$b['price'], 's' => 'money'],
            $detStr,
            $b['notes'] ?? '',
        ];
    }
    $xlsx->addSheet('Baños', $rows, [8, 20, 12, 50, 30]);

    $filename = 'reporte_petstore_' . date('Ymd_His') . '.xlsx';
    $tmp = tempnam(sys_get_temp_dir(), 'petstore') . '.xlsx';
    $xlsx->save($tmp);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    unlink($tmp);
    exit;
}

// ------------------------------------------------------------------ Helpers
function to_float($v): float
{
    return is_numeric($v) ? (float)$v : 0.0;
}

function to_int($v): int
{
    return is_numeric($v) ? (int)$v : 0;
}
