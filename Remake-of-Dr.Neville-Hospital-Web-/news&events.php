<?php $conn = new mysqli("localhost", "root", "", "hospital_db"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>News & Events - Dr Neville Fernando Teaching Hospital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="styles.css" />
    <style>
        .news-header {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('1.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0 60px;
            text-align: center;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 60px 0;
        }

        .news-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .news-card:hover {
            transform: translateY(-10px);
        }

        .news-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .news-content {
            padding: 20px;
            flex-grow: 1;
        }

        .news-date {
            color: #2ecc71;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
        }

        .news-title {
            font-size: 1.25rem;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .news-excerpt {
            color: #7f8c8d;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .read-more {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* --- Dropdown Filter --- */
        .filter-container {
            display: flex;
            align-items: center;
            justify-content: center; 
            gap: 15px;
            padding: 40px 0 20px 0; 
        }

        .filter-icon {
            color: #2ecc71; 
            font-size: 1.2rem;
        }

        .news-filter-dropdown {
            padding: 12px 40px 12px 15px; 
            border: 2px solid #2ecc71;
            border-radius: 30px; 
            font-size: 1rem;
            color: #2c3e50;
            background-color: white;
            cursor: pointer;
            min-width: 250px;
            outline: none;
            appearance: none; 
            -webkit-appearance: none;
            -moz-appearance: none;
            
            /* Enhanced Dropdown Arrow SVG */
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%232ecc71' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 18px;
            transition: all 0.3s ease;
        }

        .news-filter-dropdown:hover {
            border-color: #27ae60;
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.2);
        }

        .news-filter-dropdown:focus {
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.5);
        }

        .news-card.hidden {
            display: none;
        }
        /* Footer */
        .footer {
            background: var(--footer-bg);
            color: #d1d5db;
            padding: 48px 0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 32px;
            margin-bottom: 48px;
        }

        .footer-col h4 {
            color: white;
            font-weight: bold;
            margin-bottom: 24px;
        }

        .footer-link-list {
            list-style: disc;
            padding-left: 20px;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 14px;
            line-height: 1.4;
        }

        .footer-link-list li {
            padding-left: 0;
        }

        .footer-link-list a,
        .footer-link-list li strong {
            color: #d1d5db;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-link-list a:hover {
            color: #4ade80;
        }

        .contact-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 14px;
        }

        .contact-list li {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .contact-list i {
            color: #4ade80;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .footer-mail-link {
            color: #d1d5db;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-mail-link:hover {
            color: #4ade80;
            text-decoration: underline;
        }

        .footer-bottom {
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 32px;
            border-top: 1px solid #374151;
            flex-wrap: wrap;
            gap: 16px;
            text-align: center;
        }

        .social-links-custom {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .social-icon-img {
            width: 40px;
            height: 40px;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .social-links-custom a:hover .social-icon-img {
            transform: translateY(-2px) scale(1.05);
            opacity: 0.8;
        }

        .read-more { cursor: pointer; border: none; background: none; padding: 0; }
        .hidden { display: none !important; }

        /* --- MOBILE RESPONSIVE UPDATES --- */
        @media (max-width: 1024px) {
            .nav-wrapper {
              display: flex;
              justify-content: space-between;
              align-items: center;
              width: 100%;
            }

            /* HIDE THE PHONE INFO AND BOOK NOW BUTTON ON MOBILE */
            .nav-right {
                display: none !important;
            }

            /* Mobile Dropdown Styling */
            .nav-links {
                display: none; 
                flex-direction: column;
                position: absolute;
                top: 100%; 
                left: -24px; 
                right: -24px;
                background: white;
                padding: 20px;
                gap: 20px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                border-top: 1px solid #eee;
                z-index: 1000;
            }

            .nav-links.active {
                display: flex;
            }

            .nav-links li {
                width: 100%;
                text-align: center;
            }

            .mobile-menu-btn {
                display: block; /* Show hamburger icon */
                cursor: pointer;
                font-size: 1.5rem;
                background: none;
                border: none;
                padding: 10px;
                color: #2c3e50;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem !important;
            }

            .news-header {
                padding: 80px 0 40px;
            }

            .filter-container {
                flex-direction: column;
                padding: 20px 0;
            }

            .news-filter-dropdown {
                width: 90%;
                min-width: unset;
            }

            .news-grid {
                grid-template-columns: 1fr; /* Single column on phones */
                padding: 30px 15px;
            }
        }

    </style>
</head>
<body>
    <nav class="navbar" id="navbar">
      <div class="container">
        <div class="nav-wrapper">
          <a href="index.html" class="logo-link">
            <img src="Logo.png" alt="NFTH Logo" class="site-logo" />
          </a>
          <ul class="nav-links" id="navLinks">
            <li><a href="index.html">Home</a></li>
            <li><a href="e_channeling.php">E-Channeling</a></li>
            <li><a href="view_reports.php">E-Lab Reports</a></li>
            <li><a href="news_events.php" class="active">News & Events</a></li>
            <li><a href="ContactUs.php">Contact Us</a></li>
          </ul>
          <div class="nav-right">
            <div class="phone-info">
              <i class="fas fa-phone"></i>
              <span>+(94) 11 240 7600</span>
            </div>
            <button class="btn-primary" id="bookNowBtn">Book Now</button>
          </div>
          <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
          </button>
        </div>
      </div>
    </nav>

    <header class="news-header">
        <div class="container">
            <h1 class="hero-title">News & <span class="text-green">Events</span></h1>
            <p>Stay updated with the latest happenings at NFTH</p>
        </div>
    </header>

    <section class="container">
        <div class="filter-container">
            <i class="fas fa-filter filter-icon"></i>
            <select id="newsFilter" class="news-filter-dropdown">
                <option value="all">All News & Events</option>
                <option value="facility">Facility Updates</option>
                <option value="medical">Medical Advances</option>
                <option value="awards">Awards & Recognition</option>
                <option value="community">Community Outreach</option>
            </select>
        </div>
    </section>

    <section class="container">
        <div class="news-grid" id="newsGrid">
            <?php
            $news = $conn->query("SELECT * FROM hospital_news ORDER BY news_date DESC");
            while($row = $news->fetch_assoc()):
            ?>
            <article class="news-card" data-category="<?= $row['category'] ?>">
                <img src="<?= $row['image_path'] ?>" alt="News" class="news-image">
                <div class="news-content">
                    <span class="news-date"><?= date('F d, Y', strtotime($row['news_date'])) ?></span>
                    <h3 class="news-title"><?= htmlspecialchars($row['title']) ?></h3>
                    <p class="news-excerpt"><?= htmlspecialchars($row['caption']) ?></p>
                    <a href="news_detail.php?id=<?= $row['id'] ?>" target="_blank" class="read-more">
                        Read Full Story <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </article>
            <?php endwhile; ?>
        </div>
    </section>

    <footer class="footer" id="contact">
      <div class="container">
        <div class="footer-grid">
          <div class="footer-col contact-footer-col">
            <h4>Contact Info</h4>
            <ul class="contact-list">
              <li>
                <i class="fas fa-map-marker-alt"></i>
                <span
                  >406B, Church Road, Weliwita, Malabe 10115, Sri Lanka</span
                >
              </li>
              <li>
                <i class="fas fa-phone"></i>
                <span>+(94) 11 240 7600<br />+(94) 11 770 7600</span>
              </li>
              <li>
                <i class="fas fa-envelope"></i>
                <span
                  >Email:
                  <a href="mailto:info@nfth.lk" class="footer-mail-link"
                    >info@nfth.lk</a
                  ></span
                >
              </li>
            </ul>
          </div>

          <div class="footer-col">
            <h4>Quick Links</h4>
            <ul class="footer-link-list">
              <li><a href="#home">Home</a></li>
              <li><a href="e_channeling.php">E-Channeling</a></li>
              <li><a href="view_reports.php">E-Lab Reports</a></li>
              <li><a href="news&events.php">News & Events</a></li>
              <li><a href="ContactUs.php">Contact Us</a></li>
            </ul>
          </div>

          <div class="footer-col">
            <h4>Facilities</h4>
            <ul class="footer-link-list">
              <li>Ward Beds</li>
              <li>Luxury & Semi Luxury Room Facilities</li>
              <li>Food & Beverage</li>
              <li>Labour Room</li>
              <li>Pharmacy</li>
              <li>Quality Control Unit</li>
              <li>Car Park</li>
            </ul>
          </div>

          <div class="footer-col social-footer-col">
            <h4>FIND US ON</h4>
            <div class="social-links-custom">
              <a
                href="https://www.facebook.com/NFTH.SriLanka/"
                target="_blank"
                rel="noopener"
                aria-label="Facebook"
              >
                <img
                  src="facebook logo.png"
                  alt="Facebook"
                  class="social-icon-img"
                />
              </a>
              <a
                href="https://www.youtube.com/@drnevillefernandoteachingh3020"
                target="_blank"
                rel="noopener"
                aria-label="YouTube"
              >
                <img
                  src="youtube logo.png"
                  alt="YouTube"
                  class="social-icon-img"
                />
              </a>
              <a
                href="https://www.linkedin.com/company/nfth/"
                target="_blank"
                rel="noopener"
                aria-label="LinkedIn"
              >
                <img
                  src="linkedin logo.png"
                  alt="LinkedIn"
                  class="social-icon-img"
                />
              </a>
              <a
                href="https://maps.app.goo.gl/L35uoV5DzovnrZL9A?g_st=awb"
                target="_blank"
                rel="noopener"
                aria-label="Map Location"
              >
                <img
                  src="location logo.png"
                  alt="Map Location"
                  class="social-icon-img"
                />
              </a>
            </div>

            <h4 class="hours-heading">Working Hours</h4>
            <ul class="footer-link-list">
              <li><strong>Emergency:</strong> 24/7 Available</li>
              <li><strong>OPD:</strong> Mon - Fri: 8:00 AM - 5:00 PM</li>
              <li><strong>Saturday:</strong> 9:00 AM - 1:00 PM</li>
            </ul>
          </div>
        </div>

        <div class="footer-bottom">
          <p>
            © 2024 Dr Neville Fernando Teaching Hospital. All rights reserved.
          </p>
        </div>
      </div>
    </footer>


    <script>
        // 1. Mobile Menu Logic
        const mobileMenuBtn = document.getElementById("mobileMenuBtn");
        const navLinks = document.getElementById("navLinks");

        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener("click", function (e) {
                e.stopPropagation();
                navLinks.classList.toggle("active");
                
                const icon = mobileMenuBtn.querySelector("i");
                if (navLinks.classList.contains("active")) {
                    icon.classList.replace("fa-bars", "fa-times");
                } else {
                    icon.classList.replace("fa-times", "fa-bars");
                }
            });

            // Close menu when clicking outside
            document.addEventListener("click", function (event) {
                if (!navLinks.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                    navLinks.classList.remove("active");
                    mobileMenuBtn.querySelector("i").classList.replace("fa-times", "fa-bars");
                }
            });
        }

        // 2. News Filter Logic
        const newsFilter = document.getElementById('newsFilter');
        if (newsFilter) {
            newsFilter.addEventListener('change', function() {
                const filterValue = this.value;
                document.querySelectorAll('.news-card').forEach(card => {
                    if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                        card.style.display = "flex"; // Reset to flex to match card styling
                    } else {
                        card.style.display = "none";
                    }
                });
            });
        }
    </script>
</body>
</html>