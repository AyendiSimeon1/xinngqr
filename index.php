<?php
require_once __DIR__ . '/config.php';
session_start();
$isSignedIn = !empty($_SESSION['user_id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($APP_NAME) ?> — Smart links, QR codes, and creator pages</title>
  <meta name="description" content="Create dynamic QR codes, branded short links, and polished creator pages with Xinng. Perfect for personal brands, creators, freelancers, and professionals.">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="landing-page">
  <div class="landing-bg-glow" aria-hidden="true"></div>

  <header class="landing-nav">
    <div class="landing-nav__inner">
      <a class="landing-brand" href="index.php">
        <span class="landing-brand__mark">X</span>
        <span class="landing-brand__word">xin.ng</span>
      </a>
      <nav class="landing-nav__links" aria-label="Primary navigation">
        <a href="#features">Solutions</a>
        <a href="#benefits">Use Cases</a>
        <a href="#learn">Learn</a>
        <a href="pricing.php">Pricing</a>
        <?php if ($isSignedIn): ?>
          <a class="landing-btn landing-btn--primary" href="dashboard.php">Dashboard</a>
        <?php else: ?>
          <a href="signin.php">Login</a>
          <a class="landing-btn landing-btn--primary" href="signup.php">Sign up</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="landing-shell">
    <section class="landing-hero">
      <div class="landing-hero__copy">
        <p class="landing-eyebrow">No login required</p>
        <h1>Create smart QR codes in seconds.</h1>
        <p class="landing-subtext">Generate, customize, and download QR codes instantly — no login required.</p>

        <ul class="landing-feature-list" aria-label="Key product benefits">
          <li>Create QR codes instantly</li>
          <li>Customize design and destination</li>
          <li>Track scans when you sign up</li>
          <li>Upgrade anytime for analytics</li>
        </ul>

        <div class="landing-actions">
          <a class="landing-btn landing-btn--primary" href="q.php">Create QR Code</a>
          <a class="landing-btn landing-btn--secondary" href="#features">Explore Features</a>
        </div>
      </div>

      <aside class="landing-widget" id="how-it-works">
        <div class="landing-widget__header">
          <div class="landing-widget__icon">QR</div>
          <div>
            <h2>Live QR generator</h2>
            <p>Interactive preview</p>
          </div>
        </div>
        <div class="landing-widget__divider"></div>
        <form class="landing-form" id="qrForm" method="post" action="create_qr.php">
          <label class="landing-form__label" for="destination">Destination URL</label>
          <input id="destination" name="destination" class="landing-form__input" type="url" value="https://" placeholder="https://example.com" />

          <label class="landing-form__label" for="qrType">QR Type</label>
          <select id="qrType" name="qr_type" class="landing-form__select">
            <option>Website</option>
            <option>Product</option>
            <option>Campaign</option>
          </select>

          <label class="landing-form__label" for="style">Optional customization</label>
          <select id="style" name="style" class="landing-form__select">
            <option>Standard</option>
            <option>Bold</option>
            <option>Minimal</option>
          </select>

          <button class="landing-btn landing-btn--primary landing-btn--full" type="submit">Create QR Code</button>
        </form>

        <div class="landing-preview" aria-live="polite">
          <p class="landing-preview__eyebrow">Live preview</p>
          <div class="landing-qr-preview">
            <div class="landing-qr-preview__box"></div>
            <div class="landing-qr-preview__text">
              <strong id="previewTitle">Ready to generate</strong>
              <p id="previewCopy">Your QR code updates as you type.</p>
            </div>
          </div>
        </div>
      </aside>
    </section>

    <section class="landing-section landing-section--soft" aria-label="Trusted by companies and creators">
      <div class="landing-section__heading landing-section__heading--center">
        <p class="landing-eyebrow">Trusted by companies and creators worldwide</p>
        <h2>Designed for modern brands, campaigns, and everyday creators.</h2>
      </div>
      <div class="landing-trust-badges" aria-label="Company categories">
        <span>Startups</span>
        <span>SMEs</span>
        <span>Agencies</span>
        <span>Restaurants</span>
        <span>Events</span>
        <span>Schools</span>
        <span>Creators</span>
      </div>
    </section>

    <section class="landing-section" id="features">
      <div class="landing-section__heading">
        <p class="landing-eyebrow">Everything you need in one platform</p>
        <h2>All the tools to create, share, and track your next campaign.</h2>
      </div>
      <div class="landing-feature-grid landing-feature-grid--expanded">
        <article class="landing-card">
          <h3>QR Codes</h3>
          <p>Create dynamic and static QR codes with full customization.</p>
        </article>
        <article class="landing-card">
          <h3>Short Links</h3>
          <p>Create branded links like xin.ng/sheltercon for memorable, shareable campaigns.</p>
        </article>
        <article class="landing-card">
          <h3>Pages</h3>
          <p>Build mobile-first landing pages for creators, businesses, and campaigns.</p>
        </article>
        <article class="landing-card">
          <h3>Analytics</h3>
          <p>Track scans, clicks, and engagement with clear campaign insights.</p>
        </article>
        <article class="landing-card">
          <h3>Custom Branding</h3>
          <p>Add logos, colors, and styles to QR codes and pages to match your brand.</p>
        </article>
        <article class="landing-card">
          <h3>Campaign Ready</h3>
          <p>Launch offers, menus, registration links, and product pages in just a few clicks.</p>
        </article>
      </div>
    </section>

    <section class="landing-section landing-section--soft" id="benefits">
      <div class="landing-section__heading">
        <p class="landing-eyebrow">Why people use Xinng</p>
        <h2>Everything you need to connect offline attention to online action.</h2>
      </div>
      <div class="landing-benefits-grid">
        <article class="landing-benefit">
          <h3>Update QR destinations anytime</h3>
          <p>Keep your QR codes useful even after print, signage, or campaign launch.</p>
        </article>
        <article class="landing-benefit">
          <h3>Track real engagement</h3>
          <p>Measure what is working across every campaign, code, and share.</p>
        </article>
        <article class="landing-benefit">
          <h3>Build branded experiences</h3>
          <p>Deliver polished pages and memorable links that feel premium and consistent.</p>
        </article>
        <article class="landing-benefit">
          <h3>Connect offline to online instantly</h3>
          <p>Drive traffic from menus, posters, packaging, events, and in-person interactions.</p>
        </article>
        <article class="landing-benefit">
          <h3>Manage it in one dashboard</h3>
          <p>Run QR codes, short links, and pages from a single place without friction.</p>
        </article>
        <article class="landing-benefit">
          <h3>Start without an account</h3>
          <p>Try the product with zero setup and upgrade when you need deeper tracking.</p>
        </article>
      </div>
    </section>

    <section class="landing-section landing-section--alt" id="stories">
      <div class="landing-section__heading">
        <p class="landing-eyebrow">What users are saying</p>
        <h2>Teams and creators use Xinng to launch faster and look more polished.</h2>
      </div>
      <div class="landing-testimonials-grid">
        <article class="landing-testimonial">
          <p>“We replaced printed menus with Xinng QR codes instantly.”</p>
          <strong>— Restaurant owner</strong>
        </article>
        <article class="landing-testimonial">
          <p>“Our campaign tracking improved massively once we started using Xinng.”</p>
          <strong>— Marketing lead</strong>
        </article>
        <article class="landing-testimonial">
          <p>“I created my portfolio page in minutes and shared it across every channel.”</p>
          <strong>— Independent creator</strong>
        </article>
        <article class="landing-testimonial">
          <p>“Best QR tool for marketing teams that want a clean workflow and quick launch time.”</p>
          <strong>— Agency partner</strong>
        </article>
      </div>
    </section>

    <section class="landing-section landing-section--cta" id="learn">
      <div class="landing-cta">
        <div>
          <p class="landing-eyebrow">Start creating smarter QR codes today</p>
          <h2>No login required to get started.</h2>
        </div>
        <div class="landing-cta__actions">
          <a class="landing-btn landing-btn--primary" href="q.php">Create QR Code</a>
          <a class="landing-btn landing-btn--secondary" href="signup.php">Sign Up for Full Features</a>
        </div>
      </div>
    </section>
  </main>

  <footer class="landing-footer" id="contact">
    <div class="landing-footer__grid">
      <div class="landing-footer__brand">
        <a class="landing-brand" href="index.php">
          <span class="landing-brand__mark">X</span>
          <span class="landing-brand__word">xin.ng</span>
        </a>
        <p>Smart QR codes, short links, and branded pages for modern creators and businesses.</p>
      </div>
      <div>
        <h3>Product</h3>
        <ul class="landing-footer__list">
          <li><a href="q.php">QR Codes</a></li>
          <li><a href="pricing.php">Short Links</a></li>
          <li><a href="pages.php">Pages</a></li>
          <li><a href="pricing.php">Analytics</a></li>
        </ul>
      </div>
      <div>
        <h3>Company</h3>
        <ul class="landing-footer__list">
          <li><a href="#contact">About</a></li>
          <li><a href="#contact">Contact</a></li>
          <li><a href="pricing.php">Pricing</a></li>
          <li><a href="#">Terms</a></li>
          <li><a href="#">Privacy</a></li>
        </ul>
      </div>
      <div>
        <h3>Learn</h3>
        <ul class="landing-footer__list">
          <li><a href="#">Blog</a></li>
          <li><a href="#">Guides</a></li>
          <li><a href="#">QR Tips</a></li>
          <li><a href="#">Marketing Ideas</a></li>
        </ul>
      </div>
      <div>
        <h3>Social</h3>
        <ul class="landing-footer__list">
          <li><a href="#">Twitter/X</a></li>
          <li><a href="#">Instagram</a></li>
          <li><a href="#">LinkedIn</a></li>
        </ul>
      </div>
    </div>
  </footer>

  <script>
    (function () {
      const destinationInput = document.getElementById('destination');
      const qrTypeSelect = document.getElementById('qrType');
      const styleSelect = document.getElementById('style');
      const previewTitle = document.getElementById('previewTitle');
      const previewCopy = document.getElementById('previewCopy');

      if (!destinationInput || !qrTypeSelect || !styleSelect || !previewTitle || !previewCopy) return;

      const updatePreview = () => {
        const destination = destinationInput.value.trim() || 'https://';
        const type = qrTypeSelect.value;
        const style = styleSelect.value;
        previewTitle.textContent = `${type} QR ready`;
        previewCopy.textContent = `${style} style • ${destination}`;
      };

      [destinationInput, qrTypeSelect, styleSelect].forEach((field) => {
        field.addEventListener('input', updatePreview);
        field.addEventListener('change', updatePreview);
      });

      updatePreview();
    })();
  </script>
</body>
</html>
