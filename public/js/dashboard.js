// public/js/dashboard.js
// Полная загрузка данных для личного кабинета
console.log('✅ dashboard.js: загружен и запускается');

// === Глобальные переменные (должны быть определены в dashboard.php) ===
console.log('🔍 CURRENT_USER_ID:', typeof CURRENT_USER_ID !== 'undefined' ? CURRENT_USER_ID : 'undefined');
console.log('🔍 CURRENT_YEAR:', typeof CURRENT_YEAR !== 'undefined' ? CURRENT_YEAR : 'undefined');
console.log('🔍 CURRENT_MONTH:', typeof CURRENT_MONTH !== 'undefined' ? CURRENT_MONTH : 'undefined');

// === Проверка переменных ===
if (typeof CURRENT_USER_ID === 'undefined') {
  console.warn('⚠️ ОШИБКА: CURRENT_USER_ID не определён — проверь dashboard.php');
}
if (typeof CURRENT_YEAR === 'undefined') {
  console.warn('⚠️ ОШИБКА: CURRENT_YEAR не определён');
}
if (typeof CURRENT_MONTH === 'undefined') {
  console.warn('⚠️ ОШИБКА: CURRENT_MONTH не определён');
}

// === Запуск при загрузке страницы ===
document.addEventListener('DOMContentLoaded', function () {
  console.log('✅ DOM загружен, старт загрузки данных');

  // Просто запускаем — без ожидания вкладок
  if (typeof loadEmployeeData === 'function') {
    loadEmployeeData();
  } else {
    console.error('❌ Функция loadEmployeeData не найдена');
  }
});

// === Основная функция загрузки данных ===
async function loadEmployeeData() {
  console.log('🔄 Загрузка данных сотрудника...', CURRENT_USER_ID);

  if (typeof CURRENT_USER_ID === 'undefined') {
    console.warn('⚠️ CURRENT_USER_ID не определён — остановка загрузки');
    return;
  }

  try {
    // 1. Профиль
    console.log('➡️ Загрузка профиля...');
    const profile = await fetchJson(`/public/api/employees.php?action=get&id=${CURRENT_USER_ID}`);
    console.log('✅ Профиль получен:', profile);
    updateProfileCard(profile);

    // 2. Оклад
    console.log('➡️ Загрузка оклада...');
    const payroll = await fetchJson(
      `/public/api/calculate_payroll.php?employee_id=${CURRENT_USER_ID}&year=${CURRENT_YEAR}&month=${CURRENT_MONTH}`
    );
    if (payroll.success) {
      console.log('✅ Оклад получен:', payroll);
      updatePayrollCard(payroll);
    } else {
      console.warn('⚠️ Оклад: ошибка в ответе', payroll);
      setElementText('salary-total', 'Ошибка загрузки');
      document.getElementById('salary-breakdown')?.setAttribute('title', payroll.message || 'Неизвестная ошибка');
    }

    // 3. График
    console.log('➡️ Загрузка графика...');
    const scheduleData = await fetchJson(
      `/public/api/schedule.php?action=get&year=${CURRENT_YEAR}&month=${CURRENT_MONTH}&employee_id=${CURRENT_USER_ID}`
    );
    if (scheduleData.success) {
      const schedule = Array.isArray(scheduleData.schedule) ? scheduleData.schedule : [];
      console.log('✅ График получен:', schedule);
      updateScheduleCard(schedule);
    } else {
      console.warn('⚠️ График: ошибка в ответе', scheduleData);
      document.getElementById('next-shifts').innerHTML = '<em>Нет доступа к графику</em>';
    }

    // 4. Отпуска
    console.log('➡️ Загрузка отпусков...');
    const vacationData = await fetchJson(
      `/public/api/vacation.php?action=get_my_vacations&year=${CURRENT_YEAR}`
    );
    if (vacationData.success) {
      const vacations = Array.isArray(vacationData.vacations) ? vacationData.vacations : [];
      console.log('✅ Отпуска получены:', vacations);
      updateVacationCard(vacations);
    } else {
      console.warn('⚠️ Отпуска: ошибка в ответе', vacationData);
      document.getElementById('vacations-list').innerHTML = '<em>Ошибка загрузки</em>';
    }

  } catch (err) {
    console.error('❌ КРИТИЧЕСКАЯ ОШИБКА загрузки:', err);
    // Добавляем более понятные сообщения пользователю
    showErrorToUser('Произошла ошибка при загрузке данных. Попробуйте позже или обратитесь к администратору.');
  }
}

// === Вспомогательная функция: fetch + JSON ===
async function fetchJson(url) {
  console.log('📌 Выполняем запрос:', url);
  try {
    const res = await fetch(url, {
      method: 'GET',
      credentials: 'include'  // ← Ключевое: передаём сессию
    });

    if (!res.ok) {
      // Обрабатываем 403, 401, 500 и др.
      const errorText = await res.text();
      let errorMessage = `HTTP ${res.status}: ${res.statusText}`;
      
      try {
        const errorJson = JSON.parse(errorText);
        errorMessage = errorJson.message || errorMessage;
      } catch (e) {
        // Если не JSON — оставляем как есть
      }

      throw new Error(errorMessage);
    }

    const data = await res.json();
    console.log('🟢 Успешный ответ:', data);
    return data;
  } catch (err) {
    console.error(`❌ Ошибка запроса: ${url}`, err);
    throw err;
  }
}

// === Обновление карточки профиля ===
function updateProfileCard(profile) {
  console.log('🔄 Обновление карточки профиля');
  if (!profile) {
    console.warn('⚠️ Профиль не передан');
    return;
  }

  // Инициал
  const initials = profile.full_name?.charAt(0).toUpperCase() || '—';
  const avatar = document.querySelector('.personal-card .initials');
  if (avatar) {
    avatar.textContent = initials;
    console.log('✅ Инициал установлен:', initials);
  }

  // Основные поля
  setElementText('name', profile.full_name || '—');
  setElementText('role', getJobTitle(profile.position_code) || '—');
  setElementText('department', getDepartmentName(profile.department) || '—');
  setElementText('gender', profile.gender === 'male' ? 'Мужской' : 'Женской');

  // Дата найма
  const hireDate = profile.hire_date || profile.created_at;
  setElementText('hire-date', formatDate(hireDate) || '—');
  setElementText('experience', calculateExperience(hireDate) || '—');
}

// === Обновление карточки оклада ===
function updatePayrollCard(payroll) {
  console.log('🔄 Обновление карточки оклада');
  setElementText('salary-total', formatNumber(payroll.total_pay) + ' ₽');

  // Разбивка
  const breakdown = document.getElementById('salary-breakdown');
  if (breakdown) {
    breakdown.innerHTML = `
      <li>Оклад: ${formatNumber(payroll.base_salary)} ₽</li>
      <li>Оплата за часы: ${formatNumber(payroll.salary_for_hours)} ₽</li>
      <li>Вредность: ${formatNumber(payroll.harmful_bonus)} ₽</li>
      <li>Стаж: ${formatNumber(payroll.experience_bonus)} ₽</li>
      <li>Ночные: ${formatNumber(payroll.night_bonus)} ₽</li>
    `;
  }

  // Прогресс отработанных часов
  setElementText('norm-hours', payroll.norm_hours);
  setElementText('hours-worked', payroll.hours_worked);
  const hoursPerc = Math.min(100, (payroll.hours_worked / payroll.norm_hours) * 100);
  setProgressBarWidth('hours-progress', hoursPerc);

  // Ночные часы (цель — 200 часов)
  setElementText('night-hours', payroll.night_hours);
  const nightPerc = Math.min(100, (payroll.night_hours / 200) * 100);
  setProgressBarWidth('night-progress', nightPerc);
}

// === Обновление списка ближайших смен ===
function updateScheduleCard(schedule) {
  console.log('🔄 Обновление ближайших смен');
  const today = new Date();
  const nextWeek = new Date(today);
  nextWeek.setDate(today.getDate() + 7);

  const upcoming = schedule
    .filter(s => {
      const date = new Date(s.date);
      return date >= today && date <= nextWeek;
    })
    .sort((a, b) => new Date(a.date) - new Date(b.date));

  const container = document.getElementById('next-shifts');
  if (!container) return;

  if (upcoming.length === 0) {
    container.innerHTML = '<em>Нет смен на неделю</em>';
    return;
  }

  container.innerHTML = '';
  upcoming.forEach(shift => {
    const item = document.createElement('div');
    item.className = 'shift-item';
    item.textContent = `${formatDate(shift.date)} — ${shift.shift_type}`;
    container.appendChild(item);
  });
}

// === Обновление списка отпусков ===
function updateVacationCard(vacations) {
  console.log('🔄 Обновление отпусков');
  const container = document.getElementById('vacations-list');
  if (!container) return;

  if (vacations.length === 0) {
    container.innerHTML = '<em>Отсутствуют</em>';
    return;
  }

  const list = vacations.map(vac => {
    const start = formatDate(vac.start_date);
    const end = formatDate(vac.end_date);
    const days = Math.ceil((new Date(vac.end_date) - new Date(vac.start_date)) / (1000 * 60 * 60 * 24)) + 1;
    return `${start} – ${end} (${days} дн)`;
  }).join(', ');

  container.innerHTML = `<strong>${list}</strong>`;
}

// === Утилиты ===

function setElementText(id, text) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = text;
  } else {
    console.warn(`⚠️ Элемент #${id} не найден`);
  }
}

function setProgressBarWidth(id, percent) {
  const el = document.getElementById(id);
  if (el) {
    el.style.width = `${percent}%`;
  } else {
    console.warn(`⚠️ Прогресс-бар #${id} не найден`);
  }
}

function formatNumber(num) {
  return new Intl.NumberFormat('ru-RU').format(num);
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  try {
    const d = new Date(dateStr);
    return d.toLocaleDateString('ru-RU');
  } catch {
    return '—';
  }
}

function getJobTitle(code) {
  const map = {
    'sanitar': 'Санитар',
    'sanitarka': 'Санитарка',
    'sidelka': 'Сиделка',
    'vanshiza': 'Ванщица',
    'assistant': 'Ассистент',
    'nurse': 'Медсестра',
    'senior_nurse': 'Старшая медсестра',
    'director': 'Директор',
    'admin': 'Администратор'
  };
  return map[code] || code;
}

function getDepartmentName(code) {
  const map = {
    'floor_1': '1 этаж',
    'floor_2': '2 этаж',
    'Не указан': 'Не указан'
  };
  return map[code] || code;
}

function calculateExperience(startDate) {
  if (!startDate) return '—';
  try {
    const start = new Date(startDate);
    const now = new Date();
    const diff = now - start;

    const years = Math.floor(diff / (1000 * 60 * 60 * 24 * 365.25));
    const months = Math.floor((diff % (1000 * 60 * 60 * 24 * 365.25)) / (1000 * 60 * 60 * 24 * 30.44));

    return `${years} ${plural(years, 'год', 'года', 'лет')}, ${months} ${plural(months, 'месяц', 'месяца', 'месяцев')}`;
  } catch {
    return '—';
  }
}

function plural(n, one, few, many) {
  n = n % 100;
  if (n >= 11 && n <= 19) return many;
  n = n % 10;
  if (n === 1) return one;
  if (n >= 2 && n <= 4) return few;
  return many;
}

// === Вспомогательная функция: показ ошибки пользователю ===
function showErrorToUser(message) {
  const container = document.querySelector('.error-banner') || document.body;
  const errorEl = document.createElement('div');
  errorEl.className = 'alert alert-danger mt-3';
  errorEl.style = 'background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; border: 1px solid #f5c6cb;';
  errorEl.innerText = message;
  container.prepend(errorEl);

  // Удаляем через 5 секунд
  setTimeout(() => errorEl.remove(), 5000);
}
