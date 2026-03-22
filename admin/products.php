<?php
$pageTitle = "Manage Products";
require_once 'includes/header.php';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $img_query = "SELECT image_path FROM products WHERE product_id = :id";
    $img_stmt = $db->prepare($img_query);
    $img_stmt->execute([':id' => $id]);
    $product = $img_stmt->fetch(PDO::FETCH_ASSOC);
    
    $query = "DELETE FROM products WHERE product_id = :id";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([':id' => $id])) {
        if ($product && !empty($product['image_path']) && file_exists('../' . $product['image_path'])) {
            unlink('../' . $product['image_path']);
        }
        $_SESSION['message'] = "Product deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to delete product";
        $_SESSION['message_type'] = "danger";
    }
    header('Location: products.php');
    exit();
}

$errors = [];
$edit_mode = false;
$edit_id = 0;
$form_data = [
    'product_name' => '',
    'category_id' => '',
    'price' => '',
    'quantity' => '',
    'description' => '',
    'status' => 'active'
];

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = $_GET['edit'];
    
    $query = "SELECT * FROM products WHERE product_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $edit_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $form_data = $product;
    } else {
        $_SESSION['message'] = "Product not found";
        $_SESSION['message_type'] = "danger";
        header('Location: products.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = $_POST['product_id'];
        
        $img_query = "SELECT image_path FROM products WHERE product_id = :id";
        $img_stmt = $db->prepare($img_query);
        $img_stmt->execute([':id' => $id]);
        $product = $img_stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "DELETE FROM products WHERE product_id = :id";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([':id' => $id])) {
            if ($product && !empty($product['image_path']) && file_exists('../' . $product['image_path'])) {
                unlink('../' . $product['image_path']);
            }
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
        }
        exit();
    }
    
    $form_data['product_name'] = trim($_POST['product_name'] ?? '');
    $form_data['category_id'] = $_POST['category_id'] ?? '';
    $form_data['price'] = $_POST['price'] ?? '';
    $form_data['quantity'] = $_POST['quantity'] ?? '';
    $form_data['description'] = trim($_POST['description'] ?? '');
    $form_data['status'] = $_POST['status'] ?? 'active';
    $edit_id = $_POST['edit_id'] ?? 0;
    
    if (empty($form_data['product_name'])) {
        $errors[] = "Product name is required";
    }
    
    if (empty($form_data['category_id'])) {
        $errors[] = "Category is required";
    }
    
    if (empty($form_data['price'])) {
        $errors[] = "Price is required";
    } elseif (!is_numeric($form_data['price']) || $form_data['price'] <= 0) {
        $errors[] = "Price must be a positive number";
    }
    
    if ($form_data['quantity'] === '') {
        $errors[] = "Quantity is required";
    } elseif (!ctype_digit($form_data['quantity']) || $form_data['quantity'] < 0) {
        $errors[] = "Quantity must be a whole number (0 or more)";
    }
    
    if (empty($form_data['description'])) {
        $errors[] = "Description is required";
    }
    
    $image_path = $edit_id ? ($form_data['image_path'] ?? '') : '';
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['product_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['product_image']['size'];
        
        // Changed from 2MB to 5MB
        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = "File size too large. Maximum size is 5MB.";
        } elseif (in_array($ext, $allowed)) {
            $upload_dir = '../assets/uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                if ($edit_id && !empty($form_data['image_path']) && file_exists('../' . $form_data['image_path'])) {
                    unlink('../' . $form_data['image_path']);
                }
                $image_path = 'assets/uploads/products/' . $new_filename;
            } else {
                $errors[] = "Failed to upload image";
            }
        } else {
            $errors[] = "Invalid file type. Allowed: jpg, jpeg, png, gif, webp";
        }
    } elseif (!$edit_id && empty($image_path)) {
        $errors[] = "Product image is required for new products";
    }
    
    if (empty($errors)) {
        if ($edit_id) {
            $query = "UPDATE products SET 
                      product_name = :product_name,
                      description = :description,
                      price = :price,
                      quantity = :quantity,
                      category_id = :category_id,
                      image_path = :image_path,
                      status = :status
                      WHERE product_id = :product_id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':product_name' => $form_data['product_name'],
                ':description' => $form_data['description'],
                ':price' => $form_data['price'],
                ':quantity' => $form_data['quantity'],
                ':category_id' => $form_data['category_id'],
                ':image_path' => $image_path,
                ':status' => $form_data['status'],
                ':product_id' => $edit_id
            ]);
            
            if ($result) {
                $_SESSION['message'] = "Product updated successfully!";
                $_SESSION['message_type'] = "success";
                header('Location: products.php');
                exit();
            } else {
                $errors[] = "Failed to update product";
            }
        } else {
            $query = "INSERT INTO products (product_name, description, price, quantity, category_id, image_path, status, created_at) 
                      VALUES (:product_name, :description, :price, :quantity, :category_id, :image_path, :status, NOW())";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([
                ':product_name' => $form_data['product_name'],
                ':description' => $form_data['description'],
                ':price' => $form_data['price'],
                ':quantity' => $form_data['quantity'],
                ':category_id' => $form_data['category_id'],
                ':image_path' => $image_path,
                ':status' => $form_data['status']
            ])) {
                $_SESSION['message'] = "Product added successfully!";
                $_SESSION['message_type'] = "success";
                header('Location: products.php');
                exit();
            } else {
                $errors[] = "Failed to add product";
            }
        }
    }
}

$cat_query = "SELECT category_id, category_name FROM categories WHERE status = 'active' ORDER BY category_name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$prod_query = "SELECT p.*, c.category_name, c.color 
               FROM products p 
               LEFT JOIN categories c ON p.category_id = c.category_id 
               ORDER BY p.product_id DESC";
$prod_stmt = $db->prepare($prod_query);
$prod_stmt->execute();
$products = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?php echo $edit_mode ? 'Edit Product' : 'Add New Product'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="productForm">
                    <?php if ($edit_mode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="product_name" class="form-label required">Product Name</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" 
                               value="<?php echo htmlspecialchars($form_data['product_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label required">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" 
                                <?php echo $form_data['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label required">Price (₱)</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo htmlspecialchars($form_data['price']); ?>" 
                                   step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label required">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="<?php echo htmlspecialchars($form_data['quantity']); ?>" 
                                   min="0" step="1" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label required">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo $form_data['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $form_data['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="product_image" class="form-label <?php echo !$edit_mode ? 'required' : ''; ?>">Product Image</label>
                        <input type="file" class="form-control" id="product_image" name="product_image" 
                               accept="image/*" onchange="previewImage(this, 'imagePreview')" <?php echo !$edit_mode ? 'required' : ''; ?>>
                        <small class="text-muted">Max 5MB. Allowed: JPG, PNG, GIF, WEBP</small>
                        
                        <?php if ($edit_mode && !empty($form_data['image_path'])): ?>
                        <div class="mt-2 text-center">
                            <p class="mb-1">Current Image:</p>
                            <img src="../<?php echo $form_data['image_path']; ?>" 
                                 alt="Current" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-2 text-center">
                            <img id="imagePreview" src="#" alt="Preview" 
                                 style="max-width: 150px; max-height: 150px; display: none; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i><?php echo $edit_mode ? 'Update' : 'Save'; ?> Product
                        </button>
                        <?php if ($edit_mode): ?>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Products List</h5>
                <input type="text" id="productSearch" class="form-control form-control-sm" style="width: 250px;" placeholder="Search products...">
            </div>
            <div class="card-body">
                <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-box display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">No products yet</h5>
                    <p class="text-muted">Use the form to add your first product.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="productsTable">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($product['image_path']) && file_exists('../' . $product['image_path'])): ?>
                                    <img src="../<?php echo $product['image_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                         class="product-image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 5px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 30)) . '...'; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $product['color'] ?? 'secondary'; ?>">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </span>
                                </td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $product['quantity'] <= 5 ? 'warning' : 'success'; ?>">
                                        <?php echo $product['quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" title="Delete"
                                            onclick="confirmDelete(<?php echo $product['product_id']; ?>, '<?php echo addslashes($product['product_name']); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="product_id" id="delete_id">
</form>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        // Changed from 2MB to 5MB
        if (file.size > 5 * 1024 * 1024) {
            alert('File size too large. Maximum size is 5MB.');
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.style.display = 'block';
            preview.src = e.target.result;
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        preview.src = '#';
    }
}

function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('productSearch').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#productsTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>