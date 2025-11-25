// public/js/employees.js
const API_URL = '/public/api/employees.php';

let allEmployees = [];
let filteredEmployees = [];

// --- Форматирование должности ---
function formatPosition(pos) {
  const map = {
    'sanitar': 'Санитар',
    'sanitarka': 'Санитарка',
    'sidelka': 'Сиделка',
    'vanshiza': 'Ванщица',
    'assistant': 'Ассистент',
    'nurse': 'Медсестра',
    'senior_nurse': 'Старшая медсестра',
    'director': 'Директор'
  };
  return map[pos] || pos;
}

// --- Форматирование отдела ---
function formatDepartment(dep) {
  const map = { 'floor_1': '1 этаж', 'floor_2': '2 этаж' };
  return map[dep] || 'Не указан';
}

// --- Расчёт стажа ---
function calculateExperience(startDate) {
  if (!startDate) return '–';
  const start = new Date(startDate);
  const today = new Date();
  let years = today.getFullYear() - start.getFullYear();
  let months = today.getMonth() - start.getMonth();
  if (months < 0) { years--; months += 12; }
  if (months < 0) { months = 11; }
  let result = '';
  if (years > 0) result += years + (years === 1 ? ' год ' : (years < 5 ? ' года ' : ' лет '));
  if (months > 0) result += months + (months === 1 ? ' месяц' : (months < 5 ? ' месяца' : ' месяцев'));
  return result || 'меньше месяца';
}

// --- Формат телефона ---
function formatPhone(phone) {
  if (!phone || phone.length !== 11) return phone;
  return `+7 (${phone.slice(1,4)}) ${phone.slice(4,7)}-${phone.slice(7,9)}-${phone.slice(9)}`;
}

// --- Поиск и фильтры ---
function applyFilters() {
  const query = document.getElementById('searchInput')?.value.trim().toLowerCase() || '';
  const position = document.getElementById('positionFilter')?.value || '';

  filteredEmployees = allEmployees.filter(emp => {
    const matchesSearch = !query || emp.full_name.toLowerCase().includes(query);
    const matchesPos = !position || emp.position_code === position;
    return matchesSearch && matchesPos;
  });

  renderEmployeeList();
}

// --- Рендер таблицы ---
function renderEmployeeList() {
  const tbody = document.getElementById('employeeList');
  if (!tbody) return;
  tbody.innerHTML = '';

  filteredEmployees.forEach((emp, i) => {
    const hireDate = emp.created_at ? emp.created_at.split(' ')[0] : '–';
    const exp = calculateExperience(hireDate);
    const gender = emp.gender === 'male' ? '🟥 Мужской' : emp.gender === 'female' ? '🟦 Женский' : '—';

    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${i + 1}</td>
      <td>${emp.full_name}</td>
      <td>${formatPhone(emp.phone)}</td>
      <td>${formatPosition(emp.position_code)}</td>
      <td>${formatDepartment(emp.department)}</td>
      <td>${hireDate}</td>
      <td>${exp}</td>
      <td>${emp.role}</td>
      <td>${gender}</td>
      <td>
        <button class="btn-edit" data-id="${emp.id}">Редактировать</button>
        <button class="btn-delete" data-id="${emp.id}">Удалить</button>
      </td>
    `;
    tbody.appendChild(row);
  });

  // Очистка и перепривязка обработчиков
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.removeEventListener('click', openEditModal);
    btn.addEventListener('click', openEditModal);
  });

  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.removeEventListener('click', deleteEmployee);
    btn.addEventListener('click', deleteEmployee);
  });
}

// --- Загрузка списка ---
async function loadEmployees() {
  try {
    const response = await fetch(`${API_URL}?action=list`);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();
    if (!Array.isArray(data)) throw new Error('Ожидался массив');
    allEmployees = data;
    applyFilters();
  } catch (err) {
    console.error('❌ Ошибка загрузки:', err);
    showMessage('Не удалось загрузить список сотрудников', 'error');
  }
}

// --- Модальное окно ---
const modal = document.getElementById('employeeModal');
const closeModal = document.querySelector('.close');
const modalTitle = document.getElementById('modalTitle');
const employeeForm = document.getElementById('employeeForm');

// Кнопка "Добавить"
document.getElementById('addEmployeeBtn')?.addEventListener('click', () => {
  modal.style.display = 'block';
  modalTitle.textContent = 'Добавить сотрудника';
  employeeForm.reset();
  document.getElementById('employeeId').value = '';
  document.getElementById('password').required = true;
});

// Закрытие по крестику
closeModal?.addEventListener('click', () => {
  modal.style.display = 'none';
});

// Закрытие по клику вне окна
window.addEventListener('click', (e) => {
  if (e.target === modal) {
    modal.style.display = 'none';
  }
});

// --- Открытие формы редактирования ---
async function openEditModal(e) {
  const id = e.target.dataset.id;
  try {
    const response = await fetch(`${API_URL}?action=get&id=${id}`);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const emp = await response.json();

    document.getElementById('employeeId').value = emp.id;
    document.getElementById('fullName').value = emp.full_name;
    document.getElementById('phone').value = emp.phone;
    document.getElementById('role').value = emp.role;
    document.getElementById('position').value = emp.position_code || '';
    document.getElementById('department').value = emp.department || '';
    document.getElementById('gender').value = emp.gender || '';
    document.getElementById('hire_date').value = emp.hire_date || '';
    document.getElementById('password').required = false;

    modalTitle.textContent = 'Редактировать сотрудника';
    modal.style.display = 'block';
  } catch (err) {
    console.error('❌ Ошибка загрузки сотрудника:', err);
    showMessage('Не удалось загрузить данные сотрудника', 'error');
  }
}

// --- Удаление сотрудника ---
async function deleteEmployee(e) {
  const id = e.target.dataset.id;
  if (!confirm('Вы уверены, что хотите удалить этого сотрудника?')) return;

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id })
    });

    let result;
    try {
      result = await response.json();
    } catch (jsonError) {
      const text = await response.text();
      console.error('❌ Ответ не JSON:', text);
      showMessage('Ошибка: сервер вернул битый ответ', 'error');
      return;
    }

    if (result.success) {
      showMessage('Сотрудник удалён');
      loadEmployees();
    } else {
      showMessage('Ошибка: ' + (result.error || 'неизвестная'), 'error');
    }
  } catch (err) {
    console.error('❌ Ошибка удаления:', err);
    showMessage('Сетевая ошибка при удалении', 'error');
  }
}

// --- Сохранение формы ---
employeeForm?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const id = document.getElementById('employeeId').value;
  const fullName = document.getElementById('fullName').value.trim();
  const phone = document.getElementById('phone').value.replace(/\D/g, '');
  const role = document.getElementById('role').value;
  const positionCode = document.getElementById('position').value;
  const department = document.getElementById('department').value;
  const gender = document.getElementById('gender').value;
  const hireDate = document.getElementById('hire_date').value;
  const password = document.getElementById('password').value;

  // Валидация
  if (!fullName) {
    showMessage('ФИО обязательно для заполнения', 'error');
    return;
  }
  if (phone.length !== 11) {
    showMessage('Телефон должен содержать 11 цифр', 'error');
    return;
  }

  // Подготовка данных
  const data = {
    action: id ? 'update' : 'create',
    full_name: fullName,
    phone,
    role,
    position_code: positionCode,
    department,
    gender
  };

  if (hireDate) data.hire_date = hireDate;
  if (password) data.password = password;
  if (id) data.id = parseInt(id, 10); // Убедимся, что ID — число

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    // Проверка HTTP-статуса
    if (!response.ok) {
      const text = await response.text();
      console.error('❌ HTTP ошибка:', response.status, text);
      showMessage(`Ошибка ${response.status}: сервер не ответил`, 'error');
      return;
    }

    // Парсинг JSON с защитой
    let result;
    try {
      result = await response.json();
    } catch (jsonError) {
      const text = await response.text();
      console.error('❌ Сервер вернул не JSON:', text);
      showMessage('Ошибка: сервер вернул битый ответ', 'error');
      return;
    }

    // Обработка результата
    if (result.success) {
      showMessage(id ? 'Сотрудник успешно обновлён' : 'Сотрудник добавлен');
      modal.style.display = 'none'; // Закрываем окно
      employeeForm.reset();         // Очищаем форму
      loadEmployees();              // Обновляем список
    } else {
      showMessage('Ошибка: ' + (result.error || 'неизвестная'), 'error');
    }
  } catch (err) {
    console.error('❌ Сетевая ошибка при сохранении:', err);
    showMessage('Не удалось подключиться к серверу', 'error');
  }
});

// --- Экспорт в Excel ---
document.getElementById('exportExcelBtn')?.addEventListener('click', () => {
  if (typeof XLSX === 'undefined') {
    showMessage('Ошибка: библиотека XLSX не загружена', 'error');
    return;
  }

  const wb = XLSX.utils.book_new();
  const wsData = [
    ['ФИО', 'Телефон', 'Должность', 'Отдел', 'Роль', 'Дата найма', 'Пол']
  ];

  filteredEmployees.forEach(emp => {
    wsData.push([
      emp.full_name,
      formatPhone(emp.phone),
      formatPosition(emp.position_code),
      formatDepartment(emp.department),
      emp.role,
      emp.created_at ? emp.created_at.split(' ')[0] : '',
      emp.gender === 'male' ? 'Мужской' : emp.gender === 'female' ? 'Женский' : ''
    ]);
  });

  const ws = XLSX.utils.aoa_to_sheet(wsData);
  XLSX.utils.book_append_sheet(wb, ws, 'Сотрудники');
  XLSX.writeFile(wb, `Сотрудники_${new Date().toISOString().split('T')[0]}.xlsx`);
});

// --- Генерация шаблона ---
document.getElementById('downloadTemplateBtn')?.addEventListener('click', () => {
  if (typeof XLSX === 'undefined') {
    showMessage('Ошибка: библиотека XLSX не загружена', 'error');
    return;
  }

  const wb = XLSX.utils.book_new();
  const wsData = [
    ['ФИО', 'Телефон', 'Должность', 'Отдел', 'Роль'],
    ['Иванов Иван Иванович', '79991234567', 'nurse', 'floor_1', 'employee']
  ];
  const ws = XLSX.utils.aoa_to_sheet(wsData);
  XLSX.utils.book_append_sheet(wb, ws, 'Шаблон');
  XLSX.writeFile(wb, 'Шаблон_импорта_сотрудников.xlsx');
});

// --- Импорт из Excel ---
document.getElementById('importExcel')?.addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      const sheet = workbook.Sheets[workbook.SheetNames[0]];
      const json = XLSX.utils.sheet_to_json(sheet);

      if (!Array.isArray(json) || json.length === 0) {
        showMessage('Файл пуст или повреждён', 'error');
        return;
      }

      const imported = [];
      const errors = [];

      const validRoles = ['employee', 'senior_nurse', 'director'];
      const validPositions = [
        'sanitar', 'sanitarka', 'sidelka', 'vanshiza',
        'assistant', 'nurse', 'senior_nurse', 'director'
      ];

      for (let i = 0; i < json.length; i++) {
        const row = json[i];
        const fio = String(row['ФИО'] || row['фио'] || row['Ф.И.О.'] || '').trim();
        const phoneRaw = String(row['Телефон'] || row['телефон'] || '').replace(/\D/g, '');
        const pos = String(row['Должность'] || row['должность'] || '').trim();
        const dep = String(row['Отдел'] || row['отдел'] || '').trim();
        const role = String(row['Роль'] || row['роль'] || 'employee').trim().toLowerCase();

        if (!fio) {
          errors.push(`Строка ${i + 2}: не указано ФИО`);
          continue;
        }

        if (phoneRaw.length === 11 && phoneRaw[0] === '8') {
          phoneRaw = '7' + phoneRaw.slice(1);
        }

        if (phoneRaw.length !== 11) {
          errors.push(`Строка ${i + 2}: некорректный телефон — ${phoneRaw}`);
          continue;
        }

        if (pos && !validPositions.includes(pos)) {
          errors.push(`Строка ${i + 2}: неизвестная должность — ${pos}`);
          continue;
        }

        if (!validRoles.includes(role)) {
          errors.push(`Строка ${i + 2}: недопустимая роль — ${role}`);
          continue;
        }

        imported.push({
          full_name: fio,
          phone: phoneRaw,
          position_code: pos || 'employee',
          department: dep || null,
          role: role,
          gender: detectGender(fio)
        });
      }

      if (imported.length === 0) {
        showMessage('Нет корректных данных для импорта', 'error');
        return;
      }

      fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulk_create', employees: imported })
      })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          const msg = errors.length > 0
            ? `Импортировано: ${result.imported}, но есть ${errors.length} ошибок`
            : `Импортировано: ${result.imported} сотрудников`;
          showMessage(msg, errors.length > 0 ? 'warning' : 'success');
          loadEmployees();
        } else {
          showMessage('Ошибка сервера: ' + (result.error || 'неизвестная'), 'error');
        }
      })
      .catch(err => {
        console.error('❌ Ошибка импорта:', err);
        showMessage('Сетевая ошибка при импорте', 'error');
      });

    } catch (err) {
      console.error('❌ Ошибка обработки XLSX:', err);
      showMessage('Не удалось прочитать файл. Проверьте формат XLSX.', 'error');
    }

    e.target.value = '';
  };

  reader.readAsArrayBuffer(file);
});

// --- Определение пола ---
function detectGender(fio) {
  const parts = fio.split(' ').filter(Boolean);
  const lastName = parts[1] || parts[0];
  const femaleEndings = ['а', 'я', 'ия', 'на', 'ва', 'га', 'да', 'за', 'ка', 'ла', 'ма', 'на', 'ра', 'са', 'та', 'ва'];
  return femaleEndings.some(end => lastName.endsWith(end)) ? 'female' : 'male';
}

// --- Показ сообщения ---
function showMessage(text, type = 'success') {
  const msg = document.getElementById('message');
  if (!msg) return;

  msg.textContent = text;
  msg.className = `message ${type}`;
  msg.style.display = 'block';
  msg.classList.add('show');

  setTimeout(() => {
    msg.classList.remove('show');
    setTimeout(() => { msg.style.display = 'none'; }, 300);
  }, 5000);
}

// --- Инициализация ---
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('searchInput')?.addEventListener('input', applyFilters);
  document.getElementById('positionFilter')?.addEventListener('change', applyFilters);
  loadEmployees();
});
