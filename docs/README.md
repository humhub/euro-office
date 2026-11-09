# Euro-Office

Create, view and edit office documents, spreadsheets and presentations directly in HumHub, together and in real time.

The module works with [Euro-Office](https://github.com/euro-office/documentserver) and with ONLYOFFICE Docs, since both servers share the same integration API.

## Features

- Create new documents (`.docx`), spreadsheets (`.xlsx`) and presentations (`.pptx`)
- Edit Office Open XML files together with other users in real time
- View further formats read-only (e.g. `.doc`, `.odt`, `.rtf`, `.xls`, `.ods`, `.ppt`, `.odp`)
- Saved changes create a new file version, if file versioning is enabled
- Touch optimized editor on smartphones
- Access can be restricted with the permission "Can use Euro-Office"
- Switching from the ONLYOFFICE module: its connection settings are taken over automatically

## Configuration

1. Run a Euro-Office (or ONLYOFFICE Docs) server with JWT enabled and a secret of at least 32 characters.
2. Open `Administration -> Modules -> Euro-Office -> Configure` and enter:
   - **Document server URL:** the URL of the document server as reachable by the browsers, e.g. `https://docs.example.com`
   - **JWT secret:** the secret configured on the document server (`JWT_SECRET`)
   - **JWT header:** the header configured on the document server (`JWT_HEADER`, default `Authorization`)
   - **Internal document server URL** (optional): URL at which HumHub reaches the document server, if it differs
   - **HumHub URL for the document server** (optional): URL at which the document server reaches HumHub, if it differs from the HumHub base URL

The document server must be able to reach HumHub to download and save documents. If HumHub runs on a private IP address, allow this on the document server (`ALLOW_PRIVATE_IP_ADDRESS=true` for Euro-Office).

## Known limitations

If a new version of a file is uploaded while the file is open in the editor, the editor session continues with the previous content. When the session is saved, it becomes the current version of the file. Both versions are kept in the file history.
