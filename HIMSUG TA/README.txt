HIMSOG TA! — USTP SCHOOL CLINIC WEBSITE PROTOTYPE

PAGES INCLUDED
- index.html: Home page.
- services.html: Our Services page with five service cards and detail pop-ups.
- css/style.css: Shared styles for both pages.
- js/home.js: Home page mobile navigation.
- js/services.js: Service pop-ups, service search, and mobile navigation.

HOW TO OPEN IN VS CODE
1. Extract the ZIP file.
2. Open Visual Studio Code.
3. Select File > Open Folder and choose this extracted folder.
4. Open index.html.
5. Right-click and choose "Open with Live Server" if you have the Live Server
   extension installed. You can also open index.html directly in a browser.
6. Use the navigation menu to move between Home and Our Services.

IMPORTANT CONTENT NOTE
The five service names were supplied by the user. Descriptions and requirements
are general draft content, not verified USTP School Clinic policies. Confirm
service details, requirements, availability, clinic hours, and contact details
with the clinic before publishing.

OTHER NAVIGATION LINKS
About Us, Clinic Information, Health & Wellness, and Appointments are planned
pages and will be built in later steps. Their links are placeholders for now.
This is a front-end prototype and is not connected to a live clinic system or
database.

ABOUT PAGE UPDATE
- Added about.html for KAABAG and HIMSOG TA!.
- The page explains the platform purpose, value proposition, survey findings, and proposed project roadmap.
- The survey percentages and project stages are based on the KAABAG presentation.
- The website is labeled as a proposed concept; clinic details and operations must be verified with USTP personnel.


KAABAG LOGO UPDATE
- Added the supplied KAABAG company logo at images/kaabag-logo.jpg.
- Updated the header and footer branding on the Home, About Us, and Our Services pages.
- The company name KAABAG is now shown prominently, with HIMSOG TA! identified as the platform.


CLINIC INFORMATION PAGE UPDATE
- Added clinic-info.html with location, operating hours, contact status, visit guidelines, walk-in and appointment information, announcements, personnel placeholder, FAQs, and emergency guidance.
- Clinic location: inside USTP campus, near Building 25 (user-provided; exact details to be confirmed).
- Operating hours: 8:00 AM–5:00 PM (explicitly marked to be verified).
- Official clinic contact details: none provided; no contact number, email, or social account invented.
- Walk-ins and appointments: both allowed, based on user-provided information.
- Suggested guidelines and emergency wording are not official clinic policy and should be confirmed before publication.


HEALTH & WELLNESS AND APPOINTMENTS UPDATE
- Added wellness.html with general student wellness topics and health information disclaimer.
- Added appointments.php with a student appointment request demonstration form.
- Appointment requests are saved only in the current browser using localStorage. No data is transmitted to USTP or a server.
- Requests receive a Pending demo status. This is not a live booking system and does not notify clinic personnel.
- For a real deployment, connect a secure backend/database, authentication, staff/admin dashboard, and clinic-approved workflow. Protect student personal and health information.


STUDENT ACCOUNT AND ADMIN DASHBOARD UPDATE
- Added login.html, register.html, and admin.html.
- Added js/auth.js for browser-only demo registration/login and js/admin.js for viewing/updating demo appointment statuses.
- Demo accounts and appointment requests use browser localStorage/sessionStorage. They are not secure, are not shared between devices/browsers, and are not connected to USTP systems.
- No production credentials are included. Do not use real student credentials or sensitive personal/health information.
- Before real deployment, implement server-side authentication, password hashing, role-based admin access, secure database storage, privacy controls, and clinic-approved workflows.
