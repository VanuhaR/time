// public/js/settings.js
// Управление нормами и окладами: загрузка, редактирование, сохранение

document.addEventListener('DOMContentLoaded', () => {
  const yearSelect = document.getElementById('yearSelect');
  const normsForm = document.getElementById('normsForm');
  const normsTableBody = document.getElementById('normsTableBody');
  const salaryForm = document.getElementById('salaryForm');
  const salaryTableBody = document.getElementById('salaryTableBody');

  const months = [
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
  ];

  // --- Загрузка норм часов ---
  async function loadNorms(year) {
    try {
      const response = await fetch(`/public/api/settings.php?action=get_norms&year=${year}`);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      renderNormsTable(data.norms || {});
    } catch (err) {
      console.error('Ошибка загрузки норм:', err);
      alert('Не удалось загрузить нормы часов.');
      renderNormsTable({});
    }
  }

  function renderNormsTable(norms) {
    normsTableBody.innerHTML = '';
    months.forEach((name, i) => {
      const month = i + 1;
      const male = norms[month]?.male ?? '';
      const female = norms[month]?.female ?? '';

      const row = document.createElement('tr');
      row.innerHTML = `
        <td><strong>${name}</strong></td>
        <td>
          <input type="number" data-gender="male" data-month="${month}" 
            value="${male}" min="0" max="200" step="0.5"
            class="norm-input" style="width:70px;text-align:center" placeholder="0">
        </td>
        <td>
          <input type="number" data-gender="female" data-month="${month}" 
            value="${female}" min="0" max="200" step="0.5"
            class="norm-input" style="width:70px;text-align:center" placeholder="0">
        </td>
      `;
      normsTableBody.appendChild(row);
    });
  }

  function collectNorms() {
    const norms = {};
    let isValid = true;

    for (let m = 1; m <= 12; m++) {
      const maleInput = document.querySelector(`input[data-month="${m}"][data-gender="male"]`);
      const femaleInput = document.querySelector(`input[data-month="${m}"][data-gender="female"]`);

      const male = parseFloat(maleInput.value.trim()) || 0;
      const female = parseFloat(femaleInput.value.trim()) || 0;

      if (isNaN(male) || isNaN(female)) {
        alert(`Некорректные данные в месяце "${months[m-1]}"`);
        isValid = false;
        return null;
      }

      norms[m] = {
        male: Math.max(0, parseFloat(male.toFixed(1))),
        female: Math.max(0, parseFloat(female.toFixed(1)))
      };
    }

    return isValid ? norms : null;
  }

  normsForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const year = yearSelect.value;

    if (!year || !/^\d{4}$/.test(year)) {
      alert('Выберите корректный год');
      return;
    }

    const norms = collectNorms();
    if (!norms) return;

    try {
      const response = await fetch('/public/api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'save_norms', year, norms })
      });

      const result = await response.json();
      if (result.success) {
        alert(`✅ Нормы на ${year} успешно сохранены!`);
      } else {
        alert('❌ Ошибка: ' + (result.error || 'неизвестная'));
      }
    } catch (err) {
      console.error('Ошибка сохранения норм:', err);
      alert('❌ Не удалось отправить данные.');
    }
  });

  // --- Загрузка и редактирование окладов ---
  async function loadSalaries() {
    try {
      const response = await fetch('/public/api/positions.php?action=list');
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success || !Array.isArray(data.positions)) {
        throw new Error('Неверный формат ответа');
      }

      renderSalaryTable(data.positions);
    } catch (err) {
      console.error('Ошибка загрузки окладов:', err);
      alert('Не удалось загрузить данные о должностях.');
      salaryTableBody.innerHTML = '<tr><td colspan="3">Ошибка загрузки</td></tr>';
    }
  }

  function renderSalaryTable(positions) {
    salaryTableBody.innerHTML = '';
    positions.forEach(pos => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td><strong>${pos.title}</strong></td>
        <td>${parseFloat(pos.salary).toFixed(2)} ₽</td>
        <td>
          <input type="number" data-code="${pos.code}"
            value="${pos.salary}" min="0" step="100"
            placeholder="Новый оклад" style="width:120px;text-align:center">
        </td>
      `;
      salaryTableBody.appendChild(row);
    });
  }

  salaryForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const updates = [];
    document.querySelectorAll('#salaryTableBody input').forEach(input => {
      const code = input.dataset.code;
      const value = parseFloat(input.value.trim());
      if (isNaN(value)) return;
      updates.push({ code, salary: Math.max(0, parseFloat(value.toFixed(2))) });
    });

    if (updates.length === 0) {
      alert('Не внесено ни одного изменения.');
      return;
    }

    try {
      const response = await fetch('/public/api/positions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_salary_batch', updates })
      });

      const result = await response.json();
      if (result.success) {
        alert(`✅ Оклады успешно обновлены!`);
        loadSalaries(); // Обновить таблицу
      } else {
        alert('❌ Ошибка: ' + (result.error || 'неизвестная'));
      }
    } catch (err) {
      console.error('Ошибка сохранения окладов:', err);
      alert('❌ Не удалось отправить данные.');
    }
  });

  // --- Переключение вкладок ---
  document.querySelectorAll('.tab-button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const pane = document.getElementById('tab-' + btn.dataset.tab);
      pane.classList.add('active');

      // Загрузить данные при первом открытии вкладки "Оклады"
      if (btn.dataset.tab === 'salary' && !pane.dataset.loaded) {
        loadSalaries();
        pane.dataset.loaded = 'true';
      }
    });
  });

    // --- Загрузка и сохранение процентов доплат ---
  async function loadBonusRates() {
    try {
      const keys = ['bonus_harmful', 'bonus_experience', 'bonus_special_work', 'bonus_rayon', 'bonus_north'];
      const response = await fetch(`/public/api/settings.php?action=get_bonus_rates&keys=${keys.join(',')}`);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success || !data.rates) {
        throw new Error('Неверный формат ответа');
      }

      // Заполняем текущие значения
      document.getElementById('current_harmful').textContent = (data.rates.bonus_harmful * 100).toFixed(1) + '%';
      document.getElementById('current_experience').textContent = (data.rates.bonus_experience * 100).toFixed(1) + '%';
      document.getElementById('current_special_work').textContent = (data.rates.bonus_special_work * 100).toFixed(1) + '%';
      document.getElementById('current_rayon').textContent = (data.rates.bonus_rayon * 100).toFixed(1) + '%';
      document.getElementById('current_north').textContent = (data.rates.bonus_north * 100).toFixed(1) + '%';
    } catch (err) {
      console.error('Ошибка загрузки процентов:', err);
      alert('Не удалось загрузить текущие значения доплат.');
    }
  }

  document.getElementById('bonusRatesForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const rates = {};
    for (let input of document.querySelectorAll('#bonusRatesBody input')) {
      const key = input.name;
      const value = parseFloat(input.value.trim());
      if (isNaN(value)) {
        alert(`Введите корректное значение для "${input.previousElementSibling.textContent}"`);
        return;
      }
      rates['bonus_' + key] = value / 100; // Сохраняем как долю (0.05)
    }

    try {
      const response = await fetch('/public/api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'save_bonus_rates', rates })
      });

      const result = await response.json();
      if (result.success) {
        alert('✅ Проценты доплат успешно сохранены!');
        loadBonusRates(); // Обновить отображение
      } else {
        alert('❌ Ошибка: ' + (result.error || 'неизвестная'));
      }
    } catch (err) {
      console.error('Ошибка сохранения процентов:', err);
      alert('❌ Не удалось отправить данные.');
    }
  });

  // При открытии вкладки "Прочие настройки" — загружаем значения
  document.querySelector('[data-tab="other"]').addEventListener('click', () => {
    if (!document.getElementById('tab-other').dataset.loaded) {
      loadBonusRates();
      document.getElementById('tab-other').dataset.loaded = 'true';
    }
  });

  // --- Инициализация ---
  loadNorms(yearSelect.value);
  yearSelect.addEventListener('change', () => loadNorms(yearSelect.value));
});
