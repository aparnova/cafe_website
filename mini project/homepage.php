<?php
//This is Website for restleys resto cafe
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Westley's Resto Cafe</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <style>
    /* Fonts */
:root {
  --default-font: "Roboto",  system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
  --heading-font: "Playfair Display",  sans-serif;
  --nav-font: "Poppins",  sans-serif;
}


:root { 
  --background-color: #0c0b09; 
  --default-color: rgba(255, 255, 255, 0.7);
  --heading-color: #ffffff; 
  --accent-color: #cda45e; 
  --surface-color: #29261f; 
  --contrast-color: #0c0b09; 
}

/* Nav Menu Colors - The following color variables are used specifically for the navigation menu. They are separate from the global colors to allow for more customization options */
:root {
  --nav-color: #ffffff;  
  --nav-hover-color: #cda45e; 
  --nav-mobile-background-color: #29261f; 
  --nav-dropdown-background-color: #29261f; 
  --nav-dropdown-color: #ffffff; 
  --nav-dropdown-hover-color: #cda45e; 
}

:root {
  scroll-behavior: smooth;
}

/*--------------------------------------------------------------
# General Styling & Shared Classes
--------------------------------------------------------------*/
body {
  color: var(--default-color);
  background-color: var(--background-color);
  font-family: var(--default-font);
}

a {
  color: var(--accent-color);
  text-decoration: none;
  transition: 0.3s;
}

a:hover {
  color: color-mix(in srgb, var(--accent-color), transparent 25%);
  text-decoration: none;
}

h1,
h2,
h3,
h4,
h5,
h6 {
  color: var(--heading-color);
  font-family: var(--heading-font);
}

/* PHP Email Form Messages
------------------------------*/
.php-email-form .error-message {
  display: none;
  background: #df1529;
  color: #ffffff;
  text-align: left;
  padding: 15px;
  margin-bottom: 24px;
  font-weight: 600;
}

.php-email-form .sent-message {
  display: none;
  color: #ffffff;
  background: #059652;
  text-align: center;
  padding: 15px;
  margin-bottom: 24px;
  font-weight: 600;
}

.php-email-form .loading {
  display: none;
  background: var(--surface-color);
  text-align: center;
  padding: 15px;
  margin-bottom: 24px;
}

.php-email-form .loading:before {
  content: "";
  display: inline-block;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  margin: 0 10px -6px 0;
  border: 3px solid var(--accent-color);
  border-top-color: var(--surface-color);
  animation: php-email-form-loading 1s linear infinite;
}

@keyframes php-email-form-loading {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

/* Pulsating Play Button
------------------------------*/
.pulsating-play-btn {
  width: 94px;
  height: 94px;
  background: radial-gradient(var(--accent-color) 50%, color-mix(in srgb, var(--accent-color), transparent 75%) 52%);
  border-radius: 50%;
  display: block;
  position: relative;
  overflow: hidden;
}

.pulsating-play-btn:before {
  content: "";
  position: absolute;
  width: 120px;
  height: 120px;
  animation-delay: 0s;
  animation: pulsate-play-btn 2s;
  animation-direction: forwards;
  animation-iteration-count: infinite;
  animation-timing-function: steps;
  opacity: 1;
  border-radius: 50%;
  border: 5px solid color-mix(in srgb, var(--accent-color), transparent 30%);
  top: -15%;
  left: -15%;
  background: rgba(198, 16, 0, 0);
}

.pulsating-play-btn:after {
  content: "";
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translateX(-40%) translateY(-50%);
  width: 0;
  height: 0;
  border-top: 10px solid transparent;
  border-bottom: 10px solid transparent;
  border-left: 15px solid #fff;
  z-index: 100;
  transition: all 400ms cubic-bezier(0.55, 0.055, 0.675, 0.19);
}

.pulsating-play-btn:hover:before {
  content: "";
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translateX(-40%) translateY(-50%);
  width: 0;
  height: 0;
  border: none;
  border-top: 10px solid transparent;
  border-bottom: 10px solid transparent;
  border-left: 15px solid #fff;
  z-index: 200;
  animation: none;
  border-radius: 0;
}

.pulsating-play-btn:hover:after {
  border-left: 15px solid var(--accent-color);
  transform: scale(20);
}

@keyframes pulsate-play-btn {
  0% {
    transform: scale(0.6, 0.6);
    opacity: 1;
  }

  100% {
    transform: scale(1, 1);
    opacity: 0;
  }
}

/*--------------------------------------------------------------
# Global Header
--------------------------------------------------------------*/
.header {
  --background-color: rgba(12, 11, 9, 0.61);
  color: var(--default-color);
  transition: all 0.5s;
  z-index: 997;
}

.header .topbar {
  height: 40px;
  padding: 0;
  font-size: 14px;
  transition: all 0.5s;
}

.header .topbar .contact-info i {
  font-style: normal;
  color: var(--accent-color);
}

.header .topbar .contact-info i a,
.header .topbar .contact-info i span {
  padding-left: 5px;
  color: var(--default-color);
}

@media (max-width: 575px) {

  .header .topbar .contact-info i a,
  .header .topbar .contact-info i span {
    font-size: 13px;
  }
}

.header .topbar .contact-info i a {
  line-height: 0;
  transition: 0.3s;
}

.header .topbar .contact-info i a:hover {
  color: var(--accent-color);
  text-decoration: underline;
}

.header .topbar .languages ul {
  display: flex;
  flex-wrap: wrap;
  list-style: none;
  padding: 0;
  margin: 0;
  color: var(--accent-color);
}

.header .topbar .languages ul a {
  color: var(--default-color);
}

.header .topbar .languages ul a:hover {
  color: var(--accent-color);
}

.header .topbar .languages ul li+li {
  padding-left: 10px;
}

.header .topbar .languages ul li+li::before {
  display: inline-block;
  padding-right: 10px;
  color: color-mix(in srgb, var(--default-color), transparent 10%);
  content: "/";
}

.header .branding {
  background-color: var(--background-color);
  min-height: 60px;
  padding: 10px 0;
  transition: 0.3s;
  border-bottom: 1px solid var(--background-color);
}

.header .logo {
  line-height: 1;
}

.header .logo img {
  max-height: 60px;
  margin-right: 8px;
}

.header .logo h1 {
  font-size: 30px;
  margin: 0;
  font-weight: 700;
  color: var(--heading-color);
}


.scrolled .header .topbar {
  height: 0;
  visibility: hidden;
  overflow: hidden;
}

.scrolled .header .branding {
  border-color: color-mix(in srgb, var(--accent-color), transparent 80%);
}

/* Global Header on Scroll
------------------------------*/
.scrolled .header {
  --background-color: #0c0b09;
}

/*--------------------------------------------------------------
# Navigation Menu
--------------------------------------------------------------*/
/* Navmenu - Desktop */
@media (min-width: 1200px) {
  .navmenu {
    padding: 0;
  }

  .navmenu ul {
    margin: 0;
    padding: 0;
    display: flex;
    list-style: none;
    align-items: center;
  }

  .navmenu li {
    position: relative;
  }

  .navmenu a,
  .navmenu a:focus {
    color: var(--nav-color);
    padding: 18px 15px;
    font-size: 14px;
    font-family: var(--nav-font);
    font-weight: 400;
    display: flex;
    align-items: center;
    justify-content: space-between;
    white-space: nowrap;
    transition: 0.3s;
  }

  .navmenu a i,
  .navmenu a:focus i {
    font-size: 12px;
    line-height: 0;
    margin-left: 5px;
    transition: 0.3s;
  }

  .navmenu li:last-child a {
    padding-right: 0;
  }

  .navmenu li:hover>a,
  .navmenu .active,
  .navmenu .active:focus {
    color: var(--nav-hover-color);
  }


/* Navmenu - Mobile */
@media (max-width: 1199px) {
  .mobile-nav-toggle {
    color: var(--nav-color);
    font-size: 28px;
    line-height: 0;
    margin-right: 10px;
    cursor: pointer;
    transition: color 0.3s;
  }

  .navmenu {
    padding: 0;
    z-index: 9997;
  }

  .navmenu ul {
    display: none;
    list-style: none;
    position: absolute;
    inset: 60px 20px 20px 20px;
    padding: 10px 0;
    margin: 0;
    border-radius: 6px;
    background-color: var(--nav-mobile-background-color);
    border: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
    overflow-y: auto;
    transition: 0.3s;
    z-index: 9998;
  }

  .navmenu a,
  .navmenu a:focus {
    color: var(--nav-dropdown-color);
    padding: 10px 20px;
    font-family: var(--nav-font);
    font-size: 17px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: space-between;
    white-space: nowrap;
    transition: 0.3s;
  }

  .navmenu a i,
  .navmenu a:focus i {
    font-size: 12px;
    line-height: 0;
    margin-left: 5px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: 0.3s;
    background-color: color-mix(in srgb, var(--accent-color), transparent 90%);
  }

  .navmenu a i:hover,
  .navmenu a:focus i:hover {
    background-color: var(--accent-color);
    color: var(--contrast-color);
  }

  .navmenu a:hover,
  .navmenu .active,
  .navmenu .active:focus {
    color: var(--nav-dropdown-hover-color);
  }

  .navmenu .active i,
  .navmenu .active:focus i {
    background-color: var(--accent-color);
    color: var(--contrast-color);
    transform: rotate(180deg);
  }

  .mobile-nav-active {
    overflow: hidden;
  }

  .mobile-nav-active .mobile-nav-toggle {
    color: #fff;
    position: absolute;
    font-size: 32px;
    top: 15px;
    right: 15px;
    margin-right: 5;
    z-index: 9999;
  }

  .mobile-nav-active .navmenu {
    position: fixed;
    overflow: hidden;
    inset: 0;
    background: rgba(33, 37, 41, 0.8);
    transition: 0.3s;
  }

  .mobile-nav-active .navmenu>ul {
    display: block;
  }
}
.navmenu ul li a[href="table_reservation.php"],
.navmenu ul li a[href="login.php"] {
  border: 1px solid var(--accent-color);
  border-radius: 50px;
  padding: 6px 16px;
  text-transform: uppercase;
  margin-left: 8px;
  font-size: 14px;
}

.navmenu ul li a[href="table_reservation.php"]:hover,
.navmenu ul li a[href="login.php"]:hover {
  background-color: var(--accent-color);
  color: var(--default-color);
}


/*--------------------------------------------------------------
# Global Footer
--------------------------------------------------------------*/
.footer {
  color: var(--default-color);
  background-color: var(--background-color);
  font-size: 14px;
  padding-bottom: 50px;
  position: relative;
}

.footer .footer-top {
  padding-top: 50px;
  border-top: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
}

.footer .footer-about .logo {
  line-height: 1;
  margin-bottom: 25px;
}

.footer .footer-about .logo img {
  max-height: 40px;
  margin-right: 6px;
}

.footer .footer-about .logo span {
  font-size: 26px;
  font-weight: 700;
  letter-spacing: 1px;
  font-family: var(--heading-font);
  color: var(--heading-color);
}

.footer .footer-about p {
  font-size: 14px;
  font-family: var(--heading-font);
}
.footer .social-links a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1px solid color-mix(in srgb, var(--default-color), transparent 50%);
  font-size: 16px;
  color: color-mix(in srgb, var(--default-color), transparent 30%);
  margin-right: 10px;
  transition: 0.3s;
}

.footer .social-links a:hover {
  color: var(--accent-color);
  border-color: var(--accent-color);
}

.footer h4 {
  font-size: 16px;
  font-weight: bold;
  position: relative;
  padding-bottom: 12px;
}

.footer .footer-links {
  margin-bottom: 30px;
}

.footer .footer-links ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer .footer-links ul i {
  padding-right: 2px;
  font-size: 12px;
  line-height: 0;
}

.footer .footer-links ul li {
  padding: 10px 0;
  display: flex;
  align-items: center;
}

.footer .footer-links ul li:first-child {
  padding-top: 0;
}

.footer .footer-links ul a {
  color: color-mix(in srgb, var(--default-color), transparent 30%);
  display: inline-block;
  line-height: 1;
}

.footer .footer-links ul a:hover {
  color: var(--accent-color);
}

.footer .copyright {
  padding-top: 25px;
  padding-bottom: 25px;
  border-top: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
}

.footer .copyright p {
  margin-bottom: 0;
}

.footer .credits {
  margin-top: 6px;
  font-size: 13px;
}

/*--------------------------------------------------------------
# Preloader
--------------------------------------------------------------*/
#preloader {
  position: fixed;
  inset: 0;
  z-index: 999999;
  overflow: hidden;
  background: var(--background-color);
  transition: all 0.6s ease-out;
}

#preloader:before {
  content: "";
  position: fixed;
  top: calc(50% - 30px);
  left: calc(50% - 30px);
  border: 6px solid #ffffff;
  border-color: var(--accent-color) transparent var(--accent-color) transparent;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  animation: animate-preloader 1.5s linear infinite;
}

@keyframes animate-preloader {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

/*--------------------------------------------------------------
# Scroll Top Button
--------------------------------------------------------------*/
.scroll-top {
  position: fixed;
  visibility: hidden;
  opacity: 0;
  right: 15px;
  bottom: 15px;
  z-index: 99999;
  background-color: var(--accent-color);
  width: 30px;
  height: 30px;
  border-radius: 4px;
  transition: all 0.4s;
}

.scroll-top i {
  font-size: 24px;
  color: var(--contrast-color);
  line-height: 0;
}

.scroll-top:hover {
  background-color: color-mix(in srgb, var(--accent-color), transparent 20%);
  color: var(--contrast-color);
}

.scroll-top.active {
  visibility: visible;
  opacity: 0.5;
}

/*--------------------------------------------------------------
# Disable aos animation delay on mobile devices
--------------------------------------------------------------*/
@media screen and (max-width: 768px) {
  [data-aos-delay] {
    transition-delay: 0 !important;
  }
}

/*--------------------------------------------------------------
# Global Page Titles & Breadcrumbs
--------------------------------------------------------------*/
.page-title {
  color: var(--default-color);
  background-color: var(--background-color);
  position: relative;
  padding: 200px 0 120px 0; /* Increased padding to make section taller */
  text-align: center;
  overflow: hidden;
  height: 100vh; /* Make the section full viewport height */
  min-height: 800px; /* Set a minimum height */
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-title:before {
  content: "";
  background-color: color-mix(in srgb, var(--background-color), transparent 30%);
  position: absolute;
  inset: 0;
  z-index: 1;
}

.page-title h1 {
  font-size: 42px;
  font-weight: 700;
  margin-bottom: 10px;
  position: relative;
  z-index: 2;
}

.page-title h1 .resto-name {
  color: var(--accent-color);
}

.page-title video {
  position: absolute;
  top: 50%;
  left: 50%;
  min-width: 100%;
  min-height: 100%;
  width: auto;
  height: auto;
  transform: translateX(-50%) translateY(-50%);
  z-index: 0;
  object-fit: medium;
}

/* Page Title Buttons */
.page-title-buttons {
  position: relative;
  z-index: 2;
  margin-top: 30px;
}

.btn-our-menu,
.btn-order-now {
  color: var(--default-color);
  border: 1px solid var(--accent-color);
  text-transform: uppercase;
  font-size: 14px;
  padding: 6px 24px;
  border-radius: 50px;
  transition: 0.3s;
  font-family: var(--nav-font);
  font-weight: 400;
  display: inline-block;
  text-align: center;
}

.btn-our-menu:hover,
.btn-order-now:hover {
  color: var(--default-color);
  background: var(--accent-color);
  text-decoration: none;
}


@media (max-width: 480px) {
  .btn-our-menu,
  .btn-book-a-table {
    font-size: 12px;
    padding: 6px 18px;
  }
}

/*--------------------------------------------------------------
# Global Sections
--------------------------------------------------------------*/
section,
.section {
  color: var(--default-color);
  background-color: var(--background-color);
  padding: 60px 0;
  scroll-margin-top: 77px;
  overflow: clip;
}

@media (max-width: 1199px) {

  section,
  .section {
    scroll-margin-top: 60px;
  }
}

/*--------------------------------------------------------------
# Hero Section
--------------------------------------------------------------*/
.hero {
  width: 100%;
  min-height: 100vh;
  position: relative;
  padding: 80px 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--default-color);
}

.hero img {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 1;
}

.hero:before {
  content: "";
  background: color-mix(in srgb, var(--background-color), transparent 50%);
  position: absolute;
  inset: 0;
  z-index: 2;
}

.hero .container {
  position: relative;
  z-index: 3;
}

.hero h2 {
  margin: 0;
  font-size: 48px;
  font-weight: 700;
}

.hero h2 span {
  color: var(--accent-color);
}

.hero p {
  color: color-mix(in srgb, var(--default-color), transparent 20%);
  margin: 10px 0 0 0;
  font-size: 24px;
}

.hero .cta-btn {
  color: var(--default-color);
  border: 2px solid var(--accent-color);
  font-weight: 400;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 1px;
  display: inline-block;
  padding: 8px 30px;
  border-radius: 50px;
  transition: 0.3s;
  flex-shrink: 0;
}

.hero .cta-btn:first-child {
  margin-right: 10px;
}

.hero .cta-btn:hover {
  background: color-mix(in srgb, var(--accent-color), transparent 20%);
}

@media (max-width: 480px) {
  .hero .cta-btn {
    font-size: 12px;
  }
}

@media (max-width: 768px) {
  .hero h2 {
    font-size: 32px;
  }

  .hero p {
    font-size: 18px;
  }
}

/*--------------------------------------------------------------
# Today's Special Section - Compact Version
--------------------------------------------------------------*/
.todays-special {
  position: relative;
  padding: 60px 0;
  background: linear-gradient(135deg, #0c0b09 0%, #1a1814 100%);
  overflow: hidden;
}

.todays-special::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url('') repeat;
  opacity: 0.05;
  pointer-events: none;
}

.special-header {
  text-align: center;
  margin-bottom: 40px;
  position: relative;
}

.special-header h2 {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 15px;
  background: linear-gradient(to right, #cda45e, #f8e5b5, #cda45e);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  background-size: 200% auto;
  animation: shine 3s linear infinite;
  position: relative;
  display: inline-block;
}

.special-header h2::after {
  content: "";
  position: absolute;
  bottom: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 2px;
  background: linear-gradient(to right, transparent, #cda45e, transparent);
}

.special-header p {
  font-size: 1rem;
  color: white;
  max-width: 600px;
  margin: 0 auto;
}

@keyframes shine {
  0% {
    background-position: 0% center;
  }
  100% {
    background-position: 200% center;
  }
}

.special-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr); /* 3 columns */
  gap: 30px; /* Increased gap for better spacing */
  max-width: 1200px;
  margin: 0 auto;
}

.special-item {
  position: relative;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
  background: #1a1814;
  border: 1px solid rgba(205, 164, 94, 0.2);
  transform: translateY(0) scale(1);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.special-item:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
  border-color: rgba(205, 164, 94, 0.5);
}

.special-item.featured {
  grid-column: span 1;
  display: flex;
  flex-direction: column;
}

.special-item.featured .special-image {
  min-height: 200px;
}

.special-image {
  height: 200px; /* Fixed height for uniform images */
  position: relative;
  overflow: hidden;
}

.special-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.special-item:hover .special-image img {
  transform: scale(1.05);
}

.special-content {
  padding: 20px; /* Increased padding */
  position: relative;
}

.special-price {
  position: absolute;
  top: -18px;
  right: 15px;
  background: whitesmoke;
  color: #0c0b09;
  font-weight: bold;
  padding: 3px 12px;
  border-radius: 20px;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.special-item:hover .special-price {
  transform: scale(1.1);
  background-color: var(--accent-color);
  color: var(--contrast-color);
}

.special-title {
  font-size: 1.2rem;
  margin-bottom: 8px;
  color: white;
  position: relative;
  padding-bottom: 8px;
}

.special-title::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 40px;
  height: 2px;
  background: #cda45e;
  transition: width 0.3s ease;
}

.special-item:hover .special-title::after {
  width: 60px;
}

.special-description {
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 12px;
  font-size: 0.85rem;
  line-height: 1.4;
  transition: color 0.3s ease;
}

.special-item:hover .special-description {
  color: rgba(255, 255, 255, 0.9);
}

.special-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}

.special-tag {
  background: rgba(205, 164, 94, 0.1);
  color: whitesmoke;
  padding: 4px 8px;
  border-radius: 15px;
  font-size: 0.8rem;
  border: 1px solid rgba(205, 164, 94, 0.3);
  transition: all 0.3s ease;
}

.special-item:hover .special-tag {
  background: rgba(205, 164, 94, 0.3);
  border-color: var(--accent-color);
}

.special-button {
  display: inline-block;
  padding: 6px 15px;
  background: transparent;
  color: whitesmoke;
  border: 1px solid #cda45e;
  border-radius: 20px;
  font-size: 0.8rem;
  transition: all 0.2s ease;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  position: relative;
  overflow: hidden;
}

.special-button:hover {
  background: var(--accent-color);
  color: var(--contrast-color);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(205, 164, 94, 0.3);
}

/* Ripple effect for button */
.special-button:after {
  content: "";
  position: absolute;
  top: 50%;
  left: 50%;
  width: 5px;
  height: 5px;
  background: rgba(255, 255, 255, 0.5);
  opacity: 0;
  border-radius: 100%;
  transform: scale(1, 1) translate(-50%);
  transform-origin: 50% 50%;
}

.special-button:focus:not(:active)::after {
  animation: ripple 0.6s ease-out;
}

@keyframes ripple {
  0% {
    transform: scale(0, 0);
    opacity: 0.5;
  }
  100% {
    transform: scale(20, 20);
    opacity: 0;
  }
}

.special-rating {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.special-rating .stars {
  color: #cda45e;
  margin-right: 8px;
  font-size: 0.9rem;
}

.special-rating .reviews {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.75rem;
  transition: color 0.3s ease;
}

.special-item:hover .special-rating .reviews {
  color: rgba(255, 255, 255, 0.8);
}

/* Featured Item Adjustments */
.featured .special-badge {
  top: 15px;
  right: 15px;
  font-size: 0.7rem;
  padding: 5px 15px;
}

.featured .special-price {
  top: 15px;
  right: 15px;
  font-size: 1.1rem;
  padding: 10px 20px;
}

.featured .special-title {
  font-size: 1.4rem;
}

.featured .special-description {
  font-size: 0.9rem;
}

.featured .special-button {
  padding: 8px 20px;
  font-size: 0.9rem;
}

/* Hover Effects */
.special-item::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, rgba(205, 164, 94, 0.1) 0%, rgba(205, 164, 94, 0) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
  z-index: 1;
}

.special-item:hover::before {
  opacity: 1;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
  .special-grid {
    grid-template-columns: repeat(2, 1fr); /* 2 columns on medium screens */
  }
}

@media (max-width: 768px) {
  .special-header h2 {
    font-size: 2rem;
  }
  
  .special-header p {
    font-size: 0.9rem;
  }
  
  .special-grid {
    grid-template-columns: 1fr; /* 1 column on small screens */
  }
  
  .special-image {
    height: 180px;
  }
  
  .special-item:hover {
    transform: translateY(-5px);
  }
}

/* Image Modal */
.image-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(12, 11, 9, 0.9);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.image-modal.active {
  display: flex;
  opacity: 1;
}

.modal-content {
  position: relative;
  max-width: 80%;
  max-height: 80%;
  animation: zoomIn 0.3s ease forwards;
}

@keyframes zoomIn {
  from {
    transform: scale(0.5);
  }
  to {
    transform: scale(1);
  }
}

.modal-content img {
  max-width: 100%;
  max-height: 80vh;
  border: 2px solid var(--accent-color);
  border-radius: 5px;
}

.close-modal {
  position: absolute;
  top: -30px;
  right: -30px;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  transition: color 0.3s ease;
}

.close-modal:hover {
  color: var(--accent-color);
}

/* Pulse animation for special items */
@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(205, 164, 94, 0.4);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(205, 164, 94, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(205, 164, 94, 0);
  }
}

.special-item:hover {
  animation: pulse 1.5s infinite;
}

  </style>

</head>

<body class="starter-page-page">

  <header id="header" class="header fixed-top">

    <div class="branding d-flex align-items-cente">

      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="homepage.php" class="logo d-flex align-items-center me-auto me-xl-0">
          <!-- Uncomment the line below if you also wish to use an image logo -->
          <img src="img.png" alt="">
          <h1 class="sitename">Westley's Resto Cafe</h1>
        </a>

        <nav id="navmenu" class="navmenu">
        <ul class="ms-auto">
  <li><a href="homepage.php">Home</a></li>
  <li><a href="about.html">About</a></li>
  <li><a href="menu.php">Menu</a></li>
  <li><a href="#specials">Gallery</a></li>
  <li><a href="contact.php">Contact</a></li>
  <li><a href="table_reservation.php">Book Table</a></li>
  <li><a href="login.php">Login</a></li>
</ul>

<i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
</nav>

    </div>

  </header>

  <main class="main">

    <!-- Page Title -->
<div class="page-title position-relative" data-aos="fade">
  <video autoplay muted loop id="pageTitleVideo">
    <source src="VIDEO1.mp4" type="video/mp4">
    Your browser does not support HTML5 video.
  </video>
  <div class="container position-relative">
    <h1>Welcome to <span class="resto-name">Westley's Resto Cafe</span> <br></h1>
    
    <div class="page-title-buttons d-flex justify-content-center gap-3 mt-4">

      <a href="menu.php" class="btn-our-menu">Our Menu</a>

      <a href="table_reservation.php" class="btn-order-now">Order Now</a>
    </div>
  </div>
</div><!-- End Page Title -->

    <!-- Today's Special Section -->
    <section id="todays-special" class="todays-special section">
      <div class="container">
        <div class="special-header">
          <h2>Today's Specials!</h2>
          <p>Chef's carefully curated selection of the finest dishes, prepared with seasonal ingredients.</p>
        </div>
        
        <div class="special-grid">
          <!-- Row 1 -->
          <!-- Special Item 1 -->
          <div class="special-item" data-aos="fade-up" data-aos-delay="200">
            <div class="special-image">
              <img src="hero-bg.jpg" alt="Savory Spice Fusion">
            </div>
            <div class="special-content">
              <div class="special-price">₹190</div>
              <h3 class="special-title">Savory Spice Fusion</h3>
              <div class="special-rating">
                <div class="stars">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star"></i>
                </div>
                <div class="reviews">(50 reviews)</div>
              </div>
              <p class="special-description">A harmonious blend of exotic spices with premium cuts of meat, slow-cooked to perfection.</p>
              <div class="special-tags">
                <span class="special-tag">Spicy</span>
                <span class="special-tag">Noodles</span>
                <span class="special-tag">Seasonal</span>
              </div>
              <a href="#order" class="special-button">Order Now</a>
            </div>
          </div>
            
          <!-- Special Item 2 -->
          <div class="special-item" data-aos="fade-up" data-aos-delay="300">
            <div class="special-image">
              <img src="r3.jpg" alt="Blazing Inferno Noodles">
            </div>
            <div class="special-content">
              <div class="special-price">₹280</div>
              <h3 class="special-title">Blazing Inferno Noodles</h3>
              <div class="special-rating">
                <div class="stars">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star"></i>
                </div>
                <div class="reviews">(36 reviews)</div>
              </div>
              <p class="special-description">Hand-pulled noodles tossed with our fiery chili oil blend and fresh vegetables.</p>
              <div class="special-tags">
                <span class="special-tag">Spicy</span>
                <span class="special-tag">Chef's Special</span>
              </div>
              <a href="#order" class="special-button">Order Now</a>
            </div>
          </div>

          <!-- Special Item 3 -->
          <div class="special-item" data-aos="fade-up" data-aos-delay="400">
            <div class="special-image">
              <img src="r7.jpg" alt="Golden Crispy Tempura Platter">
            </div>
            <div class="special-content">
              <div class="special-price">₹320</div>
              <h3 class="special-title">Golden Crispy Tempura Platter</h3>
              <div class="special-rating">
                <div class="stars">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                </div>
                <div class="reviews">(58 reviews)</div>
              </div>
              <p class="special-description">Light and crispy assorted tempura featuring shrimp, vegetables, and lotus root with dipping sauce.</p>
              <div class="special-tags">
                <span class="special-tag">Crispy</span>
                <span class="special-tag">Japanese</span>
              </div>
              <a href="#order" class="special-button">Order Now</a>
            </div>
          </div>
          
          <!-- Row 2 -->
          <!-- Special Item 4 -->
          <div class="special-item" data-aos="fade-up" data-aos-delay="500">
            <div class="special-image">
              <img src="r4.jpg" alt="Molten Midnight Souffle">
            </div>
            <div class="special-content">
              <div class="special-price">₹280</div>
              <h3 class="special-title">Molten Midnight Souffle</h3>
              <div class="special-rating">
                <div class="stars">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-half"></i>
                </div>
                <div class="reviews">(42 reviews)</div>
              </div>
              <p class="special-description">Dark chocolate soufflé with a molten center, served with vanilla ice cream.</p>
              <div class="special-tags">
                <span class="special-tag">Dessert</span>
                <span class="special-tag">Chocolate</span>
              </div>
              <a href="#order" class="special-button">Order Now</a>
            </div>
          </div>
          
          <!-- Special Item 5 -->
          <div class="special-item" data-aos="fade-up" data-aos-delay="600">
            <div class="special-image">
              <img src="r8.jpg" alt="Tandoori Harvest Platter">
            </div>
            <div class="special-content">
              <div class="special-price">₹250</div>
              <h3 class="special-title">Tandoori Harvest Platter</h3>
              <div class="special-rating">
                <div class="stars">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star"></i>
                </div>
                <div class="reviews">(29 reviews)</div>
              </div>
              <p class="special-description">Assorted seasonal vegetables marinated in aromatic spices and cooked in traditional clay oven.</p>
              <div class="special-tags">
                <span class="special-tag">Vegetarian</span>
                <span class="special-tag">Tandoori</span>
              </div>
              <a href="#order" class="special-button">Order Now</a>
            </div>
          </div>

          <!-- Special Item 6 -->
          <div class="special-item" data-aos="fade-up" data-aos-delay="700">
            <div class="special-image">
              <img src="r2.jpg" alt="Sunny Berry Cloud Waffles">
            </div>
            <div class="special-content">
              <div class="special-price">₹350</div>
              <h3 class="special-title">Sunny Berry Cloud Waffles</h3>
              <div class="special-rating">
                <div class="stars">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                </div>
                <div class="reviews">(29 reviews)</div>
              </div>
              <p class="special-description">Light as air waffles topped with fresh seasonal berries and whipped cream.</p>
              <div class="special-tags">
                <span class="special-tag">Dessert</span>
                <span class="special-tag">Vegetarian</span>
              </div>
              <a href="#order" class="special-button">Order Now</a>
            </div>
          </div>
        </div>
      </div>
    </section><!-- End Today's Special Section -->

  </main>

  <footer id="footer" class="footer">

    <div class="container footer-top">
      <div class="row gy-4">
      <div class="col-12 footer-about text-center">
  <a href="homepage.html" class="logo d-inline-flex align-items-center justify-content-center">
    <span class="sitename">Westley's Resto Cafe</span>
  </a>
  <div class="footer-contact pt-3">
    <p>Metro Pillar 481, Ground Floor, Kaliyath Building, Palarivattom</p>
    <p>Edappally Rd, opp. to ARC Fertility Clinic, Mamangalam, Kochi, Kerala 682025</p>
    <p class="mt-3"><strong>Phone:</strong> <span>+1 5589 55488 55</span></p>
    <p><strong>Email:</strong> <span>Westley'sC11@example.com</span></p>
  </div>
  <div class="social-links d-flex justify-content-center mt-4">
    <a href=""><i class="bi bi-twitter-x"></i></a>
    <a href=""><i class="bi bi-facebook"></i></a>
    <a href=""><i class="bi bi-instagram"></i></a>
    <a href=""><i class="bi bi-linkedin"></i></a>
  </div>
</div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename"></strong>Westley's Resto Cafe<span>All Rights Reserved</span></p>
      <div class="credits">
        <!-- All the links in the footer should remain intact. -->
        <!-- You can delete the links only if you've purchased the pro version. -->
        <!-- Licensing information: https://bootstrapmade.com/license/ -->
        <!-- Purchase the pro version with working PHP/AJAX contact form: [buy-url] -->
        
      </div>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>


  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
    <script>
  // Image modal functionality
    document.addEventListener('DOMContentLoaded', function() {
      const galleryItems = document.querySelectorAll('.special-item');
      const modal = document.getElementById('imageModal');
      const modalImg = document.getElementById('modalImage');
      const closeModal = document.querySelector('.close-modal');
      
      // Open modal when gallery item is clicked
      galleryItems.forEach(item => {
        const image = item.querySelector('.special-image img');
        image.addEventListener('click', function(e) {
          e.stopPropagation();
          modalImg.src = this.src;
          modal.classList.add('active');
          document.body.style.overflow = 'hidden';
        });
      });
      
      // Close modal
      closeModal.addEventListener('click', function() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      });
      
      // Close when clicking outside image
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          modal.classList.remove('active');
          document.body.style.overflow = '';
        }
      });
      
      // Enhanced hover effects for special items
      const specialItems = document.querySelectorAll('.special-item');
      
      specialItems.forEach(item => {
        // Add mouseenter event
        item.addEventListener('mouseenter', function() {
          this.style.transition = 'all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1)';
          this.style.transform = 'translateY(-10px) scale(1.02)';
          this.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.3)';
          
          // Add pulse animation
          this.style.animation = 'pulse 1.5s infinite';
          
          // Highlight the order button
          const button = this.querySelector('.special-button');
          if (button) {
            button.style.transform = 'translateY(-2px)';
            button.style.boxShadow = '0 4px 8px rgba(205, 164, 94, 0.3)';
          }
        });
        
        // Add mouseleave event
        item.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0) scale(1)';
          this.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.2)';
          this.style.animation = 'none';
          
          // Reset button
          const button = this.querySelector('.special-button');
          if (button) {
            button.style.transform = '';
            button.style.boxShadow = '';
          }
        });
        
        // Add click effect to order button
        const button = item.querySelector('.special-button');
        if (button) {
          button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Ripple effect
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            this.appendChild(ripple);
            
            // Remove ripple after animation
            setTimeout(() => {
              ripple.remove();
            }, 600);
            
            // Simulate order action
            console.log('Order placed for: ' + item.querySelector('.special-title').textContent);
            
            // You can replace this with actual order functionality
            alert('Added to cart: ' + item.querySelector('.special-title').textContent);
          });
        }
      });
      
      // Animate special items on scroll
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = 1;
            entry.target.style.transform = 'translateY(0)';
          }
        });
      }, { threshold: 0.1 });
      
      document.querySelectorAll('.special-item').forEach(item => {
        item.style.opacity = 0;
        item.style.transform = 'translateY(30px)';
        item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(item);
      });
    });
  </script>
</body>
</html>