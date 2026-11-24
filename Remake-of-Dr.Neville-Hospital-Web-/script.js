// Function to handle the phone call request
function initiatePhoneCall() {
  const phoneNumber = "+(94) 11 240 7600";
  // Use window.location.href to trigger the call prompt
  window.location.href = `tel:${phoneNumber.replace(/[^0-9+]/g, "")}`;
}

document.addEventListener("DOMContentLoaded", function () {
  // --- Button Functionality ---
  const contactUsCtaBtn = document.getElementById("contactUsCtaBtn");
  const bookNowBtn = document.getElementById("bookNowBtn");
  const bookAppointmentBtn = document.getElementById("bookAppointmentBtn");

  if (contactUsCtaBtn) {
    contactUsCtaBtn.addEventListener("click", initiatePhoneCall);
  }
  if (bookNowBtn) {
    bookNowBtn.addEventListener("click", initiatePhoneCall);
  }
  if (bookAppointmentBtn) {
    bookAppointmentBtn.addEventListener("click", initiatePhoneCall);
  }

  // Navbar scroll effect
  window.addEventListener("scroll", function () {
    const navbar = document.getElementById("navbar");
    if (navbar) {
      if (window.scrollY > 20) {
        navbar.classList.add("scrolled");
      } else {
        navbar.classList.remove("scrolled");
      }
    }
  });

  // --- FIXED MOBILE MENU LOGIC ---
  const mobileMenuBtn = document.getElementById("mobileMenuBtn");
  const navLinks = document.getElementById("navLinks");

  if (mobileMenuBtn && navLinks) {
    // 1. Toggle menu when clicking the hamburger icon
    mobileMenuBtn.addEventListener("click", function (e) {
      e.stopPropagation(); // Stops the click from reaching the document listener below
      navLinks.classList.toggle("active");
      
      const icon = mobileMenuBtn.querySelector("i");
      if (navLinks.classList.contains("active")) {
        icon.classList.replace("fa-bars", "fa-times");
      } else {
        icon.classList.replace("fa-times", "fa-bars");
      }
    });

    // 2. Close menu if the user clicks anywhere else on the document
    document.addEventListener("click", function (event) {
        const isClickInsideMenu = navLinks.contains(event.target);
        const isClickOnButton = mobileMenuBtn.contains(event.target);

        // If the menu is open and we click outside, close it
        if (!isClickInsideMenu && !isClickOnButton && navLinks.classList.contains("active")) {
            navLinks.classList.remove("active");
            const icon = mobileMenuBtn.querySelector("i");
            if (icon) icon.classList.replace("fa-times", "fa-bars");
        }
    });

    // 3. Close menu when a link is clicked (important for smooth scrolling on Index)
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        navLinks.classList.remove("active");
        const icon = mobileMenuBtn.querySelector("i");
        if (icon) icon.classList.replace("fa-times", "fa-bars");
      });
    });
  }
  // Smooth scroll for navigation links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      // Only scroll if the link is just a hash (on same page)
      const targetId = this.getAttribute("href");
      if (targetId.startsWith("#") && targetId.length > 1) {
        e.preventDefault();
        const target = document.querySelector(targetId);
        if (target) {
          target.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });

          // Close mobile menu if open
          if (navLinks) navLinks.classList.remove("active");
          const icon = mobileMenuBtn?.querySelector("i");
          if (icon) {
            icon.classList.remove("fa-times");
            icon.classList.add("fa-bars");
          }
        }
      }
    });
  });

  // Intersection Observer for scroll animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  };

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("visible");
      }
    });
  }, observerOptions);

  // Observe all elements with animate-on-scroll class
  document.querySelectorAll(".animate-on-scroll").forEach((element) => {
    observer.observe(element);
  });

  // Slideshow Logic (Only runs if slides exist)
  let slideIndex = 0;
  const slides = document.querySelectorAll(".slide");

  if (slides.length > 0) {
    showSlides();
  }

  function showSlides() {
    for (let i = 0; i < slides.length; i++) {
      slides[i].classList.remove("active-slide");
    }

    slideIndex = slideIndex % slides.length;
    slides[slideIndex].classList.add("active-slide");

    slideIndex++;
    setTimeout(showSlides, 5000);
  }

  // Add ripple effect to buttons
  document.querySelectorAll("button").forEach((button) => {
    button.addEventListener("click", function (e) {
      // Only apply ripple to buttons that are not initiating a call
      if (
        this.id === "contactUsCtaBtn" ||
        this.id === "bookNowBtn" ||
        this.id === "bookAppointmentBtn"
      ) {
        return;
      }
      const ripple = document.createElement("span");
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;

      ripple.style.width = ripple.style.height = size + "px";
      ripple.style.left = x + "px";
      ripple.style.top = y + "px";
      ripple.classList.add("ripple");

      this.appendChild(ripple);

      setTimeout(() => {
        ripple.remove();
      }, 600);
    });
  });

  // Console welcome message
  console.log(
    "%cNFTH Website",
    "color: #16a34a; font-size: 24px; font-weight: bold;",
  );
  console.log(
    "%cDr Neville Fernando Teaching Hospital",
    "color: #4b5563; font-size: 14px;",
  );
  console.log(
    "%cQuality Healthcare Services",
    "color: #6b7280; font-size: 12px;",
  );
});

// --- Search Bar Logic ---
let matches = [];
let currentMatchIndex = -1;

const searchInput = document.getElementById('siteSearchInput');
const searchBtn = document.getElementById('siteSearchBtn');
const prevBtn = document.getElementById('prevMatchBtn');
const nextBtn = document.getElementById('nextMatchBtn');

function clearHighlights() {
    const highlights = document.querySelectorAll('.search-highlight');
    highlights.forEach(span => {
        const parent = span.parentNode;
        parent.replaceChild(document.createTextNode(span.textContent), span);
        parent.normalize(); 
    });
    matches = [];
    currentMatchIndex = -1;
}

function performSearch() {
    clearHighlights();
    const query = searchInput.value.trim();
    if (query.length < 2) return;

    // Use capturing group ($1) to preserve case while highlighting
    const regex = new RegExp(`(${query})`, 'gi');
    
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
    const nodesToReplace = [];

    let node;
    while (node = walker.nextNode()) {
        const parent = node.parentElement;
        // Skip administrative elements
        if (['SCRIPT', 'STYLE', 'INPUT', 'TEXTAREA', 'BUTTON'].includes(parent.tagName)) continue;
        
        // Use .match instead of .test to avoid "lastIndex" state issues
        if (node.textContent.match(regex)) {
            nodesToReplace.push(node);
        }
    }

    nodesToReplace.forEach(node => {
        const span = document.createElement('span');
        // This regex replacement ensures ALL instances in the text node are wrapped
        span.innerHTML = node.textContent.replace(regex, '<span class="search-highlight">$1</span>');
        node.parentNode.replaceChild(span, node);
    });

    matches = document.querySelectorAll('.search-highlight');
    
    if (matches.length > 0) {
        currentMatchIndex = 0;
        updateMatchSelection();
    } else {
        alert("No results found.");
    }
}

function updateMatchSelection() {
    matches.forEach(m => m.classList.remove('active-match'));
    
    const current = matches[currentMatchIndex];
    if (current) {
        current.classList.add('active-match');
        // Center the match in the screen
        current.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Listeners
searchBtn.addEventListener('click', performSearch);
searchInput.addEventListener('keypress', (e) => { 
    if (e.key === 'Enter') performSearch(); 
});

nextBtn.addEventListener('click', (e) => {
    e.preventDefault(); // Prevent page jumps
    if (matches.length > 0) {
        currentMatchIndex = (currentMatchIndex + 1) % matches.length;
        updateMatchSelection();
    }
});

prevBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (matches.length > 0) {
        currentMatchIndex = (currentMatchIndex - 1 + matches.length) % matches.length;
        updateMatchSelection();
    }
});