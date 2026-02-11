<?php
declare(strict_types=1);

require_once __DIR__ . "/forms.php";

$pdo = db();
$message = null;
$error = null;

$form_id = isset($_GET["form_id"]) ? (int)$_GET["form_id"] : 0;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $form_id = isset($_POST["form_id"]) ? (int)$_POST["form_id"] : 0;
  if ($form_id > 0) {
    $stmt = $pdo->prepare(
      "UPDATE forms
       SET published_schema = draft_schema,
           is_published = 1,
           published_at = NOW()
       WHERE id = :id"
    );
    $stmt->execute([":id" => $form_id]);
    $message = "Form published successfully.";
  } else {
    $error = "Missing form identifier.";
  }
}

$form = null;
if ($form_id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = :id");
  $stmt->execute([":id" => $form_id]);
  $form = $stmt->fetch() ?: null;
  if (!$form) {
    $error = "Form not found.";
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forms | Publish draft</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
      crossorigin="anonymous"
    >
  </head>
  <body class="bg-light">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm">
            <div class="card-body p-4 p-lg-5">
              <h1 class="h4 mb-3">Publish form</h1>

              <?php if ($message): ?>
                <div class="alert alert-success"><?php echo e($message); ?></div>
              <?php endif; ?>

              <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
              <?php endif; ?>

              <?php if ($form): ?>
                <p><strong>Title:</strong> <?php echo e((string)$form["title"]); ?></p>
                <p class="text-muted">
                  Publishing copies the current draft schema to <code>published_schema</code>.
                </p>
                <form method="post" class="d-flex gap-2">
                  <input type="hidden" name="form_id" value="<?php echo e((string)$form_id); ?>">
                  <button class="btn btn-primary" type="submit">Publish now</button>
                  <a class="btn btn-outline-secondary" href="create.php?form_id=<?php echo e((string)$form_id); ?>">Back to draft</a>
                  <a class="btn btn-outline-secondary" href="view.php?form_id=<?php echo e((string)$form_id); ?>">View published</a>
                </form>
              <?php else: ?>
                <a class="btn btn-outline-secondary" href="create.php">Create a form</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
