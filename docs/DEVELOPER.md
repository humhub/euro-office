# Developer notes

## Architecture

The module integrates the Docs API (`DocsAPI.DocEditor`) of Euro-Office / ONLYOFFICE Docs. It stores no data of its own besides the module settings.

| Part | Responsibility |
|---|---|
| `Events` / `filehandler/*` | Register "Create", "Edit" and "View" actions in the HumHub file handler menus |
| `controllers/EditorController` | Renders the editor modal with a signed editor configuration |
| `controllers/CreateController` | Creates a new file from an empty template (`resources/templates`) |
| `controllers/CallbackController` | Server-to-server endpoints called by the document server (`download`, `track`) |
| `services/EditorConfigBuilder` | Builds and signs the editor configuration |
| `services/CallbackHandler` | Saves the edited document when the document server reports status 2 or 6 |
| `services/DocumentFormats` | Supported and editable file extensions |
| `services/DocumentKey` | Document key derived from file GUID + content hash |
| `services/FileAccessToken` | Signed token in callback URLs, binding them to one file |
| `services/JwtService` | Signs / verifies JWT with the shared document server secret |
| `services/DocumentServerClient` | HTTP requests to the document server (healthcheck, downloads) |

### Flow

1. The browser opens `/euro-office/editor?guid=...`, which returns a modal with the signed editor configuration.
2. The JS widget loads `<server>/web-apps/apps/api/documents/api.js` and creates the editor.
3. The document server downloads the file via `/euro-office/callback/download?token=...` (JWT in header required).
4. After all users closed the document, the document server POSTs status 2 to `/euro-office/callback/track?token=...`. The module downloads the result and stores it as new file content (with file history).

### Document key

The key is derived from the file GUID and the SHA1 hash of the file content. All users opening the same content share a key (co-editing), and the key changes as soon as the content is saved, so no state needs to be stored in the database. This also keeps the module independent from the `onlyoffice` module, both can be installed side by side.

### Security

- JWT is mandatory. Every editor configuration is signed, and requests from the document server must carry a valid JWT.
- Callback URLs contain a token signed with the HumHub installation secret that binds them to a single file.
- When saving, the last editor reported by the document server must still have write access to the file.

## Local development

```bash
docker run -d --name euro-office -p 8090:80 \
  -e JWT_SECRET=a-secret-with-at-least-32-characters \
  -e ALLOW_PRIVATE_IP_ADDRESS=true \
  --add-host=host.docker.internal:host-gateway \
  ghcr.io/euro-office/documentserver:latest
```

Then configure `http://localhost:8090` as document server URL. If HumHub is not reachable from the container under its base URL, set "HumHub URL for the document server" accordingly (e.g. `http://host.docker.internal`).

## Not yet implemented

Conversion of legacy formats, editing of ODF formats, guest/share links, mentions, force save, version history in the editor.
