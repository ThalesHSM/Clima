const monthTitle = document.getElementById('month-title');
const prevBtn = document.getElementById('prev');
const nextBtn = document.getElementById('next');
const calendarEl = document.getElementById('calendar');

const now = new Date();
let year = now.getFullYear();
let month = now.getMonth() + 1; // 1-12

const pad = (n) => String(n).padStart(2, '0');
const isCurrentMonth = () => year === now.getFullYear() && month === now.getMonth() + 1;

async function init() {
    await requireLogin();
    document.getElementById('logout').addEventListener('click', logout);

    prevBtn.addEventListener('click', () => changeMonth(-1));
    nextBtn.addEventListener('click', () => changeMonth(1));

    await Promise.all([loadMonth(), loadList()]);
}

function changeMonth(delta) {
    month += delta;
    if (month < 1) { month = 12; year--; }
    if (month > 12) { month = 1; year++; }
    loadMonth();
}

// ---------- Calendário e estatísticas ----------

async function loadMonth() {
    const data = await api(`/month?month=${year}-${pad(month)}`);

    monthTitle.textContent = new Date(year, month - 1, 1)
        .toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    nextBtn.disabled = isCurrentMonth(); // não existe "mês que vem"

    renderStats(data.stats);
    renderCalendar(data.days);
}

function renderStats(s) {
    document.getElementById('st-total').textContent = s.total;
    document.getElementById('st-avg').textContent = String(s.average).replace('.', ',');
    document.getElementById('st-top').textContent = s.top ? s.top.title : '—';
    document.getElementById('st-top-count').textContent = s.top ? `${s.top.count}x no mês` : 'sem ações no mês';
    document.getElementById('st-co2').textContent = s.co2 > 0 ? formatCo2(s.co2) : '—';
}

function level(day) {
    if (day.done === 0) return 0;
    if (day.pct <= 25) return 1;
    if (day.pct <= 50) return 2;
    if (day.pct <= 75) return 3;
    return 4;
}

function renderCalendar(days) {
    calendarEl.innerHTML = '';
    const today = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

    for (const name of ['D', 'S', 'T', 'Q', 'Q', 'S', 'S']) {
        const h = document.createElement('div');
        h.className = 'cal-head';
        h.textContent = name;
        calendarEl.append(h);
    }

    // células vazias até o dia da semana em que o mês começa (0 = domingo)
    const offset = new Date(year, month - 1, 1).getDay();
    for (let i = 0; i < offset; i++) {
        calendarEl.append(document.createElement('div'));
    }

    for (const day of days) {
        const cell = document.createElement('div');
        cell.className = `cal-day lvl-${level(day)}`;
        if (day.future) cell.classList.add('future');
        if (day.date === today) cell.classList.add('today');
        cell.textContent = Number(day.date.slice(8));
        cell.title = day.future
            ? ''
            : `${day.date.split('-').reverse().join('/')}: ${day.done} de ${day.total} ações (${day.pct}%)`;
        calendarEl.append(cell);
    }
}

// ---------- Lista dos últimos 30 dias ----------

async function loadList() {
    const { days } = await api('/history');
    const box = document.getElementById('days');
    document.getElementById('empty').hidden = days.length > 0;

    for (const day of days) {
        const card = document.createElement('div');
        card.className = 'card day';

        const title = document.createElement('h3');
        title.textContent = formatDate(day.date) + ' ';
        const count = document.createElement('span');
        count.className = 'count';
        count.textContent = `(${day.items.length} ${day.items.length === 1 ? 'ação' : 'ações'})`;
        title.append(count);
        if (day.closed) {
            const badge = document.createElement('span');
            badge.className = 'badge';
            badge.textContent = 'finalizado';
            title.append(' ', badge);
        }

        const ul = document.createElement('ul');
        for (const item of day.items) {
            const li = document.createElement('li');
            li.textContent = item;
            ul.append(li);
        }

        card.append(title, ul);
        box.append(card);
    }
}

init();
