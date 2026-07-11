# KoreUi — instrucciones de sesión

> Las instrucciones generales del monorepo (stack, convenciones, flujo de trabajo) viven en `../CLAUDE.md`, en la raíz. Este archivo solo añade la integración del grafo de conocimiento de la librería.

## graphify

Esta librería tiene un grafo de conocimiento en `graphify-out/` (construido a partir del código de `kore-ui/`) con god nodes, estructura de comunidades y relaciones cross-file. Se generó con `/graphify` usando la parte estructural (AST) de graphify + subagentes Claude Sonnet para la capa semántica.

Reglas:
- SIEMPRE lee `graphify-out/GRAPH_REPORT.md` antes de leer archivos fuente, correr grep/glob o responder preguntas sobre el código. Es el mapa primario de la librería.
- Para preguntas cross-módulo del tipo "cómo se relaciona X con Y", prefiere `graphify query "<pregunta>"`, `graphify path "<A>" "<B>"` o `graphify explain "<concepto>"` sobre grep — recorren las aristas EXTRACTED + INFERRED del grafo en vez de escanear archivos.
- Tras modificar código, corre `graphify update .` **desde el directorio `kore-ui/`** para mantener el grafo al día (solo AST, sin costo de LLM). El hook post-commit ya hace esto automáticamente tras cada commit.
- Si cambian docs/comentarios y hace falta re-extracción semántica, corre `/graphify --update` (usa subagentes Sonnet, no la API de Gemini).
- Para reconstruir desde cero: `/graphify kore-ui/` — la capa semántica SIEMPRE se hace con subagentes Sonnet, nunca con Gemini.
