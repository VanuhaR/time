// public/js/menu-toggle.js
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('toggleSidebar');

  if (!toggleBtn) {
    console.warn('❌ Кнопка #toggleSidebar не найдена. Проверьте HTML.');
    return;
  }

  if (!sidebar) {
    console.warn('❌ Элемент #sidebar не найден.');
    return;
  }

  // Проверяем сохранённое состояние
  const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

  // Применяем состояние
  if (isCollapsed) {
    sidebar.classList.add('collapsed');
    // Меняем иконку: поворачиваем стрелку
    const icon = toggleBtn.querySelector('.toggle-icon');
    if (icon) {
      icon.setAttribute('transform', 'rotate(180 12 12)');
    }
  }

  // Обработчик клика
  toggleBtn.addEventListener('click', () => {
    const isNowCollapsed = sidebar.classList.contains('collapsed');
    sidebar.classList.toggle('collapsed');

    const icon = toggleBtn.querySelector('.toggle-icon');
    if (icon) {
      const angle = isNowCollapsed ? 'rotate(0 12 12)' : 'rotate(180 12 12)';
      icon.setAttribute('transform', angle);
    }

    // Сохраняем состояние
    localStorage.setItem('sidebarCollapsed', !isNowCollapsed);

    console.log('✅ Sidebar: ' + (!isNowCollapsed ? 'collapsed' : 'expanded'));
  });
});
