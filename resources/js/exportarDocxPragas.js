// Botão "Exportar Word" da tela de controle de pragas (CIP).
// As seções são lidas das folhas na tela, para refletir renomeações feitas sem recarregar a página.
document.addEventListener('DOMContentLoaded', () => {
    const botao = document.getElementById('exportar-word');
    const dados = window.AUDIX_PRAGAS;
    if (!botao || !dados) return;

    const rotulo = botao.querySelector('[data-rotulo]');
    botao.addEventListener('click', async () => {
        const secoes = [...document.querySelectorAll('[data-folha-secao] [data-nome-folha]')].map(el => el.textContent.trim());
        if (!secoes.length || botao.disabled) return;

        botao.disabled = true;
        rotulo.textContent = 'Gerando…';
        try {
            // A biblioteca do Word só é baixada quando o usuário exporta
            const { exportarPragasDocx } = await import('./exportarDocx.js');
            await exportarPragasDocx({ ...dados, secoes });
        } catch (e) {
            window.toast?.('Não consegui gerar o arquivo Word', 'erro');
        } finally {
            botao.disabled = false;
            rotulo.textContent = 'Exportar Word';
        }
    });
});
