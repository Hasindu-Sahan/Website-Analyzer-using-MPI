<?php
$conn = new mysqli("localhost","root","","hospital_db");
if($conn->connect_error){ die("DB Connection Failed"); }

if(isset($_POST['book'])){
    $name = $_POST['patient_name'] ?? '';
    $nic = $_POST['nic'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $doctor_id = $_POST['doctor'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $fee = $_POST['fee'] ?? '';

    if(!$name || !$nic || !$contact || !$doctor_id || !$date || !$time || !$fee){
        $error = "❌ Please fill all fields correctly!";
    } else {
        // 1. Manage Patient (Check if exists, or Insert)
        $patientRes = $conn->query("SELECT patient_id FROM patients WHERE nic='$nic'");
        
        if($patientRes->num_rows > 0){
            $pRow = $patientRes->fetch_assoc();
            $patient_id = $pRow['patient_id'];
        } else {
            $conn->query("INSERT INTO patients(patient_name, nic, contact) VALUES('$name', '$nic', '$contact')");
            $patient_id = $conn->insert_id;
        }

        // 2. Validate Doctor and Time Slot
        $check = $conn->query("SELECT * FROM appointments WHERE doctor_id='$doctor_id' AND appointment_date='$date' AND time_slot='$time'");
        
        if($check->num_rows > 0){
            $error = "❌ This specific time slot for this doctor is already taken. Please choose another.";
        } else {
            // 3. Insert Appointment using patient_id
            $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, fee, status) 
                    VALUES ('$patient_id', '$doctor_id', '$date', '$time', '$fee', 'Pending')";
            
            if ($conn->query($sql)) {
                $success = "✅ Appointment Booked Successfully!";
            } else {
                // This will tell you if a Foreign Key (like doctor_id or patient_id) is invalid
                $error = "❌ Database Error: " . $conn->error . " (Check if Doctor and Patient IDs are valid)";
            }
        }
    }
}


// AJAX: get available time slots + fee
if(isset($_GET['doctor'], $_GET['date'])){
    $doctor = $_GET['doctor'];
    $date = $_GET['date'];

    $feeRow = $conn->query("SELECT fee FROM doctors WHERE doctor_id='$doctor'")->fetch_assoc();
    $fee = $feeRow['fee'] ?? 0;

    $res = $conn->query("SELECT time_slot FROM doctor_times WHERE doctor_id='$doctor' AND time_slot NOT IN
    (SELECT time_slot FROM appointments WHERE doctor_id='$doctor' AND appointment_date='$date')");

    $times = "<option value=''>-- Select --</option>";
    while($t = $res->fetch_assoc()){
        $times .= "<option>{$t['time_slot']}</option>";
    }

    echo json_encode(["fee"=>$fee,"times"=>$times]);
    exit;
}

// Fetch doctors
$doctorResult = $conn->query("SELECT * FROM doctors");
$specResult = $conn->query("SELECT DISTINCT specialization FROM doctors");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Channeling - NFTH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="styles.css">
    
    <script>
    function loadTimeSlots(){
        let doctor = document.getElementById("doctor").value;
        let date = document.getElementById("date").value;
        if(!doctor || !date) return;

        fetch(`?doctor=${doctor}&date=${date}`)
        .then(res=>res.json())
        .then(data=>{
            document.getElementById("fee").value = data.fee;
            document.getElementById("time").innerHTML = data.times;
        });
    }
    </script>
    <style>
      /* --- UPDATED MOBILE NAVIGATION --- */
      @media (max-width: 1024px) {
        .nav-wrapper {
          position: relative;
          /* Ensure the logo and hamburger are spread out */
          display: flex;
          justify-content: space-between;
          align-items: center;
          width: 100%;
        }

        /* HIDE THE PHONE INFO AND BOOK NOW BUTTON */
        .nav-right {
          display: none !important;
        }

        /* Style the links as a vertical dropdown list */
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
          display: block;
          cursor: pointer;
          font-size: 1.5rem;
          background: none;
          border: none;
          padding: 10px;
        }

        /* Ensure Logo stays visible and properly sized */
        .site-logo {
          max-height: 50px;
        }
      }

      @media (max-width: 768px) {
        /* Scale down the very large hero title */
        .hero-title {
          font-size: 2.2rem !important;
          line-height: 1.3;
        }

        .hero-content {
          width: 95%;
          padding: 20px 15px;
        }

        /* Stack vision/mission cards vertically */
        .vm-grid {
          grid-template-columns: 1fr;
          gap: 20px;
        }

        /* Adjust service grid for two columns on phones */
        .service-grid-nfth {
          grid-template-columns: 1fr 1fr;
          gap: 12px;
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
            <li><a href="e_channeling.php" class="active">E-Channeling</a></li>
            <li><a href="view_reports.php">E-Lab Reports</a></li>
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

    <main class="container">
        
        <div class="echanneling-container">
            <h2>Hospital E-Channeling</h2>

            <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
            <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>

            <form method="post">
                <input type="text" name="patient_name" placeholder="Patient Name" required>
                <input type="text" name="nic" placeholder="Patient NIC" required>
                <input type="text" name="contact" placeholder="Contact Number" required>

                <select id="specialization" onchange="filterDoctors()" required>
                    <option value="">Select Specialization</option>
                    <?php 
                    while($s = $specResult->fetch_assoc()){ 
                        echo "<option value='{$s['specialization']}'>{$s['specialization']}</option>"; 
                    } 
                    ?>
                </select>

                <select name="doctor" id="doctor" onchange="loadTimeSlots()" required>
                    <option value="">Select Doctor</option>
                    <?php 
                    $doctorResult->data_seek(0); 
                    while($d = $doctorResult->fetch_assoc()){ 
                    ?>
                        <option value="<?= $d['doctor_id'] ?>" data-spec="<?= $d['specialization'] ?>">
                            <?= $d['doctor_name'] ?>
                        </option>
                    <?php } ?>
                </select>

                <input type="date" name="date" id="date" onchange="loadTimeSlots()" required>

                <select name="time" id="time" required>
                    <option value="">Select Time Slot</option>
                </select>

                <input type="text" name="fee" id="fee" readonly placeholder="Fee">

                <button type="submit" name="book" class="btn-primary">Book Appointment</button>
            </form>
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
                <span>406B, Church Road, Weliwita, Malabe 10115, Sri Lanka</span>
              </li>
              <li>
                <i class="fas fa-phone"></i>
                <span>+(94) 11 240 7600<br />+(94) 11 770 7600</span>
              </li>
              <li>
                <i class="fas fa-envelope"></i>
                <span>Email: <a href="mailto:info@nfth.lk" class="footer-mail-link">info@nfth.lk</a></span>
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
              <a href="https://www.facebook.com/NFTH.SriLanka/" target="_blank" rel="noopener" aria-label="Facebook">
                <img src="facebook logo.png" alt="Facebook" class="social-icon-img" />
              </a>
              <a href="https://www.youtube.com/@drnevillefernandoteachingh3020" target="_blank" rel="noopener" aria-label="YouTube">
                <img src="youtube logo.png" alt="YouTube" class="social-icon-img" />
              </a>
              <a href="https://www.linkedin.com/company/nfth/" target="_blank" rel="noopener" aria-label="LinkedIn">
                <img src="linkedin logo.png" alt="LinkedIn" class="social-icon-img" />
              </a>
              <a href="https://maps.app.goo.gl/L35uoV5DzovnrZL9A?g_st=awb" target="_blank" rel="noopener" aria-label="Map Location">
                <img src="location logo.png" alt="Map Location" class="social-icon-img" />
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
          <p>© 2024 Dr Neville Fernando Teaching Hospital. All rights reserved.</p>
        </div>
      </div>
    </footer>

    <script>
      function filterDoctors() {
          let spec = document.getElementById("specialization").value;
          let doctorSelect = document.getElementById("doctor");
          let options = doctorSelect.options;

          // Reset doctor selection and time slots
          doctorSelect.value = "";
          document.getElementById("time").innerHTML = "<option value=''>Select Time Slot</option>";

          for (let i = 1; i < options.length; i++) {
              let doctorSpec = options[i].getAttribute("data-spec");
              if (spec === "" || doctorSpec === spec) {
                  options[i].style.display = "block"; // Show matching doctors
              } else {
                  options[i].style.display = "none"; // Hide others
              }
          }
      }

      function loadTimeSlots() {
          let doctor = document.getElementById("doctor").value;
          let date = document.getElementById("date").value;
          if (!doctor || !date) return;

          fetch(`?doctor=${doctor}&date=${date}`)
          .then(res => res.json())
          .then(data => {
              document.getElementById("fee").value = data.fee;
              document.getElementById("time").innerHTML = data.times;
          });
      }

      // --- FIXED MOBILE MENU LOGIC ---
    const mobileMenuBtn = document.getElementById("mobileMenuBtn");
    const navLinks = document.getElementById("navLinks");

    if (mobileMenuBtn && navLinks) {
      // 1. Toggle menu when clicking the hamburger icon
      mobileMenuBtn.addEventListener("click", function (e) {
        // This stops the click from "bubbling" up to the document listener
        e.stopPropagation(); 
        
        navLinks.classList.toggle("active");
        
        const icon = mobileMenuBtn.querySelector("i");
        if (navLinks.classList.contains("active")) {
          icon.classList.replace("fa-bars", "fa-times");
        } else {
          icon.classList.replace("fa-times", "fa-bars");
        }
      });

      // 2. Close menu if the user clicks anywhere else on the screen
      document.addEventListener("click", function (event) {
        const isClickInsideMenu = navLinks.contains(event.target);
        const isClickOnButton = mobileMenuBtn.contains(event.target);

        // If the click is NOT on the menu and NOT on the button, close it
        if (!isClickInsideMenu && !isClickOnButton && navLinks.classList.contains("active")) {
          navLinks.classList.remove("active");
          const icon = mobileMenuBtn.querySelector("i");
          icon.classList.replace("fa-times", "fa-bars");
        }
      });

      // 3. Close menu when a link inside is clicked (for better UX)
      navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
          navLinks.classList.remove("active");
          const icon = mobileMenuBtn.querySelector("i");
          icon.classList.replace("fa-times", "fa-bars");
        });
      });
    }
    </script>
</body>
</html>