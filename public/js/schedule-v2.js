// public/js/schedule-v2.js (полностью рабочая версия с группировкой по блокам для 1 и 2 этажа)

(function () {
  'use strict';

  if (window.__SCHEDULE_V2_LOADED) {
    console.warn('⚠️ schedule-v2.js уже загружен — пропуск');
    return;
  }
  window.__SCHEDULE_V2_LOADED = true;

  window.CAN_EDIT = typeof CAN_EDIT !== 'undefined' ? !!CAN_EDIT : false;
  console.log('🔧 [INIT] CAN_EDIT установлен как:', window.CAN_EDIT);

  if (typeof window.API_URL === 'undefined') {
    window.API_URL = '/public/api/schedule.php';
    window.POSITIONS_API = '/public/api/positions.php';
    window.EMPLOYEES_API = '/public/api/employees.php';
    window.SETTINGS_API = '/public/api/settings.php';
    window.VACATION_API = '/public/api/vacation.php';
  }

  let currentMonth = new Date();
  let selectedShift = '10ч';
  let activeTemplate = null;
  let positionTitles = {};
  let vacationMap = {};

  let dragOverHandler = null;
  let dropHandler = null;

  const SHIFT_HOURS = { '10ч': 10, '14ч': 14 };
  const ORDER_KEY = 'schedule_order_v2';
  const TEMPLATES = {
    pattern1: ['10ч', '14ч', '', '', ''],
    pattern2: ['10ч', '10ч', '', '']
  };

  function getDaysInMonth(date) {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
  }

  function formatDate(year, month, day) {
    return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
  }

  function isWeekend(year, month, day) {
    const d = new Date(year, month, day);
    return d.getDay() === 0 || d.getDay() === 6;
  }

  function getDragAfterElement(container, y) {
    const els = [...container.querySelectorAll('tr:not(.group-header):not(.dragging)')];
    return els.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      return (offset < 0 && offset > closest.offset)
        ? { offset, element: child }
        : closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }

  function getGroupRowAbove(row) {
    let prev = row.previousElementSibling;
    while (prev) {
      if (prev.classList?.contains('group-header')) return prev;
      prev = prev.previousElementSibling;
    }
    return null;
  }

  function saveEmployeeOrder(groupTitle, ids) {
    if (!window.CAN_EDIT) return;
    const key = `${ORDER_KEY}_${currentMonth.getFullYear()}-${currentMonth.getMonth()}`;
    const saved = JSON.parse(localStorage.getItem(key) || '{}');
    saved[groupTitle] = ids;
    localStorage.setItem(key, JSON.stringify(saved));
  }

  function loadEmployeeOrder(groupTitle) {
    const key = `${ORDER_KEY}_${currentMonth.getFullYear()}-${currentMonth.getMonth()}`;
    const saved = JSON.parse(localStorage.getItem(key) || '{}');
    return saved[groupTitle] || null;
  }

  function showToast(message, type = 'info') {
    let toast = document.getElementById('toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'toast';
      toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 12px 20px;
        border-radius: 6px; z-index: 9999; font-size: 14px; max-width: 300px;
        color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: opacity 0.3s;
      `;
      document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.style.backgroundColor =
      type === 'error' ? '#d32f2f' :
      type === 'warning' ? '#ff8f00' : '#43a047';

    toast.style.display = 'block';
    toast.style.opacity = 1;
    setTimeout(() => {
      toast.style.opacity = 0;
      setTimeout(() => { toast.style.display = 'none'; }, 300);
    }, 3000);
  }

  function showErrorMessage(msg) {
    const container = document.getElementById('scheduleBody') || document.body;
    const html = `
      <tr>
        <td colspan="33" style="
          color: red; background: #ffebee; border: 1px solid #c62828;
          padding: 12px; text-align: center; font-size: 14px; border-radius: 4px; margin: 10px;">
          ${msg}
        </td>
      </tr>`;
    if (container.tagName === 'TBODY') {
      container.innerHTML = html;
    } else {
      container.innerHTML = `<div style="color: red; text-align: center; padding: 10px;">${msg}</div>`;
    }
  }

  async function loadPositions() {
    try {
      const res = await fetch(window.POSITIONS_API);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      if (data.success && data.positions) {
        positionTitles = data.positions;
      } else {
        positionTitles = {
          'sanitar': 'Санитар',
          'sanitarka': 'Санитарка',
          'sidelka': 'Сиделка',
          'vanshiza': 'Ванщица',
          'assistant': 'Ассистент',
          'nurse': 'Медсестра',
          'senior_nurse': 'Старшая медсестра'
        };
      }
      console.log('✅ Должности загружены:', Object.keys(positionTitles));
    } catch (e) {
      console.warn('⚠️ Ошибка загрузки должностей:', e);
      positionTitles = {};
    }
  }

  async function loadEmployees() {
    try {
      const res = await fetch(`${window.EMPLOYEES_API}?action=list`);
      if (!res.ok) {
        console.error('❌ Ошибка HTTP:', res.status, res.statusText);
        showErrorMessage('Не удалось загрузить сотрудников');
        return [];
      }
      const employees = await res.json();
      console.log('✅ Загружено сотрудников:', employees.length);
      return employees;
    } catch (e) {
      console.error('❌ Ошибка сети:', e);
      showErrorMessage('Ошибка подключения к серверу');
      return [];
    }
  }

  async function loadSchedule(year, month) {
    const url = `${window.API_URL}?action=get_all&year=${year}&month=${month + 1}`;
    console.log('📡 Запрос графика:', url);

    try {
      const res = await fetch(url);
      console.log('📡 Ответ:', res.status);

      if (res.status === 403) {
        showErrorMessage('Нет доступа к графику. Обратитесь к администратору.');
        return null;
      }
      if (!res.ok) {
        showErrorMessage('Ошибка сервера при загрузке графика');
        return null;
      }

      const data = await res.json();
      if (!data.success || !Array.isArray(data.schedule)) {
        console.warn('⚠️ Некорректный ответ:', data);
        showErrorMessage('Некорректные данные');
        return null;
      }

      console.log('✅ График загружен:', data.year, '/', data.month);
      return data;
    } catch (e) {
      console.error('❌ Ошибка сети:', e);
      showErrorMessage('Ошибка соединения с сервером');
      return null;
    }
  }

  async function loadVacations() {
    try {
      const year = currentMonth.getFullYear();
      const res = await fetch(`${window.VACATION_API}?action=get_approved_vacations_for_year&year=${year}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      vacationMap = await res.json();
      console.log('✅ Отпуска загружены:', Object.keys(vacationMap).length, 'сотрудников');
    } catch (e) {
      console.warn('⚠️ Ошибка загрузки отпусков:', e);
      vacationMap = {};
    }
  }

  async function getNormForMonth(year, month, gender) {
    try {
      const genderKey = gender === 'female' ? 'female' : 'male';
      const res = await fetch(`${window.SETTINGS_API}?action=get_norm_for_month&year=${year}&month=${month}&gender=${genderKey}`);
      if (!res.ok) return 100;
      const data = await res.json();
      return data.norm ?? 100;
    } catch (err) {
      console.warn('⚠️ Ошибка загрузки нормы:', err);
      return 100;
    }
  }

  async function updateTotal(empId) {
    const row = document.querySelector(`tr[data-emp-id="${empId}"]`);
    if (!row) return;

    const gender = row.dataset.gender || 'male';
    const year = currentMonth.getFullYear();
    const month = currentMonth.getMonth() + 1;

    let hours = 0;
    row.querySelectorAll('td[data-date]').forEach(cell => {
      const text = cell.textContent.trim();
      hours += SHIFT_HOURS[text] || 0;
    });

    const norm = await getNormForMonth(year, month, gender);
    const totalCell = row.querySelector('td[data-total]');
    if (!totalCell) return;

    totalCell.textContent = hours;
    totalCell.style.fontWeight = 'bold';
    totalCell.style.textAlign = 'center';

    if (hours < norm) {
      totalCell.style.backgroundColor = '#ffebee';
      totalCell.style.color = '#c62828';
    } else if (hours === norm) {
      totalCell.style.backgroundColor = '#fff3e0';
      totalCell.style.color = '#ef6c00';
    } else {
      totalCell.style.backgroundColor = '#e8f5e8';
      totalCell.style.color = '#2e7d32';
    }
  }

  async function saveShift(empId, date, shift) {
    if (!window.CAN_EDIT) return;

    const payload = {
      action: 'update',
      employee_id: empId,
      date: date,
      shift_type: shift === 'off' ? '' : shift
    };

    try {
      const res = await fetch(window.API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const result = await res.json();
      if (result.success) {
        updateTotal(empId);
        showToast('Смена сохранена', 'success');
      } else {
        throw new Error(result.error || 'Ошибка сохранения');
      }
    } catch (e) {
      console.error('❌ Ошибка сохранения:', e);
      showToast('Не удалось сохранить смену', 'error');
    }
  }

  function applyTemplateToEmployee(empId, clickedDate) {
    if (!window.CAN_EDIT) return;
    const row = document.querySelector(`tr[data-emp-id="${empId}"]`);
    if (!row || !activeTemplate) return;

    const cells = Array.from(row.querySelectorAll('td[data-date]'));
    const pattern = TEMPLATES[activeTemplate];
    const clicked = new Date(clickedDate);
    const clickedIndex = cells.findIndex(cell => {
      return new Date(cell.dataset.date).toDateString() === clicked.toDateString();
    });
    if (clickedIndex === -1) return;

    const updates = [];

    let patternIndex = 0;
    for (let i = clickedIndex; i < cells.length; i++) {
      const cell = cells[i];
      const shiftType = pattern[patternIndex % pattern.length];
      const displayText = shiftType === '' ? '' : shiftType;

      if (cell.dataset.vacation !== 'true') {
        if (cell.textContent.trim() !== displayText) {
          cell.textContent = displayText;
          cell.dataset.shift = displayText;
          updates.push({ empId, date: cell.dataset.date, shiftType: displayText });
        }
      }
      patternIndex++;
    }

    patternIndex = (pattern.length - 1) % pattern.length;
    for (let i = clickedIndex - 1; i >= 0; i--) {
      const cell = cells[i];
      const shiftType = pattern[patternIndex];
      const displayText = shiftType === '' ? '' : shiftType;

      if (cell.dataset.vacation !== 'true') {
        if (cell.textContent.trim() !== displayText) {
          cell.textContent = displayText;
          cell.dataset.shift = displayText;
          updates.push({ empId, date: cell.dataset.date, shiftType: displayText });
        }
      }
      patternIndex = (patternIndex - 1 + pattern.length) % pattern.length;
    }

    updates.forEach(u => saveShift(u.empId, u.date, u.shiftType));
  }

  function setupCellListeners() {
    console.log('🔧 setupCellListeners: начало');

    document.querySelectorAll('td[data-emp][data-date]').forEach(cell => {
      const newCell = cell.cloneNode(true);
      cell.replaceWith(newCell);

      if (!window.CAN_EDIT) {
        newCell.style.cursor = 'default';
        newCell.title = 'Только просмотр';
        return;
      }

      newCell.addEventListener('click', function () {
        const empId = this.dataset.emp;
        const date = this.dataset.date;

        if (this.dataset.vacation === 'true' && !activeTemplate) {
          showToast('Нельзя менять отпуск вручную', 'warning');
          return;
        }

        if (activeTemplate) {
          applyTemplateToEmployee(empId, date);
        } else {
          const shift = selectedShift;
          const displayText = shift === 'off' ? '' : shift;
          this.textContent = displayText;
          this.dataset.shift = displayText;
          saveShift(empId, date, shift);
        }
      });

      newCell.style.cursor = 'pointer';
      newCell.title = 'Кликните, чтобы поставить смену';
      newCell.addEventListener('mouseenter', () => {
        if (!newCell.textContent.trim() && newCell.dataset.vacation !== 'true') {
          newCell.style.backgroundColor = '#f0f0f0';
        }
      });
      newCell.addEventListener('mouseleave', () => {
        newCell.style.backgroundColor = '';
      });

      if (newCell.dataset.vacation === 'true') {
        newCell.style.backgroundColor = '#ffd54f';
        newCell.style.color = '#5d4037';
        newCell.style.fontWeight = '600';
        newCell.style.cursor = 'not-allowed';
        newCell.title = 'Отпуск — нельзя редактировать вручную';
      }
    });

    if (!window.CAN_EDIT) {
      document.querySelectorAll('tr[data-emp-id]').forEach(row => {
        row.removeAttribute('draggable');
        row.style.cursor = 'default';
      });

      const tbody = document.getElementById('scheduleBody');
      if (dragOverHandler && tbody) {
        tbody.removeEventListener('dragover', dragOverHandler);
        dragOverHandler = null;
      }
      if (dropHandler && tbody) {
        tbody.removeEventListener('drop', dropHandler);
        dropHandler = null;
      }
    }

    console.log('✅ setupCellListeners: завершён');
  }

  function setupDragListeners(row) {
    if (!window.CAN_EDIT) return;
    row.setAttribute('draggable', true);
    row.addEventListener('dragstart', () => row.classList.add('dragging'));
    row.addEventListener('dragend', () => row.classList.remove('dragging'));
  }

  function clearSchedule() {
    if (!window.CAN_EDIT) return;
    if (!confirm('Очистить график? Отпуска не будут удалены.')) return;

    document.querySelectorAll('td[data-emp][data-date]').forEach(cell => {
      if (cell.dataset.vacation !== 'true') {
        cell.textContent = '';
        cell.dataset.shift = '';
      }
    });

    const year = currentMonth.getFullYear();
    const month = currentMonth.getMonth() + 1;

    fetch(window.API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'clear_month', year, month })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('График очищен', 'success');
          renderSchedule();
        } else {
          showToast('Ошибка очистки: ' + (data.error || ''), 'error');
        }
      })
      .catch(err => {
        console.error('❌ Ошибка:', err);
        showToast('Ошибка при очистке', 'error');
      });
  }

  async function renderSchedule() {
    const year = currentMonth.getFullYear();
    const month = currentMonth.getMonth();
    const days = getDaysInMonth(currentMonth);

    try {
      console.log('🔄 renderSchedule: загрузка сотрудников...');
      const employees = await loadEmployees();
      if (!employees || employees.length === 0) return;

      const schedule = await loadSchedule(year, month);
      if (window.CAN_EDIT && !schedule) return;

      await loadVacations();

      const groupFilter = document.getElementById('groupFilter')?.value || 'all';
      const thead = document.querySelector('#scheduleTable thead');
      const tbody = document.getElementById('scheduleBody');
      thead.innerHTML = '';
      tbody.innerHTML = '';

      const headerRow = document.createElement('tr');
      headerRow.innerHTML = '<th style="width: 50px;">Буква</th><th style="width: 200px;">Сотрудник</th>';
      for (let d = 1; d <= days; d++) {
        const th = document.createElement('th');
        th.textContent = d;
        th.style.width = '40px';
        if (isWeekend(year, month, d)) th.classList.add('weekend');
        headerRow.appendChild(th);
      }
      headerRow.innerHTML += `
        <th data-total style="width: 60px;">Итого</th>
        <th class="print-only" style="width: 80px;">Роспись</th>
      `;
      thead.appendChild(headerRow);

      const norm = await getNormForMonth(year, month + 1, 'male');
      const normEl = document.getElementById('monthlyNorm');
      if (normEl) normEl.textContent = norm;

      const floors = ['floor_1', 'floor_2'];
      const positions = Object.keys(positionTitles);

      let groups = [];

      if (groupFilter === 'all') {
        groups = positions.flatMap(pos =>
          floors.map(floor => ({
            t: `${positionTitles[pos]} ${floor === 'floor_1' ? '1 этажа' : '2 этажа'}`,
            f: e => e.position_code === pos && e.department === floor
          }))
        );
      } else {
        groups = {
          cleaners: [
            { t: 'Санитары 1 этажа', f: e => e.position_code === 'sanitar' && (e.department === 'floor_1' || !e.department) },
            { t: 'Санитары 2 этажа', f: e => e.position_code === 'sanitar' && e.department === 'floor_2' },
            { t: 'Ассистенты', f: e => e.position_code === 'assistant' }
          ],
          floor1_staff: [
            { t: 'Санитарки 1-1 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_1' && e.block === '1' },
            { t: 'Санитарки 1-1-2 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_1' && e.block === '1-2' },
            { t: 'Санитарки 1-2 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_1' && e.block === '2' },
            { t: 'Санитарки 1-2-3 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_1' && e.block === '2-3' },
            { t: 'Санитарки 1-3 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_1' && e.block === '3' },
            { t: 'Санитарки 1 этажа (без блока)', f: e => e.position_code === 'sanitarka' && e.department === 'floor_1' && !e.block },
            { t: 'Сиделки 1 этажа', f: e => e.position_code === 'sidelka' && e.department === 'floor_1' },
            { t: 'Ванщицы 1 этажа', f: e => e.position_code === 'vanshiza' && e.department === 'floor_1' }
          ],
          floor2_staff: [
            { t: 'Санитарки 2-1 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_2' && e.block === '1' },
            { t: 'Санитарки 2-1-2 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_2' && e.block === '1-2' },
            { t: 'Санитарки 2-2 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_2' && e.block === '2' },
            { t: 'Санитарки 2-2-3 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_2' && e.block === '2-3' },
            { t: 'Санитарки 2-3 блока', f: e => e.position_code === 'sanitarka' && e.department === 'floor_2' && e.block === '3' },
            { t: 'Санитарки 2 этажа (без блока)', f: e => e.position_code === 'sanitarka' && e.department === 'floor_2' && !e.block },
            { t: 'Сиделки 2 этажа', f: e => e.position_code === 'sidelka' && e.department === 'floor_2' },
            { t: 'Ванщицы 2 этажа', f: e => e.position_code === 'vanshiza' && e.department === 'floor_2' }
          ],
          nurses: [
            { t: 'Медсёстры', f: e => e.position_code === 'nurse' },
            { t: 'Старшая медсестра', f: e => e.position_code === 'senior_nurse' }
          ]
        }[groupFilter] || [];
      }

      let idx = 0;
      for (const group of groups) {
        const emps = employees.filter(group.f);
        if (emps.length === 0) continue;

        const savedOrder = loadEmployeeOrder(group.t);
        if (savedOrder) {
          emps.sort((a, b) => savedOrder.indexOf(a.id) - savedOrder.indexOf(b.id));
        }

        const header = document.createElement('tr');
        header.className = 'group-header';
        header.innerHTML = `<td colspan="${2 + days + 2}">– ${group.t} –</td>`;
        tbody.appendChild(header);

        for (const emp of emps) {
          const letter = 'АБВГД'[idx++ % 5];
          let cells = `<td class="letter-cell">${letter}</td><td>${emp.full_name}</td>`;

          for (let d = 1; d <= days; d++) {
            const ds = formatDate(year, month, d);
            const isVacation = vacationMap[emp.id]?.[ds];
            const empData = schedule?.schedule?.find(s => s.id == emp.id);
            const shift = isVacation ? 'ОТ' : (empData?.shifts?.[ds] || '');
            const cls = [isWeekend(year, month, d) ? 'weekend' : '', isVacation ? 'vacation-locked' : '']
              .filter(Boolean).join(' ');

            const vacationAttr = isVacation ? 'data-vacation="true"' : '';

            cells += `<td data-emp="${emp.id}" data-date="${ds}" data-shift="${shift}" ${vacationAttr} ${cls ? `class="${cls}"` : ''}>${shift}</td>`;
          }

          cells += '<td data-total></td><td class="print-only signature-cell"></td>';

          const row = document.createElement('tr');
          row.dataset.empId = emp.id;
          row.dataset.gender = emp.gender || 'male';
          row.innerHTML = cells;
          tbody.appendChild(row);

          if (window.CAN_EDIT) setupDragListeners(row);
          updateTotal(emp.id);
        }
      }

      const monthNames = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
      document.getElementById('monthLabel').textContent = `${monthNames[month]} ${year}`;

      setupCellListeners();
      console.log('✅ renderSchedule: завершён');
    } catch (e) {
      console.error('❌ Ошибка рендеринга:', e);
      showErrorMessage('Ошибка при построении таблицы');
    }
  }

  document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 DOMContentLoaded: старт');
    await loadPositions();

    const tbody = document.getElementById('scheduleBody');
    if (window.CAN_EDIT && tbody && !dragOverHandler) {
      dragOverHandler = (e) => {
        e.preventDefault();
        const after = getDragAfterElement(tbody, e.clientY);
        const dragging = document.querySelector('.dragging');
        if (after) {
          tbody.insertBefore(dragging, after);
        } else {
          tbody.appendChild(dragging);
        }
      };

      dropHandler = () => {
        const row = document.querySelector('.dragging');
        if (!row) return;
        const groupRow = getGroupRowAbove(row);
        if (!groupRow) return;
        const groupName = groupRow.querySelector('td')?.textContent.trim().replace(/^–\s*/, '').replace(/\s*–$/, '');
        if (!groupName) return;
        const ids = Array.from(groupRow.parentNode.children)
          .filter(r => r.dataset.empId)
          .map(r => r.dataset.empId);
        saveEmployeeOrder(groupName, ids);
      };

      tbody.addEventListener('dragover', dragOverHandler);
      tbody.addEventListener('drop', dropHandler);
    }

    document.querySelectorAll('.shift-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.shift-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedShift = btn.dataset.shift;
        activeTemplate = null;
        document.body.style.cursor = '';
        document.querySelectorAll('.btn-template').forEach(b => b.classList.remove('active'));
      });
    });

    document.querySelectorAll('.btn-template').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.btn-template').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.shift-btn').forEach(b => b.classList.remove('active'));
        activeTemplate = activeTemplate === btn.dataset.template ? null : btn.dataset.template;
        btn.classList.toggle('active', !!activeTemplate);
        document.body.style.cursor = activeTemplate ? 'crosshair' : '';
      });
    });

    document.getElementById('clearSchedule')?.addEventListener('click', clearSchedule);

    document.getElementById('printSchedule')?.addEventListener('click', () => {
      const monthLabel = document.getElementById('monthLabel').textContent;
      const normText = document.getElementById('monthlyNorm').textContent || '0';
      const content = `
        <html><head><title>График</title><style>
          body { font: 12px Arial; margin: 15mm; }
          .info { text-align: center; font-weight: bold; }
          .norm { text-align: right; font-weight: bold; margin-bottom: 20px; }
          table { width: 100%; border-collapse: collapse; }
          th, td { border: 1px solid #000; padding: 6px; font-size: 11px; text-align: center; }
          .weekend { background: #fff3e0; }
          [data-total] { font-weight: bold; background: #f0f0f0; }
          .print-only { display: table-cell !important; }
          .vacation-locked { background: #ffd54f; color: #5d4037; font-weight: 600; }
        </style></head><body>
          <div class="info">График смен на месяц</div>
          <div class="info">${monthLabel}</div>
          <div class="norm">Норма: ${normText} ч</div>
          ${document.querySelector('#scheduleTable').outerHTML}
        </body></html>`;
      const w = window.open();
      w.document.write(content);
      w.document.close();
      w.focus();
    });

    document.getElementById('prevMonth')?.addEventListener('click', () => {
      currentMonth.setMonth(currentMonth.getMonth() - 1);
      renderSchedule();
    });
    document.getElementById('nextMonth')?.addEventListener('click', () => {
      currentMonth.setMonth(currentMonth.getMonth() + 1);
      renderSchedule();
    });
    document.getElementById('groupFilter')?.addEventListener('change', renderSchedule);

    await renderSchedule();
  });

})();
