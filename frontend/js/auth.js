// Usado por login.html e register.html
const form = document.getElementById('auth-form');
const errorBox = document.getElementById('error');
const isRegister = form.dataset.mode === 'register';

// Se já estiver logado, vai direto para o painel
api('/me').then(() => (location.href = 'dashboard.html')).catch(() => {});

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    errorBox.hidden = true;

    const data = Object.fromEntries(new FormData(form));
    try {
        await api(isRegister ? '/register' : '/login', 'POST', data);
        location.href = 'dashboard.html';
    } catch (err) {
        errorBox.textContent = err.message;
        errorBox.hidden = false;
    }
});
