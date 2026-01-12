
# Tutorial de Instalação: Laravel com Breeze, Tailwind, Font Awesome e SweetAlert2

Este guia descreve os passos para criar um novo projeto Laravel e configurar as ferramentas de frontend essenciais: Laravel Breeze (para autenticação com Blade/Alpine), Tailwind CSS (estilização), Font Awesome (ícones) e SweetAlert2 (alertas/modais elegantes).

**Pré-requisitos:**

*   PHP >= 8.2
*   Composer
*   Node.js e NPM (ou Yarn)
*   Um ambiente de desenvolvimento configurado (Ex: Docker com `docker-compose`, Laravel Sail, Herd, Laragon, Valet, etc.)
*   Banco de dados (MySQL, PostgreSQL, SQLite)

**Observação:** Este tutorial assume que você está usando NPM. Se usar Yarn, substitua `npm install` por `yarn add` e `npm run dev/build` por `yarn dev/build`. Os comandos `artisan` são para serem executados no terminal, dentro do diretório do seu projeto (ou dentro do container Docker, se aplicável, usando seus aliases como `artd`).

---

## Passo 1: Criar o Projeto Laravel

Crie um novo projeto Laravel usando o Composer:

```bash
composer create-project laravel/laravel nome-do-projeto
cd nome-do-projeto
```

*(Substitua `nome-do-projeto` pelo nome desejado).*

---

## Passo 2: Configurar Banco de Dados

1.  Crie um banco de dados para o projeto.
2.  Edite o arquivo `.env` na raiz do projeto e configure as variáveis de banco de dados (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
3.  Execute as migrações iniciais para criar as tabelas padrão do Laravel:
   
    ```bash
    php artisan migrate
    
    ```

---

## Passo 3: Instalar e Configurar Laravel Breeze (com Blade e Alpine.js)

Laravel Breeze fornece um scaffolding simples e elegante para autenticação.

1.  **Instalar Breeze via Composer:**
   
    ```bash
    composer require laravel/breeze --dev
    
    ```

2.  **Executar o Comando de Instalação do Breeze:**
   
    ```bash
    php artisan breeze:install
   
    ```
    *   **Selecione a stack:** Escolha `blade` (ou `0`).
    *   **Dark mode?** Escolha `yes` (ou `1`).
    *   **Testing framework?** Escolha `PHPUnit` (`0`) ou `Pest` (`1`).

3.  **Instalar Dependências NPM:** O Breeze adicionou dependências de frontend ao `package.json`. Instale-as:
   
    ```bash
    npm install
   
    ```

4.  **Compilar Assets (Desenvolvimento):** Rode o servidor de desenvolvimento Vite. Mantenha este comando rodando em um terminal separado enquanto desenvolve.
   
    ```bash
    npm run dev
    
    ```
    *(Para produção, você usará `npm run build` depois).*

5.  **Verificar:** Acesse seu projeto no navegador. Você deve ver a tela inicial do Laravel com links para Login e Registro. Teste o registro e login para confirmar que a autenticação está funcionando. O Breeze já configura o Tailwind CSS e Alpine.js automaticamente.

---

## Passo 4: Instalar e Configurar Font Awesome

Vamos instalar via NPM para integrar com o Vite.

1.  **Instalar Pacote NPM:**
   
    ```bash
    npm install --save-dev @fortawesome/fontawesome-free
   
    ```

2.  **Importar CSS:** Edite o arquivo `resources/css/app.css` e adicione a linha de importação do Font Awesome **após** as importações do Tailwind:

    ```css
    /* resources/css/app.css */
    @import 'tailwindcss/base';
    @import 'tailwindcss/components';
    @import 'tailwindcss/utilities';

    /* --- Adicione esta linha --- */
    @import '@fortawesome/fontawesome-free/css/all.css';

    /* Seus outros estilos personalizados podem vir aqui */
    ```

3.  **Verificar:** Se o `npm run dev` ainda estiver rodando, ele deve recompilar automaticamente. Adicione um ícone de teste em alguma view Blade (ex: `resources/views/dashboard.blade.php`) para confirmar:
   
    ```html
    <i class="fas fa-home"></i> <!-- Ícone de casa -->
    ```
    Acesse a página correspondente no navegador. O ícone deve aparecer.

---

## Passo 5: Instalar e Configurar SweetAlert2

SweetAlert2 é ótimo para pop-ups e confirmações mais bonitas que o `alert()` ou `confirm()` padrão do Javascript.

1.  **Instalar Pacote NPM:**
  
    ```bash
    npm install sweetalert2
    
    ```

2.  **Importar no JavaScript Principal:** Edite `resources/js/app.js` para importar o SweetAlert2 e, opcionalmente, torná-lo globalmente acessível (útil para chamá-lo de dentro do Alpine.js ou outros scripts).

    ```javascript
    // resources/js/app.js
    import './bootstrap';

    import Alpine from 'alpinejs';

    // --- Adicione estas linhas ---
    import Swal from 'sweetalert2';
    // Opcional: Adiciona o CSS do SweetAlert2 se quiser usar temas padrão
    // import 'sweetalert2/dist/sweetalert2.min.css'; // Você pode customizar depois

    window.Alpine = Alpine;
    window.Swal = Swal; // Torna Swal acessível globalmente (window.Swal)
    // --- Fim das adições ---

    Alpine.start();
    ```
    *Observação sobre o CSS:* Importar o CSS padrão (`sweetalert2.min.css`) é a forma mais fácil de começar. Se você quiser estilizar totalmente com Tailwind, pode pular a importação do CSS e aplicar classes Tailwind aos elementos do SweetAlert via configuração ou CSS customizado. Para começar, importar o CSS padrão é mais simples.

3.  **Importar o CSS (se desejado):** Se você decidiu importar o CSS padrão no passo anterior, edite também `resources/css/app.css`:

    ```css
    /* resources/css/app.css */
    @import 'tailwindcss/base';
    @import 'tailwindcss/components';
    @import 'tailwindcss/utilities';
    @import '@fortawesome/fontawesome-free/css/all.css';

    /* --- Adicione esta linha se importou no JS --- */
    /* @import 'sweetalert2/dist/sweetalert2.min.css'; */
    /* OU, se não importou no JS, importe aqui: */
    @import 'sweetalert2/dist/sweetalert2.min.css';

    /* Seus outros estilos */
    ```
    *Escolha importar o CSS ou no JS ou no CSS, geralmente importar no CSS principal (`app.css`) é mais comum.*

4.  **Verificar:** Adicione um botão de teste em uma view Blade para chamar o SweetAlert:

    ```html
    {{-- Em alguma view, ex: dashboard.blade.php --}}
    <button onclick="Swal.fire('Olá!', 'SweetAlert2 está funcionando!', 'success')">
        Testar SweetAlert
    </button>

    {{-- Exemplo de confirmação de exclusão usando SweetAlert --}}
    <form action="#" method="POST" onsubmit="event.preventDefault(); showConfirmation(this);">
        @csrf
        @method('DELETE')
        <button type="submit" class="bg-red-500 text-white p-2 rounded">Excluir (com Swal)</button>
    </form>

    @push('scripts') {{-- Adiciona script no final do body via stack do layout --}}
    <script>
        function showConfirmation(form) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não pode ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Cor vermelha para confirmar exclusão
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Se confirmado, submete o formulário
                    form.submit();
                }
            })
        }
    </script>
    @endpush
    ```
    Certifique-se que seu layout `app.blade.php` tem a diretiva `@stack('scripts')` antes do fechamento da tag `</body>`. Clique nos botões para testar.

---

## Passo 6: Configurações Adicionais (Opcional, mas recomendado)

*   **Tailwind Dark Mode:** O Breeze já configura `darkMode: 'class'` em `tailwind.config.js`. Certifique-se de que seu layout (`app.blade.php`) tenha a lógica Alpine para adicionar/remover a classe `dark` na tag `<html>` e persistir a preferência no `localStorage` (como fizemos no nosso projeto).
*   **Paleta de Cores Tailwind:** Adicione suas cores personalizadas (como a paleta `primary`) à seção `theme.extend.colors` em `tailwind.config.js`.
*   **Vite HMR com Docker:** Se estiver usando Docker, certifique-se que a seção `server` em `vite.config.js` está configurada com `host: '0.0.0.0'` e `hmr: { host: 'localhost' }` para o Hot Module Replacement funcionar corretamente.

---

**Conclusão:**

Após seguir estes passos, seu novo projeto Laravel estará configurado com:

*   Autenticação completa (Breeze).
*   Estilização moderna e utilitária (Tailwind CSS).
*   Uma biblioteca de ícones completa (Font Awesome).
*   Alertas e modais interativos (SweetAlert2).
*   Tudo integrado ao pipeline de build do Vite.
