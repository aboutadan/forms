<?php
declare(strict_types=1);

require_once __DIR__ . "/forms.php";

$pdo = db();
$errors = [];
$message = null;
$form = null;
$form_id = isset($_GET["form_id"]) ? (int)$_GET["form_id"] : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $form_id = isset($_POST["form_id"]) ? (int)$_POST["form_id"] : 0;
  $title = trim((string)($_POST["title"] ?? ""));
  $schema_json = trim((string)($_POST["schema"] ?? ""));

  if ($title === "") {
    $errors[] = "Title is required.";
  }

  $schema = decode_json($schema_json);
  $errors = array_merge($errors, validate_schema($schema));

  if ($errors === []) {
    if ($form_id > 0) {
      $stmt = $pdo->prepare(
        "UPDATE forms
         SET title = :title, draft_schema = :draft_schema, is_published = 0
         WHERE id = :id"
      );
      $stmt->execute([
        ":title" => $title,
        ":draft_schema" => json_encode($schema, JSON_UNESCAPED_UNICODE),
        ":id" => $form_id,
      ]);
    } else {
      $stmt = $pdo->prepare(
        "INSERT INTO forms (owner_id, title, draft_schema, is_published)
         VALUES (:owner_id, :title, :draft_schema, 0)"
      );
      $stmt->execute([
        ":owner_id" => 1,
        ":title" => $title,
        ":draft_schema" => json_encode($schema, JSON_UNESCAPED_UNICODE),
      ]);
      $form_id = (int)$pdo->lastInsertId();
    }

    $message = "Draft saved successfully.";
  }
}

if ($form_id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = :id");
  $stmt->execute([":id" => $form_id]);
  $form = $stmt->fetch() ?: null;
}

$demo_schema = <<<JSON
{
  "questions": [
    {
      "id": "full_name",
      "type": "text",
      "label": "Full name",
      "required": true
    },
    {
      "id": "department",
      "type": "select",
      "label": "Department",
      "options": ["HR", "IT", "Finance"]
    }
  ]
}
JSON;

$schema_raw = (string)($_POST["schema"] ?? ($form["draft_schema"] ?? $demo_schema));
$schema_for_js = decode_json($schema_raw);
if (!$schema_for_js || !isset($schema_for_js["questions"])) {
  $schema_for_js = ["questions" => []];
}
$schema_json = json_encode($schema_for_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($schema_json === false) {
  $schema_json = '{"questions":[]}';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forms | Create draft</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
      crossorigin="anonymous"
    >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
      .drag-handle {
        cursor: grab;
      }
      .question-card.dragging {
        opacity: 0.6;
      }
      .question-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        background: #fff;
      }
      .builder-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
      }
    </style>
  </head>
  <body class="bg-light">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
          <div class="card shadow-sm">
            <div class="card-body p-4 p-lg-5">
              <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                  <h1 class="h3 mb-1">Form builder</h1>
                  <p class="text-muted mb-0">
                    Add questions, reorder them, and save the draft. The JSON is generated automatically.
                  </p>
                </div>
                <a class="btn btn-outline-secondary" href="index.php">Back to home</a>
              </div>

              <hr class="my-4">

              <?php if ($message): ?>
                <div class="alert alert-success"><?php echo e($message); ?></div>
              <?php endif; ?>

              <?php if ($errors): ?>
                <div class="alert alert-danger">
                  <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                      <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <?php if ($form && (int)$form["is_published"] === 1): ?>
                <div class="alert alert-warning">
                  This form has already been published. Editing the draft does not affect existing submissions.
                </div>
              <?php endif; ?>

              <form id="builder-form" method="post" novalidate>
                <input type="hidden" name="form_id" value="<?php echo $form_id ? e((string)$form_id) : ""; ?>">

                <div class="mb-4">
                  <label class="form-label" for="title">Form title</label>
                  <input
                    class="form-control"
                    type="text"
                    id="title"
                    name="title"
                    value="<?php echo e((string)($form["title"] ?? ($_POST["title"] ?? ""))); ?>"
                    required
                  >
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h2 class="h5 mb-0">Questions</h2>
                  <button type="button" class="btn btn-outline-primary btn-sm" id="add-question">
                    + Add question
                  </button>
                </div>

                <div id="builder-alert" class="alert alert-danger d-none"></div>

                <div id="questions-list" class="d-grid gap-3 mb-4"></div>

                <div class="builder-actions">
                  <button class="btn btn-primary" type="submit">Save draft</button>
                  <?php if ($form_id > 0): ?>
                    <a class="btn btn-outline-secondary" href="publish.php?form_id=<?php echo e((string)$form_id); ?>">Publish</a>
                    <a class="btn btn-outline-secondary" href="view.php?form_id=<?php echo e((string)$form_id); ?>">View published</a>
                  <?php endif; ?>
                </div>

                <textarea id="schema-field" name="schema" class="d-none"></textarea>

                <details class="mt-4">
                  <summary class="text-muted">View generated JSON (advanced)</summary>
                  <textarea id="schema-preview" class="form-control mt-2" rows="10" readonly></textarea>
                </details>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
      crossorigin="anonymous"
    ></script>
    <script>
      const initialSchema = <?php echo $schema_json; ?>;
      const questionsList = document.getElementById("questions-list");
      const addQuestionBtn = document.getElementById("add-question");
      const form = document.getElementById("builder-form");
      const schemaField = document.getElementById("schema-field");
      const schemaPreview = document.getElementById("schema-preview");
      const alertBox = document.getElementById("builder-alert");

      const types = [
        { value: "text", label: "Text" },
        { value: "textarea", label: "Long text" },
        { value: "email", label: "Email" },
        { value: "number", label: "Number" },
        { value: "select", label: "Dropdown" },
        { value: "radio", label: "Single choice" },
        { value: "checkbox", label: "Multiple choice" },
        { value: "date", label: "Date" },
      ];

      let questions = Array.isArray(initialSchema.questions) ? initialSchema.questions.map((q) => ({
        type: q.type || "text",
        label: q.label || "",
        required: Boolean(q.required),
        options: Array.isArray(q.options) ? q.options : [],
        condition: q.condition ? {
          question_id: q.condition.question_id || "",
          operator: q.condition.operator || (q.condition.value === "__answered__" ? "is_answered" : "equals"),
          value: q.condition.value === "__answered__" ? "" : (q.condition.value || ""),
        } : null,
      })) : [];

      if (questions.length === 0) {
        questions.push({
          type: "text",
          label: "",
          required: false,
          options: [],
          condition: null,
        });
      }

      function slugify(text) {
        return text
          .toString()
          .toLowerCase()
          .normalize("NFD")
          .replace(/[\u0300-\u036f]/g, "")
          .replace(/[^a-z0-9]+/g, "_")
          .replace(/^_+|_+$/g, "")
          .substring(0, 50);
      }

      function needsOptions(type) {
        return ["select", "radio", "checkbox"].includes(type);
      }

      function getPreviousQuestions(index) {
        const result = [];
        const used = new Set();
        for (let i = 0; i < index; i++) {
          const q = questions[i];
          const base = slugify(q.label || `question_${i + 1}`) || `question_${i + 1}`;
          let id = base;
          let counter = 2;
          while (used.has(id)) {
            id = `${base}_${counter}`;
            counter += 1;
          }
          used.add(id);
          result.push({ id, label: q.label || `Question ${i + 1}`, type: q.type, options: q.options || [] });
        }
        return result;
      }

      function getOperatorsForType(type) {
        const ops = [
          { value: "is_answered", label: "Is answered" },
          { value: "equals", label: "Equals" },
          { value: "not_equals", label: "Does not equal" },
        ];
        if (["text", "textarea", "email"].includes(type)) {
          ops.push({ value: "contains", label: "Contains" });
        }
        if (type === "number") {
          ops.push({ value: "greater_than", label: "Greater than" });
          ops.push({ value: "less_than", label: "Less than" });
        }
        if (type === "date") {
          ops.push({ value: "greater_than", label: "Is after" });
          ops.push({ value: "less_than", label: "Is before" });
        }
        return ops;
      }

      function buildConditionHtml(q, index) {
        const prev = getPreviousQuestions(index);
        const hasCondition = q.condition !== null;
        if (!hasCondition) return "";
        const condQid = q.condition.question_id;
        const condOp = q.condition.operator || "equals";
        const condVal = q.condition.value || "";
        const parentQ = prev.find(p => p.id === condQid);
        const parentHasOptions = parentQ && needsOptions(parentQ.type);
        const needsValue = condOp !== "is_answered";

        // Operator dropdown
        let operatorHtml;
        if (parentQ) {
          const ops = getOperatorsForType(parentQ.type);
          operatorHtml = `<select class="form-select form-select-sm" data-field="cond-operator">
            ${ops.map(o => `<option value="${o.value}" ${o.value === condOp ? "selected" : ""}>${escapeHtml(o.label)}</option>`).join("")}
          </select>`;
        } else {
          operatorHtml = `<select class="form-select form-select-sm" data-field="cond-operator" disabled>
            <option value="">--</option>
          </select>`;
        }

        // Value picker
        let valuePickerHtml = "";
        if (needsValue && parentQ) {
          if (parentHasOptions && parentQ.options.length > 0) {
            valuePickerHtml = `<select class="form-select form-select-sm" data-field="cond-value">
              <option value="">-- Select --</option>
              ${parentQ.options.map(o => `<option value="${escapeHtml(o)}" ${o === condVal ? "selected" : ""}>${escapeHtml(o)}</option>`).join("")}
            </select>`;
          } else if (parentHasOptions) {
            valuePickerHtml = `<select class="form-select form-select-sm" data-field="cond-value" disabled>
              <option value="">No options yet</option>
            </select>`;
          } else {
            const inputType = parentQ.type === "number" ? "number" : parentQ.type === "date" ? "date" : "text";
            valuePickerHtml = `<input class="form-control form-control-sm" type="${inputType}" data-field="cond-value" value="${escapeHtml(condVal)}" placeholder="Value">`;
          }
        } else if (needsValue) {
          valuePickerHtml = `<input class="form-control form-control-sm" type="text" data-field="cond-value" value="" disabled placeholder="Pick a question first">`;
        }

        return `
          <div class="col-12 mt-2">
            <div class="border rounded p-2 bg-light">
              <div class="row g-2">
                <div class="col-5">
                  <label class="form-label small">When question</label>
                  <select class="form-select form-select-sm" data-field="cond-question">
                    <option value="">-- Select --</option>
                    ${prev.map(p => `<option value="${escapeHtml(p.id)}" ${p.id === condQid ? "selected" : ""}>${escapeHtml(p.label)}</option>`).join("")}
                  </select>
                </div>
                <div class="col-3">
                  <label class="form-label small">Operator</label>
                  ${operatorHtml}
                </div>
                <div class="col-4 ${needsValue ? "" : "d-none"}">
                  <label class="form-label small">Value</label>
                  ${valuePickerHtml}
                </div>
              </div>
            </div>
          </div>`;
      }

      function renderQuestions() {
        questionsList.innerHTML = "";
        questions.forEach((q, index) => {
          const card = document.createElement("div");
          card.className = "question-card";
          card.dataset.index = index;
          const prev = getPreviousQuestions(index);
          const hasCondition = q.condition !== null;
          const condActionLabel = hasCondition ? "Remove condition" : "Add condition";
          const condActionValue = hasCondition ? "remove-condition" : "add-condition";
          const showCondDropdown = prev.length > 0 || hasCondition;
          card.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light">#${index + 1}</span>
                <span class="text-muted small drag-handle" draggable="true">Drag</span>
              </div>
              <div class="d-flex align-items-center gap-2">
                ${showCondDropdown ? `<div class="dropdown">
                  <button class="btn btn-outline-secondary btn-sm dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button class="dropdown-item" type="button" data-action="${condActionValue}">${condActionLabel}</button>
                    </li>
                  </ul>
                </div>` : ""}
                <button type="button" class="btn btn-outline-danger btn-sm" data-action="remove">Remove</button>
              </div>
            </div>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">Label</label>
                <input class="form-control" data-field="label" type="text" value="${escapeHtml(q.label)}" placeholder="E.g.: Full name">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Type</label>
                <select class="form-select" data-field="type">
                  ${types.map((t) => `<option value="${t.value}" ${t.value === q.type ? "selected" : ""}>${t.label}</option>`).join("")}
                </select>
              </div><div class="col-12 col-md-6">
                <label class="form-label d-block">Required</label>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" data-field="required" ${q.required ? "checked" : ""}>
                  <label class="form-check-label">This field is required</label>
                </div>
              </div>
              <div class="col-12 ${needsOptions(q.type) ? "" : "d-none"}" data-options>
                <label class="form-label">Options (one per line)</label>
                <textarea class="form-control" rows="3" data-field="options">${escapeHtml((q.options || []).join("\n"))}</textarea>
              </div>
              ${buildConditionHtml(q, index)}
            </div>
          `;
          questionsList.appendChild(card);
        });
      }

      function escapeHtml(value) {
        return (value || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
      }

      function updateSchemaFields() {
        const used = new Set();
        const schema = {
          questions: questions.map((q, index) => {
            const base = slugify(q.label || `question_${index + 1}`) || `question_${index + 1}`;
            let id = base;
            let counter = 2;
            while (used.has(id)) {
              id = `${base}_${counter}`;
              counter += 1;
            }
            used.add(id);
            const obj = {
              id,
              type: q.type || "text",
              label: q.label || `Question ${index + 1}`,
              required: Boolean(q.required),
            };
            if (needsOptions(obj.type)) {
              const options = Array.isArray(q.options) ? q.options : [];
              obj.options = options;
            }
            if (q.condition) {
              obj.condition = q.condition;
            }
            return obj;
          }),
        };

        const json = JSON.stringify(schema, null, 2);
        schemaField.value = json;
        schemaPreview.value = json;
      }

      function showAlert(message) {
        alertBox.textContent = message;
        alertBox.classList.remove("d-none");
      }

      function clearAlert() {
        alertBox.classList.add("d-none");
        alertBox.textContent = "";
      }

      questionsList.addEventListener("input", (event) => {
        const target = event.target;
        const card = target.closest(".question-card");
        if (!card) return;
        const index = Number(card.dataset.index);
        if (Number.isNaN(index)) return;

        const field = target.dataset.field;
        if (!field) return;

        if (field === "options") {
          questions[index].options = target.value
            .split("\n")
            .map((v) => v.trim())
            .filter(Boolean);
        } else if (field === "label") {
          questions[index].label = target.value;
        } else if (field === "cond-value") {
          if (questions[index].condition) {
            questions[index].condition.value = target.value;
          }
        }

        updateSchemaFields();
      });

      questionsList.addEventListener("change", (event) => {
        const target = event.target;
        const card = target.closest(".question-card");
        if (!card) return;
        const index = Number(card.dataset.index);
        if (Number.isNaN(index)) return;

        const field = target.dataset.field;
        if (!field) return;

        if (field === "type") {
          questions[index].type = target.value;
          if (!needsOptions(target.value)) {
            questions[index].options = [];
          }
          renderQuestions();
        }

        if (field === "required") {
          questions[index].required = target.checked;
        }

        if (field === "options") {
          renderQuestions();
        }

        if (field === "cond-question") {
          questions[index].condition = { question_id: target.value, operator: "equals", value: "" };
          renderQuestions();
        }

        if (field === "cond-operator") {
          if (questions[index].condition) {
            questions[index].condition.operator = target.value;
            if (target.value === "is_answered") {
              questions[index].condition.value = "";
            }
            renderQuestions();
          }
        }

        if (field === "cond-value") {
          if (questions[index].condition) {
            questions[index].condition.value = target.value;
          }
        }

        updateSchemaFields();
      });

      questionsList.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const action = target.dataset.action;
        if (!action) return;
        const card = target.closest(".question-card");
        if (!card) return;
        const index = Number(card.dataset.index);
        if (Number.isNaN(index)) return;

        if (action === "add-condition") {
          questions[index].condition = { question_id: "", operator: "equals", value: "" };
          renderQuestions();
          updateSchemaFields();
          return;
        }

        if (action === "remove-condition") {
          questions[index].condition = null;
          renderQuestions();
          updateSchemaFields();
          return;
        }

        if (action !== "remove") return;
        questions.splice(index, 1);
        if (questions.length === 0) {
          questions.push({
            type: "text",
            label: "",
            required: false,
            options: [],
            condition: null,
          });
        }
        renderQuestions();
        updateSchemaFields();
      });

      addQuestionBtn.addEventListener("click", () => {
        questions.push({
          type: "text",
          label: "",
          required: false,
          options: [],
          condition: null,
        });
        renderQuestions();
        updateSchemaFields();
      });

      let dragIndex = null;
      questionsList.addEventListener("dragstart", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (!target.classList.contains("drag-handle")) {
          event.preventDefault();
          return;
        }
        const card = target.closest(".question-card");
        if (!card) return;
        dragIndex = Number(card.dataset.index);
        card.classList.add("dragging");
      });

      questionsList.addEventListener("dragend", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const card = target.closest(".question-card");
        if (card) card.classList.remove("dragging");
        dragIndex = null;
      });

      questionsList.addEventListener("dragover", (event) => {
        event.preventDefault();
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const card = target.closest(".question-card");
        if (!card) return;
        card.classList.add("border-primary");
      });

      questionsList.addEventListener("dragleave", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const card = target.closest(".question-card");
        if (card) card.classList.remove("border-primary");
      });

      questionsList.addEventListener("drop", (event) => {
        event.preventDefault();
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const card = target.closest(".question-card");
        if (!card) return;
        card.classList.remove("border-primary");
        const dropIndex = Number(card.dataset.index);
        if (dragIndex === null || Number.isNaN(dropIndex) || dragIndex === dropIndex) return;
        const [moved] = questions.splice(dragIndex, 1);
        questions.splice(dropIndex, 0, moved);
        renderQuestions();
        updateSchemaFields();
      });

      form.addEventListener("submit", (event) => {
        clearAlert();
        const errors = [];
        questions.forEach((q, index) => {
          if (!q.label.trim()) {
            errors.push(`Question #${index + 1} has no label.`);
          }          if (needsOptions(q.type) && (!q.options || q.options.length === 0)) {
            errors.push(`Question #${index + 1} needs options.`);
          }
        });

        if (errors.length > 0) {
          event.preventDefault();
          showAlert(errors.join(" "));
          return;
        }

        updateSchemaFields();
      });

      renderQuestions();
      updateSchemaFields();
    </script>
  </body>
</html>
