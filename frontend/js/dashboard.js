const listEl = document.getElementById('habits');
const emptyEl = document.getElementById('empty');
const barEl = document.getElementById('bar');
const progressText = document.getElementById('progress-text');
const addForm = document.getElementById('add-form');
const newTitle = document.getElementById('new-title');
const finishBtn = document.getElementById('finish');
const closedMsg = document.getElementById('closed-msg');

let habits = [];
let closed = false;
let editingId = null; // id da ação que está sendo editada

async function init() {
    const user = await requireLogin();
    document.getElementById('hello').textContent = `Olá, ${user.name}!`;
    document.getElementById('logout').addEventListener('click', logout);
    await load();
}

async function load() {
    const data = await api('/habits');
    habits = data.habits;
    closed = data.closed;
    document.getElementById('today').textContent = formatDate(data.today);
    render();
}

function render() {
    listEl.innerHTML = '';
    emptyEl.hidden = habits.length > 0;

    for (const h of habits) {
        const li = document.createElement('li');
        li.className = h.done ? 'done' : '';
        li.append(...(h.id === editingId ? editRow(h) : viewRow(h)));
        listEl.append(li);
    }
    updateProgress();

    closedMsg.hidden = !closed;
    addForm.hidden = closed;
    finishBtn.textContent = closed ? 'Reabrir o dia' : 'Finalizar o dia';
    finishBtn.classList.toggle('reopen', closed);
}

function iconButton(text, title, onClick, extraClass = '') {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'icon-btn ' + extraClass;
    b.title = title;
    b.textContent = text;
    b.addEventListener('click', onClick);
    return b;
}

function viewRow(h) {
    const box = document.createElement('input');
    box.type = 'checkbox';
    box.id = 'habit-' + h.id;
    box.checked = h.done;
    box.disabled = closed; // dia finalizado: travado
    box.addEventListener('change', () => toggle(h, box.checked));

    const label = document.createElement('label');
    label.htmlFor = box.id;
    label.textContent = h.title; // textContent evita injeção de HTML

    return [
        box, label,
        iconButton('✎', 'Editar texto', () => { editingId = h.id; render(); }),
        iconButton('✕', 'Remover da lista', () => remove(h), 'danger'),
    ];
}

function editRow(h) {
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'edit-input';
    input.value = h.title;
    input.maxLength = 120;
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') save(h, input.value);
        if (e.key === 'Escape') { editingId = null; render(); }
    });
    setTimeout(() => input.focus(), 0);

    return [
        input,
        iconButton('✓', 'Salvar', () => save(h, input.value)),
        iconButton('↩', 'Cancelar', () => { editingId = null; render(); }),
    ];
}

async function save(habit, title) {
    title = title.trim();
    if (!title || title === habit.title) {
        editingId = null;
        render();
        return;
    }
    try {
        await api('/habits/update', 'POST', { id: habit.id, title });
        editingId = null;
        load();
    } catch (err) {
        alert(err.message);
    }
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

finishBtn.addEventListener('click', async () => {
    try {
        await api('/habits/close-day', 'POST', { closed: !closed });
        load(); // recarrega para atualizar a sequência
    } catch (err) {
        alert(err.message);
    }
});

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
