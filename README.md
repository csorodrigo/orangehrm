# CIA Férias

CIA Férias é o sistema interno para gestão de colaboradores, férias, aprovações e rotinas administrativas.

## Ambiente local

Suba a aplicação com Docker:

```bash
docker compose -f docker-compose.local.yml up -d --build
```

Acesse a aplicação em:

```text
http://localhost:8081/web/index.php/auth/login
```

Credenciais iniciais:

```text
Admin / CiaFerias@2026!
Joao.Colaborador / CiaFerias@2026!
Maria.Gestora / CiaFerias@2026!
```

## Desenvolvimento

Frontend:

```bash
cd src/client
yarn lint
yarn test:unit
yarn build
```

Testes funcionais:

```bash
cd src/test/functional
yarn lint
yarn test --config baseUrl=http://localhost:8081/web/index.php
```

## Licença

Este projeto preserva os avisos de licença do software original sob GNU General Public License, incluindo as atribuições obrigatórias mantidas nos arquivos derivados.
