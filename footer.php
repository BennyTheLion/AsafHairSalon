<?php
// footer.php
?>
 
  <footer>
        <div class="container">
            <!-- 3-column footer grid -->
            <div class="footer-grid">
                
                <!-- Column 1: About -->
                <div class="footer-col">
                    <h4>המספרה של אסף</h4>
                    <p>מספרת נשים מקצועית באור עקיבא — יופי שמתחיל בשיער. למעלה מ-10 שנות ניסיון עם אלפי לקוחות מרוצות.</p>
                    <p><strong>כתובת:</strong> בלפור 1, אור עקיבא</p>
                    <p><strong>טלפון:</strong> <a href="tel:+972506760501" style="display:inline;color:var(--accent-color);">050-676-0501</a></p>
                    <p style="margin-top:10px;"><strong>שעות פעילות:</strong></p>
                    <p>א', ג'–ה': 09:00–19:00</p>
                    <p>ו': 09:00–14:00</p>
                    <p>ב', שבת: סגור</p>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer-col">
                    <h4>ניווט מהיר</h4>
                    <a href="index.php#hero">ראשי</a>
                    <a href="index.php#about">אודות</a>
                    <a href="index.php#before-after">לפני / אחרי</a>
                    <a href="index.php#services">שירותים</a>
                    <a href="index.php#BookAppointment">קביעת תור</a>
                </div>

                <!-- Column 3: Legal -->
                <div class="footer-col">
                    <h4>משפטי</h4>
                    <a href="Legal/privacy-policy.html">מדיניות פרטיות</a>
                    <a href="Legal/terms-of-use.html">תנאי שימוש</a>
                    <a href="Legal/cookie-policy.html">מדיניות עוגיות</a>
                    <a href="Legal/accessibility-statement.html">הצהרת נגישות</a>
                    <a href="Legal/copyright.html">זכויות יוצרים</a>
                    <a href="Legal/booking-policy.html">מדיניות קביעת תורים</a>
                    <a href="Legal/photo-consent.html">הסכמת צילום</a>
                </div>

                <!-- Column 4: Contact & Social -->
                <div class="footer-col">
                    <h4>צרו קשר</h4>
                    <div class="footer-social">
                        <a href="https://wa.me/972506760501" target="_blank" rel="noopener" class="footer-icon-link" aria-label="WhatsApp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="https://www.facebook.com/" target="_blank" rel="noopener" class="footer-icon-link" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a href="https://www.instagram.com/" target="_blank" rel="noopener" class="footer-icon-link" aria-label="Instagram">
                            <i class="fab fa-instagram"></i> Instagram
                        </a>
                    </div>
                    <div class="footer-nav-btns">
                        <a href="https://waze.com/ul?q=בלפור 1 אור עקיבא" target="_blank" rel="noopener" class="footer-nav-btn">
                            <i class="fab fa-waze"></i> נווט עם Waze
                        </a>
                        <a href="https://maps.google.com/?q=בלפור 1 אור עקיבא" target="_blank" rel="noopener" class="footer-nav-btn">
                            <i class="fas fa-map-marker-alt"></i> נווט עם Google Maps
                        </a>
                    </div>
                </div>
            </div>

            <div class="copyright">© <?= date('Y') ?> המספרה של אסף. כל הזכויות שמורות.</div>
        </div>
        
    </footer>
    
<!-- Modern Cookie Banner -->
<div id="cookie-banner" style="
    display:flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    bottom: -100px; /* hidden initially */
    left: 50%;
    transform: translateX(-50%);
    max-width: 500px;
    width: 90%;
    background: #333;
    color: white;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
    box-shadow: 0 -4px 15px rgba(0,0,0,0.3);
    z-index: 9999;
    font-family: Arial;
    opacity: 0;
    transition: bottom 0.5s ease, opacity 0.5s ease;
    flex-wrap: wrap;
    gap: 10px;
">
    <span style="flex:1; font-size:14px; line-height:1.4;">
        אתר זה משתמש בקוקיז כדי לשפר את חוויית המשתמש. 
<a href="#" id="openPrivacyPolicy" style="color:#0073aa; text-decoration:underline;">למד עוד</a>
    </span>
    <button id="accept-cookies" style="
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        background: #c8a97e;
        color: white;
        cursor: pointer;
        font-weight: bold;
        flex-shrink:0;
    ">קבל</button>
</div>

<script>
// Helper: get cookie value
function getCookie(name) {
    const value = "; " + document.cookie;
    const parts = value.split("; " + name + "=");
    if (parts.length === 2) return parts.pop().split(";").shift();
}

// Show banner if cookie not set
window.addEventListener('load', function() {
    const cookieAccepted = getCookie('cookies_accepted');
    const banner = document.getElementById('cookie-banner');
    if (!cookieAccepted) {
        setTimeout(() => {
            banner.style.bottom = "0";
            banner.style.opacity = "1";
        }, 500);
    }
});

// Accept cookies
document.getElementById('accept-cookies').addEventListener('click', function() {
    const d = new Date();
    d.setTime(d.getTime() + (365*24*60*60*1000)); // 1 year
    document.cookie = "cookies_accepted=true; expires=" + d.toUTCString() + "; path=/";
    const banner = document.getElementById('cookie-banner');
    banner.style.bottom = "-150px"; // slide down
    banner.style.opacity = "0";
});
</script>

</body>
</html>
