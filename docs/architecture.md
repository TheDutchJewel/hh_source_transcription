# Source Transcription Module – Architecture

## Overview

The *Source Transcription* module introduces a structured workflow for transcribing genealogical sources in webtrees.

It separates:
- **working state** (editable NOTE in webtrees)
- **historical state** (immutable revisions stored in module tables)

This enables versioning, collaboration, and integration with external transcription tools.

---

## Core Concept

### NOTE vs. Revision

| Concept      | Purpose              | Storage                  |
|--------------|----------------------|--------------------------|
| **NOTE**     | Current working copy | webtrees GEDCOM (`NOTE`) |
| **Revision** | Historical snapshot  | module database tables   |

Key principle:

> The NOTE is editable. The revision is immutable.

Workflow:

```text
NOTE (editable)
    ↓ Save as revision
Revision (immutable)
    ↓ Generate/update NOTE
NOTE (new working state)
```

# Source Transcription Module – Architecture

## Overview

The *Source Transcription* module introduces a structured workflow for transcribing genealogical sources in webtrees.

It separates:
- **working state** (editable NOTE in webtrees)
- **historical state** (immutable revisions stored in module tables)

This enables versioning, collaboration, and integration with external transcription tools.

---

## Core Concept

### NOTE vs. Revision

| Concept   | Purpose              | Storage                        |
|-----------|----------------------|--------------------------------|
| NOTE      | Working copy         | webtrees GEDCOM (NOTE)         |
| Revision  | Historical snapshot  | Module database tables         |

**Principle:** The NOTE is editable. The revision is immutable.

Workflow:

```text
NOTE (editable)
→ Save as revision
Revision (immutable)
→ Generate/update NOTE
NOTE (new working state)
```

---
## Data Model

### transcriptions
id  
tree_id  
source_xref  
media_xref  
title  
provider_key  
interaction_model  
status  
current_note_xref  
tag_note_xref  
is_active  

### transcription_revisions
id  
transcription_id  
revision_no  
provider_key  
origin_type  
origin_reference  
content_format  
content_text  
content_hash  
created_by_user_id  
import_comment  
generated_note_xref  
is_current_revision  

### transcription_note_links
id  
transcription_id  
revision_id  
note_xref  
link_type  
created_by_user_id  
is_current  
note_hash_at_link_time  

### transcription_requests (planned)
id  
transcription_id  
provider_key  
interaction_model  
request_status  
external_reference  
external_url  
request_payload_json  
response_payload_json  
created_by_user_id  
created_at  
updated_at  
closed_at  

---

## Interaction Models

Manual (direct)  
interaction_model = manual_direct  

Automated (asynchronous)  
interaction_model = automated_async  

Crowd-based (asynchronous)  
interaction_model = crowd_async  

Internal collaboration  
interaction_model = internal_collaborative  

---

## Workflow

Manual:
1. Create transcription  
2. Create NOTE  
3. Edit NOTE  
4. Save as revision  

Automated:
1. Submit request  
2. Import result  

Crowd:
1. Ask community  
2. Import answer  

---

## Services

CreateTranscriptionService  
GenerateOrUpdateNoteService  
SaveNoteAsRevisionService  
EnsureTagNoteService  

---

## Gateways

SharedNoteGateway  
SourceGateway  

---

## UI

Main menu: Transcriptions

Pages:
- Dashboard  
- Create  
- Detail  

---

## Key Decisions

NOTE = working state  
Revision = history  

---

## Known Limitations

- Direct GEDCOM updates  
- No permissions  
- No diff  

---

## Future

- Discourse  
- Transkribus  
- Viewer  
- Diff  
- Backup  

---

## Summary

NOTE = working state  
Revision = history  
Provider = source  
