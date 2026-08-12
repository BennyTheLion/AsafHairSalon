<?php
// privacy.php - include this in any page
?>

<!-- 🛡️ Privacy Popup (hidden by default) -->
<div class="privacy-overlay" id="privacyOverlay" style="
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 99999;
    justify-content: center;
    align-items: center;
">
  <div class="privacy-popup" style="
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    max-width: 600px;
    width: 90%;
    max-height: 80%;
    overflow-y: auto;
    position: relative;
    font-family: Arial, sans-serif;
  ">
    <span class="close-btn" id="closePrivacy" style="
        position: absolute;
        top: 10px;
        right: 15px;
        cursor: pointer;
        font-weight: bold;
        color: #333;
    ">סגור X</span>

    <h2>מדיניות פרטיות</h2>
    <p><strong>עודכן לאחרונה:</strong> ‎12 בנובמבר 2025</p>

    <p>פרטיותך חשובה לנו. אנו מתחייבים לשמור ולהגן על המידע האישי שתשתף איתנו בהתאם לחוק הגנת הפרטיות ולתקנות הגנת המידע (GDPR).</p>

    <h3>איסוף מידע</h3>
    <p>בעת מילוי טפסים באתר אנו עשויים לאסוף שם, טלפון, דוא"ל ופרטים נוספים שתבחר למסור.</p>

    <h3>שימוש במידע</h3>
    <p>המידע משמש לצורך יצירת קשר, מתן הצעות מחיר ושיפור חוויית המשתמש. איננו משתפים מידע עם צדדים שלישיים, למעט אם נדרש על פי חוק.</p>

    <h3>אבטחת מידע</h3>
    <p>אנו נוקטים באמצעים מקובלים לשמירת סודיות המידע, אך לא נוכל להבטיח הגנה מוחלטת מפני חדירה למערכות.</p>

    <h3>זכויות המשתמש</h3>
    <p>תוכל לפנות אלינו בכל עת לעיון, תיקון או מחיקת הנתונים שלך.  
    פניות ניתן לשלוח לכתובת: 
    <a href="mailto:privacy@yourdomain.com">privacy@yourdomain.com</a></p>

    <h3>עוגיות (Cookies)</h3>
    <p>האתר משתמש בעוגיות לשיפור חוויית הגלישה. ניתן לחסום עוגיות דרך הגדרות הדפדפן.</p>

    <h3>שינויים במדיניות</h3>
    <p>אנו שומרים לעצמנו את הזכות לעדכן את מדיניות הפרטיות מעת לעת. העדכון האחרון יופיע בעמוד זה.</p>
  </div>
</div>
