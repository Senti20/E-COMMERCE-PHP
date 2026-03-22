<?php
$pageTitle = "Manage Categories";
require_once 'includes/header.php';

$icons = [
    'bi-cpu' => 'CPU',
    'bi-gpu-card' => 'GPU',
    'bi-motherboard' => 'Motherboard',
    'bi-memory' => 'RAM',
    'bi-hdd' => 'Storage',
    'bi-lightning-charge' => 'Power Supply',
    'bi-fan' => 'Cooling',
    'bi-pc-display' => 'Case',
    'bi-box' => 'Box',
    'bi-grid' => 'Grid'
];

$colors = [
    'primary' => 'Blue',
    'success' => 'Green',
    'info' => 'Light Blue',
    'warning' => 'Yellow',
    'danger' => 'Red',
    'secondary' => 'Gray',
    'dark' => 'Black',
    'purple' => 'Purple'
];

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $check_query = "SELECT COUNT(*) as total FROM products WHERE category_id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([':id' => $id]);
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        $_SESSION['message'] = "Cannot delete category with existing products";
        $_SESSION['message_type'] = "danger";
    } else {
        $query = "DELETE FROM categories WHERE category_id = :id";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([':id' => $id])) {
            $_SESSION['message'] = "Category deleted successfully";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to delete category";
            $_SESSION['message_type'] = "danger";
        }
    }
    header('Location: categories.php');
    exit();
}

$errors = [];
$edit_mode = false;
$edit_id = 0;
$form_data = [
    'category_name' => '',
    'description' => '',
    'icon' => 'bi-box',
    'color' => 'primary',
    'status' => 'active'
];

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = $_GET['edit'];
    
    $query = "SELECT * FROM categories WHERE category_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $edit_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        $form_data = $category;
    } else {
        $_SESSION['message'] = "Category not found";
        $_SESSION['message_type'] = "danger";
        header('Location: categories.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = $_POST['category_id'];
        
        $check_query = "SELECT COUNT(*) as total FROM products WHERE category_id = :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([':id' => $id]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete category with existing products']);
        } else {
            $query = "DELETE FROM categories WHERE category_id = :id";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([':id' => $id])) {
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
            }
        }
        exit();
    }
    
    $form_data['category_name'] = trim($_POST['category_name'] ?? '');
    $form_data['description'] = trim($_POST['description'] ?? '');
    $form_data['icon'] = $_POST['icon'] ?? 'bi-box';
    $form_data['color'] = $_POST['color'] ?? 'primary';
    $form_data['status'] = $_POST['status'] ?? 'active';
    $edit_id = $_POST['edit_id'] ?? 0;
    
    if (empty($form_data['category_name'])) {
        $errors[] = "Category name is required";
    }
    
    if (empty($form_data['description'])) {
        $errors[] = "Description is required";
    }
    
    if (empty($errors)) {
        if ($edit_id) {
            $check_query = "SELECT category_id FROM categories WHERE category_name = :name AND category_id != :id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([
                ':name' => $form_data['category_name'],
                ':id' => $edit_id
            ]);
        } else {
            $check_query = "SELECT category_id FROM categories WHERE category_name = :name";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([':name' => $form_data['category_name']]);
        }
        
        if ($check_stmt->rowCount() > 0) {
            $errors[] = "Category name already exists";
        }
    }
    
    if (empty($errors)) {
        if ($edit_id) {
            $query = "UPDATE categories SET 
                      category_name = :name,
                      description = :description,
                      icon = :icon,
                      color = :color,
                      status = :status
                      WHERE category_id = :id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':name' => $form_data['category_name'],
                ':description' => $form_data['description'],
                ':icon' => $form_data['icon'],
                ':color' => $form_data['color'],
                ':status' => $form_data['status'],
                ':id' => $edit_id
            ]);
            
            if ($result) {
                $_SESSION['message'] = "Category updated successfully!";
                $_SESSION['message_type'] = "success";
                header('Location: categories.php');
                exit();
            } else {
                $errors[] = "Failed to update category";
            }
        } else {
            $query = "INSERT INTO categories (category_name, description, icon, color, status, created_at) 
                      VALUES (:name, :description, :icon, :color, :status, NOW())";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([
                ':name' => $form_data['category_name'],
                ':description' => $form_data['description'],
                ':icon' => $form_data['icon'],
                ':color' => $form_data['color'],
                ':status' => $form_data['status']
            ])) {
                $_SESSION['message'] = "Category added successfully!";
                $_SESSION['message_type'] = "success";
                header('Location: categories.php');
                exit();
            } else {
                $errors[] = "Failed to add category";
            }
        }
    }
}

$query = "SELECT c.*, COUNT(p.product_id) as product_count 
          FROM categories c 
          LEFT JOIN products p ON c.category_id = p.category_id 
          GROUP BY c.category_id 
          ORDER BY c.category_name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h5 class="mb-0"><?php echo $edit_mode ? 'Edit Category' : 'Add New Category'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="categoryForm">
                    <?php if ($edit_mode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="category_name" class="form-label required">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" 
                               value="<?php echo htmlspecialchars($form_data['category_name']); ?>" 
                               placeholder="e.g., CPU, GPU, Motherboard" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label required">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" 
                                  placeholder="Enter category description" required><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon" class="form-label">Category Icon</label>
                        <select class="form-select" id="icon" name="icon">
                            <?php foreach ($icons as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $form_data['icon'] == $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2 text-center">
                            <i class="bi <?php echo $form_data['icon']; ?> fs-1"></i>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="color" class="form-label">Category Color</label>
                        <select class="form-select" id="color" name="color">
                            <?php foreach ($colors as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $form_data['color'] == $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2 text-center">
                            <span class="badge bg-<?php echo $form_data['color']; ?> p-2">Sample Text</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo $form_data['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $form_data['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i><?php echo $edit_mode ? 'Update' : 'Save'; ?> Category
                        </button>
                        <?php if ($edit_mode): ?>
                        <a href="categories.php" class="btn btn-secondary">
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
                <h5 class="mb-0">Categories List</h5>
                <input type="text" id="categorySearch" class="form-control form-control-sm" style="width: 250px;" placeholder="Search categories...">
            </div>
            <div class="card-body">
                <?php if (empty($categories)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-grid display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">No categories yet</h5>
                    <p class="text-muted">Use the form to add your first category.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="categoriesTable">
                        <thead>
                            <tr>
                                <th>Icon</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Color</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <i class="bi <?php echo $category['icon'] ?? 'bi-folder'; ?> text-<?php echo $category['color'] ?? 'secondary'; ?>" style="font-size: 1.5rem;"></i>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars(substr($category['description'], 0, 50)) . (strlen($category['description']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $category['color'] ?? 'secondary'; ?>">
                                        <?php echo $category['color'] ?? 'secondary'; ?>
                                    </span>
                                </td>
                                <td><?php echo $category['product_count']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($category['status'] ?? 'active') == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($category['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($category['product_count'] == 0): ?>
                                    <button type="button" class="btn btn-sm btn-danger" title="Delete"
                                            onclick="confirmDelete(<?php echo $category['category_id']; ?>, '<?php echo addslashes($category['category_name']); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="Cannot delete - has products">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
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
    <input type="hidden" name="category_id" id="delete_id">
</form>

<script>
document.getElementById('icon').addEventListener('change', function() {
    const iconPreview = document.querySelector('.mb-3 .text-center i');
    iconPreview.className = 'bi ' + this.value + ' fs-1';
});

document.getElementById('color').addEventListener('change', function() {
    const colorPreview = document.querySelector('.mb-3 .text-center .badge');
    colorPreview.className = 'badge bg-' + this.value + ' p-2';
});

function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete category "' + name + '"? This action cannot be undone.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('categorySearch').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#categoriesTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>