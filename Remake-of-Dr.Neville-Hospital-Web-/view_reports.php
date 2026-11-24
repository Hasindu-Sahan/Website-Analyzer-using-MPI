<?php
$conn = new mysqli("localhost","root","","hospital_db");
if($conn->connect_error){ die("DB Connection Failed"); }

$reports = [];
$search_error = "";
$searched_nic = "";

if(isset($_POST['search_reports'])){
    $searched_nic = $_POST['nic'] ?? '';
    
    if(!$searched_nic){
        $search_error = "Please enter your NIC number.";
    } else {
        // Fetch reports specifically for this NIC
        $sql = "SELECT r.report_name, r.test_date, r.file_path, p.patient_name 
                FROM lab_reports r 
                JOIN patients p ON r.patient_id = p.patient_id 
                WHERE p.nic = '$searched_nic' 
                ORDER BY r.test_date DESC";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $reports[] = $row;
            }
        } else {
            $search_error = "No reports found for NIC: " . htmlspecialchars($searched_nic);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Lab Reports - NFTH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Modern Report Styles */
        .report-section { padding: 80px 0; min-height: 70vh; }
        .report-card { 
            background: white; padding: 40px; border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto;
        }
        .report-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .report-table th, .report-table td { 
            padding: 15px; text-align: left; border-bottom: 1px solid #eee; 
        }
        .report-table th { background-color: #f8f9fa; color: #2ecc71; }
        .btn-download { 
            background: #2ecc71; color: white; padding: 8px 15px; 
            border-radius: 5px; text-decoration: none; font-size: 14px;
            transition: 0.3s;
        }
        .btn-download:hover { background: #27ae60; }
        .search-box { display: flex; gap: 10px; margin-bottom: 30px; }
        .search-box input { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }

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

        /* --- MOBILE RESPONSIVE UPDATES --- */
        @media (max-width: 1024px) {
            /* 1. FORCE HIDE PHONE AND BOOKING BUTTON */
            .nav-right {
                display: none !important;
            }

            /* 2. ALIGN LOGO AND HAMBURGER */
            .nav-wrapper {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center;
                width: 100%;
                position: relative;
            }

            /* 3. MOBILE DROPDOWN STYLING */
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
                display: flex !important;
            }

            .mobile-menu-btn {
                display: block !important;
                cursor: pointer;
                font-size: 1.5rem;
                background: none;
                border: none;
                color: #2c3e50;
            }
        }

        @media (max-width: 768px) {
            .report-card {
                padding: 20px;
                margin: 10px;
            }
            
            .search-box {
                flex-direction: column;
            }

            .report-table thead {
                display: none; /* Hide headers on small mobile */
            }

            .report-table, .report-table tbody, .report-table tr, .report-table td {
                display: block;
                width: 100%;
            }

            .report-table tr {
                margin-bottom: 15px;
                border: 1px solid #eee;
                padding: 10px;
                border-radius: 8px;
            }

            .report-table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            .report-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                font-weight: bold;
                color: #2ecc71;
            }
        }

    </style>
</head>
<body>

    <nav class="navbar" id="navbar">
      <div class="container">
        <div class="nav-wrapper">
          <a href="#home" class="logo-link">
            <img
              src="Logo.png"
              alt="Dr Neville Fernando Teaching Hospital Logo"
              class="site-logo"
            />
          </a>

          <ul class="nav-links" id="navLinks">
            <li><a href="index.html">Home</a></li>
            <li><a href="e_channeling.php">E-Channeling</a></li>
            <li><a href="view_reports.php" class="active">E-Lab Reports</a></li>
            <li><a href="news&events.php">News & Events</a></li>
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

    <main class="report-section container">
        <div class="report-card animate-on-scroll">
            <h2 class="text-green"><i class="fas fa-file-medical"></i> Access Your Lab Reports</h2>
            <p>Enter your National Identity Card (NIC) number to view and download your medical reports.</p>

            <form method="post" class="search-box">
                <input type="text" name="nic" placeholder="Enter NIC Number (e.g., 19XXXXXXXXXX)" value="<?= htmlspecialchars($searched_nic) ?>" required>
                <button type="submit" name="search_reports" class="btn-primary">View Reports</button>
            </form>

            <?php if($search_error): ?>
                <div class="error" style="color: #e74c3c; padding: 10px; background: #fdeaea; border-radius: 5px;">
                    <?= $search_error ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($reports)): ?>
                <h3>Reports for: <span class="text-green"><?= htmlspecialchars($reports[0]['patient_name']) ?></span></h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Report Name</th>
                            <th>Test Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reports as $report): ?>
                        <tr>
                            <td data-label="Report Name"><strong><?= htmlspecialchars($report['report_name']) ?></strong></td>
                            <td data-label="Test Date"><?= date('M d, Y', strtotime($report['test_date'])) ?></td>
                            <td data-label="Action">
                                <a href="<?= htmlspecialchars($report['file_path']) ?>" target="_blank" class="btn-download">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

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
              <li><a href="index.html">Home</a></li>
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


    <script src="script.js"></script>
    <script>
        // --- MOBILE MENU LOGIC ---
        const mobileMenuBtn = document.getElementById("mobileMenuBtn");
        const navLinks = document.getElementById("navLinks");

        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener("click", function (e) {
                e.stopPropagation(); // Prevents click from bubbling to document
                navLinks.classList.toggle("active");
                
                const icon = mobileMenuBtn.querySelector("i");
                if (navLinks.classList.contains("active")) {
                    icon.classList.replace("fa-bars", "fa-times");
                } else {
                    icon.classList.replace("fa-times", "fa-bars");
                }
            });

            // Close menu when clicking anywhere else on the screen
            document.addEventListener("click", function (event) {
                if (!navLinks.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                    navLinks.classList.remove("active");
                    mobileMenuBtn.querySelector("i").className = "fas fa-bars";
                }
            });
        }

        // --- NEWS FILTER LOGIC ---
        const newsFilter = document.getElementById('newsFilter');
        if (newsFilter) {
            newsFilter.addEventListener('change', function() {
                const filterValue = this.value;
                document.querySelectorAll('.news-card').forEach(card => {
                    if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                });
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            const mobileMenuBtn = document.getElementById("mobileMenuBtn");
            const navLinks = document.getElementById("navLinks");

            if (mobileMenuBtn && navLinks) {
                // Toggle menu on click
                mobileMenuBtn.onclick = function(e) {
                    e.stopPropagation();
                    navLinks.classList.toggle("active");
                    
                    // Toggle between bars and X icon
                    const icon = this.querySelector("i");
                    if (navLinks.classList.contains("active")) {
                        icon.className = "fas fa-times";
                    } else {
                        icon.className = "fas fa-bars";
                    }
                };

                // Close menu if clicking anywhere else
                document.onclick = function(e) {
                    if (!navLinks.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                        navLinks.classList.remove("active");
                        mobileMenuBtn.querySelector("i").className = "fas fa-bars";
                    }
                };
            }
        });
    </script>
</body>
</html>