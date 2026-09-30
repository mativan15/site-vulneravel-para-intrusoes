# Relatório (LaTeX)

Formato igual ao do **Laboratório 5** da disciplina (`capa.tex`, `main.tex`, `texto/conteudo.tex`).

Gerar o PDF:

```bash
cd relatorio
pdflatex main.tex
pdflatex main.tex
```

O PDF sai como `main.pdf`. O conteúdo técnico está em `texto/conteudo.tex`.

Se a compilação falhar com `phvr8t` / Helvetica, instale as fontes recomendadas do TeX Live (por exemplo `texlive-fonts-recommended` no Linux ou o pacote completo no MacTeX).
