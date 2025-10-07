# Configuração Manual do Laravel Boost MCP

## Código JSON para Configuração

Baseado na documentação oficial do Laravel Boost via Context7, aqui está o código JSON correto para configurar o MCP:

```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "/home/gacpac/projects/EventosPro/vendor/bin/sail",
      "args": ["artisan", "boost:mcp"]
    }
  }
}
```

## Instruções de Instalação Manual

### 1. Localizar o arquivo de configuração MCP
O arquivo está localizado em: `/home/gacpac/.trae-server/data/Machine/mcp.json`

### 2. Editar o arquivo mcp.json
Abra o arquivo e adicione a configuração do Laravel Boost dentro da seção `mcpServers`:

Se o arquivo já contém outros serviços MCP, adicione apenas a entrada do `laravel-boost`:

```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "/home/gacpac/projects/EventosPro/vendor/bin/sail",
      "args": ["artisan", "boost:mcp"]
    },
    // ... outros serviços MCP existentes
  }
}
```

### 3. Verificar a instalação
Após adicionar a configuração:

1. Reinicie o VS Code ou Trae AI
2. Verifique se o Laravel Boost MCP está funcionando executando:
   ```bash
   cd /home/gacpac/projects/EventosPro
   ./vendor/bin/sail artisan boost:mcp
   ```

### 4. Comandos disponíveis
- `php artisan boost:install` - Instala as diretrizes de IA
- `php artisan boost:mcp` - Inicia o servidor MCP

## Estrutura do Projeto
O Laravel Boost foi instalado com sucesso e criou os seguintes arquivos:
- `config/boost.php` - Configuração do Laravel Boost
- `CLAUDE.md` - Diretrizes para Claude AI
- `.github/copilot-instructions.md` - Instruções para GitHub Copilot
- `.junie/guidelines.md` - Diretrizes para Junie AI

## Verificação da Configuração
Para verificar se tudo está funcionando corretamente:

```bash
# Verificar se o Laravel Boost está instalado
./vendor/bin/sail artisan list | grep boost

# Testar o servidor MCP
./vendor/bin/sail artisan boost:mcp --help
```

## Troubleshooting
- **IMPORTANTE**: Não inclua a propriedade `cwd` na configuração, pois ela não é permitida pelo MCP
- **Erro "Could not open input file: artisan"**: Use o caminho completo do Laravel Sail em vez de apenas `php`
- Verifique se o Laravel Sail está rodando: `./vendor/bin/sail ps`
- Confirme que o Laravel Boost está instalado: `composer show laravel/boost`
- Se encontrar erro "Property cwd is not allowed", remova essa propriedade da configuração JSON
- Para projetos com Laravel Sail, sempre use `/home/gacpac/projects/EventosPro/vendor/bin/sail` como comando