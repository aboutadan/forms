<?php
declare(strict_types=1);

require_once __DIR__ . "/forms.php";

$pdo = db();
$form_id = isset($_GET["form_id"]) ? (int)$_GET["form_id"] : 0;
$form = null;
$schema = null;
$errors = [];
$success = false;
$values = [];

if ($form_id > 0) {
  $stmt = $pdo->prepare(
    "SELECT id, title, published_schema
     FROM forms
     WHERE id = :id AND is_published = 1"
  );
  $stmt->execute([":id" => $form_id]);
  $form = $stmt->fetch() ?: null;
  if ($form) {
    $schema = decode_json((string)$form["published_schema"]);
  }
}

if (!$form || !$schema) {
  $load_error = "Form not available or invalid schema.";
} else {
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $values = $_POST;
    $errors = validate_responses($schema, $_POST);
    if ($errors === []) {
      $responses = [];
      foreach ($schema["questions"] as $q) {
        $id = $q["id"];
        if (isset($q["condition"]) && !is_condition_met($q["condition"], $_POST, $schema)) {
          continue;
        }
        if (isset($_POST[$id])) {
          $responses[$id] = $_POST[$id];
        }
      }
      $stmt = $pdo->prepare(
        "INSERT INTO form_submissions (form_id, responses)
         VALUES (:form_id, :responses)"
      );
      $stmt->execute([
        ":form_id" => $form_id,
        ":responses" => json_encode($responses, JSON_UNESCAPED_UNICODE),
      ]);
      $success = true;
      $values = [];
    }
  }
}

function input_value(array $values, string $id): string {
  $value = $values[$id] ?? "";
  if (is_array($value)) {
    return "";
  }
  return (string)$value;
}

function is_checked(array $values, string $id, string $option): bool {
  if (!isset($values[$id])) {
    return false;
  }
  $value = $values[$id];
  if (is_array($value)) {
    return in_array($option, $value, true);
  }
  return $value === $option;
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forms | Form</title>
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
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm">
            <div class="card-body p-4 p-lg-5">
              <?php if (!empty($load_error)): ?>
                <div class="alert alert-danger"><?php echo e($load_error); ?></div>
                <a class="btn btn-outline-secondary" href="index.php">Back to home</a>
              <?php else: ?>
                <h1 class="h4 mb-3"><?php echo e((string)$form["title"]); ?></h1>

                <?php if ($success): ?>
                  <div class="alert alert-success">
                    Thank you! Your submission has been recorded.
                  </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                  <div class="alert alert-danger">
                    Please check the fields highlighted in red.
                  </div>
                <?php endif; ?>

                <form method="post">
                  <?php foreach ($schema["questions"] as $q): ?>
                    <?php
                      $id = (string)$q["id"];
                      $type = (string)$q["type"];
                      $label = (string)$q["label"];
                      $required = !empty($q["required"]);
                      $options = $q["options"] ?? [];
                      $has_error = isset($errors[$id]);
                    ?>
                    <div class="mb-3" data-question-id="<?php echo e($id); ?>"<?php if (isset($q["condition"])): ?> data-condition="<?php echo e(json_encode($q["condition"])); ?>" style="display:none"<?php endif; ?>>
                      <label class="form-label" for="<?php echo e($id); ?>">
                        <?php echo e($label); ?>
                        <?php if ($required): ?>
                          <span class="text-danger">*</span>
                        <?php endif; ?>
                      </label>

                      <?php if (in_array($type, ["text", "email", "number", "date"], true)): ?>
                        <input
                          class="form-control<?php echo $has_error ? " is-invalid" : ""; ?>"
                          type="<?php echo e($type); ?>"
                          id="<?php echo e($id); ?>"
                          name="<?php echo e($id); ?>"
                          value="<?php echo e(input_value($values, $id)); ?>"
                          <?php echo $required ? "required" : ""; ?>
                        >
                      <?php elseif ($type === "textarea"): ?>
                        <textarea
                          class="form-control<?php echo $has_error ? " is-invalid" : ""; ?>"
                          id="<?php echo e($id); ?>"
                          name="<?php echo e($id); ?>"
                          rows="4"
                          <?php echo $required ? "required" : ""; ?>
                        ><?php echo e(input_value($values, $id)); ?></textarea>
                      <?php elseif ($type === "select"): ?>
                        <select
                          class="form-select<?php echo $has_error ? " is-invalid" : ""; ?>"
                          id="<?php echo e($id); ?>"
                          name="<?php echo e($id); ?>"
                          <?php echo $required ? "required" : ""; ?>
                        >
                          <option value="">-- Select --</option>
                          <?php foreach ($options as $option): ?>
                            <?php $selected = input_value($values, $id) === $option; ?>
                            <option value="<?php echo e((string)$option); ?>" <?php echo $selected ? "selected" : ""; ?>>
                              <?php echo e((string)$option); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      <?php elseif ($type === "radio"): ?>
                        <?php foreach ($options as $index => $option): ?>
                          <div class="form-check">
                            <input
                              class="form-check-input<?php echo $has_error ? " is-invalid" : ""; ?>"
                              type="radio"
                              name="<?php echo e($id); ?>"
                              id="<?php echo e($id . "_" . $index); ?>"
                              value="<?php echo e((string)$option); ?>"
                              <?php echo is_checked($values, $id, (string)$option) ? "checked" : ""; ?>
                              <?php echo $required && $index === 0 ? "required" : ""; ?>
                            >
                            <label class="form-check-label" for="<?php echo e($id . "_" . $index); ?>">
                              <?php echo e((string)$option); ?>
                            </label>
                          </div>
                        <?php endforeach; ?>
                      <?php elseif ($type === "checkbox"): ?>
                        <?php foreach ($options as $index => $option): ?>
                          <div class="form-check">
                            <input
                              class="form-check-input<?php echo $has_error ? " is-invalid" : ""; ?>"
                              type="checkbox"
                              name="<?php echo e($id); ?>[]"
                              id="<?php echo e($id . "_" . $index); ?>"
                              value="<?php echo e((string)$option); ?>"
                              <?php echo is_checked($values, $id, (string)$option) ? "checked" : ""; ?>
                            >
                            <label class="form-check-label" for="<?php echo e($id . "_" . $index); ?>">
                              <?php echo e((string)$option); ?>
                            </label>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>

                      <?php if ($has_error): ?>
                        <div class="invalid-feedback d-block">
                          <?php echo e($errors[$id]); ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>

                  <button class="btn btn-primary" type="submit">Submit</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php if ($form && $schema && empty($load_error)): ?>
    <script>
      (function() {
        const conditionalDivs = document.querySelectorAll("[data-condition]");
        if (conditionalDivs.length === 0) return;

        function getInputValue(id) {
          const el = document.querySelector(`[name="${id}"]`);
          if (!el) {
            // checkbox: name is id[]
            const checks = document.querySelectorAll(`[name="${id}[]"]:checked`);
            return Array.from(checks).map(c => c.value);
          }
          if (el.type === "radio") {
            const checked = document.querySelector(`[name="${id}"]:checked`);
            return checked ? checked.value : "";
          }
          return el.value;
        }

        function evaluateConditions() {
          conditionalDivs.forEach(div => {
            const cond = JSON.parse(div.dataset.condition);
            const answer = getInputValue(cond.question_id);
            let op = cond.operator || "equals";
            const expected = cond.value || "";
            let met = false;

            // Backward compat
            if (op === "equals" && expected === "__answered__") op = "is_answered";

            switch (op) {
              case "is_answered":
                met = Array.isArray(answer) ? answer.length > 0 : (answer !== "" && answer !== null);
                break;
              case "equals":
                met = Array.isArray(answer) ? answer.includes(expected) : answer === expected;
                break;
              case "not_equals":
                met = Array.isArray(answer) ? !answer.includes(expected) : answer !== expected;
                break;
              case "contains":
                met = typeof answer === "string" && answer.includes(expected);
                break;
              case "greater_than":
                if (answer !== "" && expected !== "" && !isNaN(Number(answer)) && !isNaN(Number(expected))) {
                  met = Number(answer) > Number(expected);
                } else {
                  met = String(answer) > expected;
                }
                break;
              case "less_than":
                if (answer !== "" && expected !== "" && !isNaN(Number(answer)) && !isNaN(Number(expected))) {
                  met = Number(answer) < Number(expected);
                } else {
                  met = String(answer) < expected;
                }
                break;
            }

            if (met) {
              div.style.display = "";
            } else {
              div.style.display = "none";
              // Clear inputs when hidden
              div.querySelectorAll("input, select, textarea").forEach(input => {
                if (input.type === "checkbox" || input.type === "radio") {
                  input.checked = false;
                } else {
                  input.value = "";
                }
              });
            }
          });
        }

        evaluateConditions();

        document.querySelector("form").addEventListener("input", evaluateConditions);
        document.querySelector("form").addEventListener("change", evaluateConditions);
      })();
    </script>
    <?php endif; ?>
  </body>
</html>
