const listEl = document.getElementById('habits');
const emptyEl = document.getElementById('empty');
const barEl = document.getElementById('bar');
const progressText = document.getElementById('progress-text');
const addForm = document.getElementById('add-form');
const newTitle = document.getElementById('new-title');

let habits = [];

async function init() {
    const user = await requireLogin();
    document.getElementById('hello').textContent = `Olá, ${user.name}!`;
    document.getElementById('logout').addEventListener('click', logout);
    await load();
}

async function load() {
    const data = await api('/habits');
    habits = data.habits;
    document.getElementById('today').textContent = formatDate(data.today);
    render();
}

function render() {
    listEl.innerHTML = '';
    emptyEl.hidden = habits.length > 0;

    for (const h of habits) {
        const li = document.createElement('li');
        li.className = h.done ? 'done' : '';

        const box = document.createElement('input');
        box.type = 'checkbox';
        box.id = 'habit-' + h.id;
        box.checked = h.done;
        box.addEventListener('change', () => toggle(h, box.checked));

        const label = document.createElement('label');
        label.htmlFor = box.id;
        label.textContent = h.title; // textContent evita injeção de HTML

        const del = document.createElement('button');
        del.className = 'remove';
        del.title = 'Remover da lista';
        del.textContent = '✕';
        del.addEventListener('click', () => remove(h));

        li.append(box, label, del);
        listEl.append(li);
    }
    updateProgress();
}

function updateProgress() {
    const done = habits.filter((h) => h.done).length;
    const pct = habits.length ? Math.round((done / habits.length) * 100) : 0;
    barEl.style.width = pct + '%';
    progressText.textContent = `${done} de ${habits.length} ações feitas hoje`;
}

async function toggle(habit, done) {
    habit.done = done;
    render();
    try {
        await api('/habits/toggle', 'POST', { id: habit.id, done });
    } catch (err) {
        alert(err.message);
        load();
    }
}

async function remove(habit) {
    if (!confirm(`Remover "${habit.title}" da sua lista? O histórico será mantido.`)) return;
    await api('/habits/delete', 'POST', { id: habit.id });
    load();
}

addForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const title = newTitle.value.trim();
    if (!title) return;
    try {
        await api('/habits', 'POST', { title });
        newTitle.value = '';
        load();
    } catch (err) {
        alert(err.message);
    }
});

init();
