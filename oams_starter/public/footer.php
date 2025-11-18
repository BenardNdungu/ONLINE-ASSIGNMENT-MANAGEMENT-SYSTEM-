<?php
// public/footer.php
// Shared footer for all OAMS pages.
?>
<style>
  /* =================================== */
/* 1. FOOTER BASE STYLES */
/* =================================== */

.site-footer {
    /* Background and text colors for contrast */
    background-color: #2c3e50; /* Dark Navy/Slate Blue */
    color: #ecf0f1; /* Light Gray/Off-White */
    padding: 30px 20px;
    font-family: Arial, sans-serif; /* Use a clean, modern font */
    line-height: 1.6;
}

/* Container for grouping all footer elements */
.footer-content-wrapper {
    max-width: 1200px; /* Limit width on large screens */
    margin: 0 auto; /* Center the wrapper */
    display: flex; /* Enable Flexbox layout */
    justify-content: space-between; /* Space out the main sections */
    align-items: flex-start; /* Align items to the top */
    gap: 30px; /* Space between sections */
}

/* =================================== */
/* 2. BRAND & COPYRIGHT SECTION */
/* =================================== */

.footer-brand-info {
    flex-shrink: 0; /* Prevents this section from shrinking too much */
}

.site-name {
    font-size: 1.5rem;
    font-weight: bold;
    color: #3498db; /* A bright accent color for the name */
    margin-top: 0;
    margin-bottom: 5px;
}

.copyright-text {
    font-size: 0.85rem;
    opacity: 0.8; /* Slightly dim the copyright text */
    margin: 0;
}

/* =================================== */
/* 3. NAVIGATION SECTION */
/* =================================== */

.footer-navigation {
    /* Allow navigation to take up available space */
    flex-grow: 1; 
    text-align: right; /* Align links to the right on desktop */
}

.footer-links-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex; /* Arrange links horizontally */
    flex-wrap: wrap; /* Allow links to wrap if screen is narrow */
    justify-content: flex-end; /* Push links to the right */
    gap: 20px; /* Space between links */
}

.footer-link {
    color: #ecf0f1;
    text-decoration: none;
    font-size: 1rem;
    padding: 5px 0; /* Add padding for a larger click area */
    transition: color 0.3s ease;
    white-space: nowrap; /* Prevent a single link from breaking onto two lines */
}

.footer-link:hover,
.footer-link:focus {
    color: #3498db; /* Change color on hover/focus */
    text-decoration: underline;
}

/* Icon styling */
.footer-link i {
    margin-right: 5px;
    font-size: 0.9rem;
    vertical-align: middle;
}

/* =================================== */
/* 4. RESPONSIVENESS (MOBILE FIRST) */
/* =================================== */

@media (max-width: 768px) {
    .footer-content-wrapper {
        /* Stack content vertically on smaller screens */
        flex-direction: column;
        align-items: center; /* Center everything when stacked */
        text-align: center;
    }

    .footer-brand-info {
        margin-bottom: 20px;
    }

    .footer-navigation {
        /* Override the desktop alignment */
        text-align: center;
        width: 100%; /* Take full width */
    }

    .footer-links-list {
        /* Center links when stacked */
        justify-content: center;
        /* Change from horizontal row to a centered stack or smaller row */
        gap: 15px; /* Reduce gap */
    }
  }
</style>

  </main>

  <footer class="site-footer">
    <div class="footer-content-wrapper">
      
      <div class="footer-brand-info">
        <p class="site-name">TASKNEST</p>
        <p class="copyright-text">
          &copy; <?php echo date("Y"); ?> All Rights Reserved.
        </p>
      </div>

      <nav class="footer-navigation" aria-label="Footer links">
        <ul class="footer-links-list">
          <li class="footer-link-item">
            <a href="about.php" class="footer-link">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i> About
            </a>
          </li>
          <li class="footer-link-item">
            <a href="contact.php" class="footer-link">
              <i class="fa-solid fa-envelope" aria-hidden="true"></i> Contact
            </a>
          </li>
          <li class="footer-link-item">
            <a href="privacy.php" class="footer-link">
              <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Privacy Policy
            </a>
          </li>
          </ul>
      </nav>

      <div class="footer-social">
        </div>

    </div>
  </footer>
</body>
</html>