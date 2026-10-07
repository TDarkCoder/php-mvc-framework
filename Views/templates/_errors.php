<?php
/** @var \TDarkCoder\Framework\Exceptions\HttpException $exception */
$previous = config('debug') ? $exception->getPrevious() : null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $exception->getStatusCode() ?> - <?= htmlspecialchars($exception->getMessage()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">
</head>
<body>
<div class="d-flex flex-column bg-primary top-0 text-white w-100 vh-100 justify-content-center align-items-center">
    <p class="m-0 lead"><?= $exception->getStatusCode() ?> | <?= htmlspecialchars($exception->getMessage()) ?></p>
    <?php if ($previous): ?>
        <pre class="mt-4 p-3 bg-dark text-light rounded small text-start" style="max-width: 90vw; overflow: auto;"><?= htmlspecialchars(get_class($previous) . ': ' . $previous->getMessage() . PHP_EOL . PHP_EOL . $previous->getTraceAsString()) ?></pre>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous">
</script>
</body>
</html>
