// Ao enviar um formulário, o botão de envio mostra um spinner e fica bloqueado até a resposta.
document.addEventListener('submit', (evento) => {
    if (evento.defaultPrevented) return; // ex.: confirmação de remoção cancelada

    const formulario = evento.target;
    const botao = evento.submitter
        || formulario.querySelector('button[type="submit"], button:not([type])');
    if (!botao || botao.disabled) return;

    const texto = formulario.dataset.loadingTexto || 'Salvando…';
    botao.dataset.textoOriginal = botao.innerHTML;
    botao.disabled = true;
    botao.classList.add('opacity-80', 'cursor-wait');
    botao.innerHTML = '<span class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent align-[-2px]"></span>' + texto;
});
