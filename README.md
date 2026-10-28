# PAM Native Drawer

Plugin opcional `pushinbr/pam-native-drawer` para PHP 8.5 e PAM Native 1.46.
Código Apache-2.0. Android API 26+ e iOS 15+.

```sh
composer require pushinbr/pam-native-drawer:0.1.0
```

O Composer e o launcher PAM descobrem `pam-native.plugin.json`, registram o
provider PHP e compilam as fontes nativas. Não há instalação manual de módulo.
É necessário reconstruir o host nativo após adicionar o pacote.

## Uso

```php
use Pam\Native\Drawer\DrawerRouter;
use Pam\Native\Drawer\DrawerType;
use Pam\Native\UI\{Screen, Text};

$navigator = (new DrawerRouter('home'))
    ->route('home', 'Início', Screen::make(Text::make('Início')))
    ->route('settings', 'Configurações', Screen::make(Text::make('Configurações')))
    ->presentation(DrawerType::Front)
    ->responsive(840)
    ->build();
```

`DrawerRoutes::make()` e `DrawerRoutes::screen()` oferecem grupos declarativos.
`DrawerLayoutAndroid::make($content, $drawer)` é o host básico nas duas plataformas;
o nome histórico é preservado. O provider registra também `<DrawerLayoutAndroid>`.
Os enums e o `NavigationDrawerItem` ficam em `Pam\Native\Drawer`.

O plugin preserva gestos, back, grupos, restauração e apresentação responsiva.
Android cancela animações ao sair da janela. O host recebe propriedades e eventos
por `NativePropertyView`; o iOS insere filhos pelo gancho genérico `NativeChildHost`.
Os IDs históricos do protocolo permanecem reservados e não são renumerados.

## Migração e compatibilidade

Os nomes antigos do núcleo são shims `MovedToPlugin` por uma versão menor.
Com o plugin, tornam-se aliases e emitem depreciação; sem ele, o erro informa
`composer require pushinbr/pam-native-drawer`. Migre imports para
`Pam\Native\Drawer`. Nenhuma dependência deste plugin é imposta a outros apps.

## Testes

As regressões PHP foram movidas para `tests/core-compat.php`. Para executá-las,
use o PHP 8.5 no checkout do framework:

```sh
PAM_TEST_PLUGIN_ROOTS=/caminho/para/este-plugin php packages/native/tests/run.php
```

Os testes Android estão em `android/src/test` ou `android/src/androidTest`,
conforme o pacote. Os testes instrumentados precisam de um host de teste PAM.
O pacote de testes `ios/Package.swift` aceita `PAM_NATIVE_CORE=/checkout/core`.
Execute os testes Swift em um simulador iOS; o módulo de teste é independente
do nome de módulo que o launcher gera no host de produção.

A validação em emulador deve incluir tema claro/escuro, teclado e safe areas,
movimento reduzido, substituição e fechamento da tela. Consulte `RELATORIO.md`
para a evidência efetivamente executada nesta versão.
