<?php
declare(strict_types=1);

// Welcome page for the dynamic forms system.
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forms | Welcome</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
      crossorigin="anonymous"
    >
  </head>
  <body>
    <div class="min-vh-100 d-flex align-items-center bg-light">
      <div class="container py-5">
        <div class="row justify-content-center">
          <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
              <div class="card-body p-4 p-lg-5">
        <h1>Welcome to Forms</h1>
        <p>
          Build custom forms, share them with anyone, and collect responses — no coding required.
        </p>

        <h5>How it works</h5>
        <ol>
          <li><strong>Create</strong> — Add questions, choose field types, set required fields, and drag to reorder. Save as a draft anytime.</li>
          <li><strong>Publish</strong> — When your form is ready, publish it. This locks in the structure so respondents all see the same version.</li>
          <li><strong>Share</strong> — Send the public link to your respondents. No login needed to fill it out.</li>
          <li><strong>Review</strong> — View all submissions on the responses page. Search, filter, and inspect individual answers.</li>
        </ol>

        <h5>Available field types</h5>
        <p>Text, textarea, email, number, date, dropdown, radio buttons, and checkboxes. You can also add conditional logic to show or hide questions based on previous answers.</p>

        <p class="mt-4">
          <a href="instructions.php">Read the full instructions</a> for details on field options, conditional logic, validation, and more.
        </p>

        <div class="mt-4">
          <a class="btn btn-primary" href="create.php">Create a form</a>
          <a class="btn btn-outline-secondary ms-2" href="list.php">View forms</a>
        </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
