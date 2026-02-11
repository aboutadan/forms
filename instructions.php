<?php
declare(strict_types=1);

$file = __DIR__ . '/instructions.md';
if (!file_exists($file)) {
    http_response_code(404);
    echo 'instructions.md not found';
    exit;
}

$content = file_get_contents($file);
$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

// Basic markdown rendering: headings, bold, code blocks, lists, paragraphs
$lines = explode("\n", $content);
$html = '';
$inList = false;
$inCode = false;

foreach ($lines as $line) {
    // Fenced code blocks
    if (preg_match('/^```/', $line)) {
        if ($inCode) {
            $html .= "</code></pre>\n";
            $inCode = false;
        } else {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $html .= "<pre><code>";
            $inCode = true;
        }
        continue;
    }
    if ($inCode) {
        $html .= $line . "\n";
        continue;
    }

    // Close list if line is not a list item
    if ($inList && !preg_match('/^\s*[-*\d]+[.)]\s/', $line)) {
        $html .= "</ul>\n";
        $inList = false;
    }

    // Blank line
    if (trim($line) === '') {
        continue;
    }

    // Inline formatting
    $line = preg_replace('/`([^`]+)`/', '<code>$1</code>', $line);
    $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);

    // Headings (ATX style)
    if (preg_match('/^(#{1,6})\s+(.*)/', $line, $m)) {
        $level = strlen($m[1]);
        $html .= "<h{$level}>{$m[2]}</h{$level}>\n";
        continue;
    }

    // ALL-CAPS lines as headings
    if (preg_match('/^[A-Z\s()]+$/', trim($line)) && strlen(trim($line)) > 3) {
        $html .= "<h4>{$line}</h4>\n";
        continue;
    }

    // List items (-, *, or numbered)
    if (preg_match('/^\s*[-*]\s+(.*)/', $line, $m)) {
        if (!$inList) { $html .= "<ul class=\"mb-3\">\n"; $inList = true; }
        $html .= "  <li>{$m[1]}</li>\n";
        continue;
    }
    if (preg_match('/^\s*\d+[.)]\s+(.*)/', $line, $m)) {
        if (!$inList) { $html .= "<ul class=\"mb-3\">\n"; $inList = true; }
        $html .= "  <li>{$m[1]}</li>\n";
        continue;
    }

    // Regular paragraph line
    $html .= "<p>{$line}</p>\n";
}

if ($inList) { $html .= "</ul>\n"; }
if ($inCode) { $html .= "</code></pre>\n"; }
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forms | Instructions</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
      crossorigin="anonymous"
    >
  </head>
  <body>
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
          <nav class="mb-4">
            <a href="index.php">&larr; Home</a>
          </nav>
          <?php echo $html; ?>
        </div>
      </div>
    </div>
  </body>
</html>
