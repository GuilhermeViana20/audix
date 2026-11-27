// Campos marcados com data-maiusculas: o que o usuário digita vira maiúscula na hora.
// O listener é de captura no document, então roda antes do v-model do Vue (que já recebe o texto
// em maiúsculas) e de qualquer outro listener da página.
document.addEventListener('input', (evento) => {
    const campo = evento.target;
    if (!campo.matches?.('[data-maiusculas]') || evento.isComposing) return;
    const maiusculo = campo.value.toLocaleUpperCase('pt-BR');
    if (maiusculo === campo.value) return;
    // Mantém o cursor no lugar (a troca do valor o jogaria para o fim)
    const { selectionStart: inicio, selectionEnd: fim } = campo;
    campo.value = maiusculo;
    campo.setSelectionRange?.(inicio, fim);
}, true);
