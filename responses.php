<?php
declare(strict_types=1);

require_once __DIR__ . "/forms.php";

$pdo = db();
$form_id = isset($_GET["form_id"]) ? (int)$_GET["form_id"] : 0;
$form = null;
$schema = null;
$submissions = [];
$error = null;

if ($form_id > 0) {
  $stmt = $pdo->prepare("SELECT id, title, published_schema FROM forms WHERE id = :id");
  $stmt->execute([":id" => $form_id]);
  $form = $stmt->fetch() ?: null;
  if ($form) {
    $schema = decode_json((string)$form["published_schema"]) ?? ["questions" => []];
    $stmt = $pdo->prepare(
      "SELECT id, responses, submitted_at
       FROM form_submissions
       WHERE form_id = :id
       ORDER BY submitted_at DESC"
    );
    $stmt->execute([":id" => $form_id]);
    $submissions = $stmt->fetchAll();
  } else {
    $error = "Form not found.";
  }
} else {
  $error = "Missing form identifier.";
}

function render_value(mixed $value): string {
  if (is_array($value)) {
    return implode(", ", array_map("strval", $value));
  }
  return (string)$value;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forms | Responses</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
      crossorigin="anonymous"
    >
  </head>
  <body class="bg-light">
    <div class="container py-5">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
          <h1 class="h3 mb-1">Responses</h1>
          <?php if ($form): ?>
            <p class="text-muted mb-0"><?php echo e((string)$form["title"]); ?></p>
          <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-outline-secondary" href="index.php">Home</a>
          <a class="btn btn-outline-secondary" href="list.php">All forms</a>
          <?php if ($form): ?>
            <a class="btn btn-outline-primary" href="view.php?form_id=<?php echo e((string)$form["id"]); ?>">View form</a>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
      <?php else: ?>
        <div class="card shadow-sm mb-4">
          <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <h2 class="h5 mb-0">Total submissions: <?php echo count($submissions); ?></h2>
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0" for="search">Search</label>
              <input
                type="text"
                id="search"
                class="form-control"
                placeholder="Filter responses..."
                style="min-width: 240px;"
              >
            </div>
          </div>
        </div>

        <?php if (!$submissions): ?>
          <div class="alert alert-info">No responses yet for this form.</div>
        <?php endif; ?>

        <?php foreach ($submissions as $submission): ?>
          <?php
            $responses = decode_json((string)$submission["responses"]) ?? [];
            $questions = is_array($schema["questions"] ?? null) ? $schema["questions"] : [];
            $search_text = [];
            foreach ($questions as $q) {
              $qid = (string)($q["id"] ?? "");
              $label = (string)($q["label"] ?? $qid);
              $value = $qid !== "" && array_key_exists($qid, $responses) ? render_value($responses[$qid]) : "";
              $search_text[] = $label;
              $search_text[] = $value;
            }
            $search_text[] = (string)$submission["id"];
            $search_text[] = (string)$submission["submitted_at"];
            $search_flat = strtolower(trim(implode(" ", $search_text)));
          ?>
          <div class="card shadow-sm mb-3 response-item" data-search="<?php echo e($search_flat); ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <strong>Submission #<?php echo e((string)$submission["id"]); ?></strong>
                <span class="text-muted small"><?php echo e((string)$submission["submitted_at"]); ?></span>
              </div>
              <div class="table-responsive">
                <table class="table table-sm table-borderless mb-0">
                  <tbody>
                    <?php foreach ($questions as $q): ?>
                      <?php
                        $qid = (string)($q["id"] ?? "");
                        $label = (string)($q["label"] ?? $qid);
                        $value = $qid !== "" && array_key_exists($qid, $responses) ? render_value($responses[$qid]) : "";
                      ?>
                      <tr>
                        <th class="w-50"><?php echo e($label); ?></th>
                        <td><?php echo e($value); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <details class="mt-3">
                <summary class="text-muted">View JSON</summary>
                <pre class="bg-light border rounded p-2 mb-0"><?php echo e(json_encode($responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
              </details>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <script>
      const searchInput = document.getElementById("search");
      if (searchInput) {
        const items = Array.from(document.querySelectorAll(".response-item"));
        const totalLabel = document.querySelector(".card h2.h5");
        const totalBase = totalLabel ? totalLabel.textContent : "";

        const update = () => {
          const q = searchInput.value.trim().toLowerCase();
          let visible = 0;
          items.forEach((item) => {
            const data = item.getAttribute("data-search") || "";
            const match = data.includes(q);
            item.classList.toggle("d-none", !match);
            if (match) visible += 1;
          });
          if (totalLabel) {
            totalLabel.textContent = q
              ? `Results: ${visible}`
              : totalBase;
          }
        };

        searchInput.addEventListener("input", update);
      }
    </script>
  </body>
</html>
