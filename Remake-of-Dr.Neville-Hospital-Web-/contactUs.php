<?php
$conn = new mysqli("localhost", "root", "", "hospital_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch 5 most recent reviews for the sidebar
$recent_reviews = $conn->query("SELECT name, message, created_at FROM contact_inquiries ORDER BY created_at DESC LIMIT 5");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['full_name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $subject = $conn->real_escape_string($_POST['subject'] ?? '');
    $message = $conn->real_escape_string($_POST['message'] ?? '');

    $sql = "INSERT INTO contact_inquiries (name, email, subject, message) 
            VALUES ('$name', '$email', '$subject', '$message')";

    // Set header to return JSON
    header('Content-Type: application/json');

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Thank you! Your message has been sent.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    exit; 
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us - NFTH</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link rel="stylesheet" href="styles.css" />
    <style>

      /* Hero Section*/
        .contact-header {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('1.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.7);
            /* Match news&events.php layout */
            padding: 100px 0 60px; 
            text-align: center;
            min-height: 300px;
            display: flex;
            align-items: center;
        }

        .contact-header h1 {
            font-size: 3.5rem;
            font-weight: 650;
            margin-bottom: 15px;
            letter-spacing: -1px;
        }

        .contact-header p {
            font-size: 1rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.9;
            line-height: 1.6;
        }

        .text-highlight {
            color: #27ae60; 
        }


      /* --- Contact Page Specific Styles --- */
      :root {
        --contact-bg: #f8fafc;
      }

      .contact-hero {
        padding: 160px 0 80px;
        background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
        text-align: center;
      }

      .contact-hero h1 {
        font-size: 3rem;
        color: var(--gray-900);
        margin-bottom: 16px;
      }

      .contact-hero p {
        color: var(--gray-600);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
      }

      .contact-wrapper {
        padding-bottom: 100px;
        background-color: var(--contact-bg);
      }

      /* Info Cards Section */
      .info-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-top: -50px; 
        margin-bottom: 60px;
      }

      .info-card {
        background: white;
        padding: 40px 30px;
        border-radius: 16px;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
      }

      .info-card:hover {
        transform: translateY(-10px);
      }

      .info-card i {
        font-size: 2.5rem;
        color: var(--green-600);
        margin-bottom: 20px;
      }

      .info-card h3 {
        margin-bottom: 12px;
        color: var(--gray-900);
      }

      .info-card p, .info-card a {
        color: var(--gray-600);
        text-decoration: none;
        line-height: 1.6;
      }

      /* Main Contact Content */
      .main-contact-grid {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 40px;
      }

      .contact-form-container {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
      }

      .form-group {
        margin-bottom: 20px;
      }

      .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--gray-700);
      }

      .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
      }

      .form-control:focus {
        outline: none;
        border-color: var(--green-600);
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
      }

      /* Map */
      .map-container {
        width: 100%;
        height: 350px;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 30px;
      }

      /* Redesigned Reviews Card */
      .reviews-card {
        background: white;
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); 
      }

      .reviews-card h3 { 
        margin-bottom: 20px; 
        color: var(--gray-900);
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .review-item {
        padding: 15px 0;
        border-bottom: 1px solid #f1f5f9;
      }

      .review-item:last-child { border: none; }

      .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
      }

      .reviewer-name {
        font-weight: 700;
        color: var(--green-600);
        font-size: 0.95rem;
      }

      .review-date {
        font-size: 0.75rem;
        color: var(--gray-500);
      }

      .review-text {
        color: var(--gray-600);
        font-size: 0.85rem;
        line-height: 1.5;
        font-style: italic;
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

      /* --- Navbar Responsiveness --- */

      /* On screens smaller than 992px, hide the phone info and book now button */
      @media (max-width: 991px) {
          /* Hide phone and button as requested */
          .nav-right .phone-info,
          .nav-right #bookNowBtn {
           display: none !important;
          }

          /* Mobile Dropdown Styling */
          .nav-links {
            display: none; 
            flex-direction: column;
            position: absolute;
            top: 100%; 
            left: 0; 
            right: 0;
            background: white;
            padding: 20px;
            gap: 20px; /* This creates the consistent spacing you requested */
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border-top: 1px solid #eee;
            z-index: 1000;
          }

          /* This class is toggled by script.js */
          .nav-links.active {
              display: flex !important;
          }

          .nav-links li {
              width: 100%;
              text-align: center;
              list-style: none;
          }

          /* Show the hamburger button */
          .mobile-menu-btn {
              display: block !important;
              background: none;
              border: none;
              font-size: 1.5rem;
              cursor: pointer;
          }
      }

      /* --- Main Content Responsiveness --- */

      /* Tablet (max-width: 992px) */
      @media (max-width: 992px) {
        .main-contact-grid {
          grid-template-columns: 1fr; /* Stack form and side info */
          gap: 30px;
        }

        .contact-header h1 {
          font-size: 2.5rem;
        }
      }

      @media (max-width: 768px) {
          .main-contact-grid {
              grid-template-columns: 1fr !important; /* Stack form and map */
          }
          
          .contact-form-container form .grid {
              grid-template-columns: 1fr !important; /* Stack Name and Email fields */
          }

          .contact-header h1 {
              font-size: 2.2rem;
          }
      }

      /* Mobile Phones (max-width: 600px) */
      @media (max-width: 600px) {
        .contact-header {
          padding: 60px 0 40px;
        }

        .contact-header h1 {
          font-size: 2rem;
        }

        .contact-header p {
          font-size: 0.9rem;
          padding: 0 15px;
        }

        /* Stack the Full Name and Email fields vertically */
        .contact-form-container form .grid {
          grid-template-columns: 1fr !important;
          gap: 10px !important;
        }

        .info-cards-grid {
          margin-top: -30px;
          gap: 15px;
        }

        .info-card {
          padding: 25px 20px;
        }

        .contact-form-container {
          padding: 25px;
        }

        /* Make the map smaller on mobile */
        .map-container {
          height: 250px;
        }

        /* Ensure footer columns stack and center */
        .footer-grid {
          grid-template-columns: 1fr;
          text-align: center;
        }

        .contact-list li, .social-links-custom {
          justify-content: center;
        }

        .footer-bottom {
          font-size: 0.8rem;
        }
      }

    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            <li><a href="view_reports.php">E-Lab Reports</a></li>
            <li><a href="news&events.php">News & Events</a></li>
            <li><a href="ContactUs.php" class="active">Contact Us</a></li>
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

    <header class="contact-header">
        <div class="container">
            <h1>Get in <span class="text-highlight">Touch</span></h1>
            <p>
                Have questions about our services or need to reach a specific department?<br>
                Our team is here to help you 24/7.
            </p>
        </div>
    </header>

    <div class="contact-wrapper">
      <div class="container">
        <div class="info-cards-grid">
          <div class="info-card">
            <i class="fas fa-location-dot"></i>
            <h3>Our Location</h3>
            <p>406B, Church Road, Weliwita,<br>Malabe 10115, Sri Lanka</p>
          </div>
          <div class="info-card">
            <i class="fas fa-phone-volume"></i>
            <h3>Call Us</h3>
            <p>
              <a href="tel:+94112407600">+(94) 11 240 7600</a><br>
              <a href="tel:+94117707600">+(94) 11 770 7600</a>
            </p>
          </div>
          <div class="info-card">
            <i class="fas fa-envelope-open-text"></i>
            <h3>Email Us</h3>
            <p><a href="mailto:info@nfth.lk">info@nfth.lk</a></p>
          </div>
        </div>

        <div class="main-contact-grid">
          <div class="contact-form-container">
            <h2 style="margin-bottom: 25px;">Send us a Message</h2>
            <form id="contactForm" method="POST">
                <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="John Doe" required />
                        </div>
                        <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required />
                    </div>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject" class="form-control">
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Appointment Request">Appointment Request</option>
                        <option value="Feedback/Suggestions">Feedback/Suggestions</option>
                        <option value="Reporting an Issue">Reporting an Issue</option>
                    </select>
                    </div>
                    <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control" rows="5" placeholder="How can we help you today?" required></textarea>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 15px;">Send Message</button>
            </form>
          </div>

          <div class="side-info">
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.787682283921!2d79.9602631!3d6.9244938!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2571fc0be827b%3A0xf5d12cf308f6fa81!2sDr.%20Neville%20Fernando%20Teaching%20Hospital!5e0!3m2!1sen!2slk!4v1707920000000!5m2!1sen!2slk" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

            <div class="reviews-card">
              <h3><i class="fas fa-star" style="color: #fbbf24;"></i> Recent Feedback</h3>
              
              <?php if ($recent_reviews && $recent_reviews->num_rows > 0): ?>
                  <?php while($row = $recent_reviews->fetch_assoc()): ?>
                  <div class="review-item">
                    <div class="review-header">
                      <span class="reviewer-name"><?= htmlspecialchars($row['name']) ?></span>
                      <span class="review-date"><?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                    </div>
                    <p class="review-text">"<?= htmlspecialchars(substr($row['message'], 0, 80)) ?><?= strlen($row['message']) > 80 ? '...' : '' ?>"</p>
                  </div>
                  <?php endwhile; ?>
              <?php else: ?>
                  <p style="color: var(--gray-500); font-size: 0.9rem;">No reviews yet. Be the first to send us feedback!</p>
              <?php endif; ?>

              <a href="https://maps.app.goo.gl/kHgjwXtqjeq5dhVF6" target="_blank" class="btn-primary" style="display: block; text-align: center; text-decoration: none; margin-top: 20px; font-size: 0.8rem; padding: 10px;">
                View All on Google
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

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
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const mobileMenuBtn = document.getElementById("mobileMenuBtn");
            const navLinks = document.getElementById("navLinks");

            if (mobileMenuBtn && navLinks) {
                mobileMenuBtn.addEventListener("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    navLinks.classList.toggle("active");
                    
                    // Toggle between Bars and X icon
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
                        const icon = mobileMenuBtn.querySelector("i");
                        icon.classList.replace("fa-times", "fa-bars");
                    }
                });

                // Handle Form Submission via AJAX
                const contactForm = document.getElementById("contactForm");
                if (contactForm) {
                    contactForm.addEventListener("submit", function(e) {
                        e.preventDefault();

                        const formData = new FormData(this);

                        fetch("ContactUs.php", {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Message Sent!',
                                    text: data.message,
                                    confirmButtonColor: '#27ae60'
                                });
                                contactForm.reset(); // Clear the form
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: data.message
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong. Please try again later.'
                            });
                        });
                    });
                }
            }
        });
    </script>
  </body>
</html>