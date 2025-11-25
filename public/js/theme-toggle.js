document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('themeToggle');
    const body = document.body;

    // Проверяем куки
    const isDark = document.cookie
        .split('; ')
        .find(row => row.startsWith('theme='))
        ?.split('=')[1] === 'dark';

    if (isDark) {
        body.classList.add('dark-mode');
    }

    toggle?.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        const mode = body.classList.contains('dark-mode') ? 'dark' : 'light';
        // Сохраняем на 30 дней
        document.cookie = `theme=${mode}; path=/; max-age=${30 * 24 * 60 * 60}`;
    });
});
