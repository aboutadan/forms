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
          This project implements dynamic forms (Google Forms style) using PHP 8+ and MySQL 8.
          Everything is built on just two tables and JSON structures.
        </p>
        <p>Suggested steps:</p>
        <ul>
          <li>Create the tables in the <code>forms</code> database.</li>
          <li>Use the endpoints below to create, publish, and submit forms.</li>
          <li>Public forms are always rendered from <code>published_schema</code>.</li>
        </ul>
        <p>Useful links:</p>
        <ul class="mb-4">
          <li><a href="create.php">Create form</a> — create a new draft</li>
          <li><a href="list.php">List forms</a> — view, edit, publish, and check responses</li>
          <li><a href="instructions.php">Instructions</a> — project documentation</li>
        </ul>
        <a class="btn btn-primary" href="create.php">Create form</a>
        <a class="btn btn-outline-secondary ms-2" href="list.php">View forms</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
