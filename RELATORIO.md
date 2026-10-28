# Relatório u2 — core mínimo: Drawer e Toast

**Estado: WIP local, ainda não liberado para integração.**

## O que foi feito

- Drawer PHP, enums inteiros, registro de rotas e renderizadores Android/iOS extraídos para o candidato `pushinbr/pam-native-drawer` 0.1.0.
- Toast PHP, variantes, estilo e renderizadores Android/iOS extraídos para o candidato `pushinbr/pam-native-toast` 0.1.0.
- Dois plugins, pois navegação lateral e feedback são funcionalidades independentes; o real-world app usa somente Toast. Um pacote único obrigaria todos os consumidores de Toast a carregar Drawer.
- Shims `MovedToPlugin` para as 13 classes/enums históricos, com aliases e mensagem Composer quando o pacote não está instalado.
- Núcleo conserva os IDs reservados e o despacho para registros genéricos de views/módulos. Toast não tem mais apresentação no SystemModule: a operação histórica encaminha a `toast.show`.
- Drawer usa `NativePropertyView`; inserção de filhos no iOS usa o novo gancho genérico `NativeChildHost`. `Route::customNavigator()` gerencia nesting/raiz/finally, e `Route::screenContent()` mantém factories preguiçosas. As fachadas históricas delegam ao plugin.
- Regressões PHP e testes nativos de Drawer/Toast movidos junto das implementações. Adicionados testes do gancho genérico e de identidade dos aliases, READMEs, configuração Android de testes e pacote Swift de testes.
- Cancelamento de animações/timers em detach, substituição, background e fechamento do host. Ícones com decodificação limitada; cache de fontes limitado.
- Real-world app: imports de `ThemedToast` apontam para o plugin; manifesto solicita 0.1.0. **O lockfile definitivo ainda depende da publicação e integração.**
- Swipeable e os arquivos reservados de balões, scroll/lista e shared-element do chat não foram editados.

## Commits de implementação

- núcleo: `bea987a7` — commit WIP local, mensagem com `[skip ci]`.
- real-world app: `bfb0e897` — commit WIP local, mensagem com `[skip ci]`.
- Drawer: `d192b15` — commit WIP local, mensagem com `[skip ci]`.
- Toast: `d3ebc35` — commit WIP local, mensagem com `[skip ci]`.

Os commits de documentação posteriores registram este relatório. Nenhum merge, tag,
release, registro no Packagist ou push foi executado. Os diretórios de plugins são
repositórios Git locais; os repositórios públicos de destino ainda não foram criados.

## Validadores executados

| Verificação | Resultado |
| --- | --- |
| Suíte PHP completa do SDK sem plugins opcionais | 1/1 |
| Suíte PHP completa do SDK com Drawer + Toast e regressões de compatibilidade | 1/1 |
| Paridade do protocolo (`scripts/protocol-parity.py`) | 1/1 |
| Composer dos dois plugins (`validate --strict --no-check-publish`) | 2/2 |
| Sintaxe PHP dos arquivos extraídos | 20/20 |
| Validadores pertinentes do real-world app com PHP 8.5 PAM | 6/6 |
| Compilação/testes Android | 0/1 — aguardando recurso |
| Testes Swift/iOS | 0/1 — não executados; Mac proibido nesta rodada |
| Paridade visual RN/PAM no mesmo emulador | 0/1 — aguardando recurso |

Os seis validadores do app: `validate-feedback-toast.php`,
`validate-appearance-theme.php`, `validate-qa3b-s10.php`,
`validate-live-reactions-toasts.php`, `validate-misc-onboarding-toasts.php` e
`validate-navigation-bootstrap.php`. Rodaram com dependências temporárias locais,
sem alterar o lockfile real. O PHP 8.4 do sistema foi usado apenas pelo Composer e
pela checagem de sintaxe dos arquivos novos; as suítes e validadores do app usaram
`~/.local/share/pam/v2.2.7-linux-x86_64/bin/php`.

Preflight Composer: manifests e lock existentes inspecionados; metadados dos candidatos
locais consultados, dry-run restrito a núcleo + Toast aprovado; demais versões preservadas.
A autenticação do pacote privado foi montada de `hosts.yml` somente no ambiente do
subprocesso. Nenhuma credencial foi registrada em código/relatório. Metadados Maven
das dependências Android foram consultados; versões coincidem com as do framework.

Evidências: `/tmp/codex-u2-validation/core.log`, `plugins.log` e logs individuais dos
seis validadores. A instalação temporária precisou usar cópias em vez de symlinks,
pois o launcher rejeita comandos que escapem da raiz do projeto.
`.pam-native` foi apagado após cada rodada do app; `vendor` e os manifests/locks
temporários foram removidos ao encerrar a validação PHP. PHPStan não foi executado.

## Recursos e pendências

O disco começou abaixo do piso (16 GB), chegou a 30 GB e voltou a cair para 19 GB.
Nenhum build/emulador foi iniciado por esta tarefa. Os locks existentes pertencem a
`back-predictive` (Gradle, arquivo marcado 16:45) e `parity-feed` (emulador, 15:19).
Não foram removidos nem tomados destaques de outros trabalhos. A tentativa de obter
o lock usa `mkdir`, tem prazo de cinco minutos e só inicia Gradle após confirmar
pelo menos 20 GiB livres; se conseguir, usa `-Xmx3g`, quatro workers e `--stop` no final.

Faltam:

1. Pelo menos 20 GB livres e liberação dos locks; compilar o núcleo e os dois plugins,
   executar testes Android e corrigir qualquer falha nativa ainda não observável.
2. No emulador exclusivo, comparar o APK RN de produção e o APK PAM, com a mesma
   conta QA, e medir CPU/PSS/frames. Não há números ou screenshots inventados.
   Caminho de destino exigido: `scratchpad/codex/shots/codex-slim-drawer-toast/` sob
   o diretório de evidências fornecido na tarefa.
3. Validar Swift em ambiente iOS autorizado. Nenhum Mac ou aparelho físico foi usado.
4. Criar os repositórios públicos em `push-in`, publicar tags/releases 0.1.0 e registrar
   no Packagist. Só então gerar o lockfile definitivo do app, repetir seus checks e
   integrar/push nas branches `integrate/port3-20261027`, com pull --rebase antes.

Não houve conflitos de merge: as mudanças permanecem em branches próprias, a partir
de `origin/integrate/port3-20261027`. O fetch inicial reportou um erro de GC já existente
em outro worktree do clone compartilhado; a criação deste worktree foi concluída.
