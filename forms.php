<?php
declare(strict_types=1);

require_once __DIR__ . "/db.php";

const QUESTION_TYPES = [
  "text",
  "textarea",
  "email",
  "number",
  "select",
  "radio",
  "checkbox",
  "date",
];

function e(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function decode_json(string $json): ?array {
  $data = json_decode($json, true);
  return is_array($data) ? $data : null;
}

function validate_schema(?array $schema): array {
  $errors = [];
  if ($schema === null) {
    return ["The schema is not valid JSON."];
  }

  if (!isset($schema["questions"]) || !is_array($schema["questions"])) {
    return ["The schema must include a 'questions' array."];
  }

  $ids = [];
  foreach ($schema["questions"] as $index => $q) {
    if (!is_array($q)) {
      $errors[] = "Question #" . ($index + 1) . " must be an object.";
      continue;
    }

    $id = $q["id"] ?? "";
    $type = $q["type"] ?? "";
    $label = $q["label"] ?? "";

    if (!is_string($id) || trim($id) === "") {
      $errors[] = "Question #" . ($index + 1) . " requires a valid 'id'.";
    } else {
      if (isset($ids[$id])) {
        $errors[] = "The id '" . $id . "' is duplicated.";
      }
      $ids[$id] = true;
    }

    if (!is_string($type) || !in_array($type, QUESTION_TYPES, true)) {
      $errors[] = "Question '" . $id . "' has an invalid type.";
    }

    if (!is_string($label) || trim($label) === "") {
      $errors[] = "Question '" . $id . "' requires a 'label'.";
    }

    if (in_array($type, ["select", "radio", "checkbox"], true)) {
      $options = $q["options"] ?? null;
      if (!is_array($options) || $options === []) {
        $errors[] = "Question '" . $id . "' requires 'options'.";
      }
    }

    if (isset($q["condition"])) {
      $cond = $q["condition"];
      if (!is_array($cond)) {
        $errors[] = "Question '" . $id . "': condition must be an object.";
      } else {
        $cqid = $cond["question_id"] ?? "";
        $cop = $cond["operator"] ?? "equals";
        $cval = $cond["value"] ?? "";
        if (!is_string($cqid) || trim($cqid) === "") {
          $errors[] = "Question '" . $id . "': condition requires a valid 'question_id'.";
        } elseif (!isset($ids[$cqid])) {
          $errors[] = "Question '" . $id . "': condition references unknown or later question '" . $cqid . "'.";
        }
        $valid_ops = ["equals", "not_equals", "contains", "greater_than", "less_than", "is_answered"];
        if (!is_string($cop) || !in_array($cop, $valid_ops, true)) {
          $errors[] = "Question '" . $id . "': condition has an invalid operator.";
        }
        if ($cop !== "is_answered" && (!is_string($cval) || $cval === "")) {
          $errors[] = "Question '" . $id . "': condition requires a non-empty 'value'.";
        }
      }
    }
  }

  return $errors;
}

function is_condition_met(array $condition, array $input, array $schema): bool {
  $target_id = $condition["question_id"];
  $operator = $condition["operator"] ?? "equals";
  $expected = $condition["value"] ?? "";
  $answer = $input[$target_id] ?? null;

  // Backward compat: old __answered__ sentinel
  if ($operator === "equals" && $expected === "__answered__") {
    $operator = "is_answered";
  }

  // Find the target question's type
  $target_type = null;
  foreach ($schema["questions"] as $q) {
    if ($q["id"] === $target_id) {
      $target_type = $q["type"];
      break;
    }
  }

  $is_checkbox = $target_type === "checkbox";
  $checkbox_values = $is_checkbox ? (is_array($answer) ? $answer : []) : [];

  switch ($operator) {
    case "is_answered":
      if ($is_checkbox) {
        return $checkbox_values !== [];
      }
      return $answer !== null && $answer !== "" && $answer !== [];

    case "equals":
      if ($is_checkbox) {
        return in_array($expected, $checkbox_values, true);
      }
      return (string)$answer === $expected;

    case "not_equals":
      if ($is_checkbox) {
        return !in_array($expected, $checkbox_values, true);
      }
      return (string)$answer !== $expected;

    case "contains":
      return is_string($answer) && str_contains($answer, $expected);

    case "greater_than":
      if ($target_type === "number") {
        return is_numeric($answer) && is_numeric($expected) && (float)$answer > (float)$expected;
      }
      return (string)$answer > $expected;

    case "less_than":
      if ($target_type === "number") {
        return is_numeric($answer) && is_numeric($expected) && (float)$answer < (float)$expected;
      }
      return (string)$answer < $expected;

    default:
      return false;
  }
}

function validate_responses(array $schema, array $input): array {
  $errors = [];

  foreach ($schema["questions"] as $q) {
    $id = $q["id"];
    $type = $q["type"];
    $required = !empty($q["required"]);
    $value = $input[$id] ?? null;

    if (isset($q["condition"]) && !is_condition_met($q["condition"], $input, $schema)) {
      continue;
    }

    if ($required) {
      $empty = $value === null || $value === "" || $value === [];
      if ($empty) {
        $errors[$id] = "This field is required.";
        continue;
      }
    }

    if ($value === null || $value === "" || $value === []) {
      continue;
    }

    if ($type === "email" && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
      $errors[$id] = "Invalid email.";
      continue;
    }

    if ($type === "number" && !is_numeric($value)) {
      $errors[$id] = "Invalid number.";
      continue;
    }

    if ($type === "date") {
      $dt = DateTime::createFromFormat("Y-m-d", (string)$value);
      if (!$dt || $dt->format("Y-m-d") !== $value) {
        $errors[$id] = "Invalid date.";
        continue;
      }
    }

    if (in_array($type, ["select", "radio"], true)) {
      $options = $q["options"] ?? [];
      if (!in_array($value, $options, true)) {
        $errors[$id] = "Invalid option.";
        continue;
      }
    }

    if ($type === "checkbox") {
      $options = $q["options"] ?? [];
      $values = is_array($value) ? $value : [$value];
      foreach ($values as $v) {
        if (!in_array($v, $options, true)) {
          $errors[$id] = "Invalid option.";
          break;
        }
      }
    }
  }

  return $errors;
}
