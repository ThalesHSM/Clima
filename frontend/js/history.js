async function init() {
    await requireLogin();
    document.getElementById('logout').addEventListener('click', logout);

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
