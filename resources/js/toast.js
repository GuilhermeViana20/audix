// Notificações (toasts) no canto inferior direito: sucesso ou erro, fecham sozinhas.
function garantirContainer() {
    let container = document.getElementById('toasts');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toasts';
        container.className = 'pointer-events-none fixed bottom-4 right-4 z-50 flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2';
        document.body.appendChild(container);
    }
    return container;
}

window.toast = function (texto, tipo = 'sucesso') {
    const erro = tipo === 'erro';
    const el = document.createElement('div');
    el.setAttribute('role', 'status');
    el.className = 'pointer-events-auto flex items-start gap-3 rounded-xl border-l-4 bg-white px-4 py-3 text-sm text-grafite shadow-lg ring-1 ring-grafite/5 transition duration-300 translate-y-2 opacity-0 '
        + (erro ? 'border-red-500' : 'border-azul');

    const icone = document.createElement('span');
    icone.className = 'mt-px flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white '
        + (erro ? 'bg-red-500' : 'bg-azul');
    icone.textContent = erro ? '!' : '✓';

    const mensagem = document.createElement('p');
    mensagem.className = 'flex-1 leading-snug';
    mensagem.textContent = texto;

    const fechar = document.createElement('button');
    fechar.type = 'button';
    fechar.setAttribute('aria-label', 'Fechar');
    fechar.className = 'text-grafite/40 transition hover:text-grafite';
    fechar.textContent = '×';

    el.append(icone, mensagem, fechar);
    garantirContainer().appendChild(el);

    const remover = () => {
        el.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => el.remove(), 300);
    };
    fechar.addEventListener('click', remover);
    requestAnimationFrame(() => el.classList.remove('translate-y-2', 'opacity-0'));
    setTimeout(remover, erro ? 5000 : 3500);
};
