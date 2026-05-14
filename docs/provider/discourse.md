# Discourse provider

The Discourse provider uses the Discourse User API Key flow.

Each webtrees user authorizes the module from the transcription dashboard. The module redirects the user to Discourse, Discourse asks the user to allow access, and the callback returns an encrypted user-specific API key. The key is stored encrypted in the module provider credential table for that webtrees user.

Supported Discourse instances:

- https://discourse.genealogy.net
- https://discourse-test.genealogy.net

The manual API key form is intentionally not supported. If Discourse access is revoked or the key expires because it has not been used for a long time, the user can repeat the authorization from the dashboard.

## Media attachments

The Discourse provider is prepared for the file types supported by the target Discourse instance:

- `jpg`
- `jpeg`
- `png`
- `gif`
- `heic`
- `heif`
- `webp`
- `avif`
- `txt`
- `pdf`

No instance-specific size limit is currently known. The module therefore records no Discourse file-size limit; upload failures caused by instance-side limits must be handled from the Discourse response.

## Technical flow

1. webtrees creates a temporary RSA key pair and nonce.
2. webtrees redirects to `/user-api-key/new` on the selected Discourse instance.
3. Discourse encrypts the authorization payload with the temporary public key.
4. webtrees decrypts the callback payload, verifies the nonce, stores the user API key, and tests `/session/current.json`.
5. Later Discourse requests use the `User-Api-Key` and `User-Api-Client-Id` request headers.

## Admin prerequisites

The production Discourse instance may need to allow this application ID and callback URL in its User API Key configuration. The application client ID used by the module is:

```text
hh_source_transcription
```

The requested scopes are:

```text
read,write,session_info
```
