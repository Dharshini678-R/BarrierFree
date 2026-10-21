<?php
/** @var int $page */
/** @var int $pages */
/** @var array $filters */
if ($pages <= 1) return;
$base = $paginationBase ?? url('reports.php');
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a href="<?= e($base . query_string($filters, ['page' => $page - 1])) ?>">Previous</a>
    <?php endif; ?>
    <span>Page <?= (int)$page ?> of <?= (int)$pages ?></span>
    <?php if ($page < $pages): ?>
        <a href="<?= e($base . query_string($filters, ['page' => $page + 1])) ?>">Next</a>
    <?php endif; ?>
</nav>
