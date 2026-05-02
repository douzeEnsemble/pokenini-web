# Project Overview: pokenini-web

The `pokenini-web` is the frontend application of the ecosystem, built with **Symfony 8.0 / PHP 8.4** and **Twig**. It serves as the user-facing interface for the Pokémon Living/Alternate/Gender Extended Dex.

Like the `pokenini-back` project, it does not have a local database. It fetches and displays data by connecting to external APIs via HTTP.

## Technology Stack

- **PHP**: 8.4+
- **Framework**: Symfony 8.0
- **Templating**: Twig (with `twig/extra-bundle`, `twig/intl-extra`)
- **HTTP Client**: Guzzle HTTP (`guzzlehttp/guzzle`)
- **Authentication**: OAuth2 Clients (`league/oauth2-google`, `wohali/oauth2-discord-new`) via `knpuniversity/oauth2-client-bundle`.
- **Localization**: Symfony Translation component.

## Running the Application

The project uses Docker for its local environment and provides a comprehensive `Makefile`.

### Common Commands
- `make start`: Installs dependencies, builds the docker image, starts the containers, and clears cache.
- `make stop`: Stops the docker containers.
- `make destruct`: Destroys the containers and volumes.
- `make bash`: Opens a shell inside the PHP container.

### Local Development Authentication
The project provides fake authentication endpoints for local dev to bypass the full OAuth2 flow:
- Admin: `http://localhost/fr/connect/f/c?t=admin`
- Collector: `http://localhost/fr/connect/f/c?t=collector`
- Trainer: `http://localhost/fr/connect/f/c?t=trainer`
