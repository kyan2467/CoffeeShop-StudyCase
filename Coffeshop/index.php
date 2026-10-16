<?php
// index.php
include 'auth.php';
include 'config.php';

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'guest';

// Initialize session-based ordered array if it doesn't exist
if (!isset($_SESSION['ordered_products'])) {
    $_SESSION['ordered_products'] = [];
}

// Handle order action when clicked (allowed for both customers and guests)
if (isset($_GET['order_id']) && $role != 'admin') {
    $product_id = intval($_GET['order_id']);
    
    // Add product ID to session array if not already there
    if (!in_array($product_id, $_SESSION['ordered_products'])) {
        $_SESSION['ordered_products'][] = $product_id;
    }

    header("Location: index.php");
    exit();
}

// Handle cancel action for a specific item
if (isset($_GET['cancel_id']) && $role != 'admin') {
    $cancel_id = intval($_GET['cancel_id']);
    if (($key = array_search($cancel_id, $_SESSION['ordered_products'])) !== false) {
        unset($_SESSION['ordered_products'][$key]);
        $_SESSION['ordered_products'] = array_values($_SESSION['ordered_products']);
    }

    header("Location: index.php");
    exit();
}

// Handle clearing entire cart
if (isset($_GET['clear_cart']) && $role != 'admin') {
    $_SESSION['ordered_products'] = [];
    header("Location: index.php");
    exit();
}

// Handle checkout / confirm order
if (isset($_POST['checkout']) && $role != 'admin' && !empty($_SESSION['ordered_products'])) {
    $cart_ids = array_map('intval', $_SESSION['ordered_products']);
    $cart_id_str = implode(',', $cart_ids);
    $res = mysqli_query($conn, "SELECT id, price FROM product WHERE id IN ($cart_id_str)");
    $subtotal = 0;
    $items = [];
    while ($p = mysqli_fetch_assoc($res)) {
        $subtotal += $p['price'];
        $items[] = $p;
    }

    $is_customer = (isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer');
    $discount = $is_customer ? ($subtotal * 0.10) : 0.00;
    $final_price = $subtotal - $discount;
    $uid = $is_customer ? $_SESSION['user_id'] : null;

    $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, total_price, discount, final_price) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iddd", $uid, $subtotal, $discount, $final_price);
    if (mysqli_stmt_execute($stmt)) {
        $order_id = mysqli_insert_id($conn);
        $item_stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, 1, ?)");
        foreach ($items as $item) {
            $item_price = $is_customer ? ($item['price'] * 0.90) : $item['price'];
            mysqli_stmt_bind_param($item_stmt, "iid", $order_id, $item['id'], $item_price);
            mysqli_stmt_execute($item_stmt);
        }
        $_SESSION['ordered_products'] = [];
        $_SESSION['order_success'] = "Order #" . $order_id . " placed successfully! Total: Rp " . number_format($final_price, 0, ',', '.') . ($is_customer ? " (10% Member Discount Applied)" : "");
    }
    header("Location: index.php");
    exit();
}

// Select id, name, price, and url_image from the product table
$query = "SELECT s.id, s.name, s.price, s.url_image FROM product s";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <?php if (isset($_SESSION['order_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <strong>Success!</strong> <?= htmlspecialchars($_SESSION['order_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['order_success']); ?>
        <?php endif; ?>
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Coffee Menu</h4>
                <?php if ($role == 'admin'): ?>
                    <a href="create.php" class="btn btn-success btn-sm">+ Add Product</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): 
                                $prod_id = $row['id'];
                                $is_ordered = in_array($prod_id, $_SESSION['ordered_products']);
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><img src="<?= htmlspecialchars($row['url_image']) ?>" style="width: 100px; height: 75px; object-fit: cover;" class="rounded shadow-sm" alt="coffee image" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?w=300';"></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td>
                                <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'){ ?>
                                    <strike class="text-muted small">Rp <?= number_format($row['price'], 0, ',', '.') ?></strike><br/>
                                    <span class="text-success fw-bold">Rp <?= number_format($row['price']*0.9, 0, ',', '.') ?></span>
                                <?php } else{ ?>
                                    Rp <?= number_format($row['price'], 0, ',', '.') ?>
                                <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($role == 'admin'): ?>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                    <?php else: ?>
                                        <!-- Both customers and guests can order here -->
                                        <?php if ($is_ordered): ?>
                                            <span class="btn btn-secondary btn-sm disabled">Ordered</span>
                                            <a href="index.php?cancel_id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" title="Cancel this item">Cancel</a>
                                        <?php else: ?>
                                            <a href="index.php?order_id=<?= $row['id'] ?>" class="btn btn-success btn-sm">Order</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($role != 'admin' && !empty($_SESSION['ordered_products'])): 
            $cart_ids = array_map('intval', $_SESSION['ordered_products']);
            $cart_id_str = implode(',', $cart_ids);
            $cart_result = mysqli_query($conn, "SELECT id, name, price, url_image FROM product WHERE id IN ($cart_id_str)");
            $cart_items = [];
            $total_original = 0;
            while ($c_row = mysqli_fetch_assoc($cart_result)) {
                $cart_items[] = $c_row;
                $total_original += $c_row['price'];
            }
            $is_customer = (isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer');
            $discount_amount = $is_customer ? ($total_original * 0.10) : 0;
            $final_total = $total_original - $discount_amount;
        ?>
        <div id="cart-section" class="card shadow-sm mt-4 border-success">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">🛒 Your Cart (<?= count($cart_items) ?> <?= count($cart_items) == 1 ? 'item' : 'items' ?>)</h5>
                <a href="index.php?clear_cart=1" class="btn btn-outline-light btn-sm" onclick="return confirm('Cancel all items in your cart?')">Cancel All</a>
            </div>
            <div class="card-body">
                <?php if (!$is_customer): ?>
                    <div class="alert alert-info py-2 mb-3">
                        💡 <strong>Guest Order:</strong> Regular price applies. <a href="login.php" class="alert-link">Login</a> or <a href="register.php" class="alert-link">Register</a> to get a <strong>10% Member Discount</strong>!
                    </div>
                <?php else: ?>
                    <div class="alert alert-success py-2 mb-3">
                        🎉 <strong>Member Discount:</strong> 10% discount is applied to your order!
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $c_no = 1; foreach ($cart_items as $c_item): 
                                $item_final = $is_customer ? ($c_item['price'] * 0.90) : $c_item['price'];
                            ?>
                            <tr>
                                <td><?= $c_no++ ?></td>
                                <td><img src="<?= htmlspecialchars($c_item['url_image']) ?>" width="60" class="img-fluid rounded" alt="item image"></td>
                                <td><?= htmlspecialchars($c_item['name']) ?></td>
                                <td>
                                    <?php if ($is_customer): ?>
                                        <strike class="text-muted small">Rp <?= number_format($c_item['price'], 0, ',', '.') ?></strike><br>
                                        <span class="text-success fw-bold">Rp <?= number_format($item_final, 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        Rp <?= number_format($c_item['price'], 0, ',', '.') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="index.php?cancel_id=<?= $c_item['id'] ?>" class="btn btn-outline-danger btn-sm">Cancel</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <?php if ($is_customer): ?>
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th colspan="2">Rp <?= number_format($total_original, 0, ',', '.') ?></th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end text-success">10% Member Discount:</th>
                                <th colspan="2" class="text-success">-Rp <?= number_format($discount_amount, 0, ',', '.') ?></th>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-light">
                                <th colspan="3" class="text-end fs-5">Total to Pay:</th>
                                <th colspan="2" class="fs-5 text-success">Rp <?= number_format($final_total, 0, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <form method="POST" action="" class="d-flex justify-content-between align-items-center mt-3">
                    <a href="index.php?clear_cart=1" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to cancel the entire cart?')">Cancel All Orders</a>
                    <button type="submit" name="checkout" class="btn btn-success btn-lg">Place Order (Rp <?= number_format($final_total, 0, ',', '.') ?>)</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>