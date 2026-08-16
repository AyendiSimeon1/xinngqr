<?php
declare(strict_types=1);

$capabilities = [
    ['01', 'Data System Design', 'I design complete data pipelines from collection to reporting, including source mapping, data modeling, validation rules, dashboards, and decision workflows.'],
    ['02', 'Multi-Source Integration', 'I integrate APIs, CSV uploads, direct input forms, RSS feeds, raw PDFs, and databases into structured, reliable systems.'],
    ['03', 'BI & Reporting Architecture', 'I build Power BI and web dashboards that transform complex datasets into clear operational and strategic insights.'],
    ['04', 'Verification & Intelligence Tools', 'I develop systems for certificate verification, trend monitoring, incident intelligence, and trusted reporting.'],
];

$process = [
    ['Source Mapping', 'Identify APIs, forms, CSVs, RSS feeds, PDFs, and databases.'],
    ['Data Capture', 'Design ingestion flows, upload systems, and API connections.'],
    ['Validation', 'Clean, verify, deduplicate, and standardize data.'],
    ['Storage & Modeling', 'Build databases, reporting tables, and structured models.'],
    ['Intelligence Layer', 'Develop dashboards, maps, reports, and analytical tools.'],
    ['Automation', 'Implement refresh cycles, OCR, verification, and reporting automation.'],
];

$stack = [
    'Data platforms' => ['Microsoft Fabric', 'Power BI', 'Power Apps', 'Azure'],
    'Data sources' => ['APIs', 'Kobo Toolbox', 'CSV uploads', 'Forms', 'RSS feeds', 'PDFs', 'SQL databases'],
    'Languages & tools' => ['SQL', 'Python', 'PHP', 'HTML5', 'DAX', 'Power Query'],
    'Mapping & visualisation' => ['Mapbox', 'Power BI maps', 'Dashboards', 'Interactive reports'],
    'Data engineering' => ['ETL workflows', 'Automation', 'Validation', 'OCR extraction', 'API ingestion', 'CSV processing', 'RSS ingestion'],
    'System features' => ['QR verification', 'Certificate generation', 'Trend monitoring', 'Failure detection', 'Internal tools'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Aquila Kalagbor designs data systems for intelligence, verification, and decision-making.">
  <meta name="theme-color" content="#071d3b">
  <title>Aquila Kalagbor — Data Design Engineer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css">
  <link rel="stylesheet" href="assets/aquila.css?v=<?= filemtime(__DIR__ . '/assets/aquila.css') ?>">
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="profile-header">
    <div class="profile-cover" role="img" aria-label="Abstract data systems banner">
      <div class="cover-grid"></div>
      <div class="cover-copy"><span>DATA SYSTEMS</span><strong>Collect. Structure. Verify. Decide.</strong><small>Microsoft Fabric · Power BI · Azure · SQL · Python</small></div>
      <div class="cover-mark" aria-hidden="true">AK</div>
    </div>
  </header>

  <main id="main" class="page-body">
    <section class="profile-hero" aria-labelledby="profile-name">
      <div class="profile-card">
        <img class="profile-avatar" src="assets/img/Aquila-headshot-white-bg.png" alt="Aquila Kalagbor headshot">

        <div class="profile-copy">
          <h1 id="profile-name">Aquila Kalagbor <i class="hgi-stroke hgi-shield-01 verified" aria-label="Verified profile"></i></h1>
          <p class="handle"><i class="hgi-stroke hgi-cursor-02" aria-hidden="true"></i> @ichecodes</p>
          <p class="profile-title">Data Design Engineer</p>
          <p class="profile-summary">I design data systems that collect, structure, verify, analyse, and present complex information across civic, safety, and industrial environments.</p>

          <div class="profile-row" aria-label="Specialties"><span class="row-icon"><i class="hgi-stroke hgi-luggage-01" aria-hidden="true"></i></span><div class="profile-chips"><span>Data System Design</span><span>BI Architecture</span><span>Verification Tools</span></div></div>
          <div class="profile-row" aria-label="Location"><span class="row-icon"><i class="hgi-stroke hgi-location-01" aria-hidden="true"></i></span><div class="profile-chips"><span>Nigeria</span><span>Available remotely</span></div></div>
          <div class="profile-row" aria-label="Website"><span class="row-icon"><i class="hgi-stroke hgi-globe-02" aria-hidden="true"></i></span><a class="profile-chip" href="https://github.com/ichecodes" target="_blank" rel="noopener">github.com/ichecodes</a></div>

          <div class="profile-socials" aria-label="Social links">
            <a href="https://www.linkedin.com/in/aquila-kalagbor-b143a350/" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="hgi-stroke hgi-linkedin-02" aria-hidden="true"></i></a>
            <a href="https://github.com/ichecodes" target="_blank" rel="noopener" aria-label="GitHub"><i class="hgi-stroke hgi-github" aria-hidden="true"></i></a>
            <a href="mailto:aquilakalagbor@outlook.com" aria-label="Email Aquila"><i class="hgi-stroke hgi-mail-01" aria-hidden="true"></i></a>
          </div>
        </div>

        <div class="profile-actions" aria-label="Contact actions">
          <div class="profile-action-icons">
            <a href="mailto:aquilakalagbor@outlook.com" aria-label="Email Aquila"><i class="hgi-stroke hgi-mail-01" aria-hidden="true"></i></a>
            <a href="https://www.linkedin.com/in/aquila-kalagbor-b143a350/" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="hgi-stroke hgi-linkedin-02" aria-hidden="true"></i></a>
            <a href="https://github.com/ichecodes" target="_blank" rel="noopener" aria-label="GitHub"><i class="hgi-stroke hgi-github" aria-hidden="true"></i></a>
          </div>
          <a class="profile-primary-action" href="#work">View Technical Portfolio <i class="hgi-stroke hgi-arrow-right-02" aria-hidden="true"></i></a>
        </div>
      </div>

      <aside class="profile-strip" aria-label="Portfolio overview">
        <div class="strip-info"><span class="strip-icon"><i class="hgi-stroke hgi-chart-analysis" aria-hidden="true"></i></span><div><strong>Designing data systems for intelligence</strong><p>Verification, reporting, automation, and decision-making</p></div></div>
        <a class="strip-cta" href="mailto:aquilakalagbor@outlook.com">Start a project <i class="hgi-stroke hgi-arrow-right-02" aria-hidden="true"></i></a>
        <div class="strip-stats"><div><strong>300k+</strong><span>DATA POINTS</span></div><div><strong>30k+</strong><span>USERS</span></div><div><strong>7</strong><span>POLLS</span></div></div>
      </aside>
    </section>

    <section class="section capabilities">
      <div class="section-heading"><div><p class="kicker">01 / CAPABILITIES</p><h2>From raw sources to<br><em>trusted decisions.</em></h2></div><p>I work across the full data lifecycle—building dependable systems that turn fragmented information into intelligence people can act on.</p></div>
      <div class="cap-grid">
        <?php foreach ($capabilities as [$number, $title, $text]): ?><article class="cap-card"><span><?= $number ?></span><div class="cap-icon" aria-hidden="true">⌗</div><h3><?= htmlspecialchars($title) ?></h3><p><?= htmlspecialchars($text) ?></p></article><?php endforeach; ?>
      </div>
    </section>

    <section class="section projects" id="work">
      <div class="section-heading"><div><p class="kicker">02 / SELECTED SYSTEMS</p><h2>Work designed for<br><em>real-world complexity.</em></h2></div><p>Case studies across civic data, safety intelligence, and industrial verification.</p></div>

      <article class="project project-featured">
        <div class="project-copy"><p class="project-index">CASE STUDY 01 · CIVIC INTELLIGENCE</p><h3>ARVO</h3><h4>Presidential Election Data Platform — Edo State, 2023</h4><p>Designed and supported a civic election data platform processing over 300,000 data points across 7 polls and serving more than 30,000 users.</p><p>The platform unified IREV API data, Kobo Toolbox submissions, and bulk CSV uploads through Microsoft Fabric, Power BI, and interactive Mapbox maps.</p><div class="tags"><span>Microsoft Fabric</span><span>Power BI</span><span>Power Apps</span><span>Mapbox</span><span>Kobo Toolbox</span><span>APIs</span></div></div>
        <div class="project-visual arvo-visual" aria-label="ARVO election data dashboard gallery">
          <div class="arvo-gallery">
            <figure><img src="assets/img/arvo-2.jpg" alt="ARVO Edo State election dashboard showing result trends, polling units captured, party totals, and an interactive map"><figcaption>Results intelligence · Trends and polling coverage</figcaption></figure>
            <figure><img src="assets/img/arvo-3.jpg" alt="ARVO Edo State election dashboard showing local government results, interactive map, and voter turnout"><figcaption>Geographic intelligence · LGA results and turnout</figcaption></figure>
          </div>
        </div>
        <div class="project-functions"><strong>CORE FUNCTIONS</strong><span>Election data collection</span><span>Multi-source ingestion</span><span>Automated refresh workflows</span><span>Poll-level reporting</span><span>Interactive mapping</span><span>Public reporting interface</span></div>
      </article>

      <div class="project-grid">
        <article class="project compact"><div class="project-copy"><p class="project-index">CASE STUDY 02 · SAFETY INTELLIGENCE</p><h3>Safer</h3><h4>Violent Incident Database & Intelligence Platform</h4><p>A safety intelligence platform that captures incident data through direct input and RSS feeds, then transforms it into structured, searchable dashboards and actionable insights.</p><div class="tags"><span>RSS feeds</span><span>SQL</span><span>Power BI</span><span>Web dashboards</span></div></div></article>
        <article class="project compact"><div class="project-copy"><p class="project-index">CASE STUDY 03 · INDUSTRIAL DATA</p><h3>Pressure Certificate Tool</h3><h4>Industrial Dashboard & Verification System</h4><p>A confidential system for certificate creation, QR verification, AI OCR extraction, and pressure trend monitoring to detect potential equipment failure.</p><p class="notice">Visual details are withheld. This case study focuses on architecture, workflows, and technical capabilities.</p><div class="tags"><span>AI OCR</span><span>Python</span><span>Azure</span><span>Power BI</span><span>PHP</span><span>Microsoft Fabric</span></div></div></article>
      </div>
    </section>

    <section class="section process-section" id="process">
      <div class="section-heading"><div><p class="kicker">03 / METHODOLOGY</p><h2>How I design<br><em>data systems.</em></h2></div><p>A structured path from fragmented inputs to automated intelligence.</p></div>
      <ol class="process"><?php foreach ($process as $i => [$title, $text]): ?><li><span>0<?= $i + 1 ?></span><div><h3><?= htmlspecialchars($title) ?></h3><p><?= htmlspecialchars($text) ?></p></div></li><?php endforeach; ?></ol>
    </section>

    <section class="section stack-section" id="stack">
      <div class="section-heading"><div><p class="kicker">04 / TECHNICAL STACK</p><h2>Tools selected for<br><em>the system.</em></h2></div><p>Platforms, languages, and methods used to build robust end-to-end data products.</p></div>
      <div class="stack-grid"><?php foreach ($stack as $group => $items): ?><div class="stack-group"><h3><?= htmlspecialchars($group) ?></h3><div><?php foreach ($items as $item): ?><span><?= htmlspecialchars($item) ?></span><?php endforeach; ?></div></div><?php endforeach; ?></div>
    </section>

    <section class="contact" id="contact"><p class="kicker">LET'S WORK TOGETHER</p><h2>Let’s build better<br><em>data systems.</em></h2><p>Available for data system design, BI dashboards, reporting automation, data architecture, and intelligence platforms.</p><div class="contact-emails"><a href="mailto:aquilakalagbor@outlook.com">aquilakalagbor@outlook.com</a><a href="mailto:ichecode@gmail.com">ichecode@gmail.com</a></div><div class="button-row"><a class="button light" href="mailto:aquilakalagbor@outlook.com">Email me ↗</a><a class="button outline-light" href="https://github.com/ichecodes" target="_blank" rel="noopener">GitHub ↗</a><a class="button outline-light" href="https://www.linkedin.com/in/aquila-kalagbor-b143a350/" target="_blank" rel="noopener">LinkedIn ↗</a><a class="button outline-light" href="https://drive.google.com/file/d/1ISrzgXrj38sHJR4_cK0gI7B5VLvdrxc8/view?usp=sharing" target="_blank" rel="noopener">Download CV ↓</a></div></section>
  </main>
  <footer><div class="brand"><span>AK</span><strong>Aquila Kalagbor</strong></div><p>Data Design Engineer · Nigeria</p><p>© <?= date('Y') ?> Aquila Kalagbor</p></footer>
</body>
</html>
