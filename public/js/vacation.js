// public/js/vacation.js
// Управление отпусками: динамические должности, экспорт/импорт XLSX, календарь, легенда, фильтры

if (window.__VACATION_LOADED) {
  console.warn('⚠️ vacation.js уже загружен — пропуск');
} else {
  window.__VACATION_LOADED = true;

  // Глобальное пространство имён — избежание конфликтов
  window.VacationApp = {
    currentYear: new Date().getFullYear(),
    employees: [],
    vacations: [],
    positionTitles: {},
    employeeColors: {},
    tooltip: null,
    isLoaded: false,
  };

  const app = window.VacationApp;

  // --- Уведомления ---
  function showNotification(message, type = 'success') {
    const notif = document.getElementById('notification');
    if (!notif) return;
    notif.textContent = message;
    notif.className = `notification ${type}`;
    notif.style.display = 'block';
    notif.classList.add('show');
    setTimeout(() => {
      notif.classList.remove('show');
      setTimeout(() => { notif.style.display = 'none'; }, 400);
    }, 5000);
  }

  // --- Генерация цвета ---
  function getColorForEmployee(empId) {
    if (!empId) return '#999';
    if (app.employeeColors[empId]) return app.employeeColors[empId];

    const colors = [
      '#64b5f6', '#81c784', '#ffd54f', '#ba68c8', '#4fc3f7',
      '#a1887f', '#e57373', '#4db6ac', '#ffb74d', '#9575cd'
    ];
    const index = Number(empId) % colors.length;
    app.employeeColors[empId] = colors[index];
    return colors[index];
  }

  // --- Рендер легенды ---
  function renderLegend() {
    const legendContainer = document.getElementById('legend');
    if (!legendContainer) return;

    const positionFilter = document.getElementById('positionFilter');
    const filterValue = positionFilter?.value;

    const filteredEmployees = filterValue
      ? app.employees.filter(e => e.position_code === filterValue)
      : app.employees;

    legendContainer.innerHTML = '';
    filteredEmployees.forEach(emp => {
      const item = document.createElement('div');
      item.className = 'legend-item';
      item.innerHTML = `
        <span class="color-box" style="background: ${getColorForEmployee(emp.id)}"></span>
        ${emp.full_name}
      `;
      legendContainer.appendChild(item);
    });
  }

  // --- Рендер календаря ---
  function renderCalendar() {
    const calendarEl = document.getElementById('yearCalendar');
    if (!calendarEl) return;

    calendarEl.innerHTML = '';
    const today = new Date();

    const monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн',
                        'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
    const dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

    if (!app.tooltip) {
      app.tooltip = document.createElement('div');
      app.tooltip.className = 'tooltip';
      document.body.appendChild(app.tooltip);
    }

    for (let m = 0; m < 12; m++) {
      const monthEl = document.createElement('div');
      monthEl.className = 'month';

      const header = document.createElement('div');
      header.className = 'month-header';
      header.textContent = `${monthNames[m]} ${app.currentYear}`;
      monthEl.appendChild(header);

      const weekdays = document.createElement('div');
      weekdays.className = 'weekdays';
      dayNames.forEach(d => {
        const div = document.createElement('div');
        div.textContent = d;
        weekdays.appendChild(div);
      });
      monthEl.appendChild(weekdays);

      const days = document.createElement('div');
      days.className = 'days';

      const firstDay = new Date(app.currentYear, m, 1);
      const firstWeekday = firstDay.getDay();
      const startDate = new Date(firstDay);
      startDate.setDate(startDate.getDate() - ((firstWeekday + 6) % 7));

      const filteredVacations = (positionFilter?.value
        ? app.vacations.filter(v => {
            const emp = app.employees.find(e => e.id == v.employee_id);
            return emp && emp.position_code === positionFilter.value;
          })
        : app.vacations
      );

      for (let i = 0; i < 42; i++) {
        const current = new Date(startDate);
        current.setDate(startDate.getDate() + i);

        const dayDiv = document.createElement('div');
        if (current.getMonth() === m) {
          const numSpan = document.createElement('span');
          numSpan.className = 'day-number';
          numSpan.textContent = current.getDate();
          dayDiv.appendChild(numSpan);

          if (current.toDateString() === today.toDateString()) {
            dayDiv.classList.add('today');
          }

          const vacationsToday = filteredVacations.filter(v => {
            const s = new Date(v.start_date);
            const e = new Date(v.end_date);
            return current >= s && current <= e;
          });

          if (vacationsToday.length > 0) {
            const empNames = vacationsToday.map(v => {
              const emp = app.employees.find(e => e.id == v.employee_id);
              return emp ? emp.full_name : 'Неизвестно';
            });

            if (vacationsToday.length > 1) {
              dayDiv.classList.add('conflict-day');
            } else {
              const empId = vacationsToday[0].employee_id;
              dayDiv.style.backgroundColor = getColorForEmployee(empId);
              dayDiv.style.color = 'white';
              dayDiv.style.fontWeight = '500';
            }

            dayDiv.addEventListener('mouseenter', () => {
              app.tooltip.innerHTML = empNames.map(name => `<div>${name}</div>`).join('');
              app.tooltip.classList.add('show');
              const rect = dayDiv.getBoundingClientRect();
              const tipRect = app.tooltip.getBoundingClientRect();
              let left = rect.left + window.scrollX;
              let top = rect.bottom + window.scrollY + 5;

              if (left + tipRect.width > window.innerWidth + window.scrollX) {
                left = window.innerWidth - tipRect.width - 10 + window.scrollX;
              }
              if (top + tipRect.height > window.innerHeight + window.scrollY) {
                top = rect.top + window.scrollY - tipRect.height - 8;
              }

              app.tooltip.style.left = `${left}px`;
              app.tooltip.style.top = `${top}px`;
            });

            dayDiv.addEventListener('mouseleave', () => {
              app.tooltip.classList.remove('show');
            });
          }
        }
        days.appendChild(dayDiv);
      }
      monthEl.appendChild(days);
      calendarEl.appendChild(monthEl);
    }
  }

  // --- Заполнение select'ов ---
  function setupPositionFilter() {
    const positionFilter = document.getElementById('positionFilter');
    if (!positionFilter) return;

    positionFilter.innerHTML = '<option value="">Все должности</option>';
    const uniquePositions = [...new Map(app.employees.map(emp => [emp.position_code, emp])).values()];

    uniquePositions.forEach(emp => {
      const opt = document.createElement('option');
      opt.value = emp.position_code;
      opt.textContent = emp.position_title;
      positionFilter.appendChild(opt);
    });

    positionFilter.addEventListener('change', () => {
      renderLegend();
      renderCalendar();
    });
  }

  function setupEmployeeSelect() {
    const sel = document.getElementById('employeeSelect');
    if (!sel) return;

    sel.innerHTML = '<option value="">Выберите сотрудника</option>';
    if (app.employees.length === 0) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'Нет сотрудников';
      opt.disabled = true;
      sel.appendChild(opt);
      return;
    }

    app.employees
      .sort((a, b) => a.full_name.localeCompare(b.full_name))
      .forEach(emp => {
        const opt = document.createElement('option');
        opt.value = emp.id;
        opt.textContent = `${emp.full_name} (${emp.position_title}, ${emp.department})`;
        sel.appendChild(opt);
      });
  }

  // --- Рендер таблицы ---
  function renderVacationList() {
    const tbody = document.querySelector('#vacationList tbody');
    if (!tbody) {
      console.error('❌ tbody не найден');
      return;
    }
    tbody.innerHTML = '';

    const sorted = [...app.employees].sort((a, b) => {
      if (a.position_title !== b.position_title) return a.position_title.localeCompare(b.position_title);
      if (a.department !== b.department) return a.department.localeCompare(b.department);
      return a.full_name.localeCompare(b.full_name);
    });

    sorted.forEach(emp => {
      const empVacations = app.vacations.filter(v => v.employee_id === emp.id);
      const row = document.createElement('tr');

      const dates = empVacations.map(v => {
        const start = new Date(v.start_date);
        const end = new Date(v.end_date);
        const days = Math.round((end - start) / 86400000) + 1;
        return `${start.getDate()}.${start.getMonth() + 1}–${end.getDate()}.${end.getMonth() + 1} (${days} дн)`;
      }).join(', ');

      const editButtons = empVacations.map(v =>
        `<button class="btn-icon edit" data-id="${v.id}" title="Изменить">✏️</button>`
      ).join(' ');

      const deleteButtons = empVacations.map(v =>
        `<button class="btn-icon delete" data-id="${v.id}" title="Удалить">🗑️</button>`
      ).join(' ');

      row.innerHTML = `
        <td>${emp.full_name}</td>
        <td>${emp.position_title}</td>
        <td>${emp.department}</td>
        <td>${dates || '—'}</td>
        <td>
          ${editButtons || '<button class="btn-icon edit" data-id="new" title="Добавить">➕</button>'}
          ${deleteButtons || ''}
        </td>
      `;
      tbody.appendChild(row);
    });

    // Обработчики действий
    document.querySelectorAll('.edit[data-id="new"]').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('addVacationBtn').click();
        const empName = btn.closest('tr')?.querySelector('td')?.textContent;
        const emp = app.employees.find(e => e.full_name === empName);
        if (emp) document.getElementById('employeeSelect').value = emp.id;
      });
    });

    document.querySelectorAll('.edit[data-id]:not([data-id="new"])').forEach(btn => {
      btn.addEventListener('click', openEditModal);
    });

    document.querySelectorAll('.delete').forEach(btn => {
      btn.addEventListener('click', deleteVacation);
    });

    console.log('✅ Таблица отпусков отрендерена: %d сотрудников', app.employees.length);
  }

  // --- Модалки ---
  async function openEditModal() {
    const vacationId = this.dataset.id;
    const vacation = app.vacations.find(v => v.id == vacationId);
    if (!vacation) return;

    const emp = app.employees.find(e => e.id == vacation.employee_id);
    if (!emp) return;

    document.getElementById('modalTitle').textContent = 'Изменить отпуск';
    document.getElementById('requestId').value = vacation.id;
    document.getElementById('employeeSelect').value = emp.id;
    document.getElementById('startDate').value = vacation.start_date;
    document.getElementById('endDate').value = vacation.end_date;
    updateDayCount();
    setupEmployeeSelect();
    document.getElementById('vacationModal').style.display = 'block';
  }

  function updateDayCount() {
    const start = document.getElementById('startDate')?.value;
    const end = document.getElementById('endDate')?.value;
    if (start && end) {
      const days = Math.round((new Date(end) - new Date(start)) / 86400000) + 1;
      document.getElementById('dayCount').value = days;
    }
  }

  async function deleteVacation() {
    if (!confirm('Удалить отпуск?')) return;

    const vacationId = this.dataset.id;
    const v = app.vacations.find(vac => vac.id == vacationId);
    if (!v) return;

    try {
      const res = await fetch('/public/api/vacation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: v.id })
      });
      const result = await res.json();
      if (result.success) {
        console.log('✅ Отпуск удалён:', v.id);
        loadData();
      } else {
        showNotification('Ошибка удаления', 'error');
      }
    } catch (e) {
      showNotification('Ошибка сети', 'error');
    }
  }

  // --- Загрузка данных ---
  async function loadPositionTitles() {
    try {
      const r = await fetch('/public/api/positions.php?action=list');
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      const data = await r.json();

      if (data.success && Array.isArray(data.positions)) {
        data.positions.forEach(p => {
          app.positionTitles[p.code] = p.title;
        });
        console.log('✅ Должности загружены');
      } else {
        console.warn('⚠️ API не вернул должности. Используем резервные');
      }
    } catch (e) {
      console.warn('⚠️ Не удалось загрузить должности, используются резервные');
      app.positionTitles = {
        'sanitar': 'Санитар',
        'sanitarka': 'Санитарка',
        'sidelka': 'Сиделка',
        'vanshiza': 'Ванщица',
        'assistant': 'Ассистент',
        'nurse': 'Медсестра',
        'senior_nurse': 'Старшая медсестра'
      };
    }
  }

  // --- ЗАГРУЗКА ДАННЫХ (обновлена под новый формат) ---
  async function loadData() {
    try {
      // Загружаем должности
      await loadPositionTitles();

      // Загружаем сотрудников
      const empRes = await fetch('/public/api/employees.php?action=list');
      const empData = await empRes.json();

      if (!empData || !Array.isArray(empData)) {
        throw new Error('Неверный формат данных сотрудников');
      }

      app.employees = empData.map(emp => ({
        id: Number(emp.id),
        full_name: emp.full_name,
        phone: emp.phone ?? '',
        role: emp.role,
        position_code: emp.position_code,
        position_title: emp.position_title,
        department: emp.department,
        hire_date: emp.hire_date,
        gender: emp.gender
      }));

      // Загружаем отпуска
      const vacRes = await fetch(`/public/api/vacation.php?action=list&year=${app.currentYear}`);
      const vacData = await vacRes.json();

      if (vacData.success) {
        app.vacations = (vacData.vacations || []).map(v => ({
          id: Number(v.id),
          employee_id: Number(v.employee_id),
          start_date: v.start_date,
          end_date: v.end_date,
          status: v.status,
          full_name: v.full_name
        }));
      } else {
        app.vacations = [];
        console.warn('⚠️ Не удалось загрузить отпуска:', vacData.error);
      }

      console.log('✅ Данные загружены: %d сотрудников, %d отпусков', app.employees.length, app.vacations.length);

      renderVacationList();
      if (document.getElementById('tab-calendar')?.classList.contains('active')) {
        renderCalendar();
        renderLegend();
      }

      setupEmployeeSelect();
      setupPositionFilter();

      app.isLoaded = true;
    } catch (e) {
      console.error('❌ Ошибка загрузки данных:', e);
      showNotification('Не удалось загрузить данные', 'error');
    }
  }

  // --- Защита от внешнего вмешательства ---
  function startProtection() {
    const check = () => {
      if (app.isLoaded) {
        const tbody = document.querySelector('#vacationList tbody');
        if (tbody && tbody.children.length === 0) {
          console.warn('⚠️ tbody был очищен — восстанавливаем');
          renderVacationList();
        }
      }
    };
    setInterval(check, 1000);
  }

  // === ОСНОВНОЙ ЗАПУСК ===
  document.addEventListener('DOMContentLoaded', async () => {
    console.log('✅ vacation.js: инициализация');

    const yearFilter = document.getElementById('yearFilter');
    const calendarYearDisplay = document.getElementById('calendarYearDisplay');

    // --- Вкладки ---
    document.querySelectorAll('.tab-button').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-button').forEach(b => {
          b.classList.remove('active');
          b.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
        const pane = document.getElementById('tab-' + btn.dataset.tab);
        if (pane) pane.classList.add('active');
        if (btn.dataset.tab === 'calendar') {
          renderCalendar();
          renderLegend();
        }
      });
    });

    // --- Год ---
    yearFilter?.addEventListener('change', () => {
      app.currentYear = parseInt(yearFilter.value);
      calendarYearDisplay.textContent = app.currentYear;
      loadData();
    });

    // --- Модалки ---
    document.getElementById('addVacationBtn')?.addEventListener('click', () => {
      document.getElementById('modalTitle').textContent = 'Добавить отпуск';
      document.getElementById('vacationForm').reset();
      document.getElementById('requestId').value = '';
      document.getElementById('dayCount').value = '';
      setupEmployeeSelect();
      document.getElementById('vacationModal').style.display = 'block';
    });

    document.getElementById('importVacationBtn')?.addEventListener('click', () => {
      document.getElementById('importModal').style.display = 'block';
    });

    ['cancelBtn', 'cancelImportBtn'].forEach(id => {
      document.getElementById(id)?.addEventListener('click', () => {
        document.getElementById(id === 'cancelBtn' ? 'vacationModal' : 'importModal').style.display = 'none';
      });
    });

    window.addEventListener('click', (e) => {
      if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
      }
    });

    ['startDate', 'endDate'].forEach(id => {
      document.getElementById(id)?.addEventListener('change', updateDayCount);
    });

    // --- Форма ---
    document.getElementById('vacationForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const empId = document.getElementById('employeeSelect').value;
      const start = document.getElementById('startDate').value;
      const end = document.getElementById('endDate').value;
      const editingId = document.getElementById('requestId').value;

      const existing = app.vacations.filter(v => v.employee_id == empId && (!editingId || v.id != editingId));
      for (const v of existing) {
        const s = new Date(v.start_date);
        const e = new Date(v.end_date);
        if (new Date(start) <= e && new Date(end) >= s) {
          showNotification('Отпуск пересекается', 'error');
          return;
        }
      }

      const data = {
        action: editingId ? 'update' : 'create',
        id: editingId,
        employee_id: empId,
        start_date: start,
        end_date: end,
        status: 'approved'
      };

      try {
        const res = await fetch('/public/api/vacation.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
          document.getElementById('vacationModal').style.display = 'none';
          loadData();
        } else {
          showNotification('Ошибка: ' + (result.error || ''), 'error');
        }
      } catch (e) {
        showNotification('Ошибка подключения', 'error');
      }
    });

    // --- Экспорт ---
    document.getElementById('exportCSV')?.addEventListener('click', () => {
      if (typeof XLSX === 'undefined') return showNotification('XLSX не загружен', 'error');
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet([['ФИО', 'Дата начала', 'Дата окончания'], ...app.employees.map(e => [e.full_name, '', ''])]);
      XLSX.utils.book_append_sheet(wb, ws, 'Отпуска');
      XLSX.writeFile(wb, `Отпуска_${app.currentYear}.xlsx`);
    });

    // --- Импорт ---
    document.getElementById('importForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const file = document.getElementById('importFile').files[0];
      if (!file) return showNotification('Выберите файл', 'error');
      if (typeof XLSX === 'undefined') return showNotification('XLSX не загружен', 'error');

      const reader = new FileReader();
      reader.onload = async (e) => {
        try {
          const data = new Uint8Array(e.target.result);
          const wb = XLSX.read(data, { type: 'array' });
          const json = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], { header: 1 });
          if (json.length < 2) return showNotification('Файл пуст', 'error');

          const imported = [];
          const parseDate = str => typeof str === 'number'
            ? new Date(XLSX.SSF.parse_date_code(str))
            : new Date(str.split(/[.\-\/]/).reverse().join('-'));

          for (let i = 1; i < json.length; i++) {
            const [fio, s, e] = json[i];
            if (!fio || !s || !e) continue;
            const emp = app.employees.find(e => e.full_name.toLowerCase().trim() === String(fio).toLowerCase().trim());
            if (!emp) continue;
            const start = parseDate(s);
            const end = parseDate(e);
            if (isNaN(start.getTime()) || isNaN(end.getTime())) continue;
            imported.push({
              employee_id: emp.id,
              start_date: start.toISOString().split('T')[0],
              end_date: end.toISOString().split('T')[0]
            });
          }

          if (imported.length === 0) return showNotification('Нет данных', 'error');
          await fetch('/public/api/vacation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'bulk_create', vacations: imported })
          });
          loadData();
          document.getElementById('importModal').style.display = 'none';
          showNotification('Импорт завершён', 'success');
        } catch (err) {
          showNotification('Ошибка импорта', 'error');
        }
      };
      reader.readAsArrayBuffer(file);
    });

    // --- Легенда ---
    const toggleLegendBtn = document.getElementById('toggleLegend');
    const legendContainer = document.getElementById('legend');
    if (toggleLegendBtn && legendContainer) {
      toggleLegendBtn.addEventListener('click', () => {
        const expanded = legendContainer.style.display !== 'none';
        legendContainer.style.display = expanded ? 'none' : 'flex';
        toggleLegendBtn.textContent = expanded ? '► Легенда' : '▼ Легенда';
      });
    }

    // --- Запуск ---
    await loadData();
    startProtection();
  });
}
