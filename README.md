<div align='center'>
 
![Magazord](image/logo-magazord.png)
 
 </div>

# Teste para vaga de Arquiteto de Software no Magazord.com.br

Este repositório tem como fim testar os candidatos para vaga de Arquiteto de Software na empresa [Magazord](https://magazord.com.br).

> Para esta vaga buscamos alguém apaixonado por desafios,tenha facilidade em aprendizado de diferentes tecnologias e que esteja sempre atento aos detalhes!


## O teste

O objetivo deste teste é garantir que suas habilidades de modelagem e programação sejam postas a prova. O importante é o funcionamento, cumprimento com os requisitos e utilização de boas práticas. O visual da aplicação é secundário, não será critério de avaliação.

Será realizado um simples sistema de contatos, utilizando PHP, JS, HTML, CSS e Banco de Dados. É necessário que o sistema utilize o padrão MVC e que a manipulação com o banco de dados opere em conjunto com o ORM [Doctrine](https://www.doctrine-project.org/). 

> [!IMPORTANT]
> **Não deve ser utilizado framework para o desenvolvimento da aplicação backend.**

> [!IMPORTANT]
> **Para o contexto frontend podem ser utilizados frameworks JS para simplificar o desenvolvimento, porém é importante se atentar as práticas de integração no momento da sua construção.**

> [!IMPORTANT]
> **É de extrema importância um arquivo README para instrução de como executar o projeto, sendo ponto de destaque o uso de metodologias de automação para execução do projeto considerando relacionamento de dependências ou provisionamento de recursos como versão de dependências, linguagem e banco de dados para o projeto.**

## Requisitos funcionais:

- RF01 - O sistema deve manter uma tela de consulta para pessoas.

- RF02 - O sistema deve manter um campo de pesquisa por nome de pessoa.

- RF03 - O sistema deve manter uma tela de consulta para contatos.

- RF04 - O sistema deve manter um CRUD (Cadastrar, Visualizar, Alterar, Excluir) para pessoas.

- RF05 - O sistema deve manter um CRUD (Cadastrar, Visualizar, Alterar, Excluir) para contato.


## Requisitos não funcionais:

- RNF01 - O sistema deve utilizar a linguagem PHP para o Back-end.

- RNF02 - O sistema deve utilizar a ORM Doctrine para o Back-end.

- RNF03 - O sistema deve utilizar Docker para definição do ambiente.

- RNF04 - O sistema deve utilizar docker-compose para orquestração de containers.

- RNF03 - O sistema deve utilizar JS, HTML, CSS.

- RNF04 - O sistema deve ser organizado pelo padrão MVC.

- RNF05 - O sistema deve utilizar o Composer para gerenciamento de dependências.

- RNF06 - O sistema deve utilizar um banco de dados SQL (postgres ou mysql).
![Modelagem](image/diagrama.png)

- RNF07 - O sistema deverá ter seu controle de versão no Github.


## Regra de Negócio:

- RN01 - São dados de pessoas: Nome e CPF.

- RN02 - São dados de contato: Tipo (Telefone ou Email), Descrição.

- RN03 - Uma pessoa pode ter vários contatos


## Aplicação de Design Patterns

A baixo são apresentados alguns padrões de projetos que podem ser aplicados na arquitetura do projeto proposto, utilize-os e apresente no arquivo README.md onde, como e porque da utilização deles. Fique a vontade para incluir outros padrões e descreve-los da mesma forma

- Repository Pattern
- Factory Pattern
- Singleton Pattern
- Strategy Pattern
- Dependency Injection Pattern
- Observer Pattern
- Front Controller Pattern

## Links para documentação de ferramentas utilizadas.

- Composer: https://getcomposer.org/
- Doctrine: https://www.doctrine-project.org/projects/doctrine-orm/en/2.10/index.html

## Envio do teste

> [!NOTE]
> **Suba o repositório no seu Github e envie o link diretamente para o seu recrutador.**

> [!WARNING]
> **Não serão aceitos alterações após o envio.**
