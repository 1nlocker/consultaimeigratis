# Sistema de Consulta de IMEI (Versão HTML)

Esta é uma versão HTML do sistema de consulta de IMEI, que simula a funcionalidade sem necessidade de PHP ou servidor.

## Funcionalidades

- Consulta de informações de dispositivos através do IMEI (simulada)
- Interface amigável e responsiva
- Validação de entrada do IMEI
- Tratamento de erros
- Painel administrativo demonstrativo

## Como usar

1. Abra o arquivo `index.html` em qualquer navegador moderno
2. Preencha o formulário com um IMEI (15 dígitos numéricos)
3. Clique em "Consultar IMEI"
4. O resultado simulado será exibido na tela

**Observação**: Esta versão simula as consultas e gera resultados aleatórios para demonstração.

## Painel Administrativo

Para acessar o painel administrativo demonstrativo:

1. Clique no link "Painel de Administração" no final da página principal
2. Use as credenciais:
   - Usuário: `admin`
   - Senha: `admin123`

O painel administrativo demonstra como seria a interface para gerenciar cache e logs, embora as ações sejam apenas simuladas nesta versão HTML.

## Estrutura de arquivos

- `index.html`: Página principal para consulta de IMEI
- `admin.html`: Painel administrativo
- `README.md`: Este arquivo de documentação

## Diferenças da versão PHP

Esta versão HTML:
- Simula as consultas em vez de fazer requisições reais à API
- Gera resultados aleatórios para demonstração
- Não tem funcionalidade real de cache ou logs
- Não requer PHP ou servidor para funcionar

## Convertendo para PHP

Para converter este projeto para uma versão funcional com PHP:
1. Instale o PHP e um servidor web (Apache, Nginx) ou use um pacote como XAMPP/WAMP
2. Substitua as simulações JavaScript por requisições reais à API
3. Implemente a funcionalidade real de cache e logs
4. Configure adequadamente a chave da API

## Limitações

- Os resultados são gerados aleatoriamente e não representam dados reais
- Não há comunicação real com nenhuma API
- As ações administrativas são apenas simulações visuais 