import * as pdfjs from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { interpretarPlanilha } from './interpretarPlanilha.js';

pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;


/** Lê os itens de texto de todas as páginas, com posição (x, y) e se estão em negrito. */
export async function lerItens(arquivo) {
    const pdf = await pdfjs.getDocument({ data: await arquivo.arrayBuffer() }).promise;
    const paginas = [];
    for (let n = 1; n <= pdf.numPages; n++) {
        const pagina = await pdf.getPage(n);
        const conteudo = await pagina.getTextContent();
        paginas.push(conteudo.items
            .filter(i => i.str && i.str.trim())
            .map(i => ({
                texto: i.str.trim(),
                x: i.transform[4],
                y: pagina.view[3] - i.transform[5], // y cresce para baixo
                negrito: /bold|black|heavy/i.test(conteudo.styles[i.fontName]?.fontFamily || i.fontName || ''),
            })));
    }
    return paginas;
}

/** Lê a planilha de monitoramento em PDF: [{ nome, titulo_primeira_coluna, linhas: [{ tipo, texto }] }]. */
export async function lerPlanilhaPdf(arquivo) {
    return interpretarPlanilha(await lerItens(arquivo));
}
