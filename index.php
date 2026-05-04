<?php
session_start();

// 1. Data Structure & Initialization
$genres = ["Fiction", "Non-Fiction", "Science", "History", "Biography", "Technology"];

// Initialize books in session to keep data persistent during testing
if (!isset($_SESSION['books'])) {
    $_SESSION['books'] = [
        ['id' => 1, 'title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald', 'genre' => 'Fiction', 'year' => 1925, 'pages' => 218],
        ['id' => 2, 'title' => 'A Brief History of Time', 'author' => 'Stephen Hawking', 'genre' => 'Science', 'year' => 1988, 'pages' => 256],
        ['id' => 3, 'title' => 'Steve Jobs', 'author' => 'Walter Isaacson', 'genre' => 'Biography', 'year' => 2011, 'pages' => 656],
    ];
}
$books = &$_SESSION['books'];

$errors = [];
$submittedData = ['id' => '', 'title' => '', 'author' => '', 'genre' => '', 'year' => '', 'pages' => ''];

// Check for Edit Mode
$editMode = false;
if (isset($_GET['edit_id'])) {
    $editMode = true;
    $edit_id = (int)$_GET['edit_id'];
    foreach ($books as $book) {
        if ($book['id'] === $edit_id) {
            $submittedData = $book;
            break;
        }
    }
}

// 2. Form Handling & Validation Logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Handle Delete Action
    if (isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        $books = array_filter($books, function($book) use ($delete_id) {
            return $book['id'] !== $delete_id;
        });
        $books = array_values($books); // Re-index array
        $_SESSION['success'] = "Book deleted successfully.";
        header("Location: index.php");
        exit;
    }

    // Handle Add / Update Action
    $submittedData['title'] = htmlspecialchars(trim($_POST['title'] ?? ''));
    $submittedData['author'] = htmlspecialchars(trim($_POST['author'] ?? ''));
    $submittedData['genre'] = htmlspecialchars(trim($_POST['genre'] ?? ''));
    $submittedData['year'] = htmlspecialchars(trim($_POST['year'] ?? ''));
    $submittedData['pages'] = htmlspecialchars(trim($_POST['pages'] ?? ''));

    // Title Validation
    $titleLen = strlen($submittedData['title']);
    if (empty($submittedData['title']) || $titleLen < 3 || $titleLen > 120) {
        $errors['title'] = "Title must be between 3 and 120 characters.";
    }

    // Author Validation
    $authorWords = explode(' ', trim($submittedData['author']));
    if (empty($submittedData['author']) || count(array_filter($authorWords)) < 2) {
        $errors['author'] = "Author must contain at least a first and last name.";
    }

    // Genre Validation
    if (empty($submittedData['genre']) || !in_array($submittedData['genre'], $genres)) {
        $errors['genre'] = "Please select a valid genre from the list.";
    }

    // Year Validation
    $currentYear = (int)date("Y");
    $yearVal = (int)$submittedData['year'];
    if (empty($submittedData['year']) || $yearVal < 1000 || $yearVal > $currentYear) {
        $errors['year'] = "Year must be a 4-digit integer between 1000 and $currentYear.";
    }

    // Pages Validation
    $pagesVal = (int)$submittedData['pages'];
    if (empty($submittedData['pages']) || $pagesVal <= 0) {
        $errors['pages'] = "Pages must be a positive integer greater than 0.";
    }

    // On Success
    if (empty($errors)) {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update existing book
            $update_id = (int)$_POST['id'];
            foreach ($books as $key => $book) {
                if ($book['id'] === $update_id) {
                    $books[$key] = [
                        'id' => $update_id,
                        'title' => $submittedData['title'],
                        'author' => $submittedData['author'],
                        'genre' => $submittedData['genre'],
                        'year' => $yearVal,
                        'pages' => $pagesVal
                    ];
                    break;
                }
            }
            $_SESSION['success'] = "Book updated successfully.";
        } else {
            // Add new book
            $max_id = 0;
            foreach ($books as $book) {
                if ($book['id'] > $max_id) {
                    $max_id = $book['id'];
                }
            }
            $new_id = $max_id + 1;

            $books[] = [
                'id' => $new_id,
                'title' => $submittedData['title'],
                'author' => $submittedData['author'],
                'genre' => $submittedData['genre'],
                'year' => $yearVal,
                'pages' => $pagesVal
            ];
            $_SESSION['success'] = "Book added successfully.";
        }

        // Post/Redirect/Get to prevent re-submission
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Book Library</title>
    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Personal Book Library</h2>

    <!-- Success Alert -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Form Section -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><?= $editMode ? "Update Book" : "Add New Book" ?></h5>
                </div>
                <div class="card-body">
                    
                    <!-- General Error Alert -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">Please fix the validation errors below.</div>
                    <?php endif; ?>

                    <form method="POST" action="index.php">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$submittedData['id']) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= htmlspecialchars((string)$submittedData['title']) ?>">
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?= $errors['title'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="author" class="form-label">Author</label>
                            <input type="text" class="form-control <?= isset($errors['author']) ? 'is-invalid' : '' ?>" id="author" name="author" value="<?= htmlspecialchars((string)$submittedData['author']) ?>">
                            <?php if (isset($errors['author'])): ?>
                                <div class="invalid-feedback"><?= $errors['author'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="genre" class="form-label">Genre</label>
                            <select class="form-control <?= isset($errors['genre']) ? 'is-invalid' : '' ?>" id="genre" name="genre">
                                <option value="">Select Genre...</option>
                                <?php foreach ($genres as $g): ?>
                                    <option value="<?= htmlspecialchars($g) ?>" <?= ($submittedData['genre'] === $g) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['genre'])): ?>
                                <div class="invalid-feedback"><?= $errors['genre'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control <?= isset($errors['year']) ? 'is-invalid' : '' ?>" id="year" name="year" value="<?= htmlspecialchars((string)$submittedData['year']) ?>">
                            <?php if (isset($errors['year'])): ?>
                                <div class="invalid-feedback"><?= $errors['year'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="pages" class="form-label">Pages</label>
                            <input type="number" class="form-control <?= isset($errors['pages']) ? 'is-invalid' : '' ?>" id="pages" name="pages" value="<?= htmlspecialchars((string)$submittedData['pages']) ?>">
                            <?php if (isset($errors['pages'])): ?>
                                <div class="invalid-feedback"><?= $errors['pages'] ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn <?= $editMode ? 'btn-warning' : 'btn-primary' ?> w-100">
                            <?= $editMode ? "Update Book" : "Add Book" ?>
                        </button>

                        <?php if ($editMode): ?>
                            <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="col-lg-8">
            <div class="table-responsive shadow-sm">
                <table class="table table-striped table-hover table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Genre</th>
                            <th>Year</th>
                            <th>Pages</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($books)): ?>
                            <tr><td colspan="7" class="text-center">No books found in the library.</td></tr>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$book['id']) ?></td>
                                    <td><?= htmlspecialchars($book['title']) ?></td>
                                    <td><?= htmlspecialchars($book['author']) ?></td>
                                    <td><?= htmlspecialchars($book['genre']) ?></td>
                                    <td><?= (int)$book['year'] ?></td>
                                    <td><?= htmlspecialchars((string)$book['pages']) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="index.php?edit_id=<?= $book['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            
                                            <!-- Delete Button triggering Modal -->
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $book['id'] ?>">
                                                Delete
                                            </button>
                                        </div>

                                        <!-- Bootstrap Modal for Deletion Confirmation -->
                                        <div class="modal fade" id="deleteModal<?= $book['id'] ?>" tabindex="-1" aria-hidden="true">
                                          <div class="modal-dialog">
                                            <div class="modal-content">
                                              <div class="modal-header">
                                                <h5 class="modal-title">Confirm Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                              </div>
                                              <div class="modal-body">
                                                Are you sure you want to delete <strong><?= htmlspecialchars($book['title']) ?></strong>?
                                              </div>
                                              <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="POST" action="index.php" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="delete_id" value="<?= $book['id'] ?>">
                                                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                </form>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle (Includes Popper for Modals) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>