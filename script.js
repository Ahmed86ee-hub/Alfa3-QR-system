document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;

    // استعادة الثيم من localStorage
    const currentTheme = localStorage.getItem('theme') || 'light';
    htmlElement.setAttribute('data-theme', currentTheme);
    updateThemeButtonText(currentTheme);

    // تبديل الثيم
    themeToggleBtn.addEventListener('click', () => {
        let theme = htmlElement.getAttribute('data-theme');
        let newTheme = theme === 'light' ? 'dark' : 'light';
        htmlElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeButtonText(newTheme);
    });

    function updateThemeButtonText(theme) {
        themeToggleBtn.innerText = theme === 'dark' ? '☀️ وضع نهاري' : '🌙 وضع ليلي';
    }
});

// قسم المعاينة الحية
let qrcodePreview = null;

function generatePreview() {
    const textInput = document.getElementById("qrText").value;
    const previewBox = document.getElementById("qrPreviewBox");

    if (!textInput) {
        previewBox.innerHTML = "";
        qrcodePreview = null;
        return;
    }

    if (!qrcodePreview) {
        qrcodePreview = new QRCode(previewBox, {
            text: textInput,
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    } else {
        qrcodePreview.makeCode(textInput);
    }
}

// دالة لنقل النص المكتوب إلى النموذج المخفي لإرساله لملف PHP
function syncData() {
    const textInput = document.getElementById("qrText").value;
    document.getElementById("finalData").value = textInput || 'https://your-system.com';
}