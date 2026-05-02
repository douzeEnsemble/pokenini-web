# Architecture and Patterns

The project follows a standard Symfony MVC architecture but heavily emphasizes its role as a web frontend (rendering HTML) without local persistence.

## Layered Structure

1.  **Controllers** (`src/Controller`): Handle HTTP requests, call external Services to fetch data, and render Twig templates. They return `Response` objects (HTML).
2.  **Services** (`src/Service`): External API communication logic using Guzzle HTTP client. They encapsulate all calls to the backend APIs.
3.  **DTOs** (`src/DTO`): Used as rigid data structures to parse incoming external API responses into objects that can be easily manipulated in Twig.
4.  **ResponseObjects** (`src/ResponseObject`): A specific pattern for standardizing payloads returned from APIs.
5.  **Twig Extensions** (`src/Twig`): Custom Twig filters and functions to keep template logic minimal.
6.  **Templates** (`templates/`): The View layer, using the Twig templating engine.
7.  **Translations** (`translations/`): Critical for internationalization, allowing the UI to support multiple languages.

## Coding Standards

- **Strict Typing**: Every PHP file uses `declare(strict_types=1);`.
- **PHP 8.4+ Features**: The codebase extensively uses modern features.
- **Service Injection**: Dependency injection is heavily used through constructors.

## Key Patterns

- **No Doctrine ORM**: There is no database. Data persistence is managed downstream by the API.
- **DTO-based Validation**: Responses from the API are mapped to DTOs/ResponseObjects to ensure the View layer always receives valid data.
- **Fat Templates, Thin Controllers**: Controllers mainly orchestrate data fetching, leaving rendering logic strictly to Twig.
