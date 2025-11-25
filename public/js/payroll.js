document.addEventListener('DOMContentLoaded', () => {
    const loadBtn = document.getElementById('loadPayroll');
    const resultEl = document.getElementById('payrollResult');

    const userRole = document.body.dataset.role;
    const myId = parseInt(document.body.dataset.userId) || 0;
    const employeeSelect = document.getElementById('employeeSelect');

    if (employeeSelect && !['admin', 'director'].includes(userRole)) {
        const options = employeeSelect.querySelectorAll('option');
        let found = false;
        for (let opt of options) {
            if (parseInt(opt.value) === myId) {
                employeeSelect.value = myId;
                found = true;
                break;
            }
        }
        if (!found) {
            console.warn('Текущий пользователь не найден в списке сотрудников');
            resultEl.innerHTML = '<div class="alert alert-error">Вы не найдены в системе как сотрудник.</div>';
            return;
        }
        employeeSelect.disabled = true;
    }

    const formatNum = (num, digits = 2) => {
        const value = typeof num === 'number' ? num : 0;
        return value.toLocaleString('ru-RU', {
            minimumFractionDigits: digits,
            maximumFractionDigits: digits
        });
    };

    loadBtn?.addEventListener('click', async () => {
        const employeeId = employeeSelect?.value || myId;
        const periodInput = document.getElementById('period');
        const period = periodInput?.value;

        if (!employeeId || !period) {
            resultEl.innerHTML = '<div class="alert alert-error">Выберите сотрудника и период</div>';
            return;
        }

        const [year, month] = period.split('-');
        if (!year || !month) {
            resultEl.innerHTML = '<div class="alert alert-error">Некорректный период</div>';
            return;
        }

        if (!['admin', 'director'].includes(userRole) && parseInt(employeeId) !== myId) {
            resultEl.innerHTML = '<div class="alert alert-error">Вы можете просматривать только свой расчётный лист</div>';
            return;
        }

        resultEl.innerHTML = '<div class="alert alert-info">Загрузка данных...</div>';

        try {
            const response = await fetch(`/public/api/calculate_payroll.php?employee_id=${employeeId}&year=${year}&month=${month}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            if (!data.success) {
                resultEl.innerHTML = `<div class="alert alert-error">${data.error || 'Неизвестная ошибка'}</div>`;
                return;
            }

            const monthName = new Date(parseInt(year), parseInt(month) - 1)
                .toLocaleString('ru', { month: 'long', year: 'numeric' });

            const northPercent = (data.rates_used?.north * 100) || 0;

            resultEl.innerHTML = `
              <div class="payroll-card" id="printArea">
                <h3>Расчётный лист за ${monthName}</h3>
                <p><strong>ФИО:</strong> ${data.employee.full_name || '—'}</p>
                <p><strong>Должность:</strong> ${data.employee.position_title || '—'}</p>
                <p><strong>Подразделение:</strong> ${data.employee.department || '—'}</p>

                <table class="payroll-table">
                  <tr><td>Оклад</td><td>${formatNum(data.base_salary)} ₽</td></tr>
                  <tr><td>Отработано часов</td><td>${data.hours_worked || 0} ч (из ${data.norm_hours?.toFixed(1) || 0})</td></tr>
                  <tr><td>Ночные часы</td><td>${data.night_hours || 0} ч</td></tr>
                  <tr><td>Оплата ночных часов</td><td>${formatNum(data.night_bonus)} ₽</td></tr>
                  <tr><td>Надбавка за стаж (${data.experience_years || 0} лет)</td><td>${formatNum(data.experience_bonus)} ₽</td></tr>
                  <tr><td>Доплата за вредность</td><td>${formatNum(data.harmful_bonus)} ₽</td></tr>
                  <tr><td>Доплата за специфику труда</td><td>${formatNum(data.special_work_bonus)} ₽</td></tr>
                  <tr class="divider"><td colspan="2"></td></tr>
                  <tr><td>Сумма до коэффициентов</td><td>${formatNum(data.subtotal)} ₽</td></tr>
                  <tr><td>Районный коэффициент</td><td>${formatNum(data.rayon_coeff_sum)} ₽</td></tr>
                  <tr><td>Северная надбавка</td><td>${formatNum(data.north_bonus_sum)} ₽</td></tr>
                  <tr class="total"><td><strong>Итого к выплате</strong></td><td><strong>${formatNum(data.total_pay)} ₽</strong></td></tr>
                </table>

                <div class="signatures">
                  <div>__________________<br>Бухгалтер</div>
                  <div>__________________<br>Работник</div>
                </div>
              </div>

              <div class="action-buttons" style="margin-top: 20px;">
                <button onclick="printPayroll()" class="btn-action" data-color="green">🖨 Печать PDF</button>
              </div>
            `;
        } catch (err) {
            console.error('Ошибка при загрузке расчётного листа:', err);
            resultEl.innerHTML = `<div class="alert alert-error">Ошибка: ${err.message}</div>`;
        }
    });

    window.printPayroll = () => {
        const printArea = document.getElementById('printArea');
        if (!printArea) {
            alert('Не удалось найти содержимое для печати.');
            return;
        }

        const printContent = printArea.innerHTML;
        const w = window.open('', '_blank');
        w.document.write(`
          <html>
            <head>
              <title>Расчётный лист</title>
              <link rel="stylesheet" href="/public/css/Pages/payroll.css">
              <style>
                @page { margin: 1cm; }
                body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
                .signatures { margin-top: 40px; display: flex; justify-content: space-between; }
              </style>
            </head>
            <body onload="window.print()">
              ${printContent}
              <script>
                window.onafterprint = () => window.close();
              </script>
            </body>
          </html>
        `);
        w.document.close();
    };

    if (loadBtn) loadBtn.click();
});
