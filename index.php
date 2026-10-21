<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Home';
$pageDescription = 'Report accessibility barriers in public places and follow their review.';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="shell hero-grid">
        <div>
            <p class="eyebrow">Public accessibility mapping</p>
            <h1>Name the barrier. Pin the place. Keep the record visible.</h1>
            <p class="lede">BarrierFree is a local mapper for ramps, lifts, toilets, sidewalks, and other access failures in public places. Submissions are stored with a status trail so they can be reviewed instead of disappearing into a mailbox.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= e(url('report.php')) ?>">Report a barrier</a>
                <a class="btn btn-secondary" href="<?= e(url('reports.php')) ?>">Browse submitted reports</a>
            </div>
        </div>
        <aside class="hero-panel">
            <h2>What a useful report includes</h2>
            <ol>
                <li>A clear title and the kind of barrier</li>
                <li>Where it is, with a map pin</li>
                <li>What a person actually encounters there</li>
                <li>A photo if you can take one safely</li>
            </ol>
        </aside>
    </div>
</section>
<section class="section">
    <div class="shell">
        <h2>Barriers people can document</h2>
        <div class="card-grid">
            <article class="card"><h3>Getting in</h3><p>Missing ramps, broken lifts, steps without an alternative, and blocked wheelchair routes.</p></article>
            <article class="card"><h3>Moving through</h3><p>Broken sidewalks, obstacles on accessible paths, and crossings that cannot be used safely.</p></article>
            <article class="card"><h3>Using the place</h3><p>Inaccessible toilets, missing visual cues, and barriers that affect hearing or wayfinding.</p></article>
        </div>
    </div>
</section>
<section class="section">
    <div class="shell split">
        <div class="panel">
            <h2>How review works</h2>
            <p>New reports start as pending. Administrators can move them through review, verification, in-progress work, resolution, or rejection. Each change is stored in the report history.</p>
        </div>
        <div class="note">
            This landing page does not invent city-wide statistics. Open the report list to see what has actually been submitted on this installation.
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
