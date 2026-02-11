<?php
declare(strict_types=1);

require_once __DIR__ . "/forms.php";

$pdo = db();
$stmt = $pdo->query(
  "SELECT id, title, is_published, created_at, published_at
   FROM forms
   ORDER BY created_at DESC"
);
$forms = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forms | All forms</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
      crossorigin="anonymous"
    >
    <style> 
        .red {
            border: red solid 1px;
            border-radius: 5px;
        }
    </style>
  </head>
  <body class="bg-light">
    <div class="container py-5">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
          <h1 class="h3 mb-1">Forms</h1>
          <p class="text-muted mb-0">Edit drafts or publish forms.</p>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-outline-secondary" href="index.php">Home</a>
          <a class="btn btn-primary" href="create.php">New form</a>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="table-responsive rounded">
        <table class="table mb-0 align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$forms): ?>
                <tr>
                <td colspan="4" class="text-center text-muted py-4">No forms yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($forms as $f): ?>
                <tr>
                    <td><?php echo e((string)$f["id"]); ?></td>
                    <td><?php echo e((string)$f["title"]); ?></td>
                    <td>
                    <?php if ((int)$f["is_published"] === 1): ?>
                        <span class="badge text-bg-success">Published</span>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">Draft</span>
                    <?php endif; ?>
                    </td>
                    <td class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="create.php?form_id=<?php echo e((string)$f["id"]); ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-secondary" href="publish.php?form_id=<?php echo e((string)$f["id"]); ?>">Publish</a>
                    <a class="btn btn-sm btn-outline-dark" href="responses.php?form_id=<?php echo e((string)$f["id"]); ?>">Responses</a>
                    <?php if ((int)$f["is_published"] === 1): ?>
                        <a class="btn btn-sm btn-outline-success" href="view.php?form_id=<?php echo e((string)$f["id"]); ?>">View</a>
                    <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>    
      </div>
    </div>
  </body>
</html>
