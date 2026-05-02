# Data Management

`pokenini-web` is completely stateless regarding domain data. It operates purely by consuming REST APIs.

## Data Fetching

- **Guzzle HTTP Client**: `pokenini-web` relies on Guzzle to make requests to the backend server (`pokenini-back` or directly `pokenini-api`).
- **Services**: The `src/Service` layer acts as the data access layer, fetching JSON payloads and transforming them into internal DTOs and `ResponseObject` entities.

## Mocking Data for Tests

To test the frontend without depending on a live backend, the project uses a mock server.
- **Moco**: Moco is used to mock the API responses during tests.
- **Mock Files**: Config and JSON responses from the backend are stored in `tests/resources/moco/Api` and `tests/resources/moco/OAuth`.
- **Updating Mocks**: The `README.md` provides extensive `curl` commands to re-download the latest JSON payloads from the live backend into the local mock directories. This ensures that the mock server closely mirrors real production data payloads.
