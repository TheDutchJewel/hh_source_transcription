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

## Data Model

An overview of the database schema can be found in [docs/database/schema-1.sql.txt](database/schema-1.sql.txt).

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

Internal collaboration  
interaction_model = internal_collaborative  

Automated (asynchronous)  
interaction_model = automated_async  

Crowd-based (asynchronous)  
interaction_model = crowd_async  

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

- **CreateTranscriptionService**: Initialisiert eine neue Transkription für eine Quelle und ein Medienobjekt.
- **GetTranscriptionDetailService**: Lädt die vollständigen Daten einer Transkription inklusive Metadaten und Revisionshistorie.
- **SaveNoteAsRevisionService**: Erstellt einen Snapshot des aktuellen NOTE-Inhalts als unveränderliche Revision.
- **GenerateOrUpdateNoteService**: Synchronisiert den Inhalt einer webtrees NOTE mit den Daten aus dem Modul.
- **EnsureTagNoteService**: Stellt sicher, dass das entsprechende webtrees-Tag (NOTE) für die Verknüpfung existiert.

---

## Gateways

- **SharedNoteGateway**: Abstraktionsschicht für den Zugriff auf webtrees NOTE-Datensätze (Shared Notes).
- **SourceGateway**: Ermöglicht den Zugriff auf webtrees Quellen (SOURce-Records) und deren Struktur.
- **MediaFileGateway**: Dient zum Abrufen von Informationen über Medienobjekte, die mit Quellen verknüpft sind.

---

## UI

### Pages
- **Dashboard**: Zentrale Übersicht über alle vorhandenen Transkriptionen.
- **Create**: Interface zur manuellen Erstellung einer Transkription (Auswahl von Quelle und Medium).
- **Detail**: Die Hauptarbeitsansicht. Enthält den Text-Editor für die aktuelle NOTE, zeigt das verknüpfte Medium an und listet die Revisionshistorie auf.

### Actions / API
- **UpdateCurrentNoteAction**: Speichert den aktuellen Text des Editors in der webtrees NOTE.
- **SaveNoteAsRevisionAction**: Archiviert den aktuellen Stand der NOTE als neue, unveränderliche Revision.
- **MediaForSourceAction**: Liefert via JSON die verfügbaren Medienobjekte für eine gewählte Quelle (wird im Create-Dialog genutzt).

---

## State machine


### States
```
enum TranscriptionStatus: string
{
case NEW = 'new';
case IN_PROGRESS = 'in_progress';
case READY_FOR_REVIEW = 'ready_for_review';
case FINAL = 'final';
case REOPENED = 'reopened';
case CANCELLED = 'cancelled';
}
```

### Transitions
```text
enum TranscriptionTransition: string
{
case START = 'start';
case SUBMIT_FOR_REVIEW = 'submit_for_review';
case APPROVE = 'approve';
case REOPEN = 'reopen';
case CANCEL = 'cancel';
}
```

---

## File Structure

```text
.
├── .gitignore
├── LICENSE
├── README.md
├── autoload.php
├── composer.json
├── latest-version.txt
├── module.php
├── docs/
│   ├── architecture.md
│   ├── database/
│   │   └── schema-1.sql.txt
│   └── images/
│       └── ui/
│           ├── control_panel.png
│           ├── create_manual.png
│           ├── dashboard.png
│           └── details.png
├── resources/
│   ├── lang/
│   │   ├── de.mo
│   │   ├── de.po
│   │   ├── nl.mo
│   │   └── nl.po
│   └── views/
│       ├── admin-settings.phtml
│       ├── create-manual.phtml
│       ├── dashboard.phtml
│       └── detail.phtml
└── src/
    ├── SourceTranscription.php
    ├── Application/
    │   ├── Dto/
    │   │   └── CreateTranscriptionCommand.php
    │   ├── Factory/
    │   │   └── NoteContentFactory.php
    │   ├── Provider/
    │   │   └── ProviderMetadata.php
    │   └── Service/
    │       ├── CreateTranscriptionService.php
    │       ├── EnsureTagNoteService.php
    │       ├── GenerateOrUpdateNoteService.php
    │       ├── GetTranscriptionDetailService.php
    │       └── SaveNoteAsRevisionService.php
    ├── Domain/
    │   ├── Entity/
    │   │   ├── NoteLink.php
    │   │   ├── Transcription.php
    │   │   └── TranscriptionRevision.php
    │   └── ValueObject/
    │       ├── InteractionModel.php
    │       ├── NoteStrategy.php
    │       ├── PrimaryForm.php
    │       ├── PrimaryLanguage.php
    │       ├── PrimaryScript.php
    │       ├── ProviderKey.php
    │       ├── ProviderLabel.php
    │       ├── ProviderPresentation.php
    │       ├── RevisionOriginType.php
    │       ├── TranscriptionStatus.php
    │       └── TranscriptionType.php
    ├── Http/
    │   └── RequestHandlers/
    │       ├── CreateManualAction.php
    │       ├── DashboardAction.php
    │       ├── DetailAction.php
    │       ├── MediaForSourceAction.php
    │       ├── SaveNoteAsRevisionAction.php
    │       ├── StoreManualAction.php
    │       └── UpdateCurrentNoteAction.php
    ├── Infrastructure/
    │   ├── Persistence/
    │   │   ├── Repository/
    │   │   │   ├── NoteLinkRepository.php
    │   │   │   ├── RevisionRepository.php
    │   │   │   ├── SettingsRepository.php
    │   │   │   └── TranscriptionRepository.php
    │   │   └── Schema/
    │   │       ├── Migration0.php
    │   │       └── SchemaManager.php
    │   ├── Webtrees/
    │   │   ├── MediaFileGateway.php
    │   │   ├── SharedNoteGateway.php
    │   │   └── SourceGateway.php
    │   └── WhatsNew/
    │       ├── WhatsNew0.php
    │       └── WhatsNewInterface.php
    └── Support/
        ├── HashService.php
        └── TranscriptionSlug.php
```

---

## Known Limitations

- Direct GEDCOM updates  
- No permission checks
