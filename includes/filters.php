<?php
/** @var array $filters */
$action = $filterAction ?? url('reports.php');
?>
<form class="filter-bar" method="get" action="<?= e($action) ?>">
    <div class="filter-field">
        <label for="q">Search</label>
        <input id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Title, place, or reference">
    </div>
    <div class="filter-field">
        <label for="category">Category</label>
        <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach (CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $filters['category'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (STATUSES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-field">
        <label for="severity">Severity</label>
        <select id="severity" name="severity">
            <option value="">All severities</option>
            <?php foreach (SEVERITIES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $filters['severity'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Filter</button>
        <a class="btn btn-ghost" href="<?= e($action) ?>">Reset</a>
    </div>
</form>
