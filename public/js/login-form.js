// public/js/login-form.js

document.addEventListener('DOMContentLoaded', () => {
    const phoneInput = document.getElementById('phone');
    const rememberCheckbox = document.getElementById('rememberMe');

    // При загрузке — подставляем сохранённый телефон
    const savedPhone = localStorage.getItem('lastPhone');
    if (savedPhone) {
        phoneInput.value = savedPhone;
        rememberCheckbox.checked = true;
    }

    // При отправке — сохраняем телефон (если галочка стоит)
    document.getElementById('loginForm').addEventListener('submit', () => {
        if (rememberCheckbox.checked) {
            localStorage.setItem('lastPhone', phoneInput.value);
        } else {
            localStorage.removeItem('lastPhone');
        }
        // Никакого e.preventDefault() — форма отправляется напрямую
    });
});
